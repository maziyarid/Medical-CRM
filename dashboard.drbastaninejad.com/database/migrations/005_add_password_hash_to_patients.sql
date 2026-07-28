-- Migration 005 — add patient portal auth columns to patients table
-- Idempotent via ADD COLUMN IF NOT EXISTS.
-- Implements Phase 3 of UNIFIED_MASTER_PLAN.md:
--   "Add password_hash, email_verified_at to the existing patients table
--    (not a new table)."
--
-- password_hash    : argon2id (or bcrypt fallback) for password-based login.
--                    NULL until the patient completes account-setup flow.
-- email            : patient email address (separate from intakes.email which
--                    is the intake-submission email; this is the canonical account
--                    email on the patient row).
-- email_verified_at: timestamp when email ownership was confirmed.
-- marketing_email_optin: explicit opt-in for marketing emails (GDPR/CASL pattern).

ALTER TABLE `patients`
    ADD COLUMN IF NOT EXISTS `email`                VARCHAR(191) NULL DEFAULT NULL
        COMMENT 'Patient account email (login / notifications)'
        AFTER `mobile`,
    ADD COLUMN IF NOT EXISTS `email_verified_at`    TIMESTAMP    NULL DEFAULT NULL
        COMMENT 'When email address was verified'
        AFTER `email`,
    ADD COLUMN IF NOT EXISTS `password_hash`        VARCHAR(255) NULL DEFAULT NULL
        COMMENT 'argon2id hash — NULL until account-setup flow completes'
        AFTER `email_verified_at`,
    ADD COLUMN IF NOT EXISTS `marketing_email_optin` BOOLEAN NOT NULL DEFAULT 0
        COMMENT 'Explicit marketing email opt-in (default off)'
        AFTER `password_hash`;

-- Unique index on email so we can enforce one account per address
ALTER TABLE `patients`
    ADD UNIQUE INDEX IF NOT EXISTS `uq_patients_email` (`email`);
