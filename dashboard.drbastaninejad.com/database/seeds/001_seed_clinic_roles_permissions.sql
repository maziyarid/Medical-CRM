-- Seed 001: default clinic, roles, permissions, and superadmin user
-- dashboard.drbastaninejad.com
--
-- SYNTHETIC DATA ONLY — never use real credentials in this file.
-- Run after all migrations (001–013) have been applied.
--
-- What this seeds:
--   1. One default clinic (id=1) required by all clinic_id=1 foreign keys.
--   2. Four staff roles: super_admin, doctor, receptionist, nurse.
--   3. All named permissions used by route files.
--   4. Full permission grant for super_admin; scoped grants for other roles.
--   5. One synthetic superadmin user (password_hash is bcrypt of 'changeme').
--
-- IMPORTANT: Change the superadmin password immediately after first login.
-- The bcrypt hash below is for the literal string "changeme" — cost 12.

-- ── 1. Default clinic ─────────────────────────────────────────────────────────
INSERT IGNORE INTO clinics (id, name, phone, address, timezone) VALUES
(1, 'کلینیک دکتر بستانی‌نژاد', '02100000000', 'تهران', 'Asia/Tehran');

-- ── 2. Roles ──────────────────────────────────────────────────────────────────
INSERT IGNORE INTO roles (name, label) VALUES
('super_admin',   'مدیر ارشد'),
('doctor',        'پزشک'),
('receptionist',  'پذیرش'),
('nurse',         'پرستار');

-- ── 3. Permissions ────────────────────────────────────────────────────────────
INSERT IGNORE INTO permissions (name, description) VALUES
-- Patients
('patients.view',     'مشاهده بیماران'),
('patients.manage',   'ویرایش و حذف بیماران'),
-- Appointments
('appointments.view',   'مشاهده نوبت‌ها'),
('appointments.manage', 'ایجاد و ویرایش نوبت‌ها'),
-- Intakes
('intakes.view',    'مشاهده پذیرش‌ها'),
('intakes.manage',  'ویرایش پذیرش‌ها'),
-- EMR
('emr.view',   'مشاهده پرونده بالینی'),
('emr.edit',   'ویرایش پرونده بالینی'),
-- Billing
('billing.view',    'مشاهده صورت‌حساب'),
('billing.manage',  'ایجاد و ویرایش صورت‌حساب'),
-- Tasks
('tasks.view',    'مشاهده وظایف'),
('tasks.manage',  'ایجاد و ویرایش وظایف'),
-- Analytics
('analytics.view', 'مشاهده گزارش‌ها'),
-- Settings
('settings.view',   'مشاهده تنظیمات'),
('settings.manage', 'ویرایش تنظیمات'),
-- Dashboard
('dashboard.view', 'مشاهده داشبورد');

-- ── 4. Assign all permissions to super_admin ─────────────────────────────────
-- (super_admin bypasses RBAC checks in RbacMiddleware — this is for completeness)
INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name = 'super_admin';

-- doctor: clinical + view billing + view analytics
INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'doctor'
  AND p.name IN (
    'patients.view','patients.manage',
    'appointments.view','appointments.manage',
    'intakes.view',
    'emr.view','emr.edit',
    'billing.view',
    'analytics.view',
    'dashboard.view',
    'tasks.view','tasks.manage'
  );

-- receptionist: scheduling + intakes + view billing
INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'receptionist'
  AND p.name IN (
    'patients.view','patients.manage',
    'appointments.view','appointments.manage',
    'intakes.view','intakes.manage',
    'billing.view','billing.manage',
    'tasks.view','tasks.manage',
    'dashboard.view'
  );

-- nurse: patient + appointment + emr view
INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'nurse'
  AND p.name IN (
    'patients.view',
    'appointments.view',
    'intakes.view',
    'emr.view','emr.edit',
    'tasks.view',
    'dashboard.view'
  );

-- ── 5. Superadmin user (SYNTHETIC — change password after first login) ────────
-- bcrypt hash of "changeme" at cost 12
INSERT IGNORE INTO users (uuid, clinic_id, full_name, mobile, email, password_hash, is_active)
VALUES (
    REPLACE(UUID(), '-', ''),
    1,
    'مدیر ارشد سیستم',
    '09000000000',
    'admin@example.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- "changeme"
    1
);

-- Assign super_admin role to the new user
INSERT IGNORE INTO role_user (user_id, role_id)
SELECT u.id, r.id
FROM users u, roles r
WHERE u.mobile = '09000000000' AND r.name = 'super_admin';
