-- Migration 024: paid appointment booking, availability and Calendar integration
-- MySQL remains the transactional source of truth. Google Calendar is an integration
-- bridge to the clinic's DevExpress desktop scheduler, never the sole booking store.
--
-- intake_id intentionally has no foreign key here. Historical installations use
-- both INT and BIGINT for intakes.id; keeping this link as BIGINT without an FK
-- makes the additive migration safe across both histories while preserving the
-- authoritative clinic/patient/open-day/appointment foreign keys.

CREATE TABLE IF NOT EXISTS appointment_open_days (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id             INT UNSIGNED NOT NULL,
    open_date             DATE NOT NULL,
    capacity              SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    held_count            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    booked_count          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    slot_duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    opens_at              TIME NULL,
    closes_at             TIME NULL,
    status                ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_by            INT UNSIGNED NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_open_day_clinic_date (clinic_id, open_date),
    KEY idx_open_days_status_date (clinic_id, status, open_date),
    CONSTRAINT fk_open_day_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    CONSTRAINT fk_open_day_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_open_day_counts CHECK (held_count + booked_count <= capacity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointment_booking_requests (
    id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid                   CHAR(36) NOT NULL,
    submission_uuid        VARCHAR(64) NULL,
    clinic_id              INT UNSIGNED NOT NULL,
    patient_id             INT UNSIGNED NOT NULL,
    intake_id              BIGINT UNSIGNED NULL,
    open_day_id            INT UNSIGNED NOT NULL,
    appointment_id         INT UNSIGNED NULL,
    requested_start_at     DATETIME NOT NULL,
    duration_minutes       SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    source                 ENUM('online','admin') NOT NULL,
    payment_gateway        ENUM('zarinpal','vandar','none') NULL,
    payment_status         ENUM('unpaid','pending','paid','free','failed','refunded') NOT NULL DEFAULT 'unpaid',
    confirmation_status    ENUM('holding','awaiting_payment','paid_pending_staff','confirmed','expired','cancelled') NOT NULL DEFAULT 'holding',
    receptionist_user_id   INT UNSIGNED NULL,
    amount_rials           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    slot_claim_key         CHAR(64) NULL,
    hold_expires_at        DATETIME NULL,
    staff_followup_required TINYINT(1) NOT NULL DEFAULT 1,
    confirmed_at           DATETIME NULL,
    cancelled_at           DATETIME NULL,
    created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_booking_request_uuid (uuid),
    UNIQUE KEY uq_booking_submission_uuid (submission_uuid),
    UNIQUE KEY uq_booking_active_slot_claim (slot_claim_key),
    KEY idx_booking_patient (patient_id, created_at),
    KEY idx_booking_day_status (open_day_id, confirmation_status),
    KEY idx_booking_payment (payment_status, payment_gateway),
    KEY idx_booking_hold_expiry (confirmation_status, hold_expires_at),
    CONSTRAINT fk_booking_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_open_day FOREIGN KEY (open_day_id) REFERENCES appointment_open_days(id) ON DELETE RESTRICT,
    CONSTRAINT fk_booking_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    CONSTRAINT fk_booking_receptionist FOREIGN KEY (receptionist_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointment_payment_attempts (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_request_id    BIGINT UNSIGNED NOT NULL,
    gateway               ENUM('zarinpal','vandar') NOT NULL,
    amount_rials          BIGINT UNSIGNED NOT NULL,
    currency              CHAR(3) NOT NULL DEFAULT 'IRR',
    status                ENUM('created','redirected','verified','failed','cancelled','refunded') NOT NULL DEFAULT 'created',
    idempotency_key       CHAR(64) NOT NULL,
    authority             VARCHAR(191) NULL,
    transaction_ref       VARCHAR(191) NULL,
    gateway_code          VARCHAR(64) NULL,
    request_payload       JSON NULL,
    response_payload      JSON NULL,
    verified_at           DATETIME NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_idempotency (idempotency_key),
    UNIQUE KEY uq_payment_gateway_authority (gateway, authority),
    KEY idx_payment_booking_status (booking_request_id, status),
    CONSTRAINT fk_payment_booking FOREIGN KEY (booking_request_id) REFERENCES appointment_booking_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendar_event_links (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id             INT UNSIGNED NOT NULL,
    appointment_id        INT UNSIGNED NULL,
    booking_request_id    BIGINT UNSIGNED NULL,
    google_calendar_id    VARCHAR(255) NOT NULL,
    google_event_id       VARCHAR(255) NOT NULL,
    google_etag           VARCHAR(255) NULL,
    sync_status           ENUM('pending','synced','conflict','deleted','error') NOT NULL DEFAULT 'pending',
    remote_updated_at     DATETIME NULL,
    last_synced_at        DATETIME NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_calendar_remote_event (google_calendar_id, google_event_id),
    UNIQUE KEY uq_calendar_appointment (appointment_id),
    KEY idx_calendar_booking (booking_request_id),
    KEY idx_calendar_sync_status (clinic_id, sync_status),
    CONSTRAINT fk_calendar_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    CONSTRAINT fk_calendar_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    CONSTRAINT fk_calendar_booking FOREIGN KEY (booking_request_id) REFERENCES appointment_booking_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendar_sync_outbox (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id             INT UNSIGNED NOT NULL,
    aggregate_type        ENUM('booking','appointment','open_day') NOT NULL,
    aggregate_id          BIGINT UNSIGNED NOT NULL,
    action                ENUM('upsert','delete','import') NOT NULL,
    idempotency_key       CHAR(64) NOT NULL,
    payload               JSON NOT NULL,
    status                ENUM('pending','processing','succeeded','failed','dead') NOT NULL DEFAULT 'pending',
    attempts              SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    next_attempt_at       DATETIME NULL,
    locked_at             DATETIME NULL,
    last_error            TEXT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_calendar_outbox_idempotency (idempotency_key),
    KEY idx_calendar_outbox_due (status, next_attempt_at),
    KEY idx_calendar_outbox_clinic (clinic_id, created_at),
    CONSTRAINT fk_calendar_outbox_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (name, description) VALUES
    ('booking.manage', 'View and manage appointment booking requests'),
    ('booking.availability.manage', 'Manage clinic open days, slots and quotas'),
    ('booking.payments.reconcile', 'Reconcile appointment payment attempts'),
    ('calendar.sync.manage', 'Manage Google Calendar synchronisation');
