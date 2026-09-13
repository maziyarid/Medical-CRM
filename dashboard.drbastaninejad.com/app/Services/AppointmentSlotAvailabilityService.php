<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use RuntimeException;

final class AppointmentSlotAvailabilityService
{
    private DateTimeZone $tehran;
    private DateTimeZone $utc;

    public function __construct()
    {
        $this->tehran = new DateTimeZone('Asia/Tehran');
        $this->utc = new DateTimeZone('UTC');
    }

    /** @return array<int,array<string,mixed>> */
    public function availability(int $clinicId, string $fromDate, string $toDate): array
    {
        $days = (new AppointmentBookingService())->availability($clinicId, $fromDate, $toDate);
        if (!$days) {
            return [];
        }

        $rangeStart = new DateTimeImmutable($fromDate . ' 00:00:00', $this->tehran);
        $rangeEnd = (new DateTimeImmutable($toDate . ' 00:00:00', $this->tehran))->modify('+1 day');
        $startUtc = $rangeStart->setTimezone($this->utc)->format('Y-m-d H:i:s');
        $endUtc = $rangeEnd->setTimezone($this->utc)->format('Y-m-d H:i:s');
        $blocked = $this->blockedRanges($clinicId, $startUtc, $endUtc);

        foreach ($days as &$day) {
            $day['slots'] = $this->slotsForDay($day, $blocked);
            $day['slots_configured'] = $day['opens_at'] !== null && $day['closes_at'] !== null;
        }
        unset($day);
        return $days;
    }

    /** @param array<int,array{start:int,end:int}> $blocked @return array<int,array<string,mixed>> */
    private function slotsForDay(array $day, array $blocked): array
    {
        if (($day['status'] ?? '') !== 'open' || (int)($day['remaining'] ?? 0) <= 0) {
            return [];
        }
        if (empty($day['opens_at']) || empty($day['closes_at'])) {
            return [];
        }

        $duration = max(1, (int)$day['slot_duration_minutes']);
        $cursor = new DateTimeImmutable($day['date'] . ' ' . $day['opens_at'], $this->tehran);
        $close = new DateTimeImmutable($day['date'] . ' ' . $day['closes_at'], $this->tehran);
        $step = new DateInterval('PT' . $duration . 'M');
        $slots = [];

        while ($cursor < $close) {
            $end = $cursor->add($step);
            if ($end > $close) {
                break;
            }
            $startTs = $cursor->setTimezone($this->utc)->getTimestamp();
            $endTs = $end->setTimezone($this->utc)->getTimestamp();
            if (!$this->overlaps($startTs, $endTs, $blocked)) {
                $slots[] = [
                    'start_at' => $cursor->setTimezone($this->utc)->format('Y-m-d\TH:i:s\Z'),
                    'end_at' => $end->setTimezone($this->utc)->format('Y-m-d\TH:i:s\Z'),
                    'local_date' => $cursor->format('Y-m-d'),
                    'local_time' => $cursor->format('H:i'),
                    'duration_minutes' => $duration,
                ];
            }
            $cursor = $end;
        }
        return $slots;
    }

    /** @return array<int,array{start:int,end:int}> */
    private function blockedRanges(int $clinicId, string $fromUtc, string $toUtc): array
    {
        $db = Database::conn();
        $ranges = [];

        $appointments = $db->prepare(
            'SELECT scheduled_at, duration_minutes FROM appointments
             WHERE clinic_id = ? AND deleted_at IS NULL AND status IN ("scheduled","confirmed")
               AND scheduled_at < ?
               AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?'
        );
        $appointments->execute([$clinicId, $toUtc, $fromUtc]);
        foreach ($appointments->fetchAll() as $row) {
            $ranges[] = $this->range((string)$row['scheduled_at'], (int)$row['duration_minutes']);
        }

        $holds = $db->prepare(
            'SELECT requested_start_at, duration_minutes FROM appointment_booking_requests
             WHERE clinic_id = ? AND slot_claim_key IS NOT NULL
               AND confirmation_status IN ("holding","awaiting_payment","paid_pending_staff","confirmed")
               AND requested_start_at < ?
               AND DATE_ADD(requested_start_at, INTERVAL duration_minutes MINUTE) > ?'
        );
        $holds->execute([$clinicId, $toUtc, $fromUtc]);
        foreach ($holds->fetchAll() as $row) {
            $ranges[] = $this->range((string)$row['requested_start_at'], (int)$row['duration_minutes']);
        }

        return $ranges;
    }

    /** @return array{start:int,end:int} */
    private function range(string $startUtc, int $duration): array
    {
        try {
            $start = new DateTimeImmutable($startUtc, $this->utc);
        } catch (\Throwable) {
            throw new RuntimeException('invalid stored appointment time');
        }
        return [
            'start' => $start->getTimestamp(),
            'end' => $start->modify('+' . max(1, $duration) . ' minutes')->getTimestamp(),
        ];
    }

    /** @param array<int,array{start:int,end:int}> $blocked */
    private function overlaps(int $start, int $end, array $blocked): bool
    {
        foreach ($blocked as $range) {
            if ($start < $range['end'] && $end > $range['start']) {
                return true;
            }
        }
        return false;
    }
}
