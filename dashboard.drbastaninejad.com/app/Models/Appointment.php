<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Appointment
 * Maps to the shared `appointments` table. Powers day/week/month/agenda views
 * on the Scheduling Calendar (Medical CRM.md §5.6).
 */
final class Appointment extends Model
{
    protected string $table = 'appointments';

    /** Range query for calendar rendering — inclusive of both bounds, clinic-scoped. */
    public function inRange(int $clinicId, string $from, string $to, ?int $providerId = null): array
    {
        $db = $this->db();
        $sql = "SELECT a.id, a.patient_id, a.provider_id, a.scheduled_at, a.duration_minutes,
                       a.visit_reason, a.status, a.room, a.notes,
                       p.first_name, p.last_name
                FROM appointments a
                JOIN patients p ON p.id = a.patient_id
                WHERE a.clinic_id = ? AND a.deleted_at IS NULL
                  AND a.scheduled_at >= ? AND a.scheduled_at < ?";
        $params = [$clinicId, $from, $to];
        if ($providerId) {
            $sql .= " AND a.provider_id = ?";
            $params[] = $providerId;
        }
        $sql .= " ORDER BY a.scheduled_at ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Occupying statuses — cancelled/completed do not hold the slot. */
    public const OCCUPYING_STATUSES = ['scheduled', 'confirmed'];

    /** Overlap check for conflict detection: any appointment for the same provider whose
     *  [start, start+duration) window intersects the requested slot. */
    public function hasConflict(int $clinicId, int $providerId, string $scheduledAt, int $durationMinutes, ?int $excludeId = null): bool
    {
        return $this->hasOverlap(
            $clinicId,
            $scheduledAt,
            $durationMinutes,
            'provider_id',
            $providerId,
            $excludeId
        );
    }

    /**
     * Room collision: same clinic + non-empty room + overlapping occupying slot.
     * Empty/null rooms never collide.
     */
    public function hasRoomConflict(int $clinicId, ?string $room, string $scheduledAt, int $durationMinutes, ?int $excludeId = null): bool
    {
        $room = trim((string)$room);
        if ($room === '') {
            return false;
        }
        return $this->hasOverlap(
            $clinicId,
            $scheduledAt,
            $durationMinutes,
            'room',
            $room,
            $excludeId
        );
    }

    /**
     * @param 'provider_id'|'room' $column
     * @param int|string $value
     */
    private function hasOverlap(
        int $clinicId,
        string $scheduledAt,
        int $durationMinutes,
        string $column,
        int|string $value,
        ?int $excludeId
    ): bool {
        $allowed = ['provider_id', 'room'];
        if (!in_array($column, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid overlap column');
        }
        $placeholders = implode(',', array_fill(0, count(self::OCCUPYING_STATUSES), '?'));
        $sql = "SELECT COUNT(*) FROM appointments
                WHERE clinic_id = ? AND {$column} = ? AND deleted_at IS NULL
                  AND status IN ({$placeholders})
                  AND ? < DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)
                  AND DATE_ADD(?, INTERVAL ? MINUTE) > scheduled_at";
        $params = [$clinicId, $value, ...self::OCCUPYING_STATUSES, $scheduledAt, $scheduledAt, $durationMinutes];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function reschedule(int $id, string $scheduledAt, ?int $durationMinutes = null): void
    {
        $data = ['scheduled_at' => $scheduledAt];
        if ($durationMinutes !== null) {
            $data['duration_minutes'] = $durationMinutes;
        }
        $this->update($id, $data);
    }
}
