-- Migration 014: auth_tokens table for dashboard.drbastaninejad.com
-- dashboard.drbastaninejad.com
--
-- auth_tokens stores SHA-256-hashed Bearer tokens for both patient and staff sessions.
-- AuthMiddleware.handle() queries this table on every authenticated request.
--
-- The canonical schema originates from:
--   app.drbastaninejad.com/Backend/database/migrations/004_create_auth_tokens_table.sql
-- This migration is a copy scoped to the dashboard DDL sequence.
-- Both backends share one MariaDB database (UNIFIED_MASTER_PLAN.md §2).
-- Running both migrations is safe — both use CREATE TABLE IF NOT EXISTS.
--
-- user_type: 'patient' → patients.id FK
--            'staff'   → users.id FK (created in migration 012)
-- token_hash: SHA-256 hex of the raw Bearer token (64 chars).
-- revoked_at: set when the user logs out; AuthMiddleware checks IS NULL.
-- expires_at: rolling 30-day expiry from issueToken(); AuthMiddleware checks > UTC_TIMESTAMP().

CREATE TABLE IF NOT EXISTS auth_tokens (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED  NOT NULL,
    user_type    ENUM('patient','staff') NOT NULL DEFAULT 'patient',
    token_hash   CHAR(64)      NOT NULL COMMENT 'SHA-256 hex of raw Bearer token',
    expires_at   DATETIME      NOT NULL,
    revoked_at   DATETIME      NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_auth_tokens_hash    (token_hash),
    INDEX      idx_auth_tokens_user   (user_id, user_type),
    INDEX      idx_auth_tokens_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Hashed Bearer tokens for patient and staff sessions.';
