-- Migration 017 — normalize auth_tokens / otp_codes after duplicate 003+014 histories.
-- Safe to re-run on MariaDB 10.5+ (ADD COLUMN IF NOT EXISTS).

ALTER TABLE auth_tokens
  ADD COLUMN IF NOT EXISTS purpose ENUM('session','password_reset') NOT NULL DEFAULT 'session' AFTER user_type;

ALTER TABLE otp_codes
  ADD COLUMN IF NOT EXISTS attempts INT UNSIGNED NOT NULL DEFAULT 0 AFTER used_at;

ALTER TABLE intakes
  ADD COLUMN IF NOT EXISTS sms_provider VARCHAR(64) NULL AFTER sms_status,
  ADD COLUMN IF NOT EXISTS sms_provider_id VARCHAR(128) NULL AFTER sms_provider;

CREATE TABLE IF NOT EXISTS otp_throttle (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scope      VARCHAR(80)     NOT NULL,
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_otp_throttle_scope (scope, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
