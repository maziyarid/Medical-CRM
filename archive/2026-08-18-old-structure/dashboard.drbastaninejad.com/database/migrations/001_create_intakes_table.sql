-- Migration 001 — intakes table
-- Idempotent (uses IF NOT EXISTS).
-- Do NOT create a second patients table — patients already defined in SCHEMA.md.

CREATE TABLE IF NOT EXISTS `intakes` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `submission_uuid`  VARCHAR(64)  NOT NULL,          -- client-supplied idempotency key
    `clinic_id`        INT UNSIGNED NOT NULL DEFAULT 1,
    `patient_id`       BIGINT UNSIGNED NULL,           -- FK → patients.id, set after upsert
    `patient_uuid`     CHAR(32) NOT NULL DEFAULT '',   -- denormalised for fast API lookups
    `first_name`       VARCHAR(100) NOT NULL,
    `last_name`        VARCHAR(100) NOT NULL,
    `mobile`           VARCHAR(15)  NOT NULL,
    `national_id`      VARCHAR(10)  NOT NULL,
    `birth_date`       DATE         NULL,              -- stored as Gregorian after server-side conversion
    `chief_complaint`  TEXT         NOT NULL,
    `service_type`     VARCHAR(100) NULL,
    `preferred_date`   DATE         NULL,
    `insurance_type`   VARCHAR(100) NULL,
    `raw_payload`      JSON         NULL,              -- full original submission, nothing lost
    `status`           ENUM('pending','reviewed','converted','rejected') NOT NULL DEFAULT 'pending',
    `reviewed_by`      INT UNSIGNED NULL,
    `reviewed_at`      DATETIME     NULL,
    `deleted_at`       DATETIME     NULL,
    `created_at`       DATETIME     NOT NULL DEFAULT UTC_TIMESTAMP(),
    `updated_at`       DATETIME     NOT NULL DEFAULT UTC_TIMESTAMP() ON UPDATE UTC_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_submission_uuid` (`submission_uuid`),   -- idempotency enforcement at DB level
    KEY `idx_intakes_clinic_status` (`clinic_id`, `status`),
    KEY `idx_intakes_mobile` (`mobile`),
    KEY `idx_intakes_national_id` (`national_id`),
    KEY `idx_intakes_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
