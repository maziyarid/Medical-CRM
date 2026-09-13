-- Migration 024 — scheduled online booking, payments, open-day quotas, and Calendar bridge.
-- Additive only. Safe for the existing clinical tables.

CREATE TABLE IF NOT EXISTS `appointment_open_days` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clinic_id` INT UNSIGNED NOT NULL,
    `open_date` DATE NOT NULL,
    `opens_at` TIME NOT NULL DEFAULT '09:00:00',
    `closes_at` TIME NOT NULL DEFAULT '18:00:00',
    `slot_duration_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    `capacity` SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    `booked_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
    `notes` VARCHAR(255) NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
    `updated_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP() ON UPDATE UTC_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_appointment_open_day` (`clinic_id`,`open_date`),
    KEY `idx_appointment_open_days_month` (`clinic_id`,`open_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `appointment_slot_holds` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clinic_id` INT UNSIGNED NOT NULL,
    `open_day_id` BIGINT UNSIGNED NOT NULL,
    `intake_id` BIGINT UNSIGNED NULL,
    `mobile` VARCHAR(15) NOT NULL,
    `slot_start` DATETIME NOT NULL,
    `slot_end` DATETIME NOT NULL,
    `hold_token_hash` CHAR(64) NOT NULL,
    `status` ENUM('held','consumed','released','expired') NOT NULL DEFAULT 'held',
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
    `updated_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP() ON UPDATE UTC_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_slot_hold_token` (`hold_token_hash`),
    KEY `idx_slot_hold_active` (`clinic_id`,`slot_start`,`status`,`expires_at`),
    KEY `idx_slot_hold_intake` (`intake_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `booking_payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `clinic_id` INT UNSIGNED NOT NULL,
    `intake_id` BIGINT UNSIGNED NOT NULL,
    `slot_hold_id` BIGINT UNSIGNED NULL,
    `gateway` ENUM('zarinpal','vandar','admin_free') NOT NULL,
    `amount_rial` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('initiated','pending','paid','free','failed','cancelled','outcome_unknown','refunded') NOT NULL DEFAULT 'initiated',
    `gateway_authority` VARCHAR(160) NULL,
    `reference_id` VARCHAR(160) NULL,
    `callback_nonce_hash` CHAR(64) NOT NULL,
    `gateway_payload` JSON NULL,
    `verified_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
    `updated_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP() ON UPDATE UTC_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_booking_payment_uuid` (`uuid`),
    UNIQUE KEY `uq_booking_payment_authority` (`gateway`,`gateway_authority`),
    KEY `idx_booking_payment_intake` (`intake_id`,`status`),
    KEY `idx_booking_payment_created` (`clinic_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `booking_event_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clinic_id` INT UNSIGNED NOT NULL,
    `intake_id` BIGINT UNSIGNED NULL,
    `payment_id` BIGINT UNSIGNED NULL,
    `event_type` VARCHAR(80) NOT NULL,
    `event_status` VARCHAR(32) NOT NULL DEFAULT 'ok',
    `message` VARCHAR(500) NULL,
    `context_json` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
    PRIMARY KEY (`id`),
    KEY `idx_booking_event_intake` (`intake_id`,`created_at`),
    KEY `idx_booking_event_type` (`clinic_id`,`event_type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `calendar_sync_state` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clinic_id` INT UNSIGNED NOT NULL,
    `appointment_id` INT UNSIGNED NOT NULL,
    `google_event_id` VARCHAR(255) NULL,
    `google_etag` VARCHAR(255) NULL,
    `sync_status` ENUM('pending','synced','conflict','error','deleted') NOT NULL DEFAULT 'pending',
    `last_direction` ENUM('push','pull') NULL,
    `last_error` VARCHAR(1000) NULL,
    `last_synced_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
    `updated_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP() ON UPDATE UTC_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_calendar_sync_appointment` (`appointment_id`),
    UNIQUE KEY `uq_calendar_sync_event` (`clinic_id`,`google_event_id`),
    KEY `idx_calendar_sync_status` (`clinic_id`,`sync_status`,`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `calendar_blocks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clinic_id` INT UNSIGNED NOT NULL,
    `google_event_id` VARCHAR(255) NOT NULL,
    `starts_at` DATETIME NOT NULL,
    `ends_at` DATETIME NOT NULL,
    `summary` VARCHAR(255) NULL,
    `google_updated_at` DATETIME NULL,
    `cancelled_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
    `updated_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP() ON UPDATE UTC_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_calendar_block_event` (`clinic_id`,`google_event_id`),
    KEY `idx_calendar_blocks_range` (`clinic_id`,`starts_at`,`ends_at`,`cancelled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `intakes`
    ADD COLUMN IF NOT EXISTS `booking_flow` ENUM('request','scheduled_payment') NOT NULL DEFAULT 'request' AFTER `source_type`,
    ADD COLUMN IF NOT EXISTS `booking_source` ENUM('online','admin') NOT NULL DEFAULT 'online' AFTER `booking_flow`,
    ADD COLUMN IF NOT EXISTS `paid_status` ENUM('unpaid','paid','free') NOT NULL DEFAULT 'unpaid' AFTER `booking_source`,
    ADD COLUMN IF NOT EXISTS `payment_gateway` ENUM('zarinpal','vandar','admin_free') NULL AFTER `paid_status`,
    ADD COLUMN IF NOT EXISTS `payment_id` BIGINT UNSIGNED NULL AFTER `payment_gateway`,
    ADD COLUMN IF NOT EXISTS `slot_start` DATETIME NULL AFTER `payment_id`,
    ADD COLUMN IF NOT EXISTS `slot_duration_minutes` SMALLINT UNSIGNED NULL AFTER `slot_start`,
    ADD COLUMN IF NOT EXISTS `appointment_id` INT UNSIGNED NULL AFTER `slot_duration_minutes`,
    ADD COLUMN IF NOT EXISTS `receptionist_user_id` INT UNSIGNED NULL AFTER `appointment_id`,
    ADD COLUMN IF NOT EXISTS `clinic_confirmation_status` ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending' AFTER `receptionist_user_id`,
    ADD COLUMN IF NOT EXISTS `clinic_confirmed_by` INT UNSIGNED NULL AFTER `clinic_confirmation_status`,
    ADD COLUMN IF NOT EXISTS `clinic_confirmed_at` DATETIME NULL AFTER `clinic_confirmed_by`;

ALTER TABLE `appointments`
    ADD COLUMN IF NOT EXISTS `booking_intake_id` BIGINT UNSIGNED NULL AFTER `patient_id`,
    ADD COLUMN IF NOT EXISTS `source_type` ENUM('staff','online','calendar') NOT NULL DEFAULT 'staff' AFTER `booking_intake_id`;

CREATE INDEX IF NOT EXISTS `idx_intakes_booking_slot` ON `intakes` (`clinic_id`,`slot_start`,`paid_status`);
CREATE INDEX IF NOT EXISTS `idx_intakes_booking_payment` ON `intakes` (`payment_id`);
CREATE INDEX IF NOT EXISTS `idx_appointments_booking_intake` ON `appointments` (`booking_intake_id`);
