<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * PatientModel — read/write for the patients table (app.drbastaninejad.com).
 *
 * Table: patients (created by migration 003)
 * RULE: There is exactly ONE patients table shared by both subdomains.
 *
 * All datetimes stored in UTC.
 * All SELECT results must NEVER include password_hash or remember_token.
 */
final class PatientModel extends Model
{
    /**
     * Find a patient by their auth_tokens user_id.
     * Used by PatientPortalController after AuthMiddleware resolves the user.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT id, uuid, first_name, last_name, father_name,
                    mobile, national_id, email, birth_date, birth_date_jalali,
                    home_address, home_tel, marketing_email_optin, created_at
             FROM patients
             WHERE id = ?
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Update editable profile fields for a patient.
     * Only the fields in $fields are updated; all others are left unchanged.
     * Allowed update keys: email, home_address, home_tel, marketing_email_optin.
     * Identity fields (national_id, mobile, name) are NOT updatable by the patient.
     */
    public function updateProfile(int $id, array $fields): void
    {
        $allowed = ['email', 'home_address', 'home_tel', 'marketing_email_optin'];
        $set     = [];
        $params  = [];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $fields)) {
                $set[]    = "{$col} = ?";
                $params[] = $fields[$col];
            }
        }

        if ($set === []) {
            return;
        }

        $params[] = $id;
        $this->db()->prepare(
            'UPDATE patients SET ' . implode(', ', $set) . ', updated_at = UTC_TIMESTAMP()
             WHERE id = ?
               AND deleted_at IS NULL'
        )->execute($params);
    }

    /**
     * Count total intakes filed by this patient (by mobile match).
     */
    public function countIntakes(int $patientId): int
    {
        // Intakes are linked to a patient via mobile number.
        // patient.mobile is the join key; we join via the intakes table.
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*) FROM intakes i
             JOIN patients p ON p.mobile = i.mobile
             WHERE p.id = ?
               AND p.deleted_at IS NULL'
        );
        $stmt->execute([$patientId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get the most recent intake date (as stored Jalali string) for a patient.
     */
    public function lastIntakeDate(int $patientId): ?string
    {
        $stmt = $this->db()->prepare(
            'SELECT i.birth_date_jalali, i.created_at
             FROM intakes i
             JOIN patients p ON p.mobile = i.mobile
             WHERE p.id = ?
               AND p.deleted_at IS NULL
             ORDER BY i.created_at DESC
             LIMIT 1'
        );
        $stmt->execute([$patientId]);
        $row = $stmt->fetch();
        return $row ? substr($row['created_at'], 0, 10) : null;
    }
}
