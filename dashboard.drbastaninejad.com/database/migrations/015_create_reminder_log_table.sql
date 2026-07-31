-- Migration 015: reminder_log table (Phase 5 — Scheduling & Communications)
-- dashboard.drbastaninejad.com
--
-- UNIFIED_MASTER_PLAN.md §7 Phase 5: "Build provider/room scheduling, conflicts,
-- reminders, email templates, and message log."
--
-- reminder_log records every outbound reminder attempt (SMS/email) so that:
--   a) Duplicate reminders are prevented (UNIQUE on appointment_id + channel + remind_at).
--   b) Failed attempts are retried by ReminderService::sendDue() without re-reading
--      appointments (the remind_at window is pre-computed at booking time).
--   c) Audit trail is available without exposing patient PII in application logs.
--
-- Column notes:
--   appointment_id — FK to appointments; CASCADE delete cleans up when appointment cancelled.
--   patient_id     — denormalised for efficient per-patient reminder queries; NOT a second
--                    patient table (UNIFIED_MASTER_PLAN §2 — forbidden).
--   channel        — 'sms' | 'email'; extensible via ALTER TABLE for future channels.
--   remind_at      — UTC datetime when the reminder should be dispatched; pre-computed
--                    as (scheduled_at - remind_offset_minutes) at booking time.
--   sent_at        — NULL until successfully dispatched; set by ReminderService::markSent().
--   status         — 'pending' → 'sent' | 'failed'. failed rows are retried up to max_attempts.
--   attempts       — incremented on each send attempt; stops retrying at max_attempts.
--   max_attempts   — default 3; configurable per row to allow one-off override.
--   provider       — SMS/email provider name that successfully dispatched (audit).
--   error_message  — last error string on failure (no PII — provider message only).
--
-- Uniqueness: one pending reminder per appointment × channel × offset.
-- If the appointment is rescheduled, ReminderService cancels the old reminder rows
-- (status='cancelled') and inserts new ones.

CREATE TABLE IF NOT EXISTS reminder_log (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    appointment_id      INT UNSIGNED    NOT NULL,
    patient_id          INT UNSIGNED    NOT NULL,
    clinic_id           INT UNSIGNED    NOT NULL,
    channel             ENUM('sms','email')
                            NOT NULL DEFAULT 'sms',
    remind_at           DATETIME        NOT NULL    COMMENT 'UTC; pre-computed at booking time',
    remind_offset_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60
                            COMMENT 'Minutes before scheduled_at this reminder fires',
    status              ENUM('pending','sent','failed','cancelled')
                            NOT NULL DEFAULT 'pending',
    attempts            TINYINT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts        TINYINT UNSIGNED NOT NULL DEFAULT 3,
    sent_at             DATETIME        NULL,
    provider            VARCHAR(64)     NULL        COMMENT 'Provider that dispatched (audit)',
    error_message       VARCHAR(512)    NULL        COMMENT 'Last error — no patient PII',
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                            ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    -- Prevent duplicate reminders for the same appointment + channel + offset
    UNIQUE KEY uq_reminder_appt_channel_offset (appointment_id, channel, remind_offset_minutes),
    INDEX idx_reminder_due    (status, remind_at),
    INDEX idx_reminder_appt   (appointment_id),
    INDEX idx_reminder_clinic (clinic_id),

    CONSTRAINT fk_reminder_appointment
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    CONSTRAINT fk_reminder_patient
        FOREIGN KEY (patient_id)     REFERENCES patients(id)     ON DELETE CASCADE,
    CONSTRAINT fk_reminder_clinic
        FOREIGN KEY (clinic_id)      REFERENCES clinics(id)      ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Outbound appointment reminders — SMS and email. Phase 5.';
