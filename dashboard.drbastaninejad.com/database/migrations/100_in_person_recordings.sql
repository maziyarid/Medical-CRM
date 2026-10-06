-- Migration 100: private in-person recording sessions, chunks, transcription jobs and immutable transcript versions.
-- RPH-137. Additive only; no existing patient/EMR rows are modified.

CREATE TABLE IF NOT EXISTS recording_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(32) NOT NULL,
    clinic_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    status ENUM('ready','recording','paused','finalizing','queued','processing','review_required','approved','withdrawn','failed','cancelled') NOT NULL DEFAULT 'ready',
    consent_scope VARCHAR(64) NOT NULL DEFAULT 'recording_transcription',
    consent_policy_version VARCHAR(64) NOT NULL,
    consent_text_sha256 CHAR(64) NOT NULL,
    consent_participants_json LONGTEXT NOT NULL,
    consent_recorded_at DATETIME NOT NULL,
    consent_withdrawn_at DATETIME NULL,
    started_at DATETIME NULL,
    stopped_at DATETIME NULL,
    audio_duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
    expected_chunks INT UNSIGNED NOT NULL DEFAULT 0,
    total_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    source_mime VARCHAR(96) NULL,
    provider VARCHAR(64) NULL,
    provider_model VARCHAR(128) NULL,
    processing_error_code VARCHAR(96) NULL,
    retention_until DATETIME NULL,
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recording_sessions_uuid (uuid),
    KEY idx_recording_sessions_scope (clinic_id, patient_id, created_at),
    KEY idx_recording_sessions_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recording_chunks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    sequence_no INT UNSIGNED NOT NULL,
    start_ms INT UNSIGNED NOT NULL,
    end_ms INT UNSIGNED NOT NULL,
    mime_type VARCHAR(96) NOT NULL,
    byte_length INT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    storage_path VARCHAR(512) NOT NULL,
    stored_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recording_chunk_sequence (session_id, sequence_no),
    KEY idx_recording_chunk_session (session_id, sequence_no),
    CONSTRAINT fk_recording_chunk_session FOREIGN KEY (session_id) REFERENCES recording_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recording_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    job_key VARCHAR(191) NOT NULL,
    kind ENUM('transcribe','review') NOT NULL,
    status ENUM('queued','running','retry','done','failed','cancelled') NOT NULL DEFAULT 'queued',
    provider VARCHAR(64) NULL,
    model VARCHAR(128) NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lease_token CHAR(64) NULL,
    lease_expires_at DATETIME NULL,
    error_code VARCHAR(96) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recording_job_key (job_key),
    KEY idx_recording_jobs_claim (status, available_at, lease_expires_at),
    CONSTRAINT fk_recording_job_session FOREIGN KEY (session_id) REFERENCES recording_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recording_transcript_versions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    version_no INT UNSIGNED NOT NULL,
    kind ENUM('machine','normalised','agent_candidate','consensus','manual','approved') NOT NULL,
    provider VARCHAR(64) NULL,
    model VARCHAR(128) NULL,
    transcript_json LONGTEXT NOT NULL,
    transcript_sha256 CHAR(64) NOT NULL,
    created_by INT UNSIGNED NULL,
    source_version_no INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recording_transcript_version (session_id, version_no),
    KEY idx_recording_transcript_session (session_id, created_at),
    CONSTRAINT fk_recording_transcript_session FOREIGN KEY (session_id) REFERENCES recording_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (name, description)
VALUES ('recordings.manage', 'Create, listen to, transcribe and review private in-person clinical recordings');

INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.name = 'recordings.manage'
WHERE r.name = 'super_admin';
