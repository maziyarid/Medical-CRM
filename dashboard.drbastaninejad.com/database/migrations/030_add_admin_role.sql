-- Operational admin role. Super-admin remains the only staff/account/integration owner.
INSERT IGNORE INTO roles (name, label) VALUES ('admin', 'مدیر');

INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.name IN (
  'dashboard.view',
  'patients.view','patients.manage',
  'appointments.view','appointments.manage',
  'emr.view',
  'billing.view','billing.manage',
  'tasks.view','tasks.manage',
  'analytics.view',
  'settings.view','settings.manage',
  'intakes.view','intakes.manage',
  'booking.manage','booking.availability.manage','booking.payments.reconcile'
)
WHERE r.name='admin';
