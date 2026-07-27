-- Migration 002 — otp_codes table
-- Stores hashed OTP codes with expiry and rate-limit support.

CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mobile`     VARCHAR(15)  NOT NULL,
    `code`       VARCHAR(72)  NOT NULL,    -- bcrypt hash of the 5-digit code
    `purpose`    ENUM('login','register','reset') NOT NULL DEFAULT 'login',
    `expires_at` DATETIME     NOT NULL,
    `used_at`    DATETIME     NULL,
    `created_at` DATETIME     NOT NULL DEFAULT UTC_TIMESTAMP(),
    PRIMARY KEY (`id`),
    KEY `idx_otp_mobile_created` (`mobile`, `created_at`),
    KEY `idx_otp_mobile_used` (`mobile`, `used_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
