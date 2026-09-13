-- Migration 025 — retry-safe payment redirect and rich booking-ledger sync state.
-- Additive; no existing clinical rows are removed or rewritten.

ALTER TABLE `booking_payments`
    ADD COLUMN IF NOT EXISTS `redirect_url` VARCHAR(500) NULL AFTER `gateway_authority`;

ALTER TABLE `intakes`
    ADD COLUMN IF NOT EXISTS `booking_ledger_status`
        ENUM('pending','submitted','failed_confirmed','outcome_unknown','skipped')
        NOT NULL DEFAULT 'pending' AFTER `booking_sheet_status`;
