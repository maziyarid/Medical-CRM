<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# API Contract — MΛZ Medical CRM

**Version:** 1.2 · **Author:** MAZ//ID · **Date:** 27 July 2026  
**Base URL:** `https://dashboard.drbastaninejad.com/api/v1`

All responses use the standard envelope:
```json
{ "ok": true, "data": ..., "meta": { "page": 1, "per_page": 20, "total": 0 }, "errors": null }
```
Error shape:
```json
{ "ok": false, "data": null, "errors": [{ "field": "mobile", "message": "شماره نامعتبر" }] }
```

---

## Phase A — Intake Write Path

### `POST /api/v1/intakes`

**Auth:** None (public endpoint)  
**Purpose:** Accept new patient intake form submission from `intake.html`

#### Request body

| Field | Type | Required | Notes |
|---|---|---|---|
| `submission_uuid` | string (≤64) | ✅ | Client-generated UUID v4; enables idempotent retries |
| `firstName` | string | ✅ | camelCase from intake.html; normalised server-side (§6.1 UNIFIED_MASTER_PLAN) |
| `lastName` | string | ✅ | camelCase from intake.html |
| `mobile` | string | ✅ | Persian digits accepted; normalised to `09XXXXXXXXX` |
| `nationalId` | string | ✅ | 10 digits, Persian digits accepted; mod-11 validated |
| `birthDate` | string | ✅ | Format `YYYY/MM/DD`; Persian digits OK; Jalali — converted to Gregorian server-side |
| `description` | string | ✅ | Free text — mapped to `chief_complaint` server-side |
| `visitReason` | string | ✅ | Mapped to `visit_reason` column |
| `email` | string | — | Optional email address |
| `fatherName` | string | — | camelCase; mapped to `father_name` |
| `homeTel` | string | — | Home telephone; stored in `raw_payload` only |
| `homeAd` | string | — | camelCase; mapped to `home_address` |
| `isTransfer` | int | — | Default `0` |

> **Note:** Both camelCase (from `intake.html`) and snake_case keys are accepted.  
> The server normalises camelCase to snake_case in `IntakeController::normalisePayload()`.  
> The frontend payload contract is never changed (§6.1 UNIFIED_MASTER_PLAN.md).

#### Responses

**201 Created** — new submission accepted:
```json
{
  "ok": true,
  "data": {
    "intake_id": 42,
    "patient_uuid": "a1b2c3d4e5f6...",
    "status": "pending",
    "sheets_sync_status": "ok",
    "idempotent": false
  },
  "meta": null,
  "errors": null
}
```

> `sheets_sync_status` values:  
> `"ok"` — row appended to Google Sheet  
> `"failed"` — Sheets write failed (row is safe in MySQL; will retry on next duplicate request)  
> `"skipped"` — Google Sheets not configured (expected in dev/staging)  
> `"pending"` — only persists if the process was killed between DB commit and Sheets write

**200 OK** — duplicate `submission_uuid` (safe retry):
```json
{
  "ok": true,
  "data": {
    "intake_id": 42,
    "patient_uuid": "a1b2c3d4e5f6...",
    "status": "pending",
    "sheets_sync_status": "ok",
    "idempotent": true
  },
  "meta": null,
  "errors": null
}
```

> On idempotent 200 path: if `sheets_sync_status` was `"failed"` or `"pending"`, the server
> automatically retries the Sheets write before responding.

**422 Unprocessable Entity** — validation failure:
```json
{
  "ok": false,
  "data": null,
  "errors": [
    { "field": "national_id", "message": "کد ملی معتبر نیست" },
    { "field": "mobile", "message": "شماره موبایل معتبر نیست" }
  ]
}
```

**500 Internal Server Error** — transaction failed (retry safe — use the same `submission_uuid`).

---

### `GET /api/v1/intakes`

**Auth:** Bearer token (staff, requires `intakes.view` permission)  
**Purpose:** Paginated staff review queue for `staff/dashboard.html` and `staff/patients.html`

#### Query params

| Param | Default | Notes |
|---|---|---|
| `page` | 1 | |
| `per_page` | 20 | max 100 |
| `status` | all | `pending` \| `reviewed` \| `converted` \| `rejected` |
| `q` | — | search first_name, last_name, mobile, national_id |

#### Response `data` array item

```json
{
  "id": 42,
  "submission_uuid": "...",
  "patient_uuid": "...",
  "first_name": "علی",
  "last_name": "محمدی",
  "mobile": "09121234567",
  "national_id": "0079643178",
  "service_type": "rhinoplasty",
  "chief_complaint": "اصلاح فرم بینی",
  "preferred_date": "2026-10-01",
  "status": "pending",
  "created_at": "2026-07-27 11:00:00"
}
```

---

## Phase B — OTP Authentication

### `POST /api/v1/auth/otp/send`

**Auth:** None  
**Purpose:** Send a 5-digit OTP to an Iranian mobile number

#### Request body

| Field | Type | Required | Notes |
|---|---|---|---|
| `mobile` | string | ✅ | Persian digits accepted; all standard formats |

**Rate limit:** 3 requests per 10 minutes per mobile → **429 Too Many Requests**

#### Responses

**200 OK:**
```json
{ "ok": true, "data": { "message": "کد تایید ارسال شد", "expires_in": 300 }, "errors": null }
```

**422** — invalid mobile format  
**429** — rate limit exceeded  
**503** — SMS gateway error (retry after a moment)

---

### `POST /api/v1/auth/otp/verify`

**Auth:** None  
**Purpose:** Verify OTP and receive a 30-day bearer token

#### Request body

| Field | Type | Required | Notes |
|---|---|---|---|
| `mobile` | string | ✅ | Same format as /send |
| `otp` | string | ✅ | 5 digits; Persian digits accepted |

#### Responses

**200 OK:**
```json
{
  "ok": true,
  "data": {
    "token": "<64-char hex>",
    "expires_at": "2026-08-26 11:00:00",
    "user": {
      "uuid": "...",
      "first_name": "علی",
      "last_name": "محمدی",
      "mobile": "09121234567",
      "role": "patient"
    }
  },
  "errors": null
}
```

**401** — invalid OTP  
**410** — expired OTP (re-send required)  
**422** — format error

---

## Phase C — Patient Portal (authenticated patient endpoints)

All endpoints below require a valid patient-scoped Bearer token obtained from `/auth/otp/verify`.

### `GET /api/v1/patient/overview`

**Auth:** Bearer token (patient)  
**Purpose:** Summary for the patient portal home screen

```json
{
  "ok": true,
  "data": {
    "next_appointment": {
      "uuid": "...",
      "starts_at": "2026-08-15 10:30:00",
      "ends_at": "2026-08-15 11:00:00",
      "status": "confirmed",
      "reason": "ویزیت پیگیری"
    },
    "last_intake": {
      "uuid": "...",
      "service_type": "rhinoplasty",
      "chief_complaint": "اصلاح فرم بینی",
      "status": "pending",
      "submitted_at": "2026-07-27 09:00:00"
    }
  }
}
```

`next_appointment` and `last_intake` are `null` if none exist.

---

### `GET /api/v1/patient/profile`

**Auth:** Bearer token (patient)  
**Purpose:** Read the patient's own profile fields

**Response `data`:** `uuid`, `first_name`, `last_name`, `father_name`, `mobile`, `email`, `national_id`, `birth_date`, `birth_date_jalali`, `gender`, `insurance_number`, `insurance_status`, `home_address`

---

### `PATCH /api/v1/patient/profile`

**Auth:** Bearer token (patient)  
**Purpose:** Update patient-editable fields

**Allowed fields:** `first_name`, `last_name`, `father_name`, `home_address`, `email`  
(Medical and RBAC fields are not writable by the patient.)

**Response:** `{ "ok": true, "data": { "updated": true } }`

---

### `GET /api/v1/patient/appointments`

**Auth:** Bearer token (patient)  
**Query:** `page` (default 1), `per_page` (default 20, max 50)  
**Response `data`:** array of `{ uuid, starts_at, ends_at, status, reason, reminder_sent_at }`

---

### `GET /api/v1/patient/documents`

**Auth:** Bearer token (patient)  
**Purpose:** List patient's uploaded media files  
**Response `data`:** array of `{ uuid, type, tag, mime_type, size_bytes, created_at, download_endpoint }`

> `download_endpoint` is `/api/v1/media/{uuid}/url` (signed URL generation — Phase 6, not yet live).

---

### `GET /api/v1/patient/notification-preferences`

**Auth:** Bearer token (patient)  
**Response:** `{ "ok": true, "data": { "marketing_email_optin": false } }`

---

### `PATCH /api/v1/patient/notification-preferences`

**Auth:** Bearer token (patient)  
**Body:** `{ "marketing_email_optin": true }`  
**Response:** `{ "ok": true, "data": { "marketing_email_optin": true } }`

---

## Phase D — Staff Dashboard & Patient Management

All endpoints below require a valid staff-scoped Bearer token.

---

### `GET /api/v1/dashboard/overview`

**Auth:** Bearer token (staff, requires `dashboard.view` permission)
**Purpose:** Aggregate KPI metrics and today's schedule for the staff dashboard home screen
**Route file:** `config/routes.dashboard.php`
**Controller:** `DashboardController::overview()`

#### Response `data`

```json
{
  "metrics": [
    { "label": "نوبت‌های امروز",      "value": "5",            "href": "#calendar",  "deltaDir": "" },
    { "label": "پذیرش‌های در انتظار", "value": "3",            "href": "#patients",  "deltaDir": "down" },
    { "label": "درآمد امروز",          "value": "۱۲۰٬۰۰۰ تومان", "href": "#billing",   "deltaDir": "" },
    { "label": "وظایف باز",           "value": "8",            "href": "#tasks",     "deltaDir": "" }
  ],
  "attention": [
    { "patient": "علی محمدی", "item": "پذیرش بررسی‌نشده", "badge": "warning", "status": "در انتظار" }
  ],
  "today": [
    { "patient": "مریم احمدی", "time": "10:00", "reason": "ویزیت", "status": "confirmed", "badge": "success" }
  ]
}
```

> `metrics` always has exactly 4 items in the same order.
> `attention` and `today` are empty arrays when nothing matches.
> `time` is in `HH:MM` format (UTC displayed as Tehran time by the frontend).

**401** — token missing / expired
**403** — `dashboard.view` permission not present on role

---

### `GET /api/v1/patients`

**Auth:** Bearer token (staff, requires `patients.view` permission)
**Purpose:** Paginated, searchable list of all patients for the staff Patient Master Index

#### Query params

| Param | Default | Notes |
|---|---|---|
| `q` | — | free-text search: first_name, last_name, mobile, national_id |
| `page` | 1 | |
| `per_page` | 20 | max 100 |

#### Response `data`

```json
{
  "rows": [
    {
      "id": 1,
      "name": "علی محمدی",
      "mobile": "09121234567",
      "national_id": "0079643178",
      "insurance_status": "active",
      "last_visit": "2026-07-15 10:00:00",
      "upcoming_count": 2
    }
  ],
  "total": 120,
  "page": 1,
  "per_page": 20
}
```

> `last_visit` is a UTC datetime string; Jalali conversion is at the presentation layer.
> `insurance_status` values: `active` | `inactive` | `pending` | `unknown`.

---

### `GET /api/v1/patients/{id}`

**Auth:** Bearer token (staff, requires `patients.view` permission)
**Purpose:** Full patient header + timeline for the Patient Detail screen

#### Response `data`

```json
{
  "patient": {
    "id": 1,
    "name": "علی محمدی",
    "mobile": "09121234567",
    "national_id": "0079643178",
    "birth_date": "1990-05-12",
    "insurance_status": "active",
    "home_address": "تهران، خیابان ولیعصر"
  },
  "timeline": []
}
```

---

### `POST /api/v1/patients`

**Auth:** Bearer token (staff, requires `patients.manage` permission)
**Purpose:** Create a new patient record outside the public intake flow
**Body:** `first_name`, `last_name`, `mobile` (required), `national_id`, `home_address`
**Response:** `{ "ok": true, "data": { "id": 42 }, "meta": null, "errors": null }` (201)

---

### `PUT /api/v1/patients/{id}`

**Auth:** Bearer token (staff, requires `patients.manage` permission)
**Purpose:** Update patient editable fields
**Writable fields:** `first_name`, `last_name`, `mobile`, `home_address`, `insurance_status`
**Response:** `{ "ok": true, "data": { "id": 42 } }`

---

## Idempotency & Dual-Write Notes

1. The `submission_uuid` **UNIQUE** constraint is enforced at the DB level in `intakes.submission_uuid`, not only in PHP. A duplicate INSERT will throw a PDO exception, which the controller converts to a 200 idempotent response.

2. Google Sheets dual-write happens **after** the DB transaction commits. A Sheets API failure never rolls back the DB write. The outcome is recorded in `intakes.sheets_sync_status` (`ok` | `failed` | `skipped` | `pending`).

3. **outcome_unknown / reconciliation:** if `sheets_sync_status` is not `ok` on the idempotent 200 path, the server automatically retries the Sheets write. This handles the process-kill-after-DB-commit scenario without any external reconciliation job.

4. OTP codes are stored as **bcrypt hashes** — raw codes are never persisted. In `APP_ENV != production`, the raw OTP is also logged to `error_log` for development convenience.

5. The bearer token is stored as **SHA-256 hash** in `auth_tokens.token_hash`. The raw token is only returned once in the `/verify` response and never stored in plain text.

---

## Environment Variables Required (`.env`)

```ini
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=medical_crm
DB_USER=crm_user
DB_PASS=your_password

# Clinic
DEFAULT_CLINIC_ID=1
APP_ENV=production   # set to "development" to log OTPs

# SMS (primary + fallback chain — see SmsProviderChain.php)
SMS_PROVIDERS=kavenegar,ghasedak,farazsms,tsms
KAVENEGAR_API_KEY=
KAVENEGAR_SENDER=
GHASEDAK_API_KEY=
GHASEDAK_TEMPLATE=verify
GHASEDAK_LINE=
FARAZSMS_USERNAME=
FARAZSMS_PASSWORD=
FARAZSMS_FROM=
TSMS_USERNAME=
TSMS_PASSWORD=
TSMS_FROM=

# Google Sheets dual-write
GOOGLE_SHEET_ID=
GOOGLE_SHEET_TAB=Intakes
GOOGLE_SA_KEY_PATH=/home/USER/sa-key.json  # OUTSIDE web root
```

---

_M•Z · MAZ//ID · © 2026 Dr. Shahin Bastaninejad_
