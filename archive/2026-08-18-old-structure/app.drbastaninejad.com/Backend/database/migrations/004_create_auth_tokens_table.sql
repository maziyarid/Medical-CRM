-- Migration 004 — Create auth_tokens table
-- app.drbastaninejad.com / MΛZ Medical CRM

CREATE TABLE IF NOT EXISTS auth_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    user_type   ENUM('patient','staff') NOT NULL,
    token_hash  VARCHAR(64)  NOT NULL UNIQUE COMMENT 'SHA-256 of raw token — never store plaintext',
    expires_at  DATETIME     NOT NULL,
    revoked_at  DATETIME     NULL,
    created_at  DATETIME     NOT NULL DEFAULT (UTC_TIMESTAMP()),

    INDEX idx_token_hash (token_hash),
    INDEX idx_user       (user_id, user_type),
    INDEX idx_expires    (expires_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
