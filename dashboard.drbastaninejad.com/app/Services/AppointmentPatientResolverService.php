<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Validators\ValidatorService;
use RuntimeException;

/** Resolve the patient identity for receptionist-created appointments. */
final class AppointmentPatientResolverService
{
    /** @param array<string,mixed> $input */
    public function resolve(int $clinicId, array $input): int
    {
        $patientId = (int)($input['patient_id'] ?? 0);
        $db = Database::conn();
        if ($patientId > 0) {
            $stmt = $db->prepare('SELECT id FROM patients WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL LIMIT 1');
            $stmt->execute([$patientId, $clinicId]);
            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('patient not found');
            }
            return $patientId;
        }

        $mobile = ValidatorService::normalizeMobile(trim((string)($input['mobile'] ?? '')));
        if (!ValidatorService::isValidMobile($mobile)) {
            throw new RuntimeException('invalid patient mobile');
        }
        $first = trim((string)($input['first_name'] ?? ''));
        $last = trim((string)($input['last_name'] ?? ''));
        $full = trim((string)($input['full_name'] ?? $input['name'] ?? ''));
        if ($first === '' && $last === '' && $full !== '') {
            // Do not guess Persian first/last-name boundaries. Keep the exact
            // supplied full name in first_name; display code already trims both.
            $first = $full;
        }
        if (mb_strlen(trim($first . ' ' . $last)) < 2 || mb_strlen($first) > 100 || mb_strlen($last) > 100) {
            throw new RuntimeException('invalid patient name');
        }

        $stmt = $db->prepare('SELECT id, clinic_id, first_name, last_name FROM patients WHERE mobile = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$mobile]);
        $existing = $stmt->fetch();
        if ($existing) {
            if ((int)$existing['clinic_id'] !== $clinicId) {
                throw new RuntimeException('patient belongs to another clinic');
            }
            $db->prepare(
                'UPDATE patients
                 SET first_name = IF(first_name = "", ?, first_name),
                     last_name = IF(last_name = "", ?, last_name), updated_at = UTC_TIMESTAMP()
                 WHERE id = ?'
            )->execute([$first, $last, (int)$existing['id']]);
            return (int)$existing['id'];
        }

        $uuid = bin2hex(random_bytes(16));
        $db->prepare(
            'INSERT INTO patients
             (uuid, clinic_id, first_name, last_name, mobile, insurance_status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, "pending", UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        )->execute([$uuid, $clinicId, $first, $last, $mobile]);
        $id = (int)$db->lastInsertId();
        try {
            (new PatientRegistrationNotificationService())->notify($id, false, false);
        } catch (\Throwable $e) {
            error_log('[AppointmentPatientResolverService] patient registration notification failed: ' . $e->getMessage());
        }
        return $id;
    }
}
