-- Optional metadata seed for an existing schema.
-- Production-safe: clinic/RBAC metadata only; NO patient/staff users or credentials.
-- For a fresh database prefer database/install/drbastaninejad_dash_clean_install.sql.

INSERT INTO clinics (id, name, timezone)
VALUES (1, 'کلینیک دکتر شاهین باستانی‌نژاد', 'Asia/Tehran')
ON DUPLICATE KEY UPDATE id = VALUES(id);

INSERT IGNORE INTO roles (name, label) VALUES
('super_admin','مدیر کل'),('doctor','پزشک'),('receptionist','پذیرش'),('nurse','پرستار');

INSERT IGNORE INTO permissions (name, description) VALUES
('dashboard.view','View dashboard'),
('patients.view','View patients'),('patients.manage','Manage patients'),
('appointments.view','View appointments'),('appointments.manage','Manage appointments'),
('emr.view','View EMR'),('emr.edit','Edit EMR'),
('billing.view','View billing'),('billing.manage','Manage billing'),
('tasks.view','View tasks'),('tasks.manage','Manage tasks'),
('analytics.view','View analytics'),
('settings.view','View settings'),('settings.manage','Manage settings'),
('intakes.view','View intake/booking queue'),('intakes.manage','Review intake/booking queue'),
('patient.portal','Patient portal scope marker');

-- super_admin is intentionally the only role granted here. Application middleware
-- bypasses named permission checks for super_admin; this explicit grant keeps RBAC
-- inspection complete without guessing clinic policy for doctor/receptionist/nurse.
INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'super_admin';

-- No user is inserted. Bootstrap the first real staff identity through your secure
-- administration procedure and assign super_admin explicitly.
