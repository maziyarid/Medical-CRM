-- Booking OTP grants are one-time and are consumed in the same transaction as the booking.
CREATE TABLE IF NOT EXISTS `booking_verifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mobile` VARCHAR(15) NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `consumed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_booking_verification_token` (`token_hash`),
    KEY `idx_booking_verification_mobile` (`mobile`,`consumed_at`,`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `intakes`
    ADD COLUMN IF NOT EXISTS `booking_sheet_status`
        ENUM('pending','attempting','submitted','failed_confirmed','outcome_unknown','skipped')
        NOT NULL DEFAULT 'pending' AFTER `sheets_sync_status`,
    ADD COLUMN IF NOT EXISTS `booking_email_status`
        ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending' AFTER `booking_sheet_status`;
