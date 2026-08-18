-- Migration 012: users, roles, permissions, and RBAC pivot tables
-- dashboard.drbastaninejad.com
--
-- Supports AuthMiddleware (resolveUser for 'staff') and RbacMiddleware
-- (staffHasPermission). Roles: super_admin, doctor, receptionist, nurse.
-- Permissions are named strings (e.g. 'patients.view', 'emr.edit').
--
-- Tables:
--   users            — staff accounts (NOT patients — see migration 003)
--   roles            — named roles
--   permissions      — named permissions
--   role_user        — M:M pivot: user ↔ role
--   permission_role  — M:M pivot: role ↔ permission

-- ── users ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    uuid         CHAR(36)        NOT NULL,
    clinic_id    INT UNSIGNED    NOT NULL DEFAULT 1,
    full_name    VARCHAR(200)    NOT NULL,
    mobile       VARCHAR(15)     NOT NULL,
    email        VARCHAR(255)    NULL,
    password_hash VARCHAR(255)   NULL COMMENT 'bcrypt; NULL for OTP-only staff',
    is_active    TINYINT(1)      NOT NULL DEFAULT 1,
    deleted_at   DATETIME        NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                     ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_uuid        (uuid),
    UNIQUE KEY uq_users_mobile      (mobile),
    INDEX      idx_users_clinic     (clinic_id),
    INDEX      idx_users_deleted    (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Staff accounts (doctors, receptionists, nurses). NOT patients.';

-- ── roles ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS roles (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(64)  NOT NULL COMMENT 'e.g. super_admin, doctor, receptionist, nurse',
    label       VARCHAR(100) NULL     COMMENT 'Persian display label',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── permissions ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS permissions (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL COMMENT 'e.g. patients.view, emr.edit, billing.manage',
    description VARCHAR(255) NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── role_user pivot ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS role_user (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    role_id    INT UNSIGNED NOT NULL,
    granted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_role_user         (user_id, role_id),
    INDEX      idx_role_user_role   (role_id),
    CONSTRAINT fk_ru_user FOREIGN KEY (user_id) REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_ru_role FOREIGN KEY (role_id) REFERENCES roles(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── permission_role pivot ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS permission_role (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_id       INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_permission_role          (role_id, permission_id),
    INDEX      idx_permission_role_perm    (permission_id),
    CONSTRAINT fk_pr_role FOREIGN KEY (role_id)       REFERENCES roles(id)       ON DELETE CASCADE,
    CONSTRAINT fk_pr_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
