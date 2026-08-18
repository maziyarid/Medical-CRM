-- Migration 003 — auth_tokens table
-- Separate from patients.remember_token (which was the pre-phase-B fallback).
-- Supports both patients and staff users via user_type discriminator.

CREATE TABLE IF NOT EXISTS `auth_tokens` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `user_type`  ENUM('patient','staff') NOT NULL DEFAULT 'patient',
    `purpose`    ENUM('session','password_reset') NOT NULL DEFAULT 'session',
    `token_hash` CHAR(64)     NOT NULL,   -- SHA-256 hex of the raw bearer token
    `expires_at` DATETIME     NOT NULL,
    `revoked_at` DATETIME     NULL,
    `created_at` DATETIME     NOT NULL DEFAULT UTC_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_token_hash` (`token_hash`),
    KEY `idx_at_user` (`user_id`, `user_type`),
    KEY `idx_at_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
