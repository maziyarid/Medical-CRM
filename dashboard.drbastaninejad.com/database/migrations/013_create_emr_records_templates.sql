-- Migration 013: emr_records and emr_templates tables
-- dashboard.drbastaninejad.com
--
-- emr_records  — clinical notes attached to a patient + optional appointment.
--                Used by EmrController and EmrRecord model.
-- emr_templates — schema-driven form definitions for specialty specialties.
--                Used by EmrController::templates() and SettingsController::show().
--
-- Column notes:
--   chief_complaint   — required free-text; displayed in timeline header
--   diagnosis         — ICD-10 compatible free text (optional)
--   plan              — treatment plan free text (optional)
--   specialty_fields  — JSON blob for specialty-specific structured fields
--   ai_draft          — last AI-suggested draft; never auto-saved to canonical fields
--   ai_accepted       — 0/1: whether staff explicitly accepted the AI draft
--   deleted_at        — soft-delete; all read queries filter deleted_at IS NULL

-- ── emr_records ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS emr_records (
    id               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    uuid             CHAR(32)        NOT NULL,
    clinic_id        INT UNSIGNED    NOT NULL,
    patient_id       INT UNSIGNED    NOT NULL,
    appointment_id   INT UNSIGNED    NULL,
    author_id        INT UNSIGNED    NULL COMMENT 'FK to users.id (staff)',
    template_id      INT UNSIGNED    NULL COMMENT 'FK to emr_templates.id',
    chief_complaint  TEXT            NOT NULL,
    diagnosis        TEXT            NULL,
    plan             TEXT            NULL,
    specialty_fields JSON            NULL,
    ai_draft         TEXT            NULL,
    ai_accepted      TINYINT(1)      NOT NULL DEFAULT 0,
    deleted_at       DATETIME        NULL,
    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                         ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_emr_uuid           (uuid),
    INDEX      idx_emr_patient       (patient_id),
    INDEX      idx_emr_clinic        (clinic_id),
    INDEX      idx_emr_appointment   (appointment_id),
    INDEX      idx_emr_deleted       (deleted_at),

    CONSTRAINT fk_emr_clinic
        FOREIGN KEY (clinic_id)   REFERENCES clinics(id)   ON DELETE CASCADE,
    CONSTRAINT fk_emr_patient
        FOREIGN KEY (patient_id)  REFERENCES patients(id)  ON DELETE CASCADE,
    CONSTRAINT fk_emr_appointment
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Clinical EMR notes. AI drafts stored separately; never auto-saved.';

-- ── emr_templates ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS emr_templates (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinic_id   INT UNSIGNED    NULL COMMENT 'NULL = platform-wide default',
    specialty   VARCHAR(64)     NOT NULL DEFAULT 'general'
                    COMMENT 'e.g. general, dermatology, orthopedics',
    name        VARCHAR(200)    NOT NULL,
    schema_json JSON            NOT NULL COMMENT 'Array of field definitions for dynamic form',
    is_active   TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_emr_tpl_specialty  (specialty),
    INDEX idx_emr_tpl_clinic     (clinic_id),
    INDEX idx_emr_tpl_active     (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Schema-driven EMR form templates per specialty.';
