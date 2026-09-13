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
('super_admin','مدیر کل'),('admin','مدیر'),('doctor','پزشک'),('receptionist','پذیرش'),('nurse','پرستار');

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
    `booking_sheet_status` ENUM('pending','attempting','submitted','failed_confirmed','outcome_unknown','skipped') NOT NULL DEFAULT 'pending',
    `booking_email_status` ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
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

CREATE TABLE IF NOT EXISTS `booking_verifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mobile` VARCHAR(15) NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `consumed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_booking_verification_token` (`token_hash`),
    KEY `idx_booking_verification_mobile` (`mobile`,`consumed_at`,`expires_at`)
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
-- Appointment booking v2 / payments / Calendar / ScheduledVisits projection
-- Kept in parity with migrations 024-028 for a fresh installation.
-- ---------------------------------------------------------------------------

-- Migration 024: paid appointment booking, availability and Calendar integration
-- MySQL remains the transactional source of truth. Google Calendar is an integration
-- bridge to the clinic's DevExpress desktop scheduler, never the sole booking store.

CREATE TABLE IF NOT EXISTS appointment_open_days (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id             INT UNSIGNED NOT NULL,
    open_date             DATE NOT NULL,
    capacity              SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    held_count            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    booked_count          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    slot_duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    opens_at              TIME NULL,
    closes_at             TIME NULL,
    status                ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_by            INT UNSIGNED NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_open_day_clinic_date (clinic_id, open_date),
    KEY idx_open_days_status_date (clinic_id, status, open_date),
    CONSTRAINT fk_open_day_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    CONSTRAINT fk_open_day_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_open_day_counts CHECK (held_count + booked_count <= capacity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointment_booking_requests (
    id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid                   CHAR(36) NOT NULL,
    submission_uuid        VARCHAR(64) NULL,
    clinic_id              INT UNSIGNED NOT NULL,
    patient_id             INT UNSIGNED NOT NULL,
    intake_id              INT UNSIGNED NULL,
    open_day_id            INT UNSIGNED NOT NULL,
    appointment_id         INT UNSIGNED NULL,
    requested_start_at     DATETIME NOT NULL,
    duration_minutes       SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    source                 ENUM('online','admin') NOT NULL,
    payment_gateway        ENUM('zarinpal','vandar','none') NULL,
    payment_status         ENUM('unpaid','pending','paid','free','failed','refunded') NOT NULL DEFAULT 'unpaid',
    confirmation_status    ENUM('holding','awaiting_payment','paid_pending_staff','confirmed','expired','cancelled') NOT NULL DEFAULT 'holding',
    receptionist_user_id   INT UNSIGNED NULL,
    amount_rials           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    slot_claim_key         CHAR(64) NULL,
    hold_expires_at        DATETIME NULL,
    staff_followup_required TINYINT(1) NOT NULL DEFAULT 1,
    confirmed_at           DATETIME NULL,
    cancelled_at           DATETIME NULL,
    created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_booking_request_uuid (uuid),
    UNIQUE KEY uq_booking_submission_uuid (submission_uuid),
    UNIQUE KEY uq_booking_active_slot_claim (slot_claim_key),
    KEY idx_booking_patient (patient_id, created_at),
    KEY idx_booking_day_status (open_day_id, confirmation_status),
    KEY idx_booking_payment (payment_status, payment_gateway),
    KEY idx_booking_hold_expiry (confirmation_status, hold_expires_at),
    CONSTRAINT fk_booking_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_intake FOREIGN KEY (intake_id) REFERENCES intakes(id) ON DELETE SET NULL,
    CONSTRAINT fk_booking_open_day FOREIGN KEY (open_day_id) REFERENCES appointment_open_days(id) ON DELETE RESTRICT,
    CONSTRAINT fk_booking_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    CONSTRAINT fk_booking_receptionist FOREIGN KEY (receptionist_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointment_payment_attempts (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_request_id    BIGINT UNSIGNED NOT NULL,
    gateway               ENUM('zarinpal','vandar') NOT NULL,
    amount_rials          BIGINT UNSIGNED NOT NULL,
    currency              CHAR(3) NOT NULL DEFAULT 'IRR',
    status                ENUM('created','redirected','verified','failed','cancelled','refunded') NOT NULL DEFAULT 'created',
    idempotency_key       CHAR(64) NOT NULL,
    authority             VARCHAR(191) NULL,
    transaction_ref       VARCHAR(191) NULL,
    gateway_code          VARCHAR(64) NULL,
    request_payload       JSON NULL,
    response_payload      JSON NULL,
    verified_at           DATETIME NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_idempotency (idempotency_key),
    UNIQUE KEY uq_payment_gateway_authority (gateway, authority),
    KEY idx_payment_booking_status (booking_request_id, status),
    CONSTRAINT fk_payment_booking FOREIGN KEY (booking_request_id) REFERENCES appointment_booking_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendar_event_links (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id             INT UNSIGNED NOT NULL,
    appointment_id        INT UNSIGNED NULL,
    booking_request_id    BIGINT UNSIGNED NULL,
    google_calendar_id    VARCHAR(255) NOT NULL,
    google_event_id       VARCHAR(255) NOT NULL,
    google_etag           VARCHAR(255) NULL,
    sync_status           ENUM('pending','synced','conflict','deleted','error') NOT NULL DEFAULT 'pending',
    remote_updated_at     DATETIME NULL,
    last_synced_at        DATETIME NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_calendar_remote_event (google_calendar_id, google_event_id),
    UNIQUE KEY uq_calendar_appointment (appointment_id),
    KEY idx_calendar_booking (booking_request_id),
    KEY idx_calendar_sync_status (clinic_id, sync_status),
    CONSTRAINT fk_calendar_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    CONSTRAINT fk_calendar_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    CONSTRAINT fk_calendar_booking FOREIGN KEY (booking_request_id) REFERENCES appointment_booking_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendar_sync_outbox (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id             INT UNSIGNED NOT NULL,
    aggregate_type        ENUM('booking','appointment','open_day') NOT NULL,
    aggregate_id          BIGINT UNSIGNED NOT NULL,
    action                ENUM('upsert','delete','import') NOT NULL,
    idempotency_key       CHAR(64) NOT NULL,
    payload               JSON NOT NULL,
    status                ENUM('pending','processing','succeeded','failed','dead') NOT NULL DEFAULT 'pending',
    attempts              SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    next_attempt_at       DATETIME NULL,
    locked_at             DATETIME NULL,
    last_error            TEXT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_calendar_outbox_idempotency (idempotency_key),
    KEY idx_calendar_outbox_due (status, next_attempt_at),
    KEY idx_calendar_outbox_clinic (clinic_id, created_at),
    CONSTRAINT fk_calendar_outbox_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (name, description) VALUES
    ('booking.manage', 'View and manage appointment booking requests'),
    ('booking.availability.manage', 'Manage clinic open days, slots and quotas'),
    ('booking.payments.reconcile', 'Reconcile appointment payment attempts'),
    ('calendar.sync.manage', 'Manage Google Calendar synchronisation');

-- Migration 025: booking/calendar RBAC grants

INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.name IN (
    'booking.manage',
    'booking.availability.manage',
    'booking.payments.reconcile',
    'calendar.sync.manage'
)
WHERE r.name = 'super_admin';

INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.name = 'booking.manage'
WHERE r.name = 'receptionist';

INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.name = 'booking.manage'
WHERE r.name = 'doctor';

-- Migration 026: per-clinic Google Calendar incremental sync cursor

CREATE TABLE IF NOT EXISTS calendar_sync_cursors (
    clinic_id          INT UNSIGNED NOT NULL,
    google_calendar_id VARCHAR(255) NOT NULL,
    sync_token         TEXT NULL,
    last_full_sync_at  DATETIME NULL,
    last_sync_at       DATETIME NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (clinic_id, google_calendar_id),
    CONSTRAINT fk_calendar_cursor_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 027: reliable Google Sheet projection for appointment booking lifecycle.
-- MySQL remains the transactional source of truth; ScheduledVisits is an
-- operational mirror that is retried independently and updated idempotently.
-- This migration is intended to be applied once by the normal migration runner.
-- AppointmentSchemaBootstrapService performs equivalent column/index checks for
-- file-only production deployments where migrations are not executed.

ALTER TABLE appointment_booking_requests
    ADD COLUMN sheet_sync_status ENUM('pending','synced','error','skipped') NOT NULL DEFAULT 'pending' AFTER staff_followup_required,
    ADD COLUMN sheet_synced_at DATETIME NULL AFTER sheet_sync_status,
    ADD COLUMN sheet_sync_error VARCHAR(500) NULL AFTER sheet_synced_at,
    ADD INDEX idx_booking_sheet_sync (sheet_sync_status, updated_at);

INSERT IGNORE INTO permissions (name, description)
VALUES ('booking.integrations.manage', 'Manage appointment integration credentials');

INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.name = 'booking.integrations.manage'
WHERE r.name = 'super_admin';

-- Migration 028: audit who completed the post-payment/post-confirmation staff follow-up.

ALTER TABLE appointment_booking_requests
    ADD COLUMN followup_completed_at DATETIME NULL AFTER staff_followup_required,
    ADD COLUMN followup_completed_by INT UNSIGNED NULL AFTER followup_completed_at,
    ADD INDEX idx_booking_followup_queue (clinic_id, staff_followup_required, confirmation_status),
    ADD CONSTRAINT fk_booking_followup_user FOREIGN KEY (followup_completed_by) REFERENCES users(id) ON DELETE SET NULL;

-- ---------------------------------------------------------------------------
-- Staff onboarding/password authentication + patient registration notifications
-- ---------------------------------------------------------------------------
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
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.name='staff.manage'
WHERE r.name='super_admin';

-- ---------------------------------------------------------------------------
-- Verification summary (read-only)
-- ---------------------------------------------------------------------------
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
    'clinics','patients','users','roles','permissions','role_user','permission_role',
    'intakes','otp_codes','auth_tokens','appointments','reminder_log','invoices','tasks',
    'emr_templates','emr_records','media',
    'appointment_open_days','appointment_booking_requests','appointment_payment_attempts',
    'calendar_event_links','calendar_sync_outbox','calendar_sync_cursors'
  )
ORDER BY TABLE_NAME;


-- Operational admin: broad clinic operations without staff/integration ownership.
INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.name IN (
  'dashboard.view','patients.view','patients.manage','appointments.view','appointments.manage','emr.view',
  'billing.view','billing.manage','tasks.view','tasks.manage','analytics.view','settings.view','settings.manage',
  'intakes.view','intakes.manage','booking.manage','booking.availability.manage','booking.payments.reconcile'
) WHERE r.name='admin';
