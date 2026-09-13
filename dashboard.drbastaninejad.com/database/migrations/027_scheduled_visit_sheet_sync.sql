-- Migration 027: reliable Google Sheet projection for appointment booking lifecycle.
-- MySQL remains the transactional source of truth; ScheduledVisits is an
-- operational mirror that is retried independently and updated idempotently.

ALTER TABLE appointment_booking_requests
    ADD COLUMN IF NOT EXISTS sheet_sync_status ENUM('pending','synced','error','skipped') NOT NULL DEFAULT 'pending' AFTER staff_followup_required,
    ADD COLUMN IF NOT EXISTS sheet_synced_at DATETIME NULL AFTER sheet_sync_status,
    ADD COLUMN IF NOT EXISTS sheet_sync_error VARCHAR(500) NULL AFTER sheet_synced_at;

CREATE INDEX IF NOT EXISTS idx_booking_sheet_sync
    ON appointment_booking_requests (sheet_sync_status, updated_at);
