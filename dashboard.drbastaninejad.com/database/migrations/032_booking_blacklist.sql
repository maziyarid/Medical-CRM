-- Migration 032: appointment-booking blacklist.
-- Store only a one-way identifier hash plus the last four characters for staff display.
-- Schema creation is deployment-owned; runtime requests must never execute DDL.

CREATE TABLE IF NOT EXISTS booking_blacklist (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id         BIGINT UNSIGNED NOT NULL,
    patient_id        BIGINT UNSIGNED NULL,
    identifier_type   VARCHAR(20) NOT NULL DEFAULT 'national_id',
    identifier_hash   CHAR(64) NOT NULL,
    identifier_last4  VARCHAR(4) NOT NULL,
    patient_name      VARCHAR(200) NOT NULL DEFAULT '',
    reason            VARCHAR(500) NOT NULL DEFAULT '',
    source            VARCHAR(30) NOT NULL DEFAULT 'manual',
    created_by        BIGINT UNSIGNED NULL,
    created_at        DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_booking_blacklist_identifier (clinic_id, identifier_hash),
    KEY idx_booking_blacklist_patient (clinic_id, patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;