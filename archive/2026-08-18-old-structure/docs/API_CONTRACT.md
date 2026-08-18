# API Contract — MΛZ Medical CRM
<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

This document is the canonical reference for every HTTP endpoint in the platform.
It is append-only. Every change to a response shape, field name, or status enum
**must** be reflected here before merging.

---

## Global conventions

| Convention | Value |
|---|---|
| Base URL — patient app | `https://app.drbastaninejad.com/api/v1` |
| Base URL — staff dashboard | `https://dashboard.drbastaninejad.com/api/v1` |
| Content-Type | `application/json` |
| Auth header | `Authorization: Bearer <token>` |
| Datetime format | MySQL `YYYY-MM-DD HH:MM:SS` UTC — display as Jalali via `Jalali.formatNumeric()` |
| Boolean | `1` / `0` in DB; `true` / `false` in JSON |
| Pagination default | `page=1`, `per_page=20`, max `per_page=100` |

### Response envelopes

**app.drbastaninejad.com** (patient portal):
```json
{ "success": true, "data": { ... } }
{ "success": false, "error": "message" }
```

**dashboard.drbastaninejad.com** (staff CRM):
```json
{ "ok": true,  "status": 200, "data": { ... }, "errors": null, "meta": null }
{ "ok": false, "status": 422, "data": null, "errors": [{"field": "mobile", "message": "..."}], "meta": null }
```

### HTTP status codes used

| Code | Meaning |
|---|---|
| 200 | OK |
| 201 | Created |
| 400 | Bad request |
| 401 | Unauthenticated — client must redirect to login |
| 403 | Forbidden — RBAC check failed |
| 404 | Resource not found |
| 409 | Conflict (e.g. appointment slot overlap) |
| 422 | Validation error |
| 500 | Server error |

---

## Authentication endpoints (shared / both subdomains)

### POST /api/v1/auth/otp/send
Send a one-time password to a mobile number.

**Body:**
```json
{ "mobile": "09121234567" }
```
**Response 200:**
```json
{ "success": true, "data": { "expires_in": 300 } }
```
**Error 429:** Rate limited (3 attempts per 10 minutes).

---

### POST /api/v1/auth/otp/verify
Verify OTP and issue a bearer token.

**Body:**
```json
{ "mobile": "09121234567", "code": "12345" }
```
**Response 200:**
```json
{
  "success": true,
  "data": {
    "token": "<64-char hex>",
    "expires_at": "2026-09-15 10:30:00",
    "user_type": "patient",
    "user": {
      "uuid": "abc123...",
      "first_name": "مریم",
      "last_name": "احمدی",
      "mobile": "09121234567",
      "role": "patient"
    }
  }
}
```
**`user_type`:** `"patient"` (app backend) or `"staff"` (dashboard backend).
**Token storage:** `localStorage` key `mz_auth_token` (set by `shared/api.js` `setToken()`).

---

## Patient portal endpoints (app.drbastaninejad.com)

All require `Authorization: Bearer <token>` where `user_type = "patient"`.

### GET /api/v1/patient/overview
Dashboard overview for a single patient.

**Response 200 `data`:**
```json
{
  "patient_name": "مریم احمدی",
  "next_appointment": {
    "appointment_id": 14,
    "scheduled_at": "2026-08-15 14:30:00",
    "duration_minutes": 20,
    "reason": "ویزیت پیگیری",
    "status": "confirmed"
  },
  "total_intakes": 3,
  "total_documents": 5,
  "last_intake_date": "2026-07-10"
}
```
`next_appointment` is `null` when no upcoming appointment exists.

---

### GET /api/v1/patient/appointments
List all appointments for the authenticated patient.

**Query params:** `page`, `per_page`

**Response 200 `data`:**
```json
{
  "items": [
    {
      "appointment_id": 14,
      "scheduled_at": "2026-08-15 14:30:00",
      "duration_minutes": 20,
      "reason": "ویزیت پیگیری",
      "status": "confirmed",
      "provider_name": "دکتر باستانی‌نژاد"
    }
  ],
  "pagination": {
    "total": 4,
    "per_page": 20,
    "current_page": 1,
    "last_page": 1
  }
}
```

**`status` enum — Persian display labels:**

| Value | Persian label | Pill class |
|---|---|---|
| `scheduled` | در انتظار تأیید | `info` |
| `confirmed` | تأیید شده | `evergreen` |
| `cancelled` | لغو شده | `muted` |
| `completed` | انجام شد | `success` |

---

### GET /api/v1/patient/records
Paginated read-only timeline of signed EMR records.

**Response 200 `data`:**
```json
{
  "items": [
    {
      "id": 7,
      "chief_complaint": "درد شکمی",
      "diagnosis": "کولیت",
      "plan": "استراحت + دارو",
      "ai_accepted": false,
      "created_at": "2026-07-20 09:15:00"
    }
  ],
  "pagination": { "total": 12, "per_page": 10, "current_page": 1, "last_page": 2 }
}
```

---

### GET /api/v1/patient/profile
**Response 200 `data`:** `{ id, uuid, first_name, last_name, mobile, national_id, birth_date, home_address, insurance_status }`

> ⚠ **Backend team note (2026-08-01):** The backend also returns `father_name`, `email`, and
> `home_tel` in the profile response. These fields are used by `profile.html` but are not listed
> in this contract. Please confirm they are stable and add them here, or remove them from the
> backend response. Until confirmed, the frontend renders them but relies on them gracefully
> returning empty strings if absent.

### PATCH /api/v1/patient/profile
**Body (all optional):** `{ first_name, last_name, home_address }`

> ⚠ **Contract discrepancy (2026-08-01):** `profile.html` currently sends
> `{ email, home_tel, home_address }` — NOT `{ first_name, last_name, home_address }`.
> Backend must clarify which writable fields the endpoint actually accepts and update this
> contract. Frontend will not be changed until backend confirms the correct field list.
> Tracked in `shared/api.js` JSDoc comment and `PROGRESS_LOG.md`.

---

## Staff dashboard endpoints (dashboard.drbastaninejad.com)

All require `Authorization: Bearer <token>` where `user_type = "staff"`.
All responses use the `{ ok, status, data, errors, meta }` envelope.

---

### GET /api/v1/dashboard/overview
Staff dashboard KPIs and activity timeline.

**Response 200 `data`:**
```json
{
  "metrics": [
    { "label": "نوبت‌های امروز",      "value": "12", "icon": "calendar",            "href": "/appointments" },
    { "label": "پذیرش‌های در انتظار", "value": "4",  "icon": "clipboard-account",   "href": "/intakes", "deltaDir": "down" },
    { "label": "درآمد امروز",         "value": "۱,۸۴۰,۰۰۰ تومان", "icon": "finance", "href": "/billing" },
    { "label": "وظایف باز",           "value": "6",  "icon": "check-circle-outline", "href": "/tasks" }
  ],
  "timeline": [
    {
      "id": "intake-5",
      "type": "intake",
      "timestamp": "2026-08-14 08:21:00",
      "patient": { "id": 12, "name": "رضا محمدی", "href": "/patients/12" },
      "title": "پذیرش جدید دریافت شد",
      "description": "از طریق وب‌سایت ارسال شده.",
      "status": { "label": "در انتظار بررسی", "badge": "warning" },
      "actors": [{ "type": "system", "name": "فرم وب" }],
      "href": "/intakes/5"
    }
  ]
}
```

---

### GET /api/v1/patients
List patients with search and insurance filter.

**Query params:** `q`, `page`, `per_page`, `insurance_status`

**`insurance_status` enum:** `active` | `inactive` | `pending` | `unknown`

**Response 200 `data`:**
```json
{
  "rows": [
    {
      "id": 12,
      "name": "رضا محمدی",
      "mobile": "09131112233",
      "national_id": "0022334455",
      "insurance_status": "active",
      "last_visit": "2026-07-20 09:15:00",
      "upcoming_count": 1
    }
  ],
  "total": 48,
  "page": 1,
  "per_page": 20
}
```
No nested `pagination` object — compute `last_page = Math.ceil(total / per_page)` on the client.

---

### GET /api/v1/patients/{id}
Patient detail with activity timeline.

**Response 200 `data`:**
```json
{
  "patient": {
    "id": 12,
    "name": "رضا محمدی",
    "mobile": "09131112233",
    "national_id": "0022334455",
    "birth_date": "1370-05-12",
    "insurance_status": "active",
    "home_address": "تهران، ..."
  },
  "timeline": [
    {
      "type": "appointment",
      "timestamp": "2026-08-10 11:00:00",
      "title": "مشاوره اولیه",
      "description": "",
      "status": "confirmed"
    }
  ]
}
```
**Timeline `type` values:** `intake` | `appointment` | `emr_note` | `invoice`

---

### GET /api/v1/appointments
Calendar events for a date range.

**Query params:** `from` (YYYY-MM-DD HH:MM:SS), `to`, `provider_id`

**Response 200 `data`:**
```json
{
  "events": [
    {
      "id": 14,
      "patient_id": 12,
      "patient_name": "رضا محمدی",
      "provider_id": 3,
      "start": "2026-08-15 14:30:00",
      "duration_minutes": 20,
      "reason": "ویزیت پیگیری",
      "status": "confirmed",
      "badge": "success",
      "room": "اتاق ۱",
      "notes": null
    }
  ]
}
```

**`status` / `badge` enum:**

| `status` | `badge` | Persian label |
|---|---|---|
| `scheduled` | `info` | زمان‌بندی شده |
| `confirmed` | `success` | تایید شده |
| `cancelled` | `error` | لغو شده |
| `completed` | `muted` | تکمیل شده |

---

### POST /api/v1/appointments
Create a new appointment.

**Body:**
```json
{
  "patient_id": 12,
  "provider_id": 3,
  "scheduled_at": "2026-08-15 14:30:00",
  "duration_minutes": 20,
  "visit_reason": "مشاوره اولیه",
  "room": "اتاق ۱",
  "notes": null
}
```
**Response 201:** `{ "id": 14 }`
**Response 409:** Conflict — slot already booked for this provider.

---

### PATCH /api/v1/appointments/{id}/reschedule
**Body:** `{ "scheduled_at": "...", "duration_minutes": 20 }`

### PATCH /api/v1/appointments/{id}/status
**Body:** `{ "status": "confirmed" }` — allowed: `scheduled|confirmed|cancelled|completed`

### DELETE /api/v1/appointments/{id}
**Body:** `{ "reason": "بیمار لغو کرد" }`

---

### GET /api/v1/patients/{id}/emr
**Response 200 `data`:** `{ "records": [ { id, chief_complaint, diagnosis, plan, ai_accepted, created_at } ] }`

### POST /api/v1/patients/{id}/emr
**Body:** `{ "chief_complaint": "...", "diagnosis": null, "plan": null, "appointment_id": null, "template_id": null, "specialty_fields": {} }`
**Response 201:** `{ "id": 7 }`

### GET /api/v1/emr/templates
**Query:** `specialty` (optional filter)
**Response 200 `data`:** `{ "templates": [ { id, name, specialty, schema_json } ] }`

### POST /api/v1/ai/emr-draft
**Body:** `{ "chief_complaint": "...", "context": {} }`
**Response 200 `data`:** `{ "draft": "...", "requires_review": true }`
Note: AI draft is **never** auto-saved. Staff must Accept/Edit/Discard.

---

### GET /api/v1/analytics/summary
**Query params:** `range` (`30d` | `90d` | `1y`), `date_from`, `date_to`

**Response 200 `data`:**
```json
{
  "range": { "from": "2026-07-01 00:00:00", "to": "2026-07-31 23:59:59" },
  "kpis": [
    { "key": "new_patients",    "label": "مراجعین جدید",            "value": 23,  "delta_pct": 12.5, "delta_dir": "up" },
    { "key": "conversion_rate", "label": "نرخ تبدیل مشاوره → عمل", "value": 68.0,"delta_pct": 0,    "delta_dir": "" },
    { "key": "revenue",         "label": "درآمد (ریال)",            "value": 1840000000, "delta_pct": -5.2, "delta_dir": "down" },
    { "key": "return_rate",     "label": "نرخ بازگشت بیمار",        "value": 34.0,"delta_pct": 0,    "delta_dir": "" }
  ],
  "chart_new_patients": [
    { "week": "202630", "count": 5 }
  ],
  "referral_sources": [
    { "source": "وب‌سایت", "count": 14, "pct": 60.9 }
  ]
}
```

---

### GET /api/v1/billing/invoices
**Query params:** `q`, `status`, `gateway`, `page`, `per_page`

**`status` enum:** `pending` | `paid` | `failed` | `insurance_pending`

**Response 200 `data`:**
```json
{
  "rows": [
    {
      "id": 3,
      "patient_id": 12,
      "patient_name": "رضا محمدی",
      "amount_rials": 18500000,
      "status": "paid",
      "gateway": "zarinpal",
      "notes": null,
      "created_at": "2026-07-20 09:15:00"
    }
  ],
  "total": 87,
  "page": 1,
  "per_page": 20,
  "summary": {
    "total": 87,
    "pending_count": 4,
    "pending_amount_label": "۷م",
    "failed_count": 1,
    "avg_label": "۱۸م"
  }
}
```
No nested `pagination` — compute `last_page = Math.ceil(total / per_page)`.

**`status` display labels:**

| Value | Persian | Pill class |
|---|---|---|
| `paid` | پرداخت شد | `success` |
| `pending` | در انتظار | `warning` |
| `failed` | ناموفق | `error` |
| `insurance_pending` | در انتظار بیمه | `info` |

### POST /api/v1/billing/invoices
**Body:** `{ "patient_id": 12, "amount_rials": 18500000, "gateway": "zarinpal", "notes": null }`
**Response 201:** `{ "id": 3 }`

### PATCH /api/v1/billing/invoices/{id}/status
**Body:** `{ "status": "paid" }`

---

### GET /api/v1/tasks
**Query params:** `status`, `assignee_id`

**Response 200 `data`:**
```json
{
  "columns": {
    "todo":        [ { "id": 1, "title": "...", "priority": "high", "status": "todo", "assignee_name": null, "due_date": null } ],
    "in_progress": [],
    "done":        []
  },
  "total": 6
}
```

**`priority` enum:** `high` | `medium` | `low`
**`status` enum:** `todo` | `in_progress` | `done`

### POST /api/v1/tasks
**Body:** `{ "title": "...", "priority": "medium", "status": "todo", "assignee_id": null, "due_date": null, "notes": null }`
**Response 201:** `{ "id": 1, "status": "todo" }`

### PATCH /api/v1/tasks/{id}/status
**Body:** `{ "status": "in_progress" }`

### DELETE /api/v1/tasks/{id}
**Response 200:** `{ "id": 1 }`

---

### GET /api/v1/settings/clinic
**Response 200 `data`** (flat — no nesting):
```json
{
  "id": 1,
  "name": "کلینیک دکتر باستانی‌نژاد",
  "phone": "02188001234",
  "address": "تهران، ...",
  "timezone": "Asia/Tehran",
  "working_hours": [
    { "day": "saturday",  "day_label": "شنبه",    "active": true,  "open": "09:00", "close": "18:00" },
    { "day": "friday",    "day_label": "جمعه",    "active": false, "open": null,    "close": null }
  ],
  "updated_at": "2026-07-15 12:00:00",
  "emr_templates": [
    { "id": 1, "name": "رینوپلاستی", "specialty": "plastic_surgery", "schema_json": "{...}", "created_at": "2026-07-01" }
  ]
}
```

### PATCH /api/v1/settings/clinic
**Body (all optional):** `{ "name", "phone", "address", "timezone", "working_hours" }`
**Response 200:** `{ "id": 1 }`

---

## Health check

### GET /api/v1/health (both subdomains)
No auth required. Used by `offline.html` retry probe.
**Response 200:** `{ "ok": true, "version": "1.0.0" }`

---

## Storage keys (client-side)

| Key | Storage | Set by | Value |
|---|---|---|---|
| `mz_auth_token` | `localStorage` | `shared/api.js setToken()` | 64-char hex bearer token |
| `mz_intake_uuid` | `sessionStorage` | `shared/api.js getOrCreateIntakeUUID()` | UUIDv4 for idempotent intake submissions |

---

## Changelog

| Date | Change |
|---|---|
| 2026-08-xx | Initial write — all endpoints documented, status enums confirmed, storage keys verified |
