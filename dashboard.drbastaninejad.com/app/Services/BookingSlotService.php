<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;

/**
 * Availability and short-lived payment holds for public/admin booking.
 * Open-day rules are stored explicitly rather than inferred from the website.
 */
final class BookingSlotService
{
    private DateTimeZone $clinicTz;
    private DateTimeZone $utc;

    public function __construct()
    {
        $this->clinicTz = new DateTimeZone('Asia/Tehran');
        $this->utc = new DateTimeZone('UTC');
    }

    /**
     * @return array<int,array{open_day_id:int,slot_start:string,display_start:string,duration_minutes:int,remaining_day_quota:int}>
     */
    public function available(int $clinicId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $today = new DateTimeImmutable('today', $this->clinicTz);
        $fromLocal = $this->safeLocalDate($fromDate) ?? $today;
        $maxTo = $today->add(new DateInterval('P62D'));
        $toLocal = $this->safeLocalDate($toDate) ?? $today->add(new DateInterval('P31D'));
        if ($toLocal > $maxTo) {
            $toLocal = $maxTo;
        }
        if ($fromLocal < $today) {
            $fromLocal = $today;
        }
        if ($toLocal < $fromLocal) {
            return [];
        }

        $db = Database::conn();
        $db->prepare("UPDATE appointment_slot_holds SET status = 'expired', updated_at = UTC_TIMESTAMP()
                      WHERE clinic_id = ? AND status = 'held' AND expires_at < UTC_TIMESTAMP()")
            ->execute([$clinicId]);

        $days = $db->prepare(
            "SELECT id, open_date, opens_at, closes_at, slot_duration_minutes, capacity, booked_count
             FROM appointment_open_days
             WHERE clinic_id = ? AND status = 'open' AND open_date BETWEEN ? AND ?
             ORDER BY open_date ASC"
        );
        $days->execute([$clinicId, $fromLocal->format('Y-m-d'), $toLocal->format('Y-m-d')]);
        $rows = $days->fetchAll();
        if (!$rows) {
            return [];
        }

        $rangeStart = (new DateTimeImmutable($fromLocal->format('Y-m-d') . ' 00:00:00', $this->clinicTz))->setTimezone($this->utc);
        $rangeEnd = (new DateTimeImmutable($toLocal->format('Y-m-d') . ' 23:59:59', $this->clinicTz))->setTimezone($this->utc);
        $busy = $this->busyIntervals($db, $clinicId, $rangeStart->format('Y-m-d H:i:s'), $rangeEnd->format('Y-m-d H:i:s'));
        $holdsPerDay = [];
        foreach ($busy as $interval) {
            if ($interval['kind'] !== 'hold') {
                continue;
            }
            $date = (new DateTimeImmutable($interval['start'], $this->utc))->setTimezone($this->clinicTz)->format('Y-m-d');
            $holdsPerDay[$date] = ($holdsPerDay[$date] ?? 0) + 1;
        }

        $out = [];
        $nowUtc = new DateTimeImmutable('now', $this->utc);
        foreach ($rows as $day) {
            $capacity = max(1, (int)$day['capacity']);
            $committed = max(0, (int)$day['booked_count']);
            $activeHolds = (int)($holdsPerDay[(string)$day['open_date']] ?? 0);
            $remaining = max(0, $capacity - $committed - $activeHolds);
            if ($remaining <= 0) {
                continue;
            }
            $duration = max(5, min(480, (int)$day['slot_duration_minutes']));
            $cursorLocal = new DateTimeImmutable($day['open_date'] . ' ' . $day['opens_at'], $this->clinicTz);
            $closeLocal = new DateTimeImmutable($day['open_date'] . ' ' . $day['closes_at'], $this->clinicTz);
            while ($cursorLocal < $closeLocal) {
                $endLocal = $cursorLocal->add(new DateInterval('PT' . $duration . 'M'));
                if ($endLocal > $closeLocal) {
                    break;
                }
                $startUtc = $cursorLocal->setTimezone($this->utc);
                $endUtc = $endLocal->setTimezone($this->utc);
                if ($startUtc > $nowUtc && !$this->overlapsBusy($busy, $startUtc, $endUtc)) {
                    $out[] = [
                        'open_day_id' => (int)$day['id'],
                        'slot_start' => $startUtc->format('Y-m-d H:i:s'),
                        'display_start' => $cursorLocal->format(DATE_ATOM),
                        'duration_minutes' => $duration,
                        'remaining_day_quota' => $remaining,
                    ];
                }
                $cursorLocal = $endLocal;
            }
        }
        return $out;
    }

    /**
     * Must be called inside AppointmentService::withClinicLock().
     * @return array{hold_id:int,open_day_id:int,duration_minutes:int,expires_at:string,slot_end:string}
     */
    public function reserve(PDO $db, int $clinicId, int $intakeId, string $mobile, string $slotStartUtc): array
    {
        $slotStart = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $slotStartUtc, $this->utc);
        if (!$slotStart) {
            throw new RuntimeException('invalid slot');
        }
        if ($slotStart <= new DateTimeImmutable('now', $this->utc)) {
            throw new RuntimeException('invalid slot');
        }
        $local = $slotStart->setTimezone($this->clinicTz);
        $stmt = $db->prepare(
            "SELECT * FROM appointment_open_days
             WHERE clinic_id = ? AND open_date = ? AND status = 'open' LIMIT 1 FOR UPDATE"
        );
        $stmt->execute([$clinicId, $local->format('Y-m-d')]);
        $day = $stmt->fetch();
        if (!$day) {
            throw new RuntimeException('slot unavailable');
        }

        $duration = max(5, min(480, (int)$day['slot_duration_minutes']));
        $open = new DateTimeImmutable($day['open_date'] . ' ' . $day['opens_at'], $this->clinicTz);
        $close = new DateTimeImmutable($day['open_date'] . ' ' . $day['closes_at'], $this->clinicTz);
        $endLocal = $local->add(new DateInterval('PT' . $duration . 'M'));
        if ($local < $open || $endLocal > $close) {
            throw new RuntimeException('slot unavailable');
        }
        $minutesFromOpen = (int)(($local->getTimestamp() - $open->getTimestamp()) / 60);
        if ($minutesFromOpen < 0 || $minutesFromOpen % $duration !== 0) {
            throw new RuntimeException('slot unavailable');
        }

        $db->prepare("UPDATE appointment_slot_holds SET status = 'expired', updated_at = UTC_TIMESTAMP()
                      WHERE clinic_id = ? AND status = 'held' AND expires_at < UTC_TIMESTAMP()")
            ->execute([$clinicId]);
        $countStmt = $db->prepare(
            "SELECT COUNT(*) FROM appointment_slot_holds
             WHERE open_day_id = ? AND status = 'held' AND expires_at >= UTC_TIMESTAMP()"
        );
        $countStmt->execute([(int)$day['id']]);
        $activeHolds = (int)$countStmt->fetchColumn();
        if ((int)$day['booked_count'] + $activeHolds >= (int)$day['capacity']) {
            throw new RuntimeException('day full');
        }

        $slotEnd = $slotStart->add(new DateInterval('PT' . $duration . 'M'));
        if ($this->slotHasConflict($db, $clinicId, $slotStart, $slotEnd)) {
            throw new RuntimeException('slot unavailable');
        }

        $ttl = max(5, min(45, (int)($_ENV['BOOKING_HOLD_MINUTES'] ?? 20)));
        $expiresAt = (new DateTimeImmutable('now', $this->utc))->add(new DateInterval('PT' . $ttl . 'M'));
        $hash = hash('sha256', random_bytes(32));
        $insert = $db->prepare(
            "INSERT INTO appointment_slot_holds
             (clinic_id, open_day_id, intake_id, mobile, slot_start, slot_end, hold_token_hash, status, expires_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'held', ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())"
        );
        $insert->execute([
            $clinicId,
            (int)$day['id'],
            $intakeId,
            $mobile,
            $slotStart->format('Y-m-d H:i:s'),
            $slotEnd->format('Y-m-d H:i:s'),
            $hash,
            $expiresAt->format('Y-m-d H:i:s'),
        ]);
        return [
            'hold_id' => (int)$db->lastInsertId(),
            'open_day_id' => (int)$day['id'],
            'duration_minutes' => $duration,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'slot_end' => $slotEnd->format('Y-m-d H:i:s'),
        ];
    }

    public function release(PDO $db, int $holdId): void
    {
        $db->prepare("UPDATE appointment_slot_holds SET status = 'released', updated_at = UTC_TIMESTAMP()
                      WHERE id = ? AND status = 'held'")->execute([$holdId]);
    }

    public function consume(PDO $db, int $holdId): void
    {
        $db->prepare("UPDATE appointment_slot_holds SET status = 'consumed', updated_at = UTC_TIMESTAMP()
                      WHERE id = ? AND status IN ('held','expired')")->execute([$holdId]);
    }

    public function hasConflict(PDO $db, int $clinicId, string $slotStartUtc, int $durationMinutes): bool
    {
        $start = new DateTimeImmutable($slotStartUtc, $this->utc);
        $end = $start->add(new DateInterval('PT' . max(5, $durationMinutes) . 'M'));
        return $this->slotHasConflict($db, $clinicId, $start, $end, true);
    }

    private function slotHasConflict(PDO $db, int $clinicId, DateTimeImmutable $start, DateTimeImmutable $end, bool $ignoreSamePaidIntake = false): bool
    {
        $startSql = $start->format('Y-m-d H:i:s');
        $endSql = $end->format('Y-m-d H:i:s');

        $stmt = $db->prepare(
            "SELECT 1 FROM appointments
             WHERE clinic_id = ? AND deleted_at IS NULL AND status IN ('scheduled','confirmed')
               AND scheduled_at < ? AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ? LIMIT 1"
        );
        $stmt->execute([$clinicId, $endSql, $startSql]);
        if ($stmt->fetchColumn()) {
            return true;
        }

        $stmt = $db->prepare(
            "SELECT 1 FROM appointment_slot_holds
             WHERE clinic_id = ? AND status = 'held' AND expires_at >= UTC_TIMESTAMP()
               AND slot_start < ? AND slot_end > ? LIMIT 1"
        );
        $stmt->execute([$clinicId, $endSql, $startSql]);
        if ($stmt->fetchColumn()) {
            return true;
        }

        $stmt = $db->prepare(
            "SELECT 1 FROM calendar_blocks
             WHERE clinic_id = ? AND cancelled_at IS NULL AND starts_at < ? AND ends_at > ? LIMIT 1"
        );
        $stmt->execute([$clinicId, $endSql, $startSql]);
        return (bool)$stmt->fetchColumn();
    }

    /** @return array<int,array{start:string,end:string,kind:string}> */
    private function busyIntervals(PDO $db, int $clinicId, string $fromUtc, string $toUtc): array
    {
        $out = [];
        $stmt = $db->prepare(
            "SELECT scheduled_at AS starts_at,
                    DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) AS ends_at
             FROM appointments
             WHERE clinic_id = ? AND deleted_at IS NULL AND status IN ('scheduled','confirmed')
               AND scheduled_at < ? AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?"
        );
        $stmt->execute([$clinicId, $toUtc, $fromUtc]);
        foreach ($stmt->fetchAll() as $r) {
            $out[] = ['start' => $r['starts_at'], 'end' => $r['ends_at'], 'kind' => 'appointment'];
        }
        $stmt = $db->prepare(
            "SELECT slot_start AS starts_at, slot_end AS ends_at FROM appointment_slot_holds
             WHERE clinic_id = ? AND status = 'held' AND expires_at >= UTC_TIMESTAMP()
               AND slot_start < ? AND slot_end > ?"
        );
        $stmt->execute([$clinicId, $toUtc, $fromUtc]);
        foreach ($stmt->fetchAll() as $r) {
            $out[] = ['start' => $r['starts_at'], 'end' => $r['ends_at'], 'kind' => 'hold'];
        }
        $stmt = $db->prepare(
            "SELECT starts_at, ends_at FROM calendar_blocks
             WHERE clinic_id = ? AND cancelled_at IS NULL AND starts_at < ? AND ends_at > ?"
        );
        $stmt->execute([$clinicId, $toUtc, $fromUtc]);
        foreach ($stmt->fetchAll() as $r) {
            $out[] = ['start' => $r['starts_at'], 'end' => $r['ends_at'], 'kind' => 'calendar'];
        }
        return $out;
    }

    private function overlapsBusy(array $busy, DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
        $startTs = $start->getTimestamp();
        $endTs = $end->getTimestamp();
        foreach ($busy as $r) {
            $busyStart = (new DateTimeImmutable($r['start'], $this->utc))->getTimestamp();
            $busyEnd = (new DateTimeImmutable($r['end'], $this->utc))->getTimestamp();
            if ($busyStart < $endTs && $busyEnd > $startTs) {
                return true;
            }
        }
        return false;
    }

    private function safeLocalDate(?string $value): ?DateTimeImmutable
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $this->clinicTz);
        return $d && $d->format('Y-m-d') === $value ? $d : null;
    }
}
