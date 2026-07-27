<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * IntakeModel
 * Maps to the existing `intakes` table (SCHEMA.md §2.3).
 * Does NOT create a second patients or appointments table.
 */
final class IntakeModel extends Model
{
    protected string $table = 'intakes';

    /**
     * Look up an intake by its client-supplied idempotency key.
     */
    public function findByUuid(string $submissionUuid): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT id, patient_uuid, status FROM intakes WHERE submission_uuid = ? LIMIT 1'
        );
        $stmt->execute([$submissionUuid]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Insert a new intake row. Returns the auto-increment id.
     * Uses the base Model::create() which builds the INSERT dynamically.
     */
    public function createIntake(array $data): int
    {
        return $this->create($data);
    }

    /**
     * Paginated list for the staff review queue.
     */
    public function list(int $clinicId, int $page, int $perPage, ?string $status, string $q): array
    {
        $db     = $this->db();
        $offset = max(0, ($page - 1) * $perPage);

        $where  = 'clinic_id = ? AND deleted_at IS NULL';
        $params = [$clinicId];

        if ($status !== null && $status !== '') {
            $where   .= ' AND status = ?';
            $params[] = $status;
        }

        if ($q !== '') {
            $where   .= ' AND (first_name LIKE ? OR last_name LIKE ? OR mobile LIKE ? OR national_id LIKE ?)';
            $like     = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $count = $db->prepare("SELECT COUNT(*) FROM intakes WHERE $where");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $rows = $db->prepare(
            "SELECT id, submission_uuid, patient_uuid, first_name, last_name,
                    mobile, national_id, service_type, chief_complaint,
                    preferred_date, status, created_at
             FROM intakes
             WHERE $where
             ORDER BY created_at DESC
             LIMIT $perPage OFFSET $offset"
        );
        $rows->execute($params);

        return ['rows' => $rows->fetchAll(), 'total' => $total];
    }
}
