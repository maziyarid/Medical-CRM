-- Migration: 011_create_emr_patient_records_view.sql
-- Purpose: Expose a read-only view of EMR notes scoped to a patient
--          for GET /api/v1/patient/records (app.drbastaninejad.com backend).
-- Author: MAZ//ID — © 2026
-- Note: Assumes emr_records table exists in the shared DB schema,
--       mirrored from the dashboard backend. If on a separate DB,
--       run after ensuring the emr_records table is present.

-- ── emr_records table (creates if not exists; no-op if already present) ───────
CREATE TABLE IF NOT EXISTS `emr_records` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `clinic_id`     INT UNSIGNED     NOT NULL DEFAULT 1,
  `patient_id`    INT UNSIGNED     NOT NULL,
  `author_id`     INT UNSIGNED     NOT NULL DEFAULT 0 COMMENT 'Staff user who wrote the note',
  `author_name`   VARCHAR(120)     NOT NULL DEFAULT '' COMMENT 'Denormalised author name for display',
  `visit_type`    VARCHAR(60)      NOT NULL DEFAULT 'consultation'
                  COMMENT 'e.g. consultation, pre_op, post_op, follow_up',
  `subjective`    TEXT             NULL,
  `objective`     TEXT             NULL,
  `assessment`    TEXT             NULL,
  `plan`          TEXT             NULL,
  `is_draft`      TINYINT(1)       NOT NULL DEFAULT 0,
  `is_signed`     TINYINT(1)       NOT NULL DEFAULT 0,
  `signed_at`     DATETIME         NULL,
  `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emr_patient` (`patient_id`, `created_at`),
  KEY `idx_emr_clinic`  (`clinic_id`, `patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
