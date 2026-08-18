-- Migration 007 — Create appointments table (patient portal read-only view)
-- app.drbastaninejad.com / MΛZ Medical CRM
--
-- This table stores appointment records created by staff via
-- dashboard.drbastaninejad.com. The patient portal reads from it (read-only).
--
-- IMPORTANT: Both subdomains share ONE database. Staff write via the
-- dashboard backend; patients read via the app backend.
--
-- date_jalali is stored as a denormalised VARCHAR so the frontend can display
-- it without a conversion round-trip (UTC scheduled_at is the authoritative
-- timestamp for ordering/comparison).
--
-- Status enum: confirmed | scheduled | cancelled | completed

CREATE TABLE IF NOT EXISTS appointments (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id       INT UNSIGNED NOT NULL,
    clinic_id        INT UNSIGNED NOT NULL DEFAULT 1,
    scheduled_at     DATETIME     NOT NULL COMMENT 'UTC timestamp for ordering/filtering',
    date_jalali      VARCHAR(12)  NOT NULL COMMENT 'e.g. 1405/05/14 — display use only',
    appointment_time VARCHAR(5)   NOT NULL COMMENT 'HH:MM — display use only',
    reason           VARCHAR(500) NULL,
    status           ENUM('confirmed','scheduled','cancelled','completed')
                     NOT NULL DEFAULT 'scheduled',
    staff_notes      TEXT         NULL COMMENT 'Internal staff notes — NOT returned to patient',
    created_at       DATETIME     NOT NULL DEFAULT (UTC_TIMESTAMP()),
    updated_at       DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_patient_id  (patient_id),
    INDEX idx_scheduled   (scheduled_at),
    INDEX idx_status      (status),

    CONSTRAINT fk_appt_patient
        FOREIGN KEY (patient_id) REFERENCES patients (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
