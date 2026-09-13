-- Migration 028: audit who completed the post-payment/post-confirmation staff follow-up.

ALTER TABLE appointment_booking_requests
    ADD COLUMN followup_completed_at DATETIME NULL AFTER staff_followup_required,
    ADD COLUMN followup_completed_by INT UNSIGNED NULL AFTER followup_completed_at,
    ADD INDEX idx_booking_followup_queue (clinic_id, staff_followup_required, confirmation_status),
    ADD CONSTRAINT fk_booking_followup_user FOREIGN KEY (followup_completed_by) REFERENCES users(id) ON DELETE SET NULL;
