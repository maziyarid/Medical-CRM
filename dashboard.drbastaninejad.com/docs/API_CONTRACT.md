# API Contract — Dr. Shahin Bastaninejad Clinical Dashboard

**Version:** 2.0 — 2026-08-10  
**Base URL:** `https://dashboard.drbastaninejad.com/api/v1`

This file describes the routes implemented by the PHP controllers in this repository. The canonical customer UI is `app.drbastaninejad.com/Frontend/`; `dashboard/public/index.html` is only a backend smoke-test shell.

## 1. Response envelope — locked

Every JSON response keeps all five keys:

```json
{
  "ok": true,
  "status": 200,
  "data": {},
  "errors": null,
  "meta": null
}
```

Validation/error responses use the same shape:

```json
{
  "ok": false,
  "status": 422,
  "data": null,
  "errors": [{"field":"mobile","message":"شماره موبایل معتبر نیست"}],
  "meta": null
}
```

Authenticated routes use `Authorization: Bearer <raw-token>`. Only tokens with `auth_tokens.purpose='session'` are accepted by normal API middleware.

## 2. Authentication

### POST `/auth/otp/send`
Public. Body:

```json
{"mobile":"09XXXXXXXXX","audience":"patient"}
```

`audience` is `patient` or `staff`; default is `patient`. OTP is 5 digits, expires in 300 seconds, and is rate-limited per mobile + audience + purpose.

### POST `/auth/otp/verify`
Public. Body:

```json
{"mobile":"09XXXXXXXXX","otp":"12345","audience":"patient"}
```

Success `data` contains `token`, `expires_at`, and `user`. The token is audience-specific. Patient and staff browser sessions must not share a token key.

### Patient recovery

`POST /auth/recovery/request`

```json
{"mobile":"09XXXXXXXXX","channel":"sms","email":""}
```

`channel` is `sms` or `email`. For the email branch, `email` must exactly match the patient record. Unknown accounts and email mismatches receive the same neutral response. Email delivery requires `EMAIL_OTP_ENABLED=1` and a valid `MAIL_FROM`.

`POST /auth/recovery/verify`

```json
{"mobile":"09XXXXXXXXX","otp":"12345"}
```

Returns a one-use, 15-minute `reset_token` with `purpose='password_reset'`.

`POST /auth/recovery/password`

```json
{"reset_token":"64-hex-characters","new_password":"minimum-10-characters"}
```

Stores only a password hash (Argon2id when available, bcrypt fallback), revokes the reset token, and revokes active patient sessions. Passwords are never sent by SMS or email. Patient OTP remains the primary login flow.

## 3. WorkingVersion intake bridge

### POST `/intakes`
Public for the normal intake API, but the live WorkingVersion dual-write identifies itself with:

`X-Intake-Bridge-Secret: <runtime INTAKE_BRIDGE_SECRET>`

The bridge must send a stable `submission_uuid` and may send `_bridge_sheet_status` = `pending`, `ok`, or `failed`. The server uses the same `submission_uuid` idempotently.

Canonical additive fields:

| Field | Required | Notes |
|---|---:|---|
| `submission_uuid` | yes | max 64; idempotency key |
| `firstName`, `lastName` | yes | existing field names remain accepted |
| `mobile` | yes | globally unique patient identity after normalization |
| `nationalId` | yes for medical intake | validated for medical intake; booking source may omit it |
| `birthDate` | yes for medical intake | Jalali input is preserved in `birth_date_jalali` |
| `description` / `chief_complaint` | yes | existing intake complaint/history payload |
| `email` | no | validated only when non-empty; stored in `intakes.email` and patient upsert |
| `visitReason` / `visit_reason` | no | stored in `intakes.visit_reason` |
| `doctorRequest` / `doctor_request` | no | max 2000; stored explicitly and in raw payload |

On WorkingVersion bridge calls, MySQL commit and Google Sheet delivery are isolated. Sheet failure does not delete the DB row. Intake success SMS is sent only on the bridge completion signal (`_bridge_sheet_status='ok'`); SMS failure updates `sms_status='failed'` and never rolls back the intake.

### GET `/intakes`
Staff auth + `intakes.view`. Query: `page`, `per_page`, `status`, `q`, `source_type` (`intake|booking`). Returns the admin review queue including email, visit reason, doctor request, Sheet status, SMS status, source type and review state.

### PATCH `/intakes/{id}/status`
Staff auth + `intakes.manage`. Body:

```json
{"status":"reviewed"}
```

Allowed: `pending`, `reviewed`, `converted`, `rejected`.

## 4. WordPress booking bridge — server-to-server only

WordPress remains a marketing UI. These routes require `X-WordPress-Bridge-Secret`; the secret must never be exposed to browser JavaScript.

### POST `/bookings`
Body from the trusted WordPress server:

```json
{
  "submission_uuid":"wp-...",
  "name":"نام بیمار",
  "mobile":"09XXXXXXXXX",
  "email":"optional@example.com",
  "procedure":"نوع خدمت",
  "message":"توضیحات اختیاری",
  "cooldown_minutes":30,
  "sms_template":"optional trusted admin template"
}
```

The dashboard applies its own same-mobile cooldown, globally upserts `patients.mobile`, creates `intakes.source_type='booking'`, and sends booking confirmation through the existing `SmsProviderChain` after DB commit. Booking SMS failure does not roll back the booking.

### GET `/bookings/stats`
Same bridge secret. Returns non-PII counters only: `total`, `last_7_days`, `pending`, `sms_sent`, `sms_failed`.

## 5. Patient portal

All routes below require a **patient** session token. `RbacMiddleware('patient.portal')` allows patient-scoped access by user type.

| Method | Path | Purpose |
|---|---|---|
| GET | `/patient/overview` | next appointment + counts |
| GET | `/patient/profile` | canonical profile |
| PATCH | `/patient/profile` | writable: `email`, `home_tel`, `home_address` only |
| GET | `/patient/appointments` | paginated appointment history |
| GET | `/patient/documents` | patient media/document metadata (read-only; no upload route is currently contracted) |
| GET | `/patient/records` | paginated EMR records |
| GET | `/patient/notification-preferences` | notification choices |
| PATCH | `/patient/notification-preferences` | update four boolean choices |

Notification keys:

- `sms_appointment_reminder`
- `sms_status_change`
- `email_appointment_reminder`
- `email_marketing` (stored as `marketing_email_optin`)

Profile PATCH does **not** allow changing mobile, national ID, names or medical data from the patient browser.

## 6. Staff CRM

All staff routes require a staff session token and the named permission unless `super_admin`.

### Dashboard
- `GET /dashboard/overview` — `dashboard.view`

### Patients
- `GET /patients` — `patients.view`
- `GET /patients/{id}` — `patients.view`
- `POST /patients` — `patients.manage`
- `PUT /patients/{id}` — `patients.manage`

Staff-created patient records also deduplicate globally by normalized mobile.

### Appointments
- `GET /appointments` — `appointments.view`
- `GET /appointments/{id}` — `appointments.view`
- `POST /appointments` — `appointments.manage`
- `PATCH /appointments/{id}/reschedule` — `appointments.manage`
- `PATCH /appointments/{id}/status` — `appointments.manage`
- `DELETE /appointments/{id}` — `appointments.manage`

Canonical scheduling columns are `scheduled_at`, `duration_minutes`, `visit_reason`, `provider_id`, `room`, `status`, `notes`, `cancellation_reason`.

### EMR
- `GET /patients/{id}/emr` — `emr.view`
- `POST /patients/{id}/emr` — `emr.edit`
- `GET /emr/templates` — `emr.view`
- `POST /ai/emr-draft` — `emr.edit`; suggestions are review-only and never auto-saved.

AI draft request body:

```json
{
  "patient_id": 123,
  "prompt": "Draft a concise SOAP note from the clinician-entered text.",
  "context": {"subjective": "...", "objective": "...", "assessment": "...", "plan": "..."}
}
```

The AI route is disabled unless both `AI_ENABLED=1` and `AI_ALLOW_CLINICAL_TEXT=1` are set and a server-side provider key is configured. It never exposes provider credentials to the browser and never persists the generated draft automatically. The clinician must review and explicitly save any EMR content.

**Media upload blocker:** this contract currently defines document/media listing only. There is no contracted upload/signing endpoint, so the Frontend must not pretend that uploads are available until a storage/upload contract is added.

### Billing
- `GET /billing/invoices` — `billing.view`
- `GET /billing/invoices/{id}` — `billing.view`
- `POST /billing/invoices` — `billing.manage`
- `PATCH /billing/invoices/{id}/status` — `billing.manage`

### Tasks
- `GET /tasks` — `tasks.view`
- `POST /tasks` — `tasks.manage`
- `PATCH /tasks/{id}/status` — `tasks.manage`
- `DELETE /tasks/{id}` — `tasks.manage`

### Analytics
- `GET /analytics/summary` — `analytics.view`

### Clinic settings
- `GET /settings/clinic` — `settings.view`
- `PATCH /settings/clinic` — `settings.manage`

`GET /settings/clinic` returns clinic settings plus read-only operational state needed by the canonical staff Settings page: the current MySQL-backed staff user list/roles and integration **configured/not configured** flags. It never returns bridge secrets, API keys, SMS credentials or database passwords. `PATCH` validates and stores supported clinic settings such as active working-day/time windows; it is not a user/role administration endpoint.

There is currently no contracted staff-user/RBAC mutation endpoint. User/role assignment therefore remains an explicit database/bootstrap administration task until a management contract is defined; the UI must not invent one.

## 7. Google Sheet ownership

The live WorkingVersion remains the canonical Google Sheet writer. Exact live additive placement:

- **T:** `Email`
- **U:** `VisitReason` / علت مراجعه
- **V:** `درخواست شما از دکتر چیست؟`

The JSON key is `doctor_request`. Missing values write an empty string. `Code.gs` inserts the new V header when necessary while preserving occupied columns to the right. The dashboard does not perform a duplicate Sheet append unless `DASHBOARD_SHEETS_WRITE_ENABLED=1` is explicitly enabled for a future integration.

## 8. Login URL used in SMS

Current deploy-safe value while the canonical UI is hosted on `app.drbastaninejad.com`:

`https://app.drbastaninejad.com/Frontend/pages/auth/patient-login.html`

Once the canonical patient UI is actually mapped to the dashboard host, set only:

`PATIENT_LOGIN_URL=https://dashboard.drbastaninejad.com/`

Do not point patients to `dashboard/public/index.html`; it is a development shell.

## 9. Database source of truth

For a new/empty database use:

`database/install/drbastaninejad_dash_clean_install.sql`

Target database and user are both `drbastaninejad_dash`. The installer is non-destructive and does not seed patient/staff PII. Existing installations should use numbered additive migrations after taking a backup; never use a destructive schema reset on production clinical data.
