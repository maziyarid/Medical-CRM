-- Migration 007 — birth_date_jalali column on intakes table
-- Idempotent via ADD COLUMN IF NOT EXISTS.
--
-- Context:
--   IntakeController stores the Jalali birth date string as-is from the frontend
--   in the patients table for audit purposes, but the intakes table only stores
--   the converted Gregorian date in `birth_date`.  The `runSheetsSync()` re-try
--   path (idempotent 200 response) needs to reconstruct the original row for
--   Sheets column F (`birth_date`), which is already available as `birth_date`
--   (Gregorian Y-m-d).
--
--   However, storing the original Jalali string on the intakes row provides:
--   1. A complete audit trail — what the patient actually typed is preserved.
--   2. Staff UI display — the intake review queue can show the Jalali date
--      without having to convert back from Gregorian (lossy for edge cases).
--   3. Alignment with the 22-table target schema (docs/SCHEMA.md §3.4), which
--      includes a `birth_date_jalali` field on the intake record.
--
--   The frontend sends `birthDate` (camelCase); IntakeController::normalisePayload()
--   maps this to `birth_date_jalali` (§6.1 UNIFIED_MASTER_PLAN.md).
--   IntakeController::store() currently writes it to patients but not to intakes.
--   This migration adds the column so the controller can persist it on intakes too.
--
-- Value format: VARCHAR(10), e.g. "1370/05/12" — the raw Jalali string as
--   entered by the patient (after Persian-digit normalisation).  NULL if the
--   form was submitted before this migration ran (backward-compatible).

ALTER TABLE `intakes`
    ADD COLUMN IF NOT EXISTS `birth_date_jalali`
        VARCHAR(10) NULL
        COMMENT 'Original Jalali birth date as submitted (e.g. 1370/05/12)'
        AFTER `birth_date`;
