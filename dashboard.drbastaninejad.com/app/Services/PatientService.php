<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Patient;
use App\Core\Database;

/**
 * PatientService
 * Business logic for staff-initiated patient creation. Distinct from the
 * public intake auto-account flow (IntakeController) — this path is used
 * when a receptionist adds a walk-in/phone patient directly in the CRM.
 */
final class PatientService
{
    public function createStaffPatient(int $clinicId, array $data): int
    {
        $db = Database::conn();

        $existing = $db->prepare('SELECT id FROM patients WHERE mobile = ? AND deleted_at IS NULL LIMIT 1');
        $existing->execute([$data['mobile']]);
        $existingId = $existing->fetchColumn();
        if ($existingId) {
            $db->prepare("UPDATE patients SET clinic_id = ?, first_name = COALESCE(NULLIF(?, ''), first_name), last_name = COALESCE(NULLIF(?, ''), last_name), national_id = COALESCE(NULLIF(?, ''), national_id), home_address = COALESCE(NULLIF(?, ''), home_address), updated_at = UTC_TIMESTAMP() WHERE id = ?")
               ->execute([$clinicId, $data['first_name'], $data['last_name'], $data['national_id'], $data['home_address'], (int)$existingId]);
            return (int)$existingId;
        }

        $patient = new Patient();
        return $patient->create([
            'uuid' => bin2hex(random_bytes(16)),
            'clinic_id' => $clinicId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'mobile' => $data['mobile'],
            'national_id' => $data['national_id'],
            'home_address' => $data['home_address'],
            'insurance_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
