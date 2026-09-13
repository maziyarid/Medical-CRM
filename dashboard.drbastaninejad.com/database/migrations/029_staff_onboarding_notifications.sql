-- Migration 029: staff onboarding/password auth and patient registration notification audit.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS invited_by INT UNSIGNED NULL AFTER password_hash,
    ADD COLUMN IF NOT EXISTS invited_at DATETIME NULL AFTER invited_by,
    ADD COLUMN IF NOT EXISTS activated_at DATETIME NULL AFTER invited_at,
    ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL AFTER activated_at;

CREATE TABLE IF NOT EXISTS staff_login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mobile_hash CHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_staff_login_mobile_time (mobile_hash, created_at),
    KEY idx_staff_login_ip_time (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE patients
    ADD COLUMN IF NOT EXISTS registration_sms_status ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending' AFTER password_hash,
    ADD COLUMN IF NOT EXISTS registration_email_status ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending' AFTER registration_sms_status,
    ADD COLUMN IF NOT EXISTS registration_notified_at DATETIME NULL AFTER registration_email_status;

INSERT IGNORE INTO permissions (name, description)
VALUES ('staff.manage', 'Create, invite, update and deactivate staff accounts');

INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.name = 'staff.manage'
WHERE r.name = 'super_admin';
