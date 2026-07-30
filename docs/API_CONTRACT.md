<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# API CONTRACT
## MΛZ Medical CRM — Dr. Shahin Bastaninejad Platform

> **This is the single source of truth for every HTTP endpoint.**
> Frontend agents read this. Backend agents write to it (append new sections when adding routes).
> Never invent an endpoint that is not documented here.

**Base URL:** `https://app.drbastaninejad.com/api/v1`
**Content-Type:** `application/json` (all requests and responses)
**Charset:** UTF-8
**Version:** 1.1 — Phase A+B live | Phase C pending
**Last updated:** 2026-07-29

> ⚠️ **ENVELOPE MISMATCH NOTE (2026-07-29, Blackbox AI):**
> This contract (`docs/API_CONTRACT.md`) governs **`app.drbastaninejad.com`** and uses
> `"success": true/false` as the response discriminator.
> The dashboard contract (`dashboard.drbastaninejad.com/docs/API_CONTRACT.md`) governs
> **`dashboard.drbastaninejad.com`** and uses `"ok": true/false`.
> These are intentionally different contracts for different subdomains served by separate
> backends. **Frontend code for `app.*` must use `response.success`, not `response.ok`.**
> Backend code for `app.*` must never adopt the `"ok"` envelope without a written
> amendment to UNIFIED_MASTER_PLAN.md approved by the product owner.

---

## Status Legend

| Badge | Meaning |
|---|---|
| ✅ **LIVE** | Implemented, committed, deployable |
| ⚠️ **PENDING** | Designed, not yet committed to repo |
| 🔒 **AUTH REQUIRED** | Must send `Authorization: Bearer {token}` header |
| 🟡 **STAFF ONLY** | Requires role: `receptionist`, `nurse`, `doctor`, or `superadmin` |

---

## Authentication Model

### Bearer token
All protected routes require the header:
```
Authorization: Bearer {raw_token}
```
- Token is obtained via `POST /auth/otp/verify`
- Stored client-side in `localStorage` under key `mz_auth_token`
- Server stores SHA-256 hash of the raw token in `auth_tokens` table
- Never expires in Phase A+B (expiry logic is Phase C)
- `user_type` discriminator in `auth_tokens`: `patient` | `staff`

### Rate limiting (OTP)
- Max 3 OTP send requests per mobile per 10 minutes
- Enforced in `OtpService::isRateLimited()` against `otp_codes` table
- Exceeding the limit returns `429` with code `OTP_RATE_LIMITED`

---

## Shared Response Envelope

Every response — success or error — uses this outer shape:

```json
{
  "success": true | false,
  "data":    { ... }  ,
  "error":   { "code": "ERROR_CODE", "message": "Human-readable string", "fields": { ... } }
}
```

- On success: `success: true`, `data` present, `error` absent.
- On error: `success: false`, `error` present, `data` absent.
- `error.fields` is present only for validation errors — maps field name → first error message.

---

## Error Code Registry

| Code | HTTP | Meaning |
|---|---|---|
| `VALIDATION_FAILED` | 422 | One or more input fields failed validation; see `error.fields` |
| `INVALID_NATIONAL_ID` | 422 | Code Meli failed mod-11 checksum |
| `INVALID_MOBILE` | 422 | Mobile number not in 09XXXXXXXXX format |
| `INVALID_JALALI_DATE` | 422 | Birth date is not a valid Jalali date |
| `DUPLICATE_SUBMISSION` | 200 | Idempotent re-submit: same UUID already processed; returns original `data` |
| `OTP_RATE_LIMITED` | 429 | More than 3 OTP requests in 10 min for this mobile |
| `OTP_INVALID` | 422 | OTP code does not match stored hash |
| `OTP_EXPIRED` | 422 | OTP code has passed its 5-minute TTL |
| `UNAUTHORIZED` | 401 | No or invalid bearer token |
| `FORBIDDEN` | 403 | Valid token but insufficient role |
| `NOT_FOUND` | 404 | Resource does not exist |
| `SERVER_ERROR` | 500 | Unexpected server-side failure |
| `NETWORK_ERROR` | — | Client-side: fetch failed entirely (no HTTP response) |

---

## ───────────────────────────────────────────
## SECTION 1 — Auth Endpoints
## ───────────────────────────────────────────

### `POST /auth/otp/send` ✅ LIVE

Send a 5-digit OTP SMS to an Iranian mobile number. Public endpoint.

**Request body**
```json
{
  "mobile": "09121234567"
}
```

| Field | Type | Required | Rules |
|---|---|---|---|
| `mobile` | string | ✔ | Must match `09[0-9]{9}` after digit normalisation. Persian/Arabic digits accepted. |

**Success response** `200`
```json
{
  "success": true,
  "data": {
    "expires_in": 300
  }
}
```
- `expires_in`: seconds until the OTP expires (300 = 5 minutes).
- The OTP itself is **never** returned in the response. In non-production environments it is written to `error_log` for testing.

**Error responses**

| Code | HTTP | Trigger |
|---|---|---|
| `INVALID_MOBILE` | 422 | Mobile format invalid |
| `OTP_RATE_LIMITED` | 429 | More than 3 requests in 10 min |
| `SERVER_ERROR` | 500 | SMS gateway unreachable |

---

### `POST /auth/otp/verify` ✅ LIVE

Verify a 5-digit OTP and receive a bearer token. Public endpoint.

**Request body**
```json
{
  "mobile": "09121234567",
  "otp":    "12345"
}
```

| Field | Type | Required | Rules |
|---|---|---|---|
| `mobile` | string | ✔ | Same rules as send endpoint |
| `otp` | string | ✔ | Exactly 5 digits. Persian/Arabic digits accepted. |

**Success response** `200`
```json
{
  "success": true,
  "data": {
    "token":      "a3f8...c2d1",
    "expires_at": null,
    "user_type":  "patient"
  }
}
```
- `token`: the raw bearer token. Store in `localStorage.mz_auth_token`. Only returned once, never retrievable again.
- `expires_at`: `null` in Phase A+B. Phase C will add a timestamp.
- `user_type`: `patient` | `staff`.

**Error responses**

| Code | HTTP | Trigger |
|---|---|---|
| `INVALID_MOBILE` | 422 | Mobile format invalid |
| `OTP_INVALID` | 422 | Code does not match bcrypt hash |
| `OTP_EXPIRED` | 422 | Code TTL has passed |
| `SERVER_ERROR` | 500 | Unexpected failure |

---

### `POST /auth/password` ⚠️ PENDING (Phase C)

Staff password login (receptionist, nurse, doctor, superadmin). Not yet implemented.

**Planned request body**
```json
{
  "username": "staff_username_or_email",
  "password": "plain_text_password"
}
```

**Planned success response** `200`
```json
{
  "success": true,
  "data": {
    "token":     "...",
    "user_type": "staff",
    "role":      "receptionist"
  }
}
```

---

## ───────────────────────────────────────────
## SECTION 2 — Intake Endpoints
## ───────────────────────────────────────────

### `POST /intakes` ✅ LIVE

Submit patient intake form. Public endpoint (patient self-service, OTP already verified in Step 1 of the wizard).

**Idempotency:** include `submission_uuid` (UUIDv4, client-generated). If the server receives the same UUID twice, it returns `200` with `duplicate: true` and the original `intake_id`. The DB enforces this via `UNIQUE KEY uk_submission_uuid` on the `intakes` table.

**Request body**
```json
{
  "submission_uuid": "550e8400-e29b-41d4-a716-446655440000",
  "mobile":          "09121234567",
  "first_name":      "مریم",
  "last_name":       "احمدی",
  "father_name":     "علی",
  "national_id":     "0079643178",
  "birth_date":      "1370/05/12",
  "home_tel":        "02112345678",
  "home_address":    "تهران، ...",
  "email":           "maryam@example.com",
  "visit_reason":    "مشاوره جراحی بینی",
  "description":     "توضیحات اختیاری",
  "medical_history": ["hypertension", "diabetes"],
  "current_drugs":   ["aspirin"],
  "signature":       "data:image/png;base64,...",
  "is_transfer":     0
}
```

| Field | Type | Required | Rules |
|---|---|---|---|
| `submission_uuid` | string (UUID v4) | ✔ | Client-generated. Stored once; second attempt returns existing record. |
| `mobile` | string | ✔ | `09[0-9]{9}` |
| `first_name` | string | ✔ | 2–60 chars |
| `last_name` | string | ✔ | 2–60 chars |
| `father_name` | string | ✘ | max 60 chars |
| `national_id` | string | ✔ | 10 digits, mod-11 checksum validated server-side |
| `birth_date` | string | ✔ | Jalali format `YYYY/MM/DD` — server converts to Gregorian for DB |
| `home_tel` | string | ✘ | digits only |
| `home_address` | string | ✘ | max 255 chars |
| `email` | string | ✘ | valid email format |
| `visit_reason` | string | ✔ | max 120 chars |
| `description` | string | ✘ | max 1000 chars |
| `medical_history` | string[] | ✘ | allowed values: `hypertension`, `diabetes`, `thyroid`, `heart`, `asthma`, `allergy`, `autoimmune`, `scleroderma`, `bleeding`, `pregnancy` |
| `current_drugs` | string[] | ✘ | allowed values: `hbp`, `aspirin`, `anticoagulant`, `steroid`, `ocp`, `other` |
| `signature` | string | ✔ | Base64 PNG data URI from signature pad |
| `is_transfer` | int | ✘ | `0` (default) = new patient, `1` = transfer. Staff-set only. |

**Success response** `200`
```json
{
  "success": true,
  "data": {
    "intake_id":  1042,
    "patient_id": 87,
    "duplicate":  false
  }
}
```
- If `duplicate: true` the same `intake_id` and `patient_id` from the original submission are returned. Not an error — handle gracefully on the client.

**Error responses**

| Code | HTTP | Trigger |
|---|---|---|
| `VALIDATION_FAILED` | 422 | Any required field missing or malformed; see `error.fields` |
| `INVALID_NATIONAL_ID` | 422 | Mod-11 checksum failed |
| `INVALID_MOBILE` | 422 | Mobile format invalid |
| `INVALID_JALALI_DATE` | 422 | Birth date not a valid Jalali date |
| `SERVER_ERROR` | 500 | DB write or Sheets write failure |

---

### `GET /intakes` ✅ LIVE 🔒 AUTH REQUIRED 🟡 STAFF ONLY

Paginated intake queue for staff (receptionist, doctor, superadmin).

**Query parameters**

| Param | Type | Default | Description |
|---|---|---|---|
| `page` | int | 1 | Page number (1-based) |
| `per_page` | int | 20 | Results per page (max 100) |
| `status` | string | all | Filter: `pending` \| `processed` \| `all` |
| `clinic_id` | int | — | Filter by clinic (superadmin only) |

**Success response** `200`
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "intake_id":   1042,
        "patient_id":  87,
        "first_name":  "مریم",
        "last_name":   "احمدی",
        "mobile":      "09121234567",
        "national_id": "0079643178",
        "visit_reason":"مشاوره جراحی بینی",
        "submitted_at":"2026-07-27T10:15:00Z",
        "status":      "pending"
      }
    ],
    "pagination": {
      "total":        142,
      "per_page":     20,
      "current_page": 1,
      "last_page":    8
    }
  }
}
```

**Error responses**

| Code | HTTP | Trigger |
|---|---|---|
| `UNAUTHORIZED` | 401 | No or invalid token |
| `FORBIDDEN` | 403 | Patient token used on staff route |

---

## ───────────────────────────────────────────
## SECTION 3 — Patient Portal Endpoints
## ✅ LIVE (Phase C — implemented 2026-07-30)
## ───────────────────────────────────────────

> Backend: `app.drbastaninejad.com/Backend/app/Controllers/PatientPortalController.php`
> All routes registered in `config/routes.php`. All require `AuthMiddleware` (Bearer token).
> Supporting models: `PatientModel`, `AppointmentModel`, `PatientMediaModel`, `NotificationPreferenceModel`
> Migrations: 005 (patient_media), 006 (notification_preferences), 007 (appointments)

### `GET /patient/overview` ✅ LIVE 🔒 AUTH REQUIRED

Summary card data for the patient portal home screen.

**Planned success response** `200`
```json
{
  "success": true,
  "data": {
    "patient_name":        "مریم احمدی",
    "next_appointment": {
      "date_jalali":   "۱۴ مرداد",
      "time":          "۱۰:۳۰",
      "reason":        "ویزیت پیگیری",
      "status":        "confirmed"
    },
    "total_intakes":    3,
    "total_documents":  5,
    "last_intake_date": "1405/04/12"
  }
}
```

---

### `GET /patient/profile` ✅ LIVE 🔒 AUTH REQUIRED

Patient's own editable profile fields.

**Planned success response** `200`
```json
{
  "success": true,
  "data": {
    "first_name":   "مریم",
    "last_name":    "احمدی",
    "father_name":  "علی",
    "national_id":  "0079643178",
    "birth_date":   "1370/05/12",
    "mobile":       "09121234567",
    "email":        "maryam@example.com",
    "home_tel":     "02112345678",
    "home_address": "تهران، ..."
  }
}
```

---

### `GET /patient/appointments` ✅ LIVE 🔒 AUTH REQUIRED

List of patient's own appointments (read-only).

**Query params:** `page` (int, default 1), `per_page` (int, default 10).

**Planned success response** `200`
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "appointment_id": 55,
        "date_jalali":    "1405/05/14",
        "time":           "10:30",
        "reason":         "ویزیت پیگیری — جراحی بینی",
        "status":         "confirmed",
        "provider_name":  "دکتر شاهین باستانی‌نژاد"
      }
    ],
    "pagination": { "total": 4, "per_page": 10, "current_page": 1, "last_page": 1 }
  }
}
```

**Status enum values:** `confirmed` | `scheduled` | `cancelled` | `completed`

---

### `GET /patient/documents` ✅ LIVE 🔒 AUTH REQUIRED

Patient's uploaded media files with short-lived signed download URLs.

**Planned success response** `200`
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "media_uuid":   "abc123",
        "file_name":    "pre-op-front.jpg",
        "mime_type":    "image/jpeg",
        "size_bytes":   204800,
        "uploaded_at":  "2026-07-15T08:00:00Z",
        "signed_url":   "https://cdn.arvancloud.com/...",
        "url_expires":  "2026-07-27T12:00:00Z"
      }
    ]
  }
}
```
- `signed_url` TTL: 1 hour. Client must re-fetch the list when downloading after the expiry.

---

### `GET /patient/notification-preferences` ✅ LIVE 🔒 AUTH REQUIRED

**Planned success response** `200`
```json
{
  "success": true,
  "data": {
    "sms_appointment_reminder":  true,
    "sms_status_change":         true,
    "email_appointment_reminder":false,
    "email_marketing":           false
  }
}
```

---

### `PATCH /patient/notification-preferences` ✅ LIVE 🔒 AUTH REQUIRED

Update one or more notification preferences. Send only the keys you want to change (partial update).

**Request body** (any subset of the fields above)
```json
{
  "sms_appointment_reminder":   true,
  "email_appointment_reminder": true
}
```

**Success response** `200` — returns the full updated preferences object (same shape as GET).

---

## ───────────────────────────────────────────
## SECTION 4 — Staff CRM Endpoints (Existing)
## ✅ LIVE (committed in original MVC skeleton)
## ───────────────────────────────────────────

> These routes exist in the PHP files but are not yet reachable in production because `AuthMiddleware.php` and `RbacMiddleware.php` have not been committed (Phase C Task 1). Shapes below are derived from existing controller code.

### `GET /dashboard/kpis` ✅ LIVE 🔒 AUTH REQUIRED 🟡 STAFF ONLY

**Success response** `200`
```json
{
  "success": true,
  "data": {
    "today_appointments": 12,
    "pending_intakes":    4,
    "monthly_revenue":    "18400000",
    "open_tasks":         6
  }
}
```

---

### `GET /patients` ✅ LIVE 🔒 AUTH REQUIRED 🟡 STAFF ONLY

Search/list patients.

**Query params:** `q` (string, search term), `page`, `per_page`.

**Success response** `200`
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "patient_id": 87,
        "first_name": "مریم",
        "last_name":  "احمدی",
        "mobile":     "09121234567",
        "status":     "active",
        "last_visit": "1405/04/12"
      }
    ],
    "pagination": { "total": 142, "per_page": 20, "current_page": 1, "last_page": 8 }
  }
}
```

---

### `GET /patients/{id}` ✅ LIVE 🔒 AUTH REQUIRED 🟡 STAFF ONLY

Full patient detail including EMR summary and appointment timeline.

---

### `GET /appointments` ✅ LIVE 🔒 AUTH REQUIRED 🟡 STAFF ONLY

**Query params:** `date` (YYYY-MM-DD), `provider_id`, `status`, `page`, `per_page`.

---

### `POST /appointments` ✅ LIVE 🔒 AUTH REQUIRED 🟡 STAFF ONLY

Create a new appointment. Conflict detection runs server-side.

**Request body**
```json
{
  "patient_id":   87,
  "provider_id":  1,
  "room_id":      2,
  "start_at":     "2026-08-03 10:30:00",
  "end_at":       "2026-08-03 11:00:00",
  "reason":       "ویزیت پیگیری",
  "status":       "scheduled"
}
```

**Appointment status enum:** `scheduled` | `confirmed` | `in_progress` | `completed` | `cancelled` | `no_show`

---

### `PATCH /appointments/{id}` ✅ LIVE 🔒 AUTH REQUIRED 🟡 STAFF ONLY

Update appointment fields (partial update). Used for rescheduling and status changes.

---

### `DELETE /appointments/{id}` ✅ LIVE 🔒 AUTH REQUIRED 🟡 STAFF ONLY

Soft-delete (sets `deleted_at`).

---

### `GET /emr/{patient_id}` ✅ LIVE 🔒 AUTH REQUIRED 🟡 STAFF ONLY

Fetch all EMR records for a patient.

---

### `POST /emr/{patient_id}` ✅ LIVE 🔒 AUTH REQUIRED 🟡 STAFF ONLY

Create a new EMR entry (consultation note, post-op note, etc.).

**Request body**
```json
{
  "template_id":  3,
  "notes":        "post-op week 2 — healing well",
  "attachments":  []
}
```

---

## ───────────────────────────────────────────
## SECTION 4B — Public Utility Endpoints
## ✅ LIVE
## ───────────────────────────────────────────

### `POST /api/v1/inquiries` ✅ LIVE — public, no auth required

Contact form submission from `drbastaninejad.com/contact.html`.
No OTP, no national-ID, no medical data.

**Request body**
```json
{
  "name":    "علی رضایی",
  "phone":   "09121234567",
  "message": "لطفاً در مورد جراحی بینی راهنمایی بفرمایید"
}
```

- `phone` — Iranian mobile; normalised server-side (accepts `09xx`, `+989xx`, Persian digits)
- `message` — 10–2000 characters
- Rate limit: max 3 submissions per phone per 30 minutes → `429 INQUIRY_RATE_LIMITED`

**Success response** `201`
```json
{
  "success": true,
  "data": { "inquiry_id": 7 }
}
```

**Error codes**

| Code | HTTP | Meaning |
|---|---|---|
| `VALIDATION_FAILED` | 422 | Missing/invalid field — `error.fields` map |
| `INQUIRY_RATE_LIMITED` | 429 | Too many submissions from this phone |

> Backend: `InquiryController::store()` → `InquiryModel` → `inquiries` table (migration 009)

---

## ───────────────────────────────────────────
## SECTION 5 — Future Endpoints (Phase 6–8)
## ⚠️ PENDING — not yet designed in detail
## ───────────────────────────────────────────

| Endpoint | Phase | Notes |
|---|---|---|
| `POST /billing/payment` | 6 | Zarinpal + IDPay redirect init |
| `GET /billing` | 6 | Patient/staff billing list |
| `GET /tasks` | 6 | Kanban task list |
| `POST /tasks` | 6 | Create task |
| `PATCH /tasks/{id}` | 6 | Update task status/assignee |
| `GET /analytics/referrals` | 6 | Referral source conversion |
| `GET /analytics/revenue` | 6 | Date-range revenue |
| `GET /settings/clinic` | 6 | Clinic profile |
| `PATCH /settings/clinic` | 6 | Update clinic profile |
| `GET /settings/working-hours` | 6 | Weekly schedule |
| `PATCH /settings/working-hours` | 6 | Update weekly schedule |
| `POST /ai/query` | 7 | OpenRouter AI copilot |
| `POST /media/upload` | 6 | File upload (ArvanCloud S3) |
| `GET /media/{uuid}/url` | 6 | Get signed short-TTL URL |

---

## Appendix A — Jalali Date Format

All dates in **request bodies** must be Jalali (Shamsi) format: `YYYY/MM/DD` (e.g. `1370/05/12`).  
All dates in **response bodies** are returned as ISO 8601 UTC (e.g. `2026-07-27T10:00:00Z`) unless the field is named `*_jalali` in which case it is the formatted Jalali string for display.

Conversion is handled server-side by `JalaliConverter::jalaliStringToGregorian()` (pure PHP, no external deps). The client must never convert dates independently — always send Jalali, always store Gregorian. `birth_date` in the `intakes` table is now populated from the Jalali input.

---

## Appendix B — Persian Digit Normalisation

Before any numeric field is validated server-side, it passes through `ValidatorService::normalizePersianDigits()` which converts Unicode Persian (`۰`–`۹`) and Arabic (`٠`–`٩`) digits to ASCII. **The frontend must do the same** before sending using `normalizePersianDigits()` from `shared/api.js`.

---

*MZ — MAZ//ID · https://maziyarid.com · © 2026 Maziyar / Dr. Shahin Bastaninejad*
