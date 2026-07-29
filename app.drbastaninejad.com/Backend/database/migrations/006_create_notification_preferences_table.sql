-- Migration 006 — Create notification_preferences table
-- app.drbastaninejad.com / MΛZ Medical CRM
--
-- One row per patient_id. Missing row = all defaults (see defaults in
-- NotificationPreferenceModel::DEFAULTS and docs/API_CONTRACT.md).
--
-- Upsert design: INSERT … ON DUPLICATE KEY UPDATE (no explicit insert guard needed).
-- All columns default to the contract-defined values so an empty insert is valid.

CREATE TABLE IF NOT EXISTS notification_preferences (
    id                           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id                   INT UNSIGNED NOT NULL UNIQUE,
    sms_appointment_reminder     TINYINT(1)   NOT NULL DEFAULT 1,
    sms_status_change            TINYINT(1)   NOT NULL DEFAULT 1,
    email_appointment_reminder   TINYINT(1)   NOT NULL DEFAULT 0,
    email_marketing              TINYINT(1)   NOT NULL DEFAULT 0,
    updated_at                   DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_np_patient
        FOREIGN KEY (patient_id) REFERENCES patients (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
