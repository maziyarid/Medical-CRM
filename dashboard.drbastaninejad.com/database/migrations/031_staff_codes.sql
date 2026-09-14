-- Migration 031: stable staff codes for CRM and ScheduledVisits audit.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS staff_code VARCHAR(32) NULL AFTER uuid;

CREATE UNIQUE INDEX IF NOT EXISTS uq_users_staff_code ON users (staff_code);

UPDATE users u
LEFT JOIN role_user ru ON ru.user_id = u.id
LEFT JOIN roles r ON r.id = ru.role_id
SET u.staff_code = CONCAT(
    CASE COALESCE(r.name, '')
        WHEN 'super_admin' THEN 'SUP'
        WHEN 'admin' THEN 'ADM'
        WHEN 'receptionist' THEN 'REC'
        WHEN 'doctor' THEN 'DOC'
        WHEN 'nurse' THEN 'NUR'
        ELSE 'STF'
    END,
    '-', LPAD(u.id, 4, '0')
)
WHERE u.staff_code IS NULL OR u.staff_code = '';
