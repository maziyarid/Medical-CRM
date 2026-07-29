-- Migration 003 — Create patients table (patient portal identity)
-- app.drbastaninejad.com / MΛZ Medical CRM
-- RULE: Do NOT create a parallel patients table. This is the ONE patients table.
-- The dashboard backend (dashboard.drbastaninejad.com) uses the same database.

CREATE TABLE IF NOT EXISTS patients (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid                VARCHAR(32)  NOT NULL UNIQUE,
    clinic_id           INT UNSIGNED NOT NULL DEFAULT 1,
    first_name          VARCHAR(100) NULL,
    last_name           VARCHAR(100) NULL,
    father_name         VARCHAR(100) NULL,
    mobile              VARCHAR(15)  NOT NULL UNIQUE,
    national_id         VARCHAR(10)  NULL,
    email               VARCHAR(255) NULL,
    birth_date          DATE         NULL,
    birth_date_jalali   VARCHAR(12)  NULL,
    gender              ENUM('male','female','other') NULL,
    home_address        TEXT         NULL,
    insurance_number    VARCHAR(30)  NULL,
    insurance_status    ENUM('active','expired','none','pending') NOT NULL DEFAULT 'pending',
    password_hash       VARCHAR(255) NULL COMMENT 'bcrypt — set only after account activation',
    remember_token      VARCHAR(64)  NULL COMMENT 'pre-auth_tokens fallback only',
    marketing_email_optin TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at          DATETIME     NULL,
    created_at          DATETIME     NOT NULL DEFAULT (UTC_TIMESTAMP()),
    updated_at          DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_mobile     (mobile),
    INDEX idx_national_id (national_id),
    INDEX idx_clinic_id  (clinic_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
