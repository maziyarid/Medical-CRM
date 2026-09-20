-- Migration 031: appointment booking blacklist.
-- Idempotent; runtime service also ensures the table for file-only deployments.

CREATE TABLE IF NOT EXISTS booking_blacklist (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id         INT UNSIGNED NOT NULL,
    patient_id        INT UNSIGNED NULL,
    identifier_type   VARCHAR(20) NOT NULL,
    identifier_value  VARCHAR(32) NOT NULL,
    reason            VARCHAR(500) NULL,
    created_by        INT UNSIGNED NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_by        INT UNSIGNED NULL,
    deleted_at        DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_booking_blacklist_identifier (clinic_id, identifier_type, identifier_value),
    KEY idx_booking_blacklist_patient (clinic_id, patient_id, deleted_at),
    KEY idx_booking_blacklist_active (clinic_id, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
