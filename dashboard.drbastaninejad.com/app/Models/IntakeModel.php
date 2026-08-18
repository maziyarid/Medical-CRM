<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class IntakeModel extends Model
{
    protected string $table = 'intakes';

    public function findByUuid(string $submissionUuid): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT id, clinic_id, patient_id, patient_uuid, submission_uuid, source_type,
                    first_name, last_name, mobile, national_id, birth_date, birth_date_jalali,
                    service_type, chief_complaint, preferred_date, email, visit_reason, doctor_request,
                    status, sheets_sync_status, sms_status, created_at
             FROM intakes WHERE submission_uuid = ? LIMIT 1'
        );
        $stmt->execute([$submissionUuid]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateSyncStatus(int $intakeId, string $status): void
    {
        if (!in_array($status, ['pending', 'ok', 'failed', 'skipped'], true)) {
            throw new \InvalidArgumentException('Invalid Sheets sync status.');
        }
        $this->db()->prepare('UPDATE intakes SET sheets_sync_status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?')
            ->execute([$status, $intakeId]);
    }

    public function updateSmsStatus(int $intakeId, string $status, bool $sent = false): void
    {
        if (!in_array($status, ['pending', 'sent', 'failed', 'skipped'], true)) {
            throw new \InvalidArgumentException('Invalid SMS status.');
        }
        $this->db()->prepare(
            'UPDATE intakes SET sms_status = ?, sms_sent_at = IF(?, UTC_TIMESTAMP(), sms_sent_at), updated_at = UTC_TIMESTAMP() WHERE id = ?'
        )->execute([$status, $sent ? 1 : 0, $intakeId]);
    }

    public function createIntake(array $data): int
    {
        return $this->create($data);
    }

    public function list(int $clinicId, int $page, int $perPage, ?string $status, string $q, ?string $sourceType = null): array
    {
        $db = $this->db();
        $offset = max(0, ($page - 1) * $perPage);
        $where = 'clinic_id = ? AND deleted_at IS NULL';
        $params = [$clinicId];
        if ($status) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        if ($sourceType && in_array($sourceType, ['intake', 'booking'], true)) {
            $where .= ' AND source_type = ?';
            $params[] = $sourceType;
        }
        if ($q !== '') {
            $where .= ' AND (first_name LIKE ? OR last_name LIKE ? OR mobile LIKE ? OR national_id LIKE ? OR email LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $count = $db->prepare("SELECT COUNT(*) FROM intakes WHERE {$where}");
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $stmt = $db->prepare(
            "SELECT id, submission_uuid, patient_uuid, source_type, first_name, last_name,
                    mobile, national_id, email, visit_reason, doctor_request, service_type,
                    chief_complaint, preferred_date, status, sheets_sync_status, sms_status,
                    reviewed_by, reviewed_at, created_at
             FROM intakes WHERE {$where}
             ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);
        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    public function updateReviewStatus(int $id, int $clinicId, string $status, int $reviewerId): bool
    {
        if (!in_array($status, ['pending', 'reviewed', 'converted', 'rejected'], true)) {
            return false;
        }
        $stmt = $this->db()->prepare(
            'UPDATE intakes SET status = ?, reviewed_by = ?, reviewed_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
             WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$status, $reviewerId, $id, $clinicId]);
        return $stmt->rowCount() > 0;
    }
}
