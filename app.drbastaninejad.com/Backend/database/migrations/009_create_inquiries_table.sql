-- Migration 009 — Create inquiries table
-- app.drbastaninejad.com / MΛZ Medical CRM
--
-- Backs the POST /api/v1/inquiries endpoint for the drbastaninejad.com
-- contact form. Inquiries are unauthenticated submissions from the
-- public marketing site; they contain no medical/clinical data.
--
-- No OTP or national-ID required; only name, phone, message.
-- Rate-limiting is enforced at the application layer (InquiryController).

CREATE TABLE IF NOT EXISTS inquiries (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    phone       VARCHAR(15)  NOT NULL COMMENT 'Normalised 09XXXXXXXXX',
    message     TEXT         NOT NULL,
    ip_address  VARCHAR(45)  NULL      COMMENT 'For rate-limit audit only; redact after 90 days',
    status      ENUM('new','read','replied','spam') NOT NULL DEFAULT 'new',
    created_at  DATETIME     NOT NULL DEFAULT (UTC_TIMESTAMP()),

    INDEX idx_phone      (phone),
    INDEX idx_status     (status),
    INDEX idx_created_at (created_at)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
