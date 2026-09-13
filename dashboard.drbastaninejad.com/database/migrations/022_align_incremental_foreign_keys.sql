-- Migration 022 — align incremental foreign keys after mixed 003+014 histories.
-- Adds FKs only when the constraint name is absent. intakes.patient_id is BIGINT
-- while patients.id is INT, so that FK is intentionally not added.

DROP PROCEDURE IF EXISTS mazcrm_add_fk_if_missing;
DELIMITER $$
CREATE PROCEDURE mazcrm_add_fk_if_missing(
    IN p_table VARCHAR(64),
    IN p_name VARCHAR(64),
    IN p_ddl TEXT
)
BEGIN
    DECLARE v_cnt INT DEFAULT 0;

    SELECT COUNT(*) INTO v_cnt
      FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = p_table
       AND CONSTRAINT_NAME = p_name
       AND CONSTRAINT_TYPE = 'FOREIGN KEY';

    IF v_cnt = 0 THEN
        SET @ddl = p_ddl;
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL mazcrm_add_fk_if_missing(
    'intakes',
    'fk_intakes_clinic',
    'ALTER TABLE intakes ADD CONSTRAINT fk_intakes_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE'
);

DROP PROCEDURE IF EXISTS mazcrm_add_fk_if_missing;
