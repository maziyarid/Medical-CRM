<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# MySQL Schema — MΛZ Medical CRM

**Version:** 1.0 · **Author:** MAZ//ID · **Date:** 25 July 2026
**Engine:** MySQL 8.x · `InnoDB` · `utf8mb4_unicode_ci`

> This document is the **long-term target schema** (22 tables, full normalized
> EMR, multi-tenant). It is **not** the operational schema currently running.
>
> **Operational shape:** the actual live table DDL is in
> `dashboard.drbastaninejad.com/database/migrations/001–007_*.sql`.
> Those migration files are the current source of truth for what MySQL actually
> contains. The `intakes` table in migration 001, for example, uses
> `submission_uuid`, a flat denormalised shape, and a different `status` enum
> than this document — this is intentional (see `UNIFIED_MASTER_PLAN.md §6.4`).
>
> **Do not** write PHP code against this document's DDL until the full
> migration from migrations → this schema has been planned and executed.
> All current PHP (`app/Controllers`, `app/Models`) targets the migrations
> shape, not this document.

---

## 1. Design principles

| Principle | Decision |
|---|---|
| Charset | `utf8mb4` / `utf8mb4_unicode_ci` (full Persian + emoji) |
| Engine  | `InnoDB` (FK constraints, transactions) |
| Time    | **All timestamps UTC** (`TIMESTAMP`/`DATETIME`); Jalali only at presentation |
| Deletes | Soft delete (`deleted_at`) on every clinical table |
| Audit   | Global `audit_logs` + `created_by`/`updated_by` on key tables |
| IDs     | `BIGINT UNSIGNED AUTO_INCREMENT` primary keys; `uuid CHAR(36)` for public/external references |
| Multi-tenant | `clinic_id` foreign key on core tables from day 1 |
| EMR flexibility | Hybrid — fixed columns + `JSON` for template answers + EAV mirror for searchable fields |

---

## 2. Frozen intake column → normalized mapping

The historical Google Sheet has 19 columns. They map into three normalized tables (`patients`, `patient_contacts`, `intakes`):

| Sheet column | New location | Notes |
|---|---|---|
| FirstName          | `patients.first_name` | |
| LastName           | `patients.last_name`  | |
| FatherName         | `patients.father_name` | |
| TavalodDay/Month/Year | `patients.birth_date` (DATE) | Jalali → Gregorian on write |
| HomeTel            | `patient_contacts` (type=home_tel) | |
| Mobile             | `patients.mobile` + `patient_contacts` | Primary, EN-normalised |
| Mobile2            | `patient_contacts` (type=mobile2) | |
| CodeAshnaei        | `intakes.referral_code` | |
| CodeBimeh          | `patients.insurance_number` | |
| CodeMeli           | `patients.national_id` | mod-11 validated |
| CodeJob            | `patients.job_code` | |
| HomeAd             | `patients.home_address` | |
| Description        | `intakes.description` | |
| IsTransfer         | `intakes.is_transfer` | |
| drugs              | `intakes.current_drugs` | |
| difficult          | `intakes.difficulties` | |
| morefmob           | `patient_contacts` (type=more_mobile) | |

---

## 3. Core DDL

### 3.1 Foundational / multi-tenant

```sql
CREATE TABLE clinics (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid        CHAR(36) NOT NULL,
    name        VARCHAR(191) NOT NULL,
    slug        VARCHAR(191) NOT NULL,
    timezone    VARCHAR(64)  NOT NULL DEFAULT 'Asia/Tehran',
    logo_path   VARCHAR(255) NULL,
    settings    JSON NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_clinics_uuid (uuid),
    UNIQUE KEY uq_clinics_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 Users & RBAC

```sql
CREATE TABLE users (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid          CHAR(36) NOT NULL,
    clinic_id     BIGINT UNSIGNED NOT NULL,
    full_name     VARCHAR(191) NOT NULL,
    mobile        VARCHAR(15)  NOT NULL,          -- EN digits, e.g. 09121234567
    email         VARCHAR(191) NULL,
    password_hash VARCHAR(255) NULL,              -- argon2id
    is_active     BOOLEAN NOT NULL DEFAULT 1,
    last_login_at TIMESTAMP NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_uuid (uuid),
    UNIQUE KEY uq_users_mobile (mobile),
    KEY idx_users_clinic (clinic_id),
    CONSTRAINT fk_users_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name     VARCHAR(64) NOT NULL,                 -- super_admin, doctor, receptionist, nurse, patient
    label_fa VARCHAR(64) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name   VARCHAR(96) NOT NULL,                   -- patients.view, emr.edit, billing.manage
    module VARCHAR(64) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_perm_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_user (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_ru_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ru_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permission_role (
    role_id       BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_pr_role FOREIGN KEY (role_id)       REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_pr_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.3 Patients (normalized frozen columns)

```sql
CREATE TABLE patients (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid              CHAR(36) NOT NULL,
    clinic_id         BIGINT UNSIGNED NOT NULL,

    first_name        VARCHAR(96)  NOT NULL,
    last_name         VARCHAR(96)  NOT NULL,
    father_name       VARCHAR(96)  NULL,
    birth_date        DATE NULL,
    birth_date_jalali VARCHAR(10) NULL,           -- optional display cache "1370/05/12"
    mobile            VARCHAR(15) NOT NULL,        -- EN normalised
    national_id       CHAR(10) NULL,               -- mod-11 validated
    insurance_number  VARCHAR(32) NULL,
    job_code          VARCHAR(32) NULL,
    home_address      TEXT NULL,

    gender            ENUM('male','female','other') NULL,
    insurance_status  ENUM('none','pending','verified','rejected')
                          NOT NULL DEFAULT 'none',
    notes             TEXT NULL,

    created_by        BIGINT UNSIGNED NULL,
    updated_by        BIGINT UNSIGNED NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        TIMESTAMP NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_patients_uuid (uuid),
    KEY idx_patients_clinic   (clinic_id),
    KEY idx_patients_mobile   (mobile),
    KEY idx_patients_national (national_id),
    KEY idx_patients_name     (last_name, first_name),
    CONSTRAINT fk_patients_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_contacts (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_id BIGINT UNSIGNED NOT NULL,
    type       ENUM('home_tel','mobile','mobile2','more_mobile','other') NOT NULL,
    value      VARCHAR(32) NOT NULL,
    label      VARCHAR(64) NULL,
    is_primary BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pc_patient (patient_id),
    CONSTRAINT fk_pc_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.4 Intakes

```sql
CREATE TABLE intakes (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid              CHAR(36) NOT NULL,
    clinic_id         BIGINT UNSIGNED NOT NULL,
    patient_id        BIGINT UNSIGNED NULL,           -- linked after matching/creating

    referral_code     VARCHAR(64) NULL,
    description       TEXT NULL,
    is_transfer       BOOLEAN NOT NULL DEFAULT 0,
    current_drugs     TEXT NULL,
    difficulties      TEXT NULL,

    referral_source   VARCHAR(64) NULL,               -- instagram, google, chat, banner…
    utm               JSON NULL,                       -- {source,medium,campaign}

    status            ENUM('new','verified','triaged','scheduled','archived')
                          NOT NULL DEFAULT 'new',
    otp_verified_at   TIMESTAMP NULL,
    consent_signed_at TIMESTAMP NULL,
    consent_path      VARCHAR(255) NULL,               -- signature image on S3

    raw_payload       JSON NULL,                       -- full original submission (safety net)
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        TIMESTAMP NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_intakes_uuid (uuid),
    KEY idx_intakes_patient (patient_id),
    KEY idx_intakes_status  (status),
    KEY idx_intakes_source  (referral_source),
    CONSTRAINT fk_intakes_clinic  FOREIGN KEY (clinic_id)  REFERENCES clinics(id),
    CONSTRAINT fk_intakes_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.5 Scheduling

```sql
CREATE TABLE specialties (
    id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name     VARCHAR(96) NOT NULL,
    label_fa VARCHAR(96) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_spec_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE providers (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      BIGINT UNSIGNED NOT NULL,
    specialty_id BIGINT UNSIGNED NOT NULL,
    medical_no   VARCHAR(32) NULL,       -- nezam-pezeshki number
    color        VARCHAR(7)  NULL,        -- calendar color
    PRIMARY KEY (id),
    KEY idx_prov_user (user_id),
    CONSTRAINT fk_prov_user FOREIGN KEY (user_id)      REFERENCES users(id),
    CONSTRAINT fk_prov_spec FOREIGN KEY (specialty_id) REFERENCES specialties(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rooms (
    id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    name      VARCHAR(96) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_rooms_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointments (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid             CHAR(36) NOT NULL,
    clinic_id        BIGINT UNSIGNED NOT NULL,
    patient_id       BIGINT UNSIGNED NOT NULL,
    provider_id      BIGINT UNSIGNED NOT NULL,
    room_id          BIGINT UNSIGNED NULL,

    starts_at        DATETIME NOT NULL,           -- UTC
    ends_at          DATETIME NOT NULL,           -- UTC
    status           ENUM('booked','confirmed','arrived','in_progress',
                         'completed','no_show','cancelled')
                         NOT NULL DEFAULT 'booked',
    reason           VARCHAR(255) NULL,
    reminder_sent_at TIMESTAMP NULL,

    created_by       BIGINT UNSIGNED NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at       TIMESTAMP NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_appt_uuid (uuid),
    KEY idx_appt_provider_time (provider_id, starts_at),
    KEY idx_appt_patient (patient_id),
    KEY idx_appt_time    (starts_at, ends_at),
    CONSTRAINT fk_appt_clinic   FOREIGN KEY (clinic_id)   REFERENCES clinics(id),
    CONSTRAINT fk_appt_patient  FOREIGN KEY (patient_id)  REFERENCES patients(id),
    CONSTRAINT fk_appt_provider FOREIGN KEY (provider_id) REFERENCES providers(id),
    CONSTRAINT fk_appt_room     FOREIGN KEY (room_id)     REFERENCES rooms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 4. Dynamic EMR — Hybrid EAV + JSON

Three-layer design:

1. **`emr_templates`** — versioned form definitions per specialty.
2. **`emr_records`** — one clinical encounter; `data_json` is the source of truth.
3. **`emr_field_values`** — EAV mirror of *searchable/reportable* fields only.

Why hybrid? JSON gives fast full-form read/write; the EAV mirror gives indexed queryability ("all diabetic patients") without the classic EAV performance trap.

```sql
CREATE TABLE emr_templates (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id     BIGINT UNSIGNED NOT NULL,
    specialty_id  BIGINT UNSIGNED NOT NULL,
    name          VARCHAR(191) NOT NULL,          -- "Rhinoplasty Pre-Op"
    version       INT UNSIGNED NOT NULL DEFAULT 1,
    schema_json   JSON NOT NULL,                   -- field defs
    is_active     BOOLEAN NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tpl_spec (specialty_id),
    CONSTRAINT fk_tpl_clinic FOREIGN KEY (clinic_id)    REFERENCES clinics(id),
    CONSTRAINT fk_tpl_spec   FOREIGN KEY (specialty_id) REFERENCES specialties(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE emr_records (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid              CHAR(36) NOT NULL,
    clinic_id         BIGINT UNSIGNED NOT NULL,
    patient_id        BIGINT UNSIGNED NOT NULL,
    provider_id       BIGINT UNSIGNED NOT NULL,
    appointment_id    BIGINT UNSIGNED NULL,
    template_id       BIGINT UNSIGNED NOT NULL,
    template_version  INT UNSIGNED    NOT NULL,

    chief_complaint   TEXT NULL,
    diagnosis         TEXT NULL,
    plan              TEXT NULL,

    data_json         JSON NOT NULL,               -- specialty-specific answers
    ai_summary        TEXT NULL,                   -- Copilot brief
    ai_model_used     VARCHAR(96) NULL,

    signed_at         TIMESTAMP NULL,
    created_by        BIGINT UNSIGNED NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        TIMESTAMP NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_emr_uuid (uuid),
    KEY idx_emr_patient  (patient_id),
    KEY idx_emr_provider (provider_id),
    CONSTRAINT fk_emr_clinic   FOREIGN KEY (clinic_id)      REFERENCES clinics(id),
    CONSTRAINT fk_emr_patient  FOREIGN KEY (patient_id)     REFERENCES patients(id),
    CONSTRAINT fk_emr_provider FOREIGN KEY (provider_id)    REFERENCES providers(id),
    CONSTRAINT fk_emr_appt     FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    CONSTRAINT fk_emr_tpl      FOREIGN KEY (template_id)    REFERENCES emr_templates(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE emr_field_values (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    emr_record_id BIGINT UNSIGNED NOT NULL,
    patient_id    BIGINT UNSIGNED NOT NULL,        -- denormalised for fast patient-wide queries
    field_key     VARCHAR(96) NOT NULL,            -- e.g. "is_diabetic", "smoker"
    value_string  VARCHAR(255) NULL,
    value_number  DECIMAL(15,4) NULL,
    value_bool    BOOLEAN NULL,
    value_date    DATE NULL,
    PRIMARY KEY (id),
    KEY idx_eav_record      (emr_record_id),
    KEY idx_eav_search      (field_key, value_string),
    KEY idx_eav_patient_key (patient_id, field_key),
    CONSTRAINT fk_eav_record FOREIGN KEY (emr_record_id) REFERENCES emr_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Example `schema_json`** (Rhinoplasty pre-op):
```json
{
  "sections": [{
    "title": "پیش از عمل",
    "fields": [
      { "key":"smoker",           "type":"boolean",     "label":"سیگاری",              "searchable": true },
      { "key":"prev_surgery",     "type":"boolean",     "label":"جراحی قبلی بینی" },
      { "key":"contraindications","type":"multiselect", "label":"منع‌های جراحی",
        "options":["دیابت کنترل‌نشده","اختلال انعقادی","بارداری"] },
      { "key":"preop_photos",     "type":"media_grid",  "label":"تصاویر قبل از عمل" }
    ]
  }]
}
```

---

## 5. Media, Billing, Tasks, AI, Audit, OTP

```sql
CREATE TABLE media (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid          CHAR(36) NOT NULL,
    clinic_id     BIGINT UNSIGNED NOT NULL,
    patient_id    BIGINT UNSIGNED NULL,
    emr_record_id BIGINT UNSIGNED NULL,
    type          ENUM('xray','photo','lab','pdf','signature','other') NOT NULL,
    tag           VARCHAR(64) NULL,              -- "before","after","panoramic"
    storage_disk  VARCHAR(32) NOT NULL DEFAULT 'arvan',
    object_key    VARCHAR(512) NOT NULL,          -- S3 key
    mime_type     VARCHAR(96) NULL,
    size_bytes    BIGINT UNSIGNED NULL,
    created_by    BIGINT UNSIGNED NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at    TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_media_uuid (uuid),
    KEY idx_media_patient (patient_id),
    KEY idx_media_emr     (emr_record_id),
    CONSTRAINT fk_media_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoices (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid              CHAR(36) NOT NULL,
    clinic_id         BIGINT UNSIGNED NOT NULL,
    patient_id        BIGINT UNSIGNED NOT NULL,
    appointment_id    BIGINT UNSIGNED NULL,
    number            VARCHAR(32) NOT NULL,
    total_amount      BIGINT UNSIGNED NOT NULL,          -- Rials (integer)
    discount          BIGINT UNSIGNED NOT NULL DEFAULT 0,
    insurance_covered BIGINT UNSIGNED NOT NULL DEFAULT 0,
    payable           BIGINT UNSIGNED NOT NULL,
    status            ENUM('draft','issued','paid','partial','void') NOT NULL DEFAULT 'draft',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_inv_uuid   (uuid),
    UNIQUE KEY uq_inv_number (clinic_id, number),
    KEY idx_inv_patient (patient_id),
    CONSTRAINT fk_inv_clinic  FOREIGN KEY (clinic_id)  REFERENCES clinics(id),
    CONSTRAINT fk_inv_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoice_items (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_id BIGINT UNSIGNED NOT NULL,
    title      VARCHAR(191) NOT NULL,
    qty        INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price BIGINT UNSIGNED NOT NULL,
    line_total BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_ii_invoice (invoice_id),
    CONSTRAINT fk_ii_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_id BIGINT UNSIGNED NOT NULL,
    gateway    ENUM('zarinpal','idpay','cash','card','other') NOT NULL,
    amount     BIGINT UNSIGNED NOT NULL,
    ref_id     VARCHAR(96) NULL,
    status     ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
    paid_at    TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pay_invoice (invoice_id),
    CONSTRAINT fk_pay_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tasks (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id   BIGINT UNSIGNED NOT NULL,
    title       VARCHAR(191) NOT NULL,
    description TEXT NULL,
    assignee_id BIGINT UNSIGNED NULL,
    patient_id  BIGINT UNSIGNED NULL,
    status      ENUM('todo','doing','done','cancelled') NOT NULL DEFAULT 'todo',
    priority    ENUM('low','normal','high','urgent')    NOT NULL DEFAULT 'normal',
    due_at      DATETIME NULL,
    created_by  BIGINT UNSIGNED NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tasks_assignee      (assignee_id),
    KEY idx_tasks_clinic_status (clinic_id, status),
    CONSTRAINT fk_tasks_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_interactions (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id         BIGINT UNSIGNED NOT NULL,
    user_id           BIGINT UNSIGNED NULL,
    context_type      VARCHAR(64) NOT NULL,           -- intake.parse, emr.draft, ...
    context_id        BIGINT UNSIGNED NULL,
    model_used        VARCHAR(96) NOT NULL,           -- openrouter model slug
    tier              ENUM('small','mid','premium') NOT NULL,
    prompt_tokens     INT UNSIGNED NULL,
    completion_tokens INT UNSIGNED NULL,
    cost_usd          DECIMAL(10,6) NULL,
    latency_ms        INT UNSIGNED NULL,
    was_fallback      BOOLEAN NOT NULL DEFAULT 0,
    approved_by       BIGINT UNSIGNED NULL,           -- human-in-the-loop
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ai_context     (context_type, context_id),
    KEY idx_ai_clinic_time (clinic_id, created_at),
    CONSTRAINT fk_ai_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE otp_codes (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mobile      VARCHAR(15) NOT NULL,
    code_hash   VARCHAR(255) NOT NULL,       -- never store plaintext
    purpose     ENUM('intake','login','verify') NOT NULL,
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at  TIMESTAMP NOT NULL,
    consumed_at TIMESTAMP NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_otp_mobile (mobile, purpose)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NULL,
    action      VARCHAR(64) NOT NULL,            -- created, updated, deleted, viewed
    entity_type VARCHAR(64) NOT NULL,            -- patient, emr_record...
    entity_id   BIGINT UNSIGNED NULL,
    changes     JSON NULL,                        -- before/after diff
    ip_address  VARCHAR(45) NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_entity (entity_type, entity_id),
    KEY idx_audit_user   (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 6. Migration staging table (Google Sheet import)

```sql
CREATE TABLE _import_smartformat (
    row_id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    FirstName    VARCHAR(191), LastName VARCHAR(191), FatherName VARCHAR(191),
    TavalodDay   VARCHAR(8),  TavalodMonth VARCHAR(8), TavalodYear VARCHAR(8),
    HomeTel      VARCHAR(32), Mobile VARCHAR(32),      Mobile2 VARCHAR(32),
    CodeAshnaei  VARCHAR(64), CodeBimeh VARCHAR(64),   CodeMeli VARCHAR(16),
    CodeJob      VARCHAR(64), HomeAd TEXT,             Description TEXT,
    IsTransfer   VARCHAR(8),  drugs TEXT, difficult TEXT, morefmob VARCHAR(32),
    imported     BOOLEAN DEFAULT 0,
    PRIMARY KEY (row_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Migration steps** (implemented as `php artisan intakes:import-sheet`):

1. Pull all rows from Google Sheet → `_import_smartformat`.
2. Per row: normalise digits (fa/ar → en), validate `CodeMeli` (mod-11), convert Jalali birth date → Gregorian.
3. Upsert `patients` (match on `mobile` or `national_id`).
4. Insert `patient_contacts` for `HomeTel`, `Mobile2`, `morefmob`.
5. Insert `intakes` with `raw_payload = full original row` (safety net).
6. Mark `imported = 1`. Idempotent — re-runnable without duplicates.

---

## 7. Seed data

```sql
INSERT INTO roles (name, label_fa) VALUES
  ('super_admin','مدیر کل'),
  ('doctor','پزشک'),
  ('receptionist','منشی'),
  ('nurse','پرستار'),
  ('patient','بیمار');

INSERT INTO specialties (name, label_fa) VALUES
  ('rhinoplasty','جراحی بینی'),
  ('dentistry','دندانپزشکی'),
  ('dermatology','پوست'),
  ('ophthalmology','چشم'),
  ('orthopedics','ارتوپدی'),
  ('general_surgery','جراحی عمومی'),
  ('cosmetology','زیبایی');
```

---

## 📋 Schema summary

| Group | Tables |
|---|---|
| Tenant/Auth       | `clinics`, `users`, `roles`, `permissions`, `role_user`, `permission_role` |
| Patients          | `patients`, `patient_contacts`, `intakes` |
| Scheduling        | `specialties`, `providers`, `rooms`, `appointments` |
| EMR               | `emr_templates`, `emr_records`, `emr_field_values` |
| Media/Billing     | `media`, `invoices`, `invoice_items`, `payments` |
| Ops/AI            | `tasks`, `ai_interactions`, `otp_codes`, `audit_logs` |
| Migration         | `_import_smartformat` |

**Total: 22 tables** — covers the entire MVP + growth path, with `ai_interactions` already wired to feed the AI Copilot cost dashboard.

---

_M•Z · MAZ//ID · © 2026 Dr. Shahin Bastaninejad_
