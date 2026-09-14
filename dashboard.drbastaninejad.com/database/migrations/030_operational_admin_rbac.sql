-- Migration 030: durable operational staff RBAC
-- Keeps super_admin as system owner while making admin/reception/clinical roles usable.

INSERT INTO roles (name, label, created_at)
SELECT 'admin', 'مدیر', UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'admin');

INSERT INTO permissions (name, description, created_at)
SELECT 'booking.fee.manage', 'Update the public appointment visit/deposit fee', UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'booking.fee.manage');

-- System owner always receives every permission.
INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'super_admin';

-- Operational admin: broad clinic operations, but no staff ownership, integration secrets or calendar-sync administration.
INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.name = 'admin'
  AND p.name IN (
    'dashboard.view','patients.view','patients.manage','appointments.view','appointments.manage',
    'emr.view','emr.edit','billing.view','billing.manage','tasks.view','tasks.manage','analytics.view',
    'settings.view','settings.manage','intakes.view','intakes.manage','booking.manage',
    'booking.availability.manage','booking.payments.reconcile','booking.fee.manage'
  );

-- Reception: patient intake, scheduling, free staff bookings, billing follow-up and day capacity.
INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.name = 'receptionist'
  AND p.name IN (
    'dashboard.view','patients.view','patients.manage','appointments.view','appointments.manage',
    'billing.view','billing.manage','tasks.view','tasks.manage','intakes.view','intakes.manage',
    'booking.manage','booking.availability.manage','booking.payments.reconcile','settings.view'
  );

-- Doctor: clinical workflow only.
INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.name = 'doctor'
  AND p.name IN (
    'dashboard.view','patients.view','appointments.view','appointments.manage',
    'emr.view','emr.edit','tasks.view','tasks.manage','booking.manage','settings.view'
  );

-- Nurse: clinical read/edit workflow without billing or clinic configuration.
INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.name = 'nurse'
  AND p.name IN (
    'dashboard.view','patients.view','appointments.view','emr.view','emr.edit','tasks.view','tasks.manage'
  );
