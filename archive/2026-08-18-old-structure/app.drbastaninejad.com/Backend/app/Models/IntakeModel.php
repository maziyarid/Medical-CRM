<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * IntakeModel — persistence for the intakes table.
 *
 * Table: intakes
 * All datetimes stored in UTC. Jalali dates stored as received from client.
 */
final class IntakeModel extends Model
{
    // -------------------------------------------------------------------------
    // Writes
    // -------------------------------------------------------------------------

    /**
     * Insert a new intake row. Returns the new auto-increment id.
     * The caller must have already confirmed no row exists for submission_uuid.
     */
    public function insert(array $fields): int
    {
        $db = $this->db();
        $db->prepare(
            'INSERT INTO intakes
             (submission_uuid, clinic_id, first_name, last_name, father_name,
              mobile, national_id, birth_date, birth_date_jalali, email,
              home_address, chief_complaint, visit_reason, is_transfer,
              raw_payload, sheets_sync_status, status, created_at)
             VALUES
             (?, ?, ?, ?, ?,
              ?, ?, ?, ?, ?,
              ?, ?, ?, ?,
              ?, "pending", "pending", UTC_TIMESTAMP())'
        )->execute([
            $fields['submission_uuid'],
            $fields['clinic_id']        ?? (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1),
            $fields['first_name']       ?? '',
            $fields['last_name']        ?? '',
            $fields['father_name']      ?? null,
            $fields['mobile'],
            $fields['national_id'],
            $fields['birth_date']       ?? null,
            $fields['birth_date_jalali'] ?? null,
            $fields['email']            ?? null,
            $fields['home_address']     ?? null,
            $fields['chief_complaint']  ?? '',
            $fields['visit_reason']     ?? null,
            $fields['is_transfer']      ?? 0,
            json_encode($fields['raw_payload'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Find an intake by submission_uuid (idempotency lookup).
     */
    public function findByUuid(string $uuid): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT id, submission_uuid, clinic_id, first_name, last_name,
                    mobile, national_id, status, sheets_sync_status, created_at
             FROM intakes
             WHERE submission_uuid = ?
             LIMIT 1'
        );
        $stmt->execute([$uuid]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Update sheets_sync_status for a given intake id.
     */
    public function updateSyncStatus(int $id, string $status): void
    {
        $this->db()->prepare(
            "UPDATE intakes SET sheets_sync_status = ? WHERE id = ?"
        )->execute([$status, $id]);
    }
}
