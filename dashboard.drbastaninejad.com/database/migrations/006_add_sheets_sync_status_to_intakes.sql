-- Migration 006 — sheets_sync_status column on intakes table
-- Idempotent via ADD COLUMN IF NOT EXISTS.
-- Implements §6.3 of UNIFIED_MASTER_PLAN.md (Phase C addendum).
--
-- sheets_sync_status tracks the outcome of the Google Sheets dual-write
-- independently from the DB transaction so that failed/pending rows can
-- be identified and retried without re-submitting the form.
--
-- Values:
--   'pending'  — DB row committed; Sheets write not yet attempted.
--                Should not persist in production (a process-kill artefact).
--   'ok'       — Sheets append confirmed (API returned "updates" key).
--   'failed'   — Sheets throw was caught; row is in DB but NOT in Sheet.
--   'skipped'  — GOOGLE_SHEET_ID / GOOGLE_SA_KEY_PATH not configured.
--                Expected in dev/staging; never happens in production.
--
-- Index allows a reconciliation job to page through failed/pending rows.

ALTER TABLE `intakes`
    ADD COLUMN IF NOT EXISTS `sheets_sync_status`
        ENUM('pending','ok','failed','skipped') NOT NULL DEFAULT 'pending'
        COMMENT 'Outcome of Google Sheets dual-write (§6.3 UNIFIED_MASTER_PLAN)'
        AFTER `updated_at`;

ALTER TABLE `intakes`
    ADD INDEX IF NOT EXISTS `idx_intakes_sheets_sync`
        (`sheets_sync_status`, `created_at`);
