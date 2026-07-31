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

    public function search(
        int $clinicId,
        string $q = '',
        int $page = 1,
        int $perPage = 20,
        string $insuranceStatus = ''
    ): array {
        $offset = max(0, ($page - 1) * $perPage);
        $db = $this->db();

        $where = 'clinic_id = ? AND deleted_at IS NULL';
        $params = [$clinicId];

        if ($q !== '') {
            $where .= ' AND (first_name LIKE ? OR last_name LIKE ? OR mobile LIKE ? OR national_id LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }

        if ($insuranceStatus !== '') {
            $where .= ' AND insurance_status = ?';
            $params[] = $insuranceStatus;
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM patients WHERE $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT id, first_name, last_name, mobile, national_id, insurance_status,
                    (SELECT MAX(scheduled_at) FROM appointments a
                     WHERE a.patient_id = patients.id AND a.deleted_at IS NULL) AS last_visit,
                    (SELECT COUNT(*) FROM appointments a
                     WHERE a.patient_id = patients.id AND a.scheduled_at > NOW()
                       AND a.deleted_at IS NULL) AS upcoming_count
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

        // Columns aliased to shape expected by patient-detail.html renderTimeline():
        //   { type, timestamp, title, description, status }
        $sql = "
            (SELECT 'intake'      AS type, created_at  AS timestamp,
                    'پذیرش جدید' AS title, COALESCE(description,'') AS description, status
             FROM intakes WHERE patient_id = ? AND deleted_at IS NULL)
            UNION ALL
            (SELECT 'appointment' AS type, scheduled_at AS timestamp,
                    COALESCE(visit_reason,'نوبت') AS title, COALESCE(notes,'') AS description, status
             FROM appointments WHERE patient_id = ? AND deleted_at IS NULL)
            UNION ALL
            (SELECT 'emr_note'    AS type, created_at   AS timestamp,
                    COALESCE(chief_complaint,'یادداشت پزشکی') AS title,
                    COALESCE(diagnosis,'') AS description, 'saved' AS status
             FROM emr_records WHERE patient_id = ? AND deleted_at IS NULL)
            UNION ALL
            (SELECT 'invoice'     AS type, created_at   AS timestamp,
                    CONCAT('فاکتور #', id) AS title, '' AS description, status
             FROM invoices WHERE patient_id = ?)
            ORDER BY timestamp DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$patientId, $patientId, $patientId, $patientId]);
        return $stmt->fetchAll();
    }
}
