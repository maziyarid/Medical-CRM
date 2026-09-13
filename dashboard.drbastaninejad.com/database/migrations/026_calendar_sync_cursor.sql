-- Migration 026: per-clinic Google Calendar incremental sync cursor

CREATE TABLE IF NOT EXISTS calendar_sync_cursors (
    clinic_id          INT UNSIGNED NOT NULL,
    google_calendar_id VARCHAR(255) NOT NULL,
    sync_token         TEXT NULL,
    last_full_sync_at  DATETIME NULL,
    last_sync_at       DATETIME NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (clinic_id, google_calendar_id),
    CONSTRAINT fk_calendar_cursor_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
