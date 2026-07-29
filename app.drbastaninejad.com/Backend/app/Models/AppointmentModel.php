<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * AppointmentModel — patient-facing read queries for the appointments table.
 *
 * Table: appointments (created by migration 005 in dashboard.drbastaninejad.com,
 * or migration 007 in app.drbastaninejad.com — the schema is shared).
 *
 * This model provides READ-ONLY access for the patient portal.
 * Staff write operations live in the dashboard backend only.
 *
 * Status enum: confirmed | scheduled | cancelled | completed
 * Per docs/API_CONTRACT.md §GET /patient/appointments
 */
final class AppointmentModel extends Model
{
    /**
     * Paginated list of appointments for one patient (by patient_id FK).
     */
    public function listForPatient(int $patientId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->db()->prepare(
            'SELECT COUNT(*) FROM appointments WHERE patient_id = ?'
        );
        $countStmt->execute([$patientId]);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db()->prepare(
            'SELECT id AS appointment_id,
                    date_jalali,
                    appointment_time AS `time`,
                    reason,
                    status,
                    "دکتر شاهین باستانی‌نژاد" AS provider_name
             FROM appointments
             WHERE patient_id = ?
             ORDER BY scheduled_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$patientId, $perPage, $offset]);
        $items = $stmt->fetchAll();

        return [
            'items'      => $items,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int)ceil($total / max(1, $perPage)),
            ],
        ];
    }

    /**
     * Next upcoming confirmed/scheduled appointment for a patient.
     * Returns null if none exists.
     */
    public function nextForPatient(int $patientId): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT date_jalali,
                    appointment_time AS `time`,
                    reason,
                    status
             FROM appointments
             WHERE patient_id = ?
               AND status IN ("confirmed", "scheduled")
               AND scheduled_at >= UTC_TIMESTAMP()
             ORDER BY scheduled_at ASC
             LIMIT 1'
        );
        $stmt->execute([$patientId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
