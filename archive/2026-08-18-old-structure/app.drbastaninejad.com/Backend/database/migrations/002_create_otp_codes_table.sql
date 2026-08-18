-- Migration 002 — Create otp_codes table
-- app.drbastaninejad.com / MΛZ Medical CRM

CREATE TABLE IF NOT EXISTS otp_codes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mobile      VARCHAR(15)  NOT NULL,
    code        VARCHAR(60)  NOT NULL COMMENT 'bcrypt hash of 5-digit code — never store plaintext',
    purpose     VARCHAR(30)  NOT NULL DEFAULT 'login',
    expires_at  DATETIME     NOT NULL,
    used_at     DATETIME     NULL,
    created_at  DATETIME     NOT NULL DEFAULT (UTC_TIMESTAMP()),

    INDEX idx_mobile_created (mobile, created_at),
    INDEX idx_mobile_used    (mobile, used_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
