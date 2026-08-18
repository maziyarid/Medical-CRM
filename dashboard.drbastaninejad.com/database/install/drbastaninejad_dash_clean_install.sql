-- Dr. Shahin Bastaninejad Clinical Dashboard
-- Clean/non-destructive installer for an empty or newly-created database.
-- Target database: drbastaninejad_dash
-- Generated/verified: 2026-08-10
--
-- SAFETY:
--   * No DROP/TRUNCATE/DELETE statements.
--   * No patient/staff PII rows are seeded.
--   * Existing tables are left intact by CREATE TABLE IF NOT EXISTS.
--   * If this database already contains older/partial tables, use the additive
--     upgrade migration supplied with this deployment before relying on new columns.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+00:00';
USE `drbastaninejad_dash`;

-- ---------------------------------------------------------------------------
-- 1. Clinic / identity
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clinics` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`               VARCHAR(255) NOT NULL,
    `phone`              VARCHAR(32) NULL,
    `address`            TEXT NULL,
    `timezone`           VARCHAR(64) NOT NULL DEFAULT 'Asia/Tehran',
    `working_hours_json` JSON NULL,
    `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clinics` (`id`, `name`, `timezone`)
VALUES (1, 'کلینیک دکتر شاهین باستانی‌نژاد', 'Asia/Tehran')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

CREATE TABLE IF NOT EXISTS `patients` (
    `id`                         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`                       CHAR(32) NOT NULL,
    `clinic_id`                  INT UNSIGNED NOT NULL DEFAULT 1,
    `first_name`                 VARCHAR(100) NOT NULL DEFAULT '',
    `last_name`                  VARCHAR(100) NOT NULL DEFAULT '',
    `father_name`                VARCHAR(100) NULL,
    `mobile`                     VARCHAR(15) NOT NULL,
    `email`                      VARCHAR(191) NULL,
    `email_verified_at`          DATETIME NULL,
    `password_hash`              VARCHAR(255) NULL,
    `national_id`                VARCHAR(10) NULL,
    `birth_date`                 DATE NULL,
    `birth_date_jalali`          VARCHAR(10) NULL,
    `gender`                     VARCHAR(20) NULL,
    `home_tel`                   VARCHAR(32) NULL,
    `home_address`               TEXT NULL,
    `insurance_number`           VARCHAR(100) NULL,
    `insurance_status`           ENUM('active','inactive','pending','unknown') NOT NULL DEFAULT 'pending',
    `sms_appointment_reminder`   TINYINT(1) NOT NULL DEFAULT 1,
    `sms_status_change`          TINYINT(1) NOT NULL DEFAULT 1,
    `email_appointment_reminder` TINYINT(1) NOT NULL DEFAULT 0,
    `marketing_email_optin`      TINYINT(1) NOT NULL DEFAULT 0,
    `deleted_at`                 DATETIME NULL,
    `created_at`                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_patients_uuid` (`uuid`),
    UNIQUE KEY `uq_patients_mobile` (`mobile`),
    UNIQUE KEY `uq_patients_email` (`email`),
    UNIQUE KEY `uq_patients_national_id` (`national_id`),
    KEY `idx_patients_clinic` (`clinic_id`),
    KEY `idx_patients_deleted` (`deleted_at`),
    KEY `idx_patients_name` (`last_name`, `first_name`),
    CONSTRAINT `fk_patients_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. Staff + RBAC
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`          CHAR(36) NOT NULL,
    `clinic_id`     INT UNSIGNED NOT NULL DEFAULT 1,
    `full_name`     VARCHAR(200) NOT NULL,
    `mobile`        VARCHAR(15) NOT NULL,
    `email`         VARCHAR(255) NULL,
    `password_hash` VARCHAR(255) NULL,
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at`    DATETIME NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_uuid` (`uuid`),
    UNIQUE KEY `uq_users_mobile` (`mobile`),
    KEY `idx_users_clinic` (`clinic_id`),
    KEY `idx_users_deleted` (`deleted_at`),
    CONSTRAINT `fk_users_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(64) NOT NULL,
    `label`      VARCHAR(100) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permissions_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_user` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `role_id`    INT UNSIGNED NOT NULL,
    `granted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_role_user` (`user_id`,`role_id`),
    KEY `idx_role_user_role` (`role_id`),
    CONSTRAINT `fk_ru_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ru_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permission_role` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id`       INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permission_role` (`role_id`,`permission_id`),
    KEY `idx_permission_role_perm` (`permission_id`),
    CONSTRAINT `fk_pr_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pr_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `roles` (`name`,`label`) VALUES
('super_admin','مدیر کل'),('doctor','پزشک'),('receptionist','پذیرش'),('nurse','پرستار');

INSERT IGNORE INTO `permissions` (`name`,`description`) VALUES
('dashboard.view','View dashboard'),
('patients.view','View patients'),('patients.manage','Manage patients'),
('appointments.view','View appointments'),('appointments.manage','Manage appointments'),
('emr.view','View EMR'),('emr.edit','Edit EMR'),
('billing.view','View billing'),('billing.manage','Manage billing'),
('tasks.view','View tasks'),('tasks.manage','Manage tasks'),
('analytics.view','View analytics'),
('settings.view','View settings'),('settings.manage','Manage settings'),
('intakes.view','View intake/booking queue'),('intakes.manage','Review intake/booking queue'),
('patient.portal','Patient portal scope marker');

-- Explicit metadata grant for super_admin. Runtime middleware also treats this
-- role as a bypass, but retaining the rows makes RBAC inspection/auditing complete.
INSERT IGNORE INTO `permission_role` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r CROSS JOIN `permissions` p
WHERE r.name = 'super_admin';

-- No staff user is seeded. Create the first real staff identity through a secure
-- administration/bootstrap procedure and assign the super_admin role explicitly.

-- ---------------------------------------------------------------------------
-- 3. Intake / authentication
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `intakes` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `submission_uuid`    VARCHAR(64) NOT NULL,
    `clinic_id`          INT UNSIGNED NOT NULL DEFAULT 1,
    `patient_id`         INT UNSIGNED NULL,
    `patient_uuid`       CHAR(32) NOT NULL DEFAULT '',
    `source_type`        ENUM('intake','booking') NOT NULL DEFAULT 'intake',
    `first_name`         VARCHAR(100) NOT NULL,
    `last_name`          VARCHAR(100) NOT NULL DEFAULT '',
    `mobile`             VARCHAR(15) NOT NULL,
    `national_id`        VARCHAR(10) NULL,
    `birth_date`         DATE NULL,
    `birth_date_jalali`  VARCHAR(10) NULL,
    `chief_complaint`    TEXT NOT NULL,
    `service_type`       VARCHAR(200) NULL,
    `preferred_date`     DATE NULL,
    `insurance_type`     VARCHAR(100) NULL,
    `email`              VARCHAR(191) NULL,
    `visit_reason`       VARCHAR(255) NULL,
    `doctor_request`     VARCHAR(2000) NULL,
    `raw_payload`        JSON NULL,
    `status`             ENUM('pending','reviewed','converted','rejected') NOT NULL DEFAULT 'pending',
    `sheets_sync_status` ENUM('pending','ok','failed','skipped') NOT NULL DEFAULT 'pending',
    `sms_status`         ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
    `sms_sent_at`        DATETIME NULL,
    `reviewed_by`        INT UNSIGNED NULL,
    `reviewed_at`        DATETIME NULL,
    `deleted_at`         DATETIME NULL,
    `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_intakes_submission_uuid` (`submission_uuid`),
    KEY `idx_intakes_clinic_status` (`clinic_id`,`status`),
    KEY `idx_intakes_source` (`clinic_id`,`source_type`,`created_at`),
    KEY `idx_intakes_patient` (`patient_id`),
    KEY `idx_intakes_mobile` (`mobile`),
    KEY `idx_intakes_national_id` (`national_id`),
    KEY `idx_intakes_email` (`email`),
    KEY `idx_intakes_sheets_sync` (`sheets_sync_status`,`created_at`),
    KEY `idx_intakes_sms_status` (`sms_status`,`created_at`),
    KEY `idx_intakes_created` (`created_at`),
    CONSTRAINT `fk_intakes_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_intakes_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_intakes_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mobile`     VARCHAR(15) NOT NULL,
    `code`       VARCHAR(255) NOT NULL,
    `purpose`    ENUM('login','register','reset') NOT NULL DEFAULT 'login',
    `audience`   ENUM('patient','staff') NOT NULL DEFAULT 'patient',
    `expires_at` DATETIME NOT NULL,
    `used_at`    DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_otp_scope_created` (`mobile`,`audience`,`purpose`,`created_at`),
    KEY `idx_otp_scope_used` (`mobile`,`audience`,`purpose`,`used_at`),
    KEY `idx_otp_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auth_tokens` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `user_type`  ENUM('patient','staff') NOT NULL DEFAULT 'patient',
    `purpose`    ENUM('session','password_reset') NOT NULL DEFAULT 'session',
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `revoked_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_auth_token_hash` (`token_hash`),
    KEY `idx_auth_user` (`user_id`,`user_type`,`purpose`),
    KEY `idx_auth_expires` (`expires_at`),
    KEY `idx_auth_active` (`user_type`,`purpose`,`revoked_at`,`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 4. Scheduling / billing / tasks
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointments` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`                CHAR(32) NOT NULL,
    `clinic_id`           INT UNSIGNED NOT NULL,
    `patient_id`          INT UNSIGNED NOT NULL,
    `provider_id`         INT UNSIGNED NULL,
    `scheduled_at`        DATETIME NOT NULL,
    `duration_minutes`    SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    `visit_reason`        VARCHAR(255) NULL,
    `room`                VARCHAR(64) NULL,
    `status`              ENUM('scheduled','confirmed','cancelled','completed') NOT NULL DEFAULT 'scheduled',
    `notes`               TEXT NULL,
    `cancellation_reason` TEXT NULL,
    `deleted_at`          DATETIME NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_appointments_uuid` (`uuid`),
    KEY `idx_appointments_clinic_sched` (`clinic_id`,`scheduled_at`),
    KEY `idx_appointments_patient` (`patient_id`),
    KEY `idx_appointments_provider` (`provider_id`),
    KEY `idx_appointments_deleted` (`deleted_at`),
    CONSTRAINT `fk_appointments_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_appointments_provider` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reminder_log` (
    `id`                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `appointment_id`          INT UNSIGNED NOT NULL,
    `patient_id`              INT UNSIGNED NOT NULL,
    `clinic_id`               INT UNSIGNED NOT NULL,
    `channel`                 ENUM('sms','email') NOT NULL DEFAULT 'sms',
    `remind_at`               DATETIME NOT NULL,
    `remind_offset_minutes`   SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    `status`                  ENUM('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
    `attempts`                TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts`            TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `sent_at`                 DATETIME NULL,
    `provider`                VARCHAR(64) NULL,
    `error_message`           VARCHAR(512) NULL,
    `created_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reminder_appt_channel_offset` (`appointment_id`,`channel`,`remind_offset_minutes`),
    KEY `idx_reminder_due` (`status`,`remind_at`),
    KEY `idx_reminder_patient` (`patient_id`),
    KEY `idx_reminder_clinic` (`clinic_id`),
    CONSTRAINT `fk_reminder_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reminder_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_reminder_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoices` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clinic_id`    INT UNSIGNED NOT NULL,
    `patient_id`   INT UNSIGNED NOT NULL,
    `amount_rials` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `status`       ENUM('pending','paid','failed','insurance_pending') NOT NULL DEFAULT 'pending',
    `gateway`      VARCHAR(64) NULL,
    `notes`        TEXT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_invoices_clinic` (`clinic_id`),
    KEY `idx_invoices_patient` (`patient_id`),
    KEY `idx_invoices_status` (`status`),
    KEY `idx_invoices_created` (`created_at`),
    CONSTRAINT `fk_invoices_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_invoices_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tasks` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clinic_id`   INT UNSIGNED NOT NULL,
    `title`       VARCHAR(255) NOT NULL,
    `priority`    ENUM('high','medium','low') NOT NULL DEFAULT 'medium',
    `status`      ENUM('todo','in_progress','done') NOT NULL DEFAULT 'todo',
    `assignee_id` INT UNSIGNED NULL,
    `due_date`    DATE NULL,
    `notes`       TEXT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tasks_clinic` (`clinic_id`),
    KEY `idx_tasks_status` (`status`),
    KEY `idx_tasks_assignee` (`assignee_id`),
    CONSTRAINT `fk_tasks_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_tasks_assignee` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 5. EMR / patient documents
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `emr_templates` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clinic_id`   INT UNSIGNED NULL,
    `specialty`   VARCHAR(64) NOT NULL DEFAULT 'general',
    `name`        VARCHAR(200) NOT NULL,
    `schema_json` JSON NOT NULL,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_emr_tpl_specialty` (`specialty`),
    KEY `idx_emr_tpl_clinic` (`clinic_id`),
    KEY `idx_emr_tpl_active` (`is_active`),
    CONSTRAINT `fk_emr_tpl_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `emr_records` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`             CHAR(32) NOT NULL,
    `clinic_id`        INT UNSIGNED NOT NULL,
    `patient_id`       INT UNSIGNED NOT NULL,
    `appointment_id`   INT UNSIGNED NULL,
    `author_id`        INT UNSIGNED NULL,
    `template_id`      INT UNSIGNED NULL,
    `chief_complaint`  TEXT NOT NULL,
    `diagnosis`        TEXT NULL,
    `plan`             TEXT NULL,
    `specialty_fields` JSON NULL,
    `ai_draft`         TEXT NULL,
    `ai_accepted`      TINYINT(1) NOT NULL DEFAULT 0,
    `deleted_at`       DATETIME NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_emr_uuid` (`uuid`),
    KEY `idx_emr_patient` (`patient_id`),
    KEY `idx_emr_clinic` (`clinic_id`),
    KEY `idx_emr_appointment` (`appointment_id`),
    KEY `idx_emr_deleted` (`deleted_at`),
    CONSTRAINT `fk_emr_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_emr_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_emr_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_emr_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_emr_template` FOREIGN KEY (`template_id`) REFERENCES `emr_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`           CHAR(32) NOT NULL,
    `clinic_id`      INT UNSIGNED NOT NULL,
    `patient_id`     INT UNSIGNED NOT NULL,
    `title`          VARCHAR(255) NOT NULL,
    `type`           VARCHAR(64) NULL,
    `tag`            VARCHAR(64) NULL,
    `mime_type`      VARCHAR(128) NULL,
    `size_bytes`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `url`            TEXT NULL,
    `url_expires_at` DATETIME NULL,
    `deleted_at`     DATETIME NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_media_uuid` (`uuid`),
    KEY `idx_media_patient` (`patient_id`,`created_at`),
    KEY `idx_media_clinic` (`clinic_id`),
    KEY `idx_media_deleted` (`deleted_at`),
    CONSTRAINT `fk_media_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_media_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Verification summary (read-only)
-- ---------------------------------------------------------------------------
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
    'clinics','patients','users','roles','permissions','role_user','permission_role',
    'intakes','otp_codes','auth_tokens','appointments','reminder_log','invoices','tasks',
    'emr_templates','emr_records','media'
  )
ORDER BY TABLE_NAME;
