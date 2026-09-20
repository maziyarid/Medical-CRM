<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Validators\ValidatorService;
use PDO;
use RuntimeException;

final class BookingBlacklistService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conn();
        $this->ensureSchema();
    }

    public function isBlocked(int $clinicId, string $identifier, ?string $type = null): bool
    {
        [$kind, $value] = $this->normaliseIdentifier($identifier, $type);
        $stmt = $this->db->prepare(
            'SELECT 1 FROM booking_blacklist
             WHERE clinic_id = ? AND identifier_type = ? AND identifier_value = ? AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$clinicId, $kind, $value]);
        return (bool)$stmt->fetchColumn();
    }

    public function isPatientBlocked(int $clinicId, int $patientId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM booking_blacklist b
             LEFT JOIN patients p ON p.id = ? AND p.clinic_id = ? AND p.deleted_at IS NULL
             WHERE b.clinic_id = ? AND b.deleted_at IS NULL
               AND (b.patient_id = ? OR (b.identifier_type = "national_id" AND p.national_id = b.identifier_value))
             LIMIT 1'
        );
        $stmt->execute([$patientId, $clinicId, $clinicId, $patientId]);
        return (bool)$stmt->fetchColumn();
    }

    public function flagsForPatients(int $clinicId, array $patientIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $patientIds), fn($id) => $id > 0)));
        if (!$ids) return [];
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $sql =
            'SELECT p.id AS patient_id, MIN(b.id) AS blacklist_id
             FROM patients p
             JOIN booking_blacklist b
               ON b.clinic_id = p.clinic_id AND b.deleted_at IS NULL
              AND (b.patient_id = p.id OR (b.identifier_type = "national_id" AND b.identifier_value = p.national_id))
             WHERE p.clinic_id = ? AND p.deleted_at IS NULL AND p.id IN (' . $marks . ')
             GROUP BY p.id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([$clinicId], $ids));
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int)$row['patient_id']] = (int)$row['blacklist_id'];
        }
        return $out;
    }

    public function listing(int $clinicId): array
    {
        $stmt = $this->db->prepare(
            'SELECT b.id, b.patient_id, b.identifier_type, b.identifier_value, b.reason, b.created_at,
                    TRIM(CONCAT(COALESCE(p.first_name, ""), " ", COALESCE(p.last_name, ""))) AS patient_name
             FROM booking_blacklist b
             LEFT JOIN patients p ON p.id = b.patient_id AND p.clinic_id = b.clinic_id
             WHERE b.clinic_id = ? AND b.deleted_at IS NULL
             ORDER BY b.created_at DESC, b.id DESC'
        );
        $stmt->execute([$clinicId]);
        return array_map(fn($row) => $this->publicRow($row), $stmt->fetchAll());
    }

    public function add(int $clinicId, int $staffId, array $input): array
    {
        $patientId = (int)($input['patient_id'] ?? 0);
        $reason = trim((string)($input['reason'] ?? ''));
        if (mb_strlen($reason) > 500) $reason = mb_substr($reason, 0, 500);

        $identifier = trim((string)($input['identifier'] ?? ''));
        $type = trim((string)($input['identifier_type'] ?? '')) ?: null;

        if ($patientId > 0) {
            $patient = $this->db->prepare(
                'SELECT id, national_id FROM patients WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL LIMIT 1'
            );
            $patient->execute([$patientId, $clinicId]);
            $row = $patient->fetch();
            if (!$row) throw new RuntimeException('patient not found');
            if ($identifier === '') {
                $identifier = trim((string)($row['national_id'] ?? ''));
                $type = 'national_id';
            }
            if ($identifier === '') throw new RuntimeException('patient identifier missing');
        }

        [$kind, $value] = $this->normaliseIdentifier($identifier, $type);

        if ($patientId < 1 && $kind === 'national_id') {
            $patient = $this->db->prepare(
                'SELECT id FROM patients WHERE clinic_id = ? AND national_id = ? AND deleted_at IS NULL LIMIT 1'
            );
            $patient->execute([$clinicId, $value]);
            $patientId = (int)($patient->fetchColumn() ?: 0);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO booking_blacklist
             (clinic_id, patient_id, identifier_type, identifier_value, reason, created_by, created_at, updated_at)
             VALUES (?, NULLIF(?, 0), ?, ?, NULLIF(?, ""), ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
               patient_id = COALESCE(VALUES(patient_id), patient_id),
               reason = VALUES(reason),
               created_by = VALUES(created_by),
               deleted_by = NULL,
               deleted_at = NULL,
               updated_at = UTC_TIMESTAMP()'
        );
        $stmt->execute([$clinicId, $patientId, $kind, $value, $reason, $staffId]);

        $get = $this->db->prepare(
            'SELECT b.id, b.patient_id, b.identifier_type, b.identifier_value, b.reason, b.created_at,
                    TRIM(CONCAT(COALESCE(p.first_name, ""), " ", COALESCE(p.last_name, ""))) AS patient_name
             FROM booking_blacklist b
             LEFT JOIN patients p ON p.id = b.patient_id AND p.clinic_id = b.clinic_id
             WHERE b.clinic_id = ? AND b.identifier_type = ? AND b.identifier_value = ? LIMIT 1'
        );
        $get->execute([$clinicId, $kind, $value]);
        return $this->publicRow($get->fetch() ?: []);
    }

    public function remove(int $clinicId, int $staffId, int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE booking_blacklist
             SET deleted_by = ?, deleted_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
             WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$staffId, $id, $clinicId]);
        return $stmt->rowCount() > 0;
    }

    private function normaliseIdentifier(string $raw, ?string $hint = null): array
    {
        $value = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', ValidatorService::normalizePersianDigits($raw)) ?? '');
        $hint = strtolower(trim((string)$hint));

        if ($hint === 'national_id' || ($hint === '' && preg_match('/^\d{10}$/', $value))) {
            if (!ValidatorService::isValidNationalId($value)) throw new RuntimeException('invalid national id');
            return ['national_id', $value];
        }
        if ($hint !== '' && $hint !== 'passport') throw new RuntimeException('invalid identifier type');
        if (!preg_match('/^[A-Z0-9]{6,32}$/', $value)) throw new RuntimeException('invalid passport');
        return ['passport', $value];
    }

    private function publicRow(array $row): array
    {
        $value = (string)($row['identifier_value'] ?? '');
        $tail = mb_substr($value, -4);
        return [
            'id' => (int)($row['id'] ?? 0),
            'patient_id' => !empty($row['patient_id']) ? (int)$row['patient_id'] : null,
            'patient_name' => trim((string)($row['patient_name'] ?? '')) ?: null,
            'identifier_type' => (string)($row['identifier_type'] ?? ''),
            'identifier_masked' => str_repeat('•', max(2, mb_strlen($value) - 4)) . $tail,
            'reason' => (string)($row['reason'] ?? ''),
            'created_at' => (string)($row['created_at'] ?? ''),
        ];
    }

    private function ensureSchema(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS booking_blacklist (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                clinic_id INT UNSIGNED NOT NULL,
                patient_id INT UNSIGNED NULL,
                identifier_type VARCHAR(20) NOT NULL,
                identifier_value VARCHAR(32) NOT NULL,
                reason VARCHAR(500) NULL,
                created_by INT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_by INT UNSIGNED NULL,
                deleted_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_booking_blacklist_identifier (clinic_id, identifier_type, identifier_value),
                KEY idx_booking_blacklist_patient (clinic_id, patient_id, deleted_at),
                KEY idx_booking_blacklist_active (clinic_id, deleted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}
