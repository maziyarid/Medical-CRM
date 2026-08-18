-- Migration 010: clinics table
-- dashboard.drbastaninejad.com
-- Clinic profile used by SettingsController.

CREATE TABLE IF NOT EXISTS clinics (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name                 VARCHAR(255) NOT NULL,
    phone                VARCHAR(32)  NULL,
    address              TEXT         NULL,
    timezone             VARCHAR(64)  NOT NULL DEFAULT 'Asia/Tehran',
    working_hours_json   JSON         NULL,
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- cancellation_reason is created with appointments in migration 011.
