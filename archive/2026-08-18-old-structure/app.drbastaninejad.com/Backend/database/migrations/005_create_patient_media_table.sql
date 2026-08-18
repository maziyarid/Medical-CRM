-- Migration 005 — Create patient_media table
-- app.drbastaninejad.com / MΛZ Medical CRM
--
-- Stores metadata for files uploaded by or on behalf of a patient
-- (pre-op photos, consent forms, referral letters, etc.).
--
-- SECURITY RULES:
--   1. storage_path MUST NOT be returned to the client directly.
--      PatientMediaModel generates a short-lived signed URL instead.
--   2. storage_path is outside public web root (storage/private/).
--   3. This table is read-only from the patient portal; uploads go through
--      a staff-facing endpoint only (Phase 6 scope).
--
-- charset: utf8mb4 / InnoDB / UTC timestamps throughout.

CREATE TABLE IF NOT EXISTS patient_media (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    media_uuid      VARCHAR(36)  NOT NULL UNIQUE COMMENT 'UUID v4, safe to expose to client',
    patient_id      INT UNSIGNED NOT NULL,
    intake_id       INT UNSIGNED NULL COMMENT 'NULL if uploaded outside an intake',
    file_name       VARCHAR(255) NOT NULL COMMENT 'Original file name, sanitised on upload',
    mime_type       VARCHAR(100) NOT NULL,
    size_bytes      INT UNSIGNED NOT NULL DEFAULT 0,
    storage_path    VARCHAR(500) NOT NULL COMMENT 'Server path — NEVER returned to client',
    uploaded_by     INT UNSIGNED NULL COMMENT 'staff user_id who uploaded; NULL = patient self-upload (future)',
    deleted_at      DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT (UTC_TIMESTAMP()),

    INDEX idx_patient_id (patient_id),
    INDEX idx_intake_id  (intake_id),

    CONSTRAINT fk_pm_patient
        FOREIGN KEY (patient_id) REFERENCES patients (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_pm_intake
        FOREIGN KEY (intake_id)  REFERENCES intakes (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
