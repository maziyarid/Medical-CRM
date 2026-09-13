-- Migration 027: reliable Google Sheet projection for appointment booking lifecycle.
-- MySQL remains the transactional source of truth; ScheduledVisits is an
-- operational mirror that is retried independently and updated idempotently.
-- This migration is intended to be applied once by the normal migration runner.
-- AppointmentSchemaBootstrapService performs equivalent column/index checks for
-- file-only production deployments where migrations are not executed.

ALTER TABLE appointment_booking_requests
    ADD COLUMN sheet_sync_status ENUM('pending','synced','error','skipped') NOT NULL DEFAULT 'pending' AFTER staff_followup_required,
    ADD COLUMN sheet_synced_at DATETIME NULL AFTER sheet_sync_status,
    ADD COLUMN sheet_sync_error VARCHAR(500) NULL AFTER sheet_synced_at,
    ADD INDEX idx_booking_sheet_sync (sheet_sync_status, updated_at);

INSERT IGNORE INTO permissions (name, description)
VALUES ('booking.integrations.manage', 'Manage appointment integration credentials');

INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.name = 'booking.integrations.manage'
WHERE r.name = 'super_admin';
