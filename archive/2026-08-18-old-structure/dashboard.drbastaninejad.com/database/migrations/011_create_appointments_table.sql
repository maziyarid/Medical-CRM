-- Migration 011: appointments table
-- dashboard.drbastaninejad.com
--
-- This table is used by AppointmentController (Smart Scheduling Calendar §5.6),
-- DashboardController (getTimelineEvents), and AnalyticsController.
--
-- Column notes:
--   provider_id       — FK to users (treating provider / doctor); nullable to
--                       support clinics that do not track per-provider scheduling yet.
--   visit_reason      — free-text chief-reason shown on calendar event tile and
--                       DashboardController timeline title.
--   room              — optional treatment room label (e.g. "اتاق ۱").
--   duration_minutes  — slot length in minutes; used for conflict detection in
--                       Appointment::hasConflict() and calendar rendering.
--   notes             — internal staff notes (not visible to patient portal).
--   deleted_at        — soft-delete; all read queries filter deleted_at IS NULL.
--   cancellation_reason — free-text reason recorded when status → cancelled
--                         (AppointmentController::destroy / updateStatus).
--
-- Foreign keys reference the shared `patients` table.
-- The clinics table is created in migration 010.

CREATE TABLE IF NOT EXISTS appointments (
    id                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    uuid                 CHAR(32)        NOT NULL,
    clinic_id            INT UNSIGNED    NOT NULL,
    patient_id           INT UNSIGNED    NOT NULL,
    provider_id          INT UNSIGNED    NULL,
    scheduled_at         DATETIME        NOT NULL,
    duration_minutes     SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    visit_reason         VARCHAR(255)    NULL,
    room                 VARCHAR(64)     NULL,
    status               ENUM('scheduled','confirmed','cancelled','completed')
                             NOT NULL DEFAULT 'scheduled',
    notes                TEXT            NULL,
    cancellation_reason  TEXT            NULL,
    deleted_at           DATETIME        NULL,
    created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                             ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE  KEY uq_appointments_uuid          (uuid),
    INDEX       idx_appointments_clinic_sched (clinic_id, scheduled_at),
    INDEX       idx_appointments_patient      (patient_id),
    INDEX       idx_appointments_provider     (provider_id),
    INDEX       idx_appointments_deleted      (deleted_at),

    CONSTRAINT fk_appointments_clinic
        FOREIGN KEY (clinic_id)  REFERENCES clinics(id)  ON DELETE CASCADE,
    CONSTRAINT fk_appointments_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Scheduled appointments; used by calendar, dashboard, and analytics.';
