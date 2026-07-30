-- Migration 008 — Add home_tel to patients table
-- app.drbastaninejad.com / MΛZ Medical CRM
--
-- home_tel was referenced by PatientPortalController::profile() and
-- PatientModel::findById() but was not present in migration 003.
-- Added as a separate migration to preserve applied migration history.

ALTER TABLE patients
    ADD COLUMN home_tel VARCHAR(15) NULL COMMENT 'Home/landline number — optional'
        AFTER home_address;
