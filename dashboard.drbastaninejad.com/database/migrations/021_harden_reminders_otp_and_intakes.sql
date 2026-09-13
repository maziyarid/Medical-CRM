-- Migration 021 — harden reminders, OTP, intakes (Checkpoint 3 recovery).
-- Numbers jump 017 → 021 because recovered work referenced 021/022; 018–020 were
-- never published on public main. Additive: skip any index that already exists.

DROP PROCEDURE IF EXISTS mazcrm_add_index_if_missing;
DELIMITER $$
CREATE PROCEDURE mazcrm_add_index_if_missing(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_ddl TEXT
)
BEGIN
    DECLARE v_cnt INT DEFAULT 0;
    SELECT COUNT(*) INTO v_cnt
      FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = p_table
       AND INDEX_NAME = p_index;
    IF v_cnt = 0 THEN
        SET @ddl = p_ddl;
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL mazcrm_add_index_if_missing(
    'appointments',
    'idx_appointments_clinic_room_sched',
    'ALTER TABLE appointments ADD INDEX idx_appointments_clinic_room_sched (clinic_id, room, scheduled_at)'
);

-- 001 already has uk_submission_uuid; keep a named unique if a mixed history dropped it.
CALL mazcrm_add_index_if_missing(
    'intakes',
    'uk_submission_uuid',
    'ALTER TABLE intakes ADD UNIQUE INDEX uk_submission_uuid (submission_uuid)'
);

CALL mazcrm_add_index_if_missing(
    'otp_codes',
    'idx_otp_codes_expires',
    'ALTER TABLE otp_codes ADD INDEX idx_otp_codes_expires (expires_at)'
);

CALL mazcrm_add_index_if_missing(
    'reminder_log',
    'idx_reminder_appt_status',
    'ALTER TABLE reminder_log ADD INDEX idx_reminder_appt_status (appointment_id, status, remind_at)'
);

DROP PROCEDURE IF EXISTS mazcrm_add_index_if_missing;
