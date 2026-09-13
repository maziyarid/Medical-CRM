-- Migration 025: booking/calendar RBAC grants

INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.name IN (
    'booking.manage',
    'booking.availability.manage',
    'booking.payments.reconcile',
    'calendar.sync.manage'
)
WHERE r.name = 'super_admin';

INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.name = 'booking.manage'
WHERE r.name = 'receptionist';

INSERT IGNORE INTO permission_role (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.name = 'booking.manage'
WHERE r.name = 'doctor';
