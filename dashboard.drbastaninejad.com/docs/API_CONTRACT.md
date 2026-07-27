<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# API Contract — Phase A & B

**Version:** 1.0 · **Author:** MAZ//ID · **Date:** 27 July 2026  
**Base URL:** `https://dashboard.drbastaninejad.com/api/v1`

All responses use the standard envelope:
```json
{ "data": ..., "meta": { "page": 1, "per_page": 20, "total": 0 }, "errors": null }
```
Error shape:
```json
{ "data": null, "errors": [{ "field": "mobile", "message": "شماره نامعتبر" }] }
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
| `first_name` | string | ✅ | |
| `last_name` | string | ✅ | |
| `mobile` | string | ✅ | Persian digits accepted; normalised to `09XXXXXXXXX` |
| `national_id` | string | ✅ | 10 digits, Persian digits accepted; mod-11 validated |
| `birth_date_jalali` | string | ✅ | Format `YYYY/MM/DD` or `YYYY-MM-DD`; Persian digits OK |
| `chief_complaint` | string | ✅ | Free text |
| `gender` | string | — | `male` \| `female` |
| `service_type` | string | — | e.g. `rhinoplasty` |
| `preferred_date` | string | — | Jalali or Gregorian date |
| `insurance_type` | string | — | |
| `home_address` | string | — | |

#### Responses

**201 Created** — new submission accepted:
```json
{
  "data": {
    "intake_id": 42,
    "patient_uuid": "a1b2c3d4e5f6...",
    "status": "pending",
    "idempotent": false
  },
  "meta": null,
  "errors": null
}
```

**200 OK** — duplicate `submission_uuid` (safe retry):
```json
{
  "data": {
    "intake_id": 42,
    "patient_uuid": "a1b2c3d4e5f6...",
    "status": "pending",
    "idempotent": true
  },
  "meta": null,
  "errors": null
}
```

**422 Unprocessable Entity** — validation failure:
```json
{
  "data": null,
  "errors": [
    { "field": "national_id", "message": "کد ملی معتبر نیست" },
    { "field": "mobile", "message": "شماره موبایل معتبر نیست" }
  ]
}
```

**500 Internal Server Error** — transaction failed (retry safe if you have a fresh `submission_uuid`).

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
{ "data": { "message": "کد تایید ارسال شد", "expires_in": 300 }, "errors": null }
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

## Idempotency & Dual-Write Notes

1. The `submission_uuid` **UNIQUE** constraint is enforced at the DB level in `intakes.submission_uuid`, not only in PHP. A duplicate INSERT will throw a PDO exception, which the controller converts to a 200 idempotent response.

2. Google Sheets dual-write happens **after** the DB transaction commits. A Sheets API failure never rolls back the DB write. Failures are logged to `error_log` and visible in cPanel error logs.

3. OTP codes are stored as **bcrypt hashes** — raw codes are never persisted. In `APP_ENV != production`, the raw OTP is also logged to `error_log` for development convenience.

4. The bearer token is stored as **SHA-256 hash** in `auth_tokens.token_hash`. The raw token is only returned once in the `/verify` response and never stored in plain text.

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

# SMS (Kavenegar)
KAVENEGAR_API_KEY=
KAVENEGAR_SENDER=

# Google Sheets dual-write
GOOGLE_SHEET_ID=
GOOGLE_SHEET_TAB=Intakes
GOOGLE_SA_KEY_PATH=/home/USER/sa-key.json  # OUTSIDE web root
```

---

_M•Z · MAZ//ID · © 2026 Dr. Shahin Bastaninejad_
