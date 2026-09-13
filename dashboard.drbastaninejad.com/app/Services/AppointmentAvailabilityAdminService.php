<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;
use RuntimeException;

final class AppointmentAvailabilityAdminService
{
    /** @return array<string,mixed> */
    public function upsert(int $clinicId, int $staffUserId, array $input): array
    {
        $date = trim((string)($input['open_date'] ?? ''));
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new RuntimeException('invalid open date');
        }
        $capacity = max(1, min(200, (int)($input['capacity'] ?? 20)));
        $duration = max(5, min(240, (int)($input['slot_duration_minutes'] ?? 20)));
        $opensAt = $this->timeOrNull($input['opens_at'] ?? null);
        $closesAt = $this->timeOrNull($input['closes_at'] ?? null);
        if ($opensAt !== null && $closesAt !== null && $opensAt >= $closesAt) {
            throw new RuntimeException('working hours are invalid');
        }
        $status = (string)($input['status'] ?? 'open');
        if (!in_array($status, ['open', 'closed'], true)) {
            throw new RuntimeException('invalid open-day status');
        }

        $db = Database::conn();
        $lockName = 'crm:open-days:' . $clinicId;
        $lock = $db->prepare('SELECT GET_LOCK(?, 8)');
        $lock->execute([$lockName]);
        if ((int)$lock->fetchColumn() !== 1) {
            throw new RuntimeException('clinic lock timeout');
        }

        try {
            $db->beginTransaction();
            $existing = $db->prepare(
                'SELECT * FROM appointment_open_days WHERE clinic_id = ? AND open_date = ? FOR UPDATE'
            );
            $existing->execute([$clinicId, $date]);
            $row = $existing->fetch();
            $currentId = $row ? (int)$row['id'] : 0;

            if ($status === 'open') {
                $week = $db->prepare(
                    'SELECT COUNT(*) FROM appointment_open_days
                     WHERE clinic_id = ? AND status = "open" AND id <> ?
                       AND YEARWEEK(open_date, 3) = YEARWEEK(?, 3)'
                );
                $week->execute([$clinicId, $currentId, $date]);
                if ((int)$week->fetchColumn() >= 2) {
                    throw new RuntimeException('weekly open-day limit reached');
                }

                $month = $db->prepare(
                    'SELECT COUNT(*) FROM appointment_open_days
                     WHERE clinic_id = ? AND status = "open" AND id <> ?
                       AND YEAR(open_date) = YEAR(?) AND MONTH(open_date) = MONTH(?)'
                );
                $month->execute([$clinicId, $currentId, $date, $date]);
                if ((int)$month->fetchColumn() >= 8) {
                    throw new RuntimeException('monthly open-day limit reached');
                }
            }

            if ($row && $capacity < ((int)$row['held_count'] + (int)$row['booked_count'])) {
                throw new RuntimeException('capacity cannot be below current reservations');
            }
            if ($row) {
                $db->prepare(
                    'UPDATE appointment_open_days
                     SET capacity = ?, slot_duration_minutes = ?, opens_at = ?, closes_at = ?, status = ?, updated_at = UTC_TIMESTAMP()
                     WHERE id = ?'
                )->execute([$capacity, $duration, $opensAt, $closesAt, $status, $currentId]);
                $id = $currentId;
            } else {
                $db->prepare(
                    'INSERT INTO appointment_open_days
                     (clinic_id, open_date, capacity, slot_duration_minutes, opens_at, closes_at, status, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([$clinicId, $date, $capacity, $duration, $opensAt, $closesAt, $status, $staffUserId]);
                $id = (int)$db->lastInsertId();
            }
            $db->commit();
            return $this->find($clinicId, $id);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        } finally {
            try {
                $release = $db->prepare('SELECT RELEASE_LOCK(?)');
                $release->execute([$lockName]);
            } catch (\Throwable $e) {
                error_log('[AppointmentAvailabilityAdminService] failed to release lock: ' . $e->getMessage());
            }
        }
    }

    /** @return array<string,mixed> */
    public function find(int $clinicId, int $id): array
    {
        $stmt = Database::conn()->prepare(
            'SELECT id, open_date, capacity, held_count, booked_count, slot_duration_minutes, opens_at, closes_at, status, created_by, created_at, updated_at
             FROM appointment_open_days WHERE id = ? AND clinic_id = ? LIMIT 1'
        );
        $stmt->execute([$id, $clinicId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('open day not found');
        }
        $row['id'] = (int)$row['id'];
        $row['capacity'] = (int)$row['capacity'];
        $row['held_count'] = (int)$row['held_count'];
        $row['booked_count'] = (int)$row['booked_count'];
        $row['remaining'] = max(0, $row['capacity'] - $row['held_count'] - $row['booked_count']);
        $row['slot_duration_minutes'] = (int)$row['slot_duration_minutes'];
        return $row;
    }

    private function timeOrNull(mixed $value): ?string
    {
        $value = trim((string)($value ?? ''));
        if ($value === '') {
            return null;
        }
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            return $value . ':00';
        }
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $value)) {
            return $value;
        }
        throw new RuntimeException('invalid working-hour time');
    }
}
