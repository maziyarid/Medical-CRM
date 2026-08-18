-- Migration 001 — Create intakes table
-- app.drbastaninejad.com / MΛZ Medical CRM
-- Run this in local dev first, then private staging, then production (see DEPLOYMENT_GATE.md).

CREATE TABLE IF NOT EXISTS intakes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_uuid     VARCHAR(64)  NOT NULL UNIQUE COMMENT 'Client-generated UUID v4; idempotency key',
    clinic_id           INT UNSIGNED NOT NULL DEFAULT 1,
    first_name          VARCHAR(100) NOT NULL,
    last_name           VARCHAR(100) NOT NULL,
    father_name         VARCHAR(100) NULL,
    mobile              VARCHAR(15)  NOT NULL,
    national_id         VARCHAR(10)  NOT NULL,
    birth_date          DATE         NULL COMMENT 'Gregorian — converted server-side from birth_date_jalali',
    birth_date_jalali   VARCHAR(12)  NULL COMMENT 'Jalali YYYY/MM/DD as submitted by client',
    email               VARCHAR(255) NULL,
    home_address        TEXT         NULL,
    chief_complaint     TEXT         NOT NULL,
    visit_reason        VARCHAR(255) NULL,
    is_transfer         TINYINT(1)   NOT NULL DEFAULT 0,
    raw_payload         JSON         NULL COMMENT 'Full client JSON for audit and reconciliation',
    sheets_sync_status  ENUM('pending','ok','failed','skipped') NOT NULL DEFAULT 'pending',
    status              ENUM('pending','reviewed','converted','rejected') NOT NULL DEFAULT 'pending',
    created_at          DATETIME     NOT NULL DEFAULT (UTC_TIMESTAMP()),
    updated_at          DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_mobile       (mobile),
    INDEX idx_national_id  (national_id),
    INDEX idx_status       (status),
    INDEX idx_created_at   (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
