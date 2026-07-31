-- Migration 008: invoices table
-- dashboard.drbastaninejad.com
-- Stores billing invoices for clinic patients.

CREATE TABLE IF NOT EXISTS invoices (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id    INT UNSIGNED NOT NULL,
    patient_id   INT UNSIGNED NOT NULL,
    amount_rials DECIMAL(14,2) NOT NULL DEFAULT 0,
    status       ENUM('pending','paid','failed','insurance_pending') NOT NULL DEFAULT 'pending',
    gateway      VARCHAR(64)  NULL,
    notes        TEXT         NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_invoices_clinic    (clinic_id),
    KEY idx_invoices_patient   (patient_id),
    KEY idx_invoices_status    (status),
    KEY idx_invoices_created   (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
