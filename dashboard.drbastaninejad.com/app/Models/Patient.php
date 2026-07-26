<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * Patient
 * Maps to the shared `patients` table (see Detailed MySQL Schema doc).
 * Read/search operations only here — writes go through PatientService with
 * validation (Code Meli mod-11, mobile normalization) already defined in
 * ValidatorService.
 */
final class Patient extends Model
{
    protected string $table = 'patients';

    public function search(int $clinicId, string $q = '', int $page = 1, int $perPage = 20): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $db = $this->db();

        $where = 'clinic_id = ? AND deleted_at IS NULL';
        $params = [$clinicId];

        if ($q !== '') {
            $where .= ' AND (first_name LIKE ? OR last_name LIKE ? OR mobile LIKE ? OR national_id LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM patients WHERE $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT id, first_name, last_name, mobile, national_id, insurance_status,
                    (SELECT MAX(scheduled_at) FROM appointments a WHERE a.patient_id = patients.id) AS last_visit,
                    (SELECT COUNT(*) FROM appointments a WHERE a.patient_id = patients.id AND a.scheduled_at > NOW()) AS upcoming_count
             FROM patients
             WHERE $where
             ORDER BY updated_at DESC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function timeline(int $patientId, int $clinicId): array
    {
        $db = $this->db();

        $verifyStmt = $db->prepare('SELECT id FROM patients WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL');
        $verifyStmt->execute([$patientId, $clinicId]);
        if (!$verifyStmt->fetch()) {
            return [];
        }

        $sql = "
            (SELECT 'intake' AS type, created_at AS ts, description AS summary, status FROM intakes WHERE patient_id = ?)
            UNION ALL
            (SELECT 'appointment' AS type, scheduled_at AS ts, visit_reason AS summary, status FROM appointments WHERE patient_id = ?)
            UNION ALL
            (SELECT 'emr_note' AS type, created_at AS ts, chief_complaint AS summary, 'saved' AS status FROM emr_records WHERE patient_id = ?)
            UNION ALL
            (SELECT 'invoice' AS type, created_at AS ts, CONCAT('فاکتور #', id) AS summary, status FROM invoices WHERE patient_id = ?)
            ORDER BY ts DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$patientId, $patientId, $patientId, $patientId]);
        return $stmt->fetchAll();
    }
}
