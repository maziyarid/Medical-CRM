# SCHEMA.md — MΛZ Medical CRM Database Schema
<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

**Version:** 2.0  
**Date:** 2026-07-31  
**Database:** MariaDB 10.11+ / MySQL 8.0+, utf8mb4, InnoDB, UTC timezone  
**Applies to:** Both `app.drbastaninejad.com` and `dashboard.drbastaninejad.com` — they share one database.  
**RULE:** Never create a second patients table or a second clinical database (UNIFIED_MASTER_PLAN.md §2).

---

## Migration Run Order

Run all migrations in numeric order. Migrations are idempotent (`IF NOT EXISTS`).

| # | File (dashboard) | File (app) | Creates / Alters |
|---|---|---|---|
| 001 | `001_create_intakes_table.sql` | `001_create_intakes_table.sql` | `intakes` |
| 002 | `002_create_otp_codes_table.sql` | `002_create_otp_codes_table.sql` | `otp_codes` |
| 003 | _(shared — run app version)_ | `003_create_patients_table.sql` | `patients` |
| 004 | `014_create_auth_tokens_table.sql` | `004_create_auth_tokens_table.sql` | `auth_tokens` |
| 005 | _(app only)_ | `005_create_patient_media_table.sql` | `patient_media` |
| 006 | _(app only)_ | `006_create_notification_preferences_table.sql` | `notification_preferences` |
| 007 | _(app only)_ | `007_create_appointments_table.sql` | _(superseded by dashboard 011)_ |
| 008 | _(app only)_ | `008_add_home_tel_to_patients.sql` | ALTER `patients` |
| 009 | _(app only)_ | `009_create_inquiries_table.sql` | `inquiries` |
| 010 | _(app only)_ | `010_create_email_log_table.sql` | `email_log` |
| — | `004_add_email_visit_reason_to_intakes.sql` | — | ALTER `intakes` |
| — | `005_add_password_hash_to_patients.sql` | — | ALTER `patients` |
| — | `006_add_sheets_sync_status_to_intakes.sql` | — | ALTER `intakes` |
| — | `007_add_birth_date_jalali_to_intakes.sql` | — | ALTER `intakes` |
| — | `008_create_invoices_table.sql` | — | `invoices` |
| — | `009_create_tasks_table.sql` | — | `tasks` |
| — | `010_create_clinics_add_cancel_reason.sql` | — | `clinics`, ALTER `appointments` |
| — | `011_create_appointments_table.sql` | — | `appointments` (authoritative) |
| — | `012_create_users_roles_permissions.sql` | — | `users`, `roles`, `permissions`, `role_user`, `permission_role` |
| — | `013_create_emr_records_templates.sql` | — | `emr_records`, `emr_templates` |

After all migrations, run seed: `database/seeds/001_seed_clinic_roles_permissions.sql`

---

## Tables

### `intakes`
Public intake submissions from `app.drbastaninejad.com`.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `submission_uuid` | CHAR(36) UNIQUE | Idempotency key |
| `clinic_id` | INT UNSIGNED | Default 1 |
| `patient_id` | INT UNSIGNED NULL | FK to `patients.id` — set after OTP verification |
| `first_name` | VARCHAR(100) | |
| `last_name` | VARCHAR(100) | |
| `father_name` | VARCHAR(100) NULL | |
| `mobile` | VARCHAR(15) | Normalised 09xxxxxxxxx |
| `national_id` | VARCHAR(10) | mod-11 validated |
| `birth_date` | DATE NULL | Gregorian |
| `birth_date_jalali` | VARCHAR(12) NULL | Raw Jalali string e.g. 1370/05/12 |
| `email` | VARCHAR(255) NULL | |
| `visit_reason` | VARCHAR(255) NULL | |
| `referral_source` | VARCHAR(100) NULL | |
| `status` | ENUM | `new`, `verified`, `triaged`, `scheduled`, `archived` |
| `sheets_sync_status` | ENUM | `pending`, `synced`, `failed` |
| `created_at` | DATETIME | UTC |

### `otp_codes`
One-time passwords for login and intake verification.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `mobile` | VARCHAR(15) | |
| `code` | VARCHAR(255) | bcrypt hash of 5-digit code |
| `purpose` | VARCHAR(20) | `login` |
| `expires_at` | DATETIME | UTC; 5-minute TTL |
| `used_at` | DATETIME NULL | Set on successful verify |
| `created_at` | DATETIME | UTC |

Rate limit: max 3 rows per mobile in any 10-minute window (checked in OtpService).

### `auth_tokens`
Bearer token sessions for both patients and staff.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED | FK to `patients.id` or `users.id` depending on `user_type` |
| `user_type` | ENUM | `patient`, `staff` |
| `token_hash` | CHAR(64) UNIQUE | SHA-256 hex of raw Bearer token |
| `expires_at` | DATETIME | UTC; 30-day rolling |
| `revoked_at` | DATETIME NULL | Set on logout |
| `created_at` | DATETIME | UTC |

### `patients`
The **one** patients table shared by both backends. Created by `app/migrations/003`.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `uuid` | VARCHAR(32) UNIQUE | bin2hex(random_bytes(16)) |
| `clinic_id` | INT UNSIGNED | Default 1 |
| `first_name` | VARCHAR(100) NULL | |
| `last_name` | VARCHAR(100) NULL | |
| `father_name` | VARCHAR(100) NULL | |
| `mobile` | VARCHAR(15) UNIQUE | Normalised 09xxxxxxxxx |
| `national_id` | VARCHAR(10) NULL | mod-11 |
| `email` | VARCHAR(255) NULL | |
| `birth_date` | DATE NULL | Gregorian |
| `birth_date_jalali` | VARCHAR(12) NULL | |
| `gender` | ENUM NULL | `male`, `female`, `other` |
| `home_address` | TEXT NULL | |
| `home_tel` | VARCHAR(20) NULL | Added in migration 008 (app) |
| `insurance_number` | VARCHAR(30) NULL | |
| `insurance_status` | ENUM | `active`, `expired`, `none`, `pending` |
| `password_hash` | VARCHAR(255) NULL | bcrypt; set after account activation |
| `marketing_email_optin` | TINYINT(1) | Default 0 |
| `deleted_at` | DATETIME NULL | Soft-delete |
| `created_at` / `updated_at` | DATETIME | UTC |

**Security:** Never return `password_hash` or `remember_token` in API responses.

### `clinics`
Created by dashboard migration 010.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `name` | VARCHAR(255) | |
| `phone` | VARCHAR(32) NULL | |
| `address` | TEXT NULL | |
| `timezone` | VARCHAR(64) | Default `Asia/Tehran` |
| `working_hours_json` | JSON NULL | |
| `created_at` / `updated_at` | DATETIME | UTC |

### `appointments`
Created by dashboard migration 011 (authoritative). App migration 007 is superseded.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `uuid` | CHAR(32) UNIQUE | bin2hex(random_bytes(16)) — **NOT NULL** |
| `clinic_id` | INT UNSIGNED | FK → `clinics.id` |
| `patient_id` | INT UNSIGNED | FK → `patients.id` |
| `provider_id` | INT UNSIGNED NULL | FK → `users.id` (treating doctor) |
| `scheduled_at` | DATETIME | UTC |
| `duration_minutes` | SMALLINT UNSIGNED | Default 20 |
| `visit_reason` | VARCHAR(255) NULL | |
| `room` | VARCHAR(64) NULL | |
| `status` | ENUM | `scheduled`, `confirmed`, `cancelled`, `completed` |
| `notes` | TEXT NULL | |
| `cancellation_reason` | TEXT NULL | |
| `deleted_at` | DATETIME NULL | Soft-delete — **all queries must filter `deleted_at IS NULL`** |
| `created_at` / `updated_at` | DATETIME | UTC |

### `invoices`
Created by dashboard migration 008.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `clinic_id` | INT UNSIGNED | |
| `patient_id` | INT UNSIGNED | FK → `patients.id` |
| `amount_rials` | DECIMAL(14,2) | |
| `status` | ENUM | `pending`, `paid`, `failed`, `insurance_pending` |
| `gateway` | VARCHAR(64) NULL | |
| `notes` | TEXT NULL | |
| `created_at` / `updated_at` | DATETIME | UTC |

### `tasks`
Created by dashboard migration 009.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `clinic_id` | INT UNSIGNED | |
| `title` | VARCHAR(255) | |
| `priority` | ENUM | `high`, `medium`, `low` |
| `status` | ENUM | `todo`, `in_progress`, `done` |
| `assignee_id` | INT UNSIGNED NULL | FK → `users.id` |
| `due_date` | DATE NULL | |
| `notes` | TEXT NULL | |
| `created_at` / `updated_at` | DATETIME | UTC |

### `users`
Staff accounts (doctors, receptionists, nurses). Created by dashboard migration 012.  
**NOT patients.** Do not create a separate patients table for the portal (UNIFIED_MASTER_PLAN §2).

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `uuid` | CHAR(36) UNIQUE | |
| `clinic_id` | INT UNSIGNED | Default 1 |
| `full_name` | VARCHAR(200) | |
| `mobile` | VARCHAR(15) UNIQUE | |
| `email` | VARCHAR(255) NULL | |
| `password_hash` | VARCHAR(255) NULL | bcrypt; NULL for OTP-only staff |
| `is_active` | TINYINT(1) | Default 1 |
| `deleted_at` | DATETIME NULL | |
| `created_at` / `updated_at` | DATETIME | UTC |

### `roles` / `permissions` / `role_user` / `permission_role`
RBAC tables. Created by dashboard migration 012.

**roles:** `id`, `name` (e.g. `super_admin`, `doctor`, `receptionist`, `nurse`), `label`, `created_at`

**permissions:** `id`, `name` (e.g. `patients.view`, `emr.edit`, `billing.manage`), `description`, `created_at`

**role_user:** `id`, `user_id` → users, `role_id` → roles, `granted_at`

**permission_role:** `id`, `role_id` → roles, `permission_id` → permissions

Named permissions used in route files:
`patients.view`, `patients.manage`, `appointments.view`, `appointments.manage`,
`intakes.view`, `intakes.manage`, `emr.view`, `emr.edit`,
`billing.view`, `billing.manage`, `tasks.view`, `tasks.manage`,
`analytics.view`, `settings.view`, `settings.manage`, `dashboard.view`

### `emr_records`
Clinical EMR notes. Created by dashboard migration 013.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `uuid` | CHAR(32) UNIQUE | |
| `clinic_id` | INT UNSIGNED | FK → `clinics.id` |
| `patient_id` | INT UNSIGNED | FK → `patients.id` |
| `appointment_id` | INT UNSIGNED NULL | FK → `appointments.id` |
| `author_id` | INT UNSIGNED NULL | FK → `users.id` |
| `template_id` | INT UNSIGNED NULL | FK → `emr_templates.id` |
| `chief_complaint` | TEXT | **Required** |
| `diagnosis` | TEXT NULL | |
| `plan` | TEXT NULL | |
| `specialty_fields` | JSON NULL | Specialty-specific structured fields |
| `ai_draft` | TEXT NULL | Last AI suggestion — **never auto-saved** |
| `ai_accepted` | TINYINT(1) | 0 = not accepted; 1 = staff explicitly accepted |
| `deleted_at` | DATETIME NULL | |
| `created_at` / `updated_at` | DATETIME | UTC |

**AI rule:** AI-drafted content is stored only in `ai_draft`. It must never be copied to `chief_complaint`/`diagnosis`/`plan` without explicit staff acceptance.

### `emr_templates`
Schema-driven EMR form definitions per specialty. Created by dashboard migration 013.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `clinic_id` | INT UNSIGNED NULL | NULL = platform-wide default |
| `specialty` | VARCHAR(64) | e.g. `general`, `dermatology`, `orthopedics` |
| `name` | VARCHAR(200) | |
| `schema_json` | JSON | Array of field definition objects for the dynamic form |
| `is_active` | TINYINT(1) | Default 1 |
| `created_at` / `updated_at` | DATETIME | UTC |

### `patient_media`
Patient document uploads. Created by app migration 005.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `patient_id` | INT UNSIGNED | FK → `patients.id` |
| `media_uuid` | VARCHAR(36) UNIQUE | Safe to expose; not the storage path |
| `file_name` | VARCHAR(255) | |
| `mime_type` | VARCHAR(100) | |
| `size_bytes` | INT UNSIGNED | |
| `storage_path` | TEXT | **NEVER returned in API responses** |
| `deleted_at` | DATETIME NULL | |
| `created_at` | DATETIME | UTC |

### `notification_preferences`
Patient notification opt-ins. Created by app migration 006.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `patient_id` | INT UNSIGNED UNIQUE | FK → `patients.id` |
| `sms_appointment_reminder` | TINYINT(1) | Default 1 |
| `sms_status_change` | TINYINT(1) | Default 1 |
| `email_appointment_reminder` | TINYINT(1) | Default 0 |
| `email_marketing` | TINYINT(1) | Default 0 |
| `updated_at` | DATETIME | UTC |

### `inquiries`
Contact form submissions. Created by app migration 009.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `name` | VARCHAR(200) | |
| `mobile` | VARCHAR(15) NULL | |
| `email` | VARCHAR(255) NULL | |
| `subject` | VARCHAR(255) NULL | |
| `message` | TEXT | |
| `status` | ENUM | `new`, `read`, `replied` |
| `created_at` | DATETIME | UTC |

### `email_log`
Outbound email delivery audit trail. Created by app migration 010.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `to_email` | VARCHAR(255) | |
| `subject` | VARCHAR(500) | |
| `body_html` | MEDIUMTEXT NULL | |
| `status` | ENUM | `queued`, `sent`, `failed` |
| `sent_at` | DATETIME NULL | UTC |
| `created_at` | DATETIME | UTC |

---

## Derived Views

### `DashboardTimelineEvent` (application-level shape, not a DB view)

```json
{
  "id": "intake-42",
  "type": "intake|appointment",
  "timestamp": "2026-07-30T10:00:00",
  "patient": { "id": 1, "name": "علی احمدی", "href": "/patients/1" },
  "title": "پذیرش جدید دریافت شد",
  "description": "از طریق وب‌سایت ارسال شده.",
  "status": { "label": "جدید", "badge": "info" },
  "actors": [{ "type": "system", "name": "فرم وب" }],
  "href": "/intakes/42"
}
```

### `PatientPortalTimelineEntry` (application-level, `GET /patient/records`)

```json
{
  "id": 7,
  "chief_complaint": "سردرد مزمن",
  "diagnosis": "میگرن",
  "plan": "استراحت و مصرف دارو",
  "ai_accepted": 0,
  "created_at": "2026-07-28T09:15:00"
}
```

---

## Naming Conventions

| Rule | Example |
|---|---|
| All column names: `snake_case` | `first_name`, `scheduled_at` |
| All timestamps: UTC, DATETIME | `created_at`, `expires_at` |
| Soft-delete: always `deleted_at` | All queries filter `deleted_at IS NULL` |
| FK columns: `{table_singular}_id` | `patient_id`, `clinic_id` |
| UUID/token columns: never storage path | `uuid`, `media_uuid` — safe to expose |
| Monetary amounts: Rials, DECIMAL(14,2) | `amount_rials` |
| Boolean columns: TINYINT(1) | `is_active`, `ai_accepted` |
| JSON blobs: JSON type | `specialty_fields`, `working_hours_json` |
