-- Migration 004 — add email + visit_reason columns to intakes table
-- Idempotent via IF NOT EXISTS / column existence check pattern.
-- Adds the two fields that the live intake form already captures
-- (introduced by the frontend in a prior fix session) but that had
-- nowhere to land in the DB schema.
-- Per UNIFIED_MASTER_PLAN.md §1 and §3 Phase 1.

ALTER TABLE `intakes`
    ADD COLUMN IF NOT EXISTS `email`        VARCHAR(120) NULL DEFAULT NULL
        COMMENT 'Patient email captured on intake form (optional)'
        AFTER `insurance_type`,
    ADD COLUMN IF NOT EXISTS `visit_reason` VARCHAR(120) NULL DEFAULT NULL
        COMMENT 'Short visit-reason label (علت مراجعه) distinct from chief_complaint'
        AFTER `email`;

-- Index for email lookups (staff search by email)
ALTER TABLE `intakes`
    ADD INDEX IF NOT EXISTS `idx_intakes_email` (`email`);
