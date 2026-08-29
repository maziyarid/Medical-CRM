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

-- Migration 001 defaults new intakes to clinic 1. Ensure that parent exists
-- before validating the FK; other orphan IDs are rejected explicitly below.
INSERT INTO clinics (id, name)
SELECT 1, 'Default clinic'
WHERE NOT EXISTS (SELECT 1 FROM clinics WHERE id = 1);

DROP PROCEDURE IF EXISTS mazcrm_check_intake_clinic_orphans;
DELIMITER $$
CREATE PROCEDURE mazcrm_check_intake_clinic_orphans()
BEGIN
    IF EXISTS (SELECT 1 FROM intakes i LEFT JOIN clinics c ON c.id = i.clinic_id WHERE c.id IS NULL) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot add fk_intakes_clinic: intakes contain unknown clinic_id values';
    END IF;
END$$
DELIMITER ;
CALL mazcrm_check_intake_clinic_orphans();
DROP PROCEDURE IF EXISTS mazcrm_check_intake_clinic_orphans;

CALL mazcrm_add_fk_if_missing(
    'intakes',
    'fk_intakes_clinic',
    'ALTER TABLE intakes ADD CONSTRAINT fk_intakes_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE'
);

DROP PROCEDURE IF EXISTS mazcrm_add_fk_if_missing;
