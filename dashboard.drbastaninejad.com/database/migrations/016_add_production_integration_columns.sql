-- Migration 016 — additive production integration fields.
-- Safe intent: add current intake bridge, portal notification and auth-scope columns.
-- No DROP/TRUNCATE/DELETE and no patient rows are modified beyond adding nullable/default fields.

ALTER TABLE `intakes`
    ADD COLUMN IF NOT EXISTS `source_type` ENUM('intake','booking') NOT NULL DEFAULT 'intake' AFTER `patient_uuid`,
    ADD COLUMN IF NOT EXISTS `email` VARCHAR(191) NULL AFTER `insurance_type`,
    ADD COLUMN IF NOT EXISTS `visit_reason` VARCHAR(255) NULL AFTER `email`,
    ADD COLUMN IF NOT EXISTS `doctor_request` VARCHAR(2000) NULL AFTER `visit_reason`,
    ADD COLUMN IF NOT EXISTS `sheets_sync_status` ENUM('pending','ok','failed','skipped') NOT NULL DEFAULT 'pending' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `sms_status` ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending' AFTER `sheets_sync_status`,
    ADD COLUMN IF NOT EXISTS `sms_sent_at` DATETIME NULL AFTER `sms_status`;

-- Booking requests intentionally have no national ID, while medical intakes still validate it in PHP.
ALTER TABLE `intakes` MODIFY COLUMN `national_id` VARCHAR(10) NULL;

ALTER TABLE `otp_codes`
    ADD COLUMN IF NOT EXISTS `audience` ENUM('patient','staff') NOT NULL DEFAULT 'patient' AFTER `purpose`;

ALTER TABLE `auth_tokens`
    ADD COLUMN IF NOT EXISTS `purpose` ENUM('session','password_reset') NOT NULL DEFAULT 'session' AFTER `user_type`;

ALTER TABLE `patients`
    ADD COLUMN IF NOT EXISTS `father_name` VARCHAR(100) NULL AFTER `last_name`,
    ADD COLUMN IF NOT EXISTS `email` VARCHAR(191) NULL AFTER `mobile`,
    ADD COLUMN IF NOT EXISTS `email_verified_at` DATETIME NULL AFTER `email`,
    ADD COLUMN IF NOT EXISTS `password_hash` VARCHAR(255) NULL AFTER `email_verified_at`,
    ADD COLUMN IF NOT EXISTS `birth_date_jalali` VARCHAR(10) NULL AFTER `birth_date`,
    ADD COLUMN IF NOT EXISTS `gender` VARCHAR(20) NULL AFTER `birth_date_jalali`,
    ADD COLUMN IF NOT EXISTS `home_tel` VARCHAR(32) NULL AFTER `gender`,
    ADD COLUMN IF NOT EXISTS `insurance_number` VARCHAR(100) NULL AFTER `home_address`,
    ADD COLUMN IF NOT EXISTS `insurance_status` ENUM('active','inactive','pending','unknown') NOT NULL DEFAULT 'pending' AFTER `insurance_number`,
    ADD COLUMN IF NOT EXISTS `sms_appointment_reminder` TINYINT(1) NOT NULL DEFAULT 1 AFTER `insurance_status`,
    ADD COLUMN IF NOT EXISTS `sms_status_change` TINYINT(1) NOT NULL DEFAULT 1 AFTER `sms_appointment_reminder`,
    ADD COLUMN IF NOT EXISTS `email_appointment_reminder` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sms_status_change`,
    ADD COLUMN IF NOT EXISTS `marketing_email_optin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `email_appointment_reminder`;

CREATE TABLE IF NOT EXISTS `media` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(32) NOT NULL,
    `clinic_id` INT UNSIGNED NOT NULL,
    `patient_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `type` VARCHAR(64) NULL,
    `tag` VARCHAR(64) NULL,
    `mime_type` VARCHAR(128) NULL,
    `size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `url` TEXT NULL,
    `url_expires_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_media_uuid` (`uuid`),
    KEY `idx_media_patient` (`patient_id`,`created_at`),
    KEY `idx_media_clinic` (`clinic_id`),
    KEY `idx_media_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
