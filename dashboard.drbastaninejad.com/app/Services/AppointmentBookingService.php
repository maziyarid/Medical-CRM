<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use RuntimeException;

final class AppointmentBookingService
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
        if (!$this->validDate($fromDate) || !$this->validDate($toDate) || $fromDate > $toDate) {
            throw new RuntimeException('invalid date range');
        }
        $this->releaseExpiredHolds($clinicId);
        $stmt = Database::conn()->prepare(
            'SELECT id, open_date, capacity, held_count, booked_count, slot_duration_minutes, opens_at, closes_at, status
             FROM appointment_open_days
             WHERE clinic_id = ? AND open_date BETWEEN ? AND ?
             ORDER BY open_date ASC'
        );
        $stmt->execute([$clinicId, $fromDate, $toDate]);
        return array_map(static function (array $row): array {
            $capacity = (int)$row['capacity'];
            $used = (int)$row['held_count'] + (int)$row['booked_count'];
            return [
                'id' => (int)$row['id'],
                'date' => $row['open_date'],
                'capacity' => $capacity,
                'held' => (int)$row['held_count'],
                'booked' => (int)$row['booked_count'],
                'remaining' => max(0, $capacity - $used),
                'slot_duration_minutes' => (int)$row['slot_duration_minutes'],
                'opens_at' => $row['opens_at'],
                'closes_at' => $row['closes_at'],
                'status' => $row['status'],
            ];
        }, $stmt->fetchAll());
    }

    /** @return array<string,mixed> */
    public function createPatientHold(
        int $clinicId,
        int $patientId,
        int $openDayId,
        string $requestedStart,
        int $amountRials,
        string $gateway,
        ?string $submissionUuid = null,
        string $source = 'online',
        ?int $receptionistUserId = null
    ): array {
        if (!in_array($gateway, ['zarinpal', 'vandar'], true)) {
            throw new RuntimeException('invalid payment gateway');
        }
        if ($amountRials <= 0) {
            throw new RuntimeException('invalid payment amount');
        }
        if (!in_array($source, ['online', 'admin'], true)) {
            throw new RuntimeException('invalid booking source');
        }
        if ($source === 'admin' && ($receptionistUserId ?? 0) < 1) {
            throw new RuntimeException('receptionist user is required');
        }
        if ($source === 'online') {
            $receptionistUserId = null;
        }
        $startUtc = $this->normaliseStart($requestedStart);
        $db = Database::conn();
        $db->beginTransaction();
        try {
            $day = $this->lockOpenDay($db, $clinicId, $openDayId);
            $this->expireLockedDayHolds($db, $day);
            $this->assertDayCanAccept($day, $startUtc);

            $holdMinutes = max(5, min(20, (int)($_ENV['BOOKING_HOLD_MINUTES'] ?? 15)));
            $uuid = $this->uuidV4();
            $claim = hash('sha256', $clinicId . '|' . $startUtc);
            $stmt = $db->prepare(
                'INSERT INTO appointment_booking_requests
                 (uuid, submission_uuid, clinic_id, patient_id, open_day_id, requested_start_at,
                  duration_minutes, source, payment_gateway, payment_status, confirmation_status,
                  receptionist_user_id, amount_rials, slot_claim_key, hold_expires_at, staff_followup_required)
                 VALUES (?, NULLIF(?, ""), ?, ?, ?, ?, ?, ?, ?, "pending", "awaiting_payment", NULLIF(?, 0), ?, ?,
                         DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? MINUTE), 1)'
            );
            $stmt->execute([
                $uuid,
                $submissionUuid ?? '',
                $clinicId,
                $patientId,
                $openDayId,
                $startUtc,
                (int)$day['slot_duration_minutes'],
                $source,
                $gateway,
                $receptionistUserId ?? 0,
                $amountRials,
                $claim,
                $holdMinutes,
            ]);
            $id = (int)$db->lastInsertId();
            $db->prepare('UPDATE appointment_open_days SET held_count = held_count + 1 WHERE id = ?')
                ->execute([$openDayId]);
            $db->commit();
            return [
                'id' => $id,
                'uuid' => $uuid,
                'requested_start_at' => $startUtc,
                'payment_status' => 'pending',
                'confirmation_status' => 'awaiting_payment',
                'source' => $source,
                'receptionist_user_id' => $receptionistUserId,
                'hold_minutes' => $holdMinutes,
            ];
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ((string)$e->getCode() === '23000') {
                throw new RuntimeException('slot unavailable', 0, $e);
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string,mixed> */
    public function createAdminFreeBooking(
        int $clinicId,
        int $patientId,
        int $receptionistUserId,
        int $openDayId,
        string $requestedStart,
        ?string $visitReason = null,
        ?string $notes = null
    ): array {
        $startUtc = $this->normaliseStart($requestedStart);
        $db = Database::conn();
        $db->beginTransaction();
        try {
            $day = $this->lockOpenDay($db, $clinicId, $openDayId);
            $this->expireLockedDayHolds($db, $day);
            $this->assertDayCanAccept($day, $startUtc);
            $duration = (int)$day['slot_duration_minutes'];
            $this->assertNoAppointmentOverlap($db, $clinicId, $startUtc, $duration);

            $uuid = $this->uuidV4();
            $claim = hash('sha256', $clinicId . '|' . $startUtc);
            $db->prepare(
                'INSERT INTO appointment_booking_requests
                 (uuid, clinic_id, patient_id, open_day_id, requested_start_at, duration_minutes,
                  source, payment_gateway, payment_status, confirmation_status, receptionist_user_id,
                  amount_rials, slot_claim_key, staff_followup_required, confirmed_at)
                 VALUES (?, ?, ?, ?, ?, ?, "admin", "none", "free", "confirmed", ?, 0, ?, 0, UTC_TIMESTAMP())'
            )->execute([$uuid, $clinicId, $patientId, $openDayId, $startUtc, $duration, $receptionistUserId, $claim]);
            $bookingId = (int)$db->lastInsertId();

            $appointmentUuid = bin2hex(random_bytes(16));
            $providerId = (int)($_ENV['BOOKING_DEFAULT_PROVIDER_ID'] ?? 0);
            $db->prepare(
                'INSERT INTO appointments
                 (uuid, clinic_id, patient_id, provider_id, scheduled_at, duration_minutes,
                  visit_reason, status, notes, created_at, updated_at)
                 VALUES (?, ?, ?, NULLIF(?, 0), ?, ?, NULLIF(?, ""), "confirmed", NULLIF(?, ""), UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([$appointmentUuid, $clinicId, $patientId, $providerId, $startUtc, $duration, $visitReason ?? '', $notes ?? '']);
            $appointmentId = (int)$db->lastInsertId();
            $db->prepare('UPDATE appointment_booking_requests SET appointment_id = ? WHERE id = ?')
                ->execute([$appointmentId, $bookingId]);
            $db->prepare('UPDATE appointment_open_days SET booked_count = booked_count + 1 WHERE id = ?')
                ->execute([$openDayId]);
            $this->enqueueCalendar($db, $clinicId, 'appointment', $appointmentId, 'upsert', [
                'appointment_id' => $appointmentId,
                'booking_request_id' => $bookingId,
                'scheduled_at' => $startUtc,
                'duration_minutes' => $duration,
            ]);
            $db->commit();
            return [
                'id' => $bookingId,
                'uuid' => $uuid,
                'appointment_id' => $appointmentId,
                'payment_status' => 'free',
                'confirmation_status' => 'confirmed',
            ];
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ((string)$e->getCode() === '23000') {
                throw new RuntimeException('slot unavailable', 0, $e);
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Transition a verified gateway payment. Paid requests keep the slot reserved,
     * but remain paid_pending_staff until the clinic finalises the appointment.
     * @return array{paid:bool,slot_reserved:bool,status:string}
     */
    public function markPaid(int $bookingId, string $gateway, string $transactionRef): array
    {
        $db = Database::conn();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT * FROM appointment_booking_requests WHERE id = ? FOR UPDATE');
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            if (!$booking) {
                throw new RuntimeException('booking not found');
            }
            if ((string)$booking['payment_status'] === 'paid') {
                $db->commit();
                return ['id' => $bookingId, 'booking_id' => $bookingId, 'paid' => true, 'slot_reserved' => $booking['slot_claim_key'] !== null, 'status' => (string)$booking['confirmation_status']];
            }
            if ((string)$booking['payment_gateway'] !== $gateway) {
                throw new RuntimeException('gateway mismatch');
            }

            $day = $this->lockOpenDay($db, (int)$booking['clinic_id'], (int)$booking['open_day_id']);
            $holdActive = $booking['slot_claim_key'] !== null
                && $booking['hold_expires_at'] !== null
                && strtotime((string)$booking['hold_expires_at']) >= time();

            if ($holdActive) {
                $db->prepare(
                    'UPDATE appointment_open_days
                     SET held_count = GREATEST(held_count - 1, 0), booked_count = booked_count + 1
                     WHERE id = ?'
                )->execute([(int)$day['id']]);
            }

            $newStatus = 'paid_pending_staff';
            $db->prepare(
                'UPDATE appointment_booking_requests
                 SET payment_status = "paid", confirmation_status = ?, hold_expires_at = NULL,
                     staff_followup_required = 1, sheet_sync_status = "pending", sheet_sync_error = NULL,
                     updated_at = UTC_TIMESTAMP()
                 WHERE id = ?'
            )->execute([$newStatus, $bookingId]);

            $db->commit();
            return ['id' => $bookingId, 'booking_id' => $bookingId, 'paid' => true, 'slot_reserved' => $holdActive, 'status' => $newStatus];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string,mixed> */
    public function confirmPaidByStaff(int $clinicId, int $bookingId, int $staffUserId): array
    {
        $db = Database::conn();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'SELECT b.* FROM appointment_booking_requests b
                 WHERE b.id = ? AND b.clinic_id = ? FOR UPDATE'
            );
            $stmt->execute([$bookingId, $clinicId]);
            $booking = $stmt->fetch();
            if (!$booking) {
                throw new RuntimeException('booking not found');
            }
            if ((string)$booking['confirmation_status'] === 'confirmed' && $booking['appointment_id']) {
                $db->commit();
                return ['id' => $bookingId, 'appointment_id' => (int)$booking['appointment_id'], 'status' => 'confirmed'];
            }
            if (!in_array((string)$booking['payment_status'], ['paid', 'free'], true)) {
                throw new RuntimeException('payment not verified');
            }
            if ($booking['slot_claim_key'] === null) {
                throw new RuntimeException('paid slot requires reconciliation');
            }
            $this->assertNoAppointmentOverlap(
                $db,
                $clinicId,
                (string)$booking['requested_start_at'],
                (int)$booking['duration_minutes']
            );

            $appointmentUuid = bin2hex(random_bytes(16));
            $providerId = (int)($_ENV['BOOKING_DEFAULT_PROVIDER_ID'] ?? 0);
            $db->prepare(
                'INSERT INTO appointments
                 (uuid, clinic_id, patient_id, provider_id, scheduled_at, duration_minutes, visit_reason,
                  status, notes, created_at, updated_at)
                 VALUES (?, ?, ?, NULLIF(?, 0), ?, ?, "رزرو آنلاین", "confirmed", ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([
                $appointmentUuid,
                $clinicId,
                (int)$booking['patient_id'],
                $providerId,
                $booking['requested_start_at'],
                (int)$booking['duration_minutes'],
                'Confirmed by staff user #' . $staffUserId,
            ]);
            $appointmentId = (int)$db->lastInsertId();
            $db->prepare(
                'UPDATE appointment_booking_requests
                 SET appointment_id = ?, confirmation_status = "confirmed", receptionist_user_id = COALESCE(receptionist_user_id, ?),
                     confirmed_at = UTC_TIMESTAMP(), staff_followup_required = 1,
                     sheet_sync_status = "pending", sheet_sync_error = NULL, updated_at = UTC_TIMESTAMP()
                 WHERE id = ?'
            )->execute([$appointmentId, $staffUserId, $bookingId]);
            $this->enqueueCalendar($db, $clinicId, 'appointment', $appointmentId, 'upsert', [
                'appointment_id' => $appointmentId,
                'booking_request_id' => $bookingId,
                'scheduled_at' => $booking['requested_start_at'],
                'duration_minutes' => (int)$booking['duration_minutes'],
            ]);
            $db->commit();
            return ['id' => $bookingId, 'appointment_id' => $appointmentId, 'status' => 'confirmed'];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string,mixed> */
    public function assignPaidReconciliationSlot(
        int $clinicId,
        int $bookingId,
        int $staffUserId,
        int $openDayId,
        string $requestedStart
    ): array {
        $startUtc = $this->normaliseStart($requestedStart);
        $db = Database::conn();
        $db->beginTransaction();
        try {
            $day = $this->lockOpenDay($db, $clinicId, $openDayId);
            $this->expireLockedDayHolds($db, $day);
            $this->assertDayCanAccept($day, $startUtc);

            $stmt = $db->prepare(
                'SELECT * FROM appointment_booking_requests WHERE id = ? AND clinic_id = ? FOR UPDATE'
            );
            $stmt->execute([$bookingId, $clinicId]);
            $booking = $stmt->fetch();
            if (!$booking) {
                throw new RuntimeException('booking not found');
            }
            if ((string)$booking['payment_status'] !== 'paid'
                || (string)$booking['confirmation_status'] !== 'paid_pending_staff') {
                throw new RuntimeException('booking is not awaiting paid-slot reconciliation');
            }
            if ($booking['slot_claim_key'] !== null) {
                throw new RuntimeException('booking slot already reserved');
            }

            $duration = (int)$day['slot_duration_minutes'];
            $this->assertNoAppointmentOverlap($db, $clinicId, $startUtc, $duration);
            $claim = hash('sha256', $clinicId . '|' . $startUtc);
            $db->prepare(
                'UPDATE appointment_booking_requests
                 SET open_day_id = ?, requested_start_at = ?, duration_minutes = ?, slot_claim_key = ?,
                     hold_expires_at = NULL, receptionist_user_id = COALESCE(receptionist_user_id, ?),
                     sheet_sync_status = "pending", sheet_sync_error = NULL, updated_at = UTC_TIMESTAMP()
                 WHERE id = ?'
            )->execute([$openDayId, $startUtc, $duration, $claim, $staffUserId, $bookingId]);
            $db->prepare(
                'UPDATE appointment_open_days
                 SET booked_count = booked_count + 1, updated_at = UTC_TIMESTAMP() WHERE id = ?'
            )->execute([$openDayId]);
            $db->commit();
            return [
                'id' => $bookingId,
                'booking_id' => $bookingId,
                'payment_status' => 'paid',
                'confirmation_status' => 'paid_pending_staff',
                'slot_reserved' => true,
                'open_day_id' => $openDayId,
                'requested_start_at' => $startUtc,
                'duration_minutes' => $duration,
            ];
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ((string)$e->getCode() === '23000') {
                throw new RuntimeException('slot unavailable', 0, $e);
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function releaseExpiredHolds(int $clinicId): int
    {
        $db = Database::conn();
        $db->beginTransaction();
        try {
            $days = $db->prepare(
                'SELECT DISTINCT d.* FROM appointment_open_days d
                 JOIN appointment_booking_requests b ON b.open_day_id = d.id
                 WHERE d.clinic_id = ? AND b.confirmation_status IN ("holding","awaiting_payment")
                   AND b.hold_expires_at < UTC_TIMESTAMP()
                 FOR UPDATE'
            );
            $days->execute([$clinicId]);
            $released = 0;
            foreach ($days->fetchAll() as $day) {
                $released += $this->expireLockedDayHolds($db, $day);
            }
            $db->commit();
            return $released;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string,mixed> */
    private function lockOpenDay(PDO $db, int $clinicId, int $openDayId): array
    {
        $stmt = $db->prepare('SELECT * FROM appointment_open_days WHERE id = ? AND clinic_id = ? FOR UPDATE');
        $stmt->execute([$openDayId, $clinicId]);
        $day = $stmt->fetch();
        if (!$day) {
            throw new RuntimeException('open day not found');
        }
        return $day;
    }

    private function expireLockedDayHolds(PDO $db, array &$day): int
    {
        $stmt = $db->prepare(
            'SELECT id FROM appointment_booking_requests
             WHERE open_day_id = ? AND confirmation_status IN ("holding","awaiting_payment")
               AND hold_expires_at IS NOT NULL AND hold_expires_at < UTC_TIMESTAMP()
             FOR UPDATE'
        );
        $stmt->execute([(int)$day['id']]);
        $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));
        if (!$ids) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare(
            "UPDATE appointment_booking_requests
             SET confirmation_status = 'expired', payment_status = IF(payment_status = 'paid', 'paid', 'failed'),
                 slot_claim_key = NULL, hold_expires_at = NULL,
                 sheet_sync_status = 'pending', sheet_sync_error = NULL, updated_at = UTC_TIMESTAMP()
             WHERE id IN ({$placeholders})"
        )->execute($ids);
        $count = count($ids);
        $db->prepare('UPDATE appointment_open_days SET held_count = GREATEST(held_count - ?, 0) WHERE id = ?')
            ->execute([$count, (int)$day['id']]);
        $day['held_count'] = max(0, (int)$day['held_count'] - $count);
        return $count;
    }

    private function assertDayCanAccept(array $day, string $startUtc): void
    {
        if ((string)$day['status'] !== 'open') {
            throw new RuntimeException('open day closed');
        }
        if ((int)$day['held_count'] + (int)$day['booked_count'] >= (int)$day['capacity']) {
            throw new RuntimeException('daily quota reached');
        }
        $local = (new DateTimeImmutable($startUtc, $this->utc))->setTimezone($this->tehran);
        if ($local->format('Y-m-d') !== (string)$day['open_date']) {
            throw new RuntimeException('slot outside open day');
        }
        $time = $local->format('H:i:s');
        if ($day['opens_at'] !== null && $time < (string)$day['opens_at']) {
            throw new RuntimeException('slot outside working hours');
        }
        if ($day['closes_at'] !== null) {
            $end = $local->modify('+' . (int)$day['slot_duration_minutes'] . ' minutes')->format('H:i:s');
            if ($end > (string)$day['closes_at']) {
                throw new RuntimeException('slot outside working hours');
            }
        }
    }

    private function assertNoAppointmentOverlap(PDO $db, int $clinicId, string $startUtc, int $duration): void
    {
        $stmt = $db->prepare(
            'SELECT id FROM appointments
             WHERE clinic_id = ? AND deleted_at IS NULL AND status IN ("scheduled","confirmed")
               AND ? < DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)
               AND DATE_ADD(?, INTERVAL ? MINUTE) > scheduled_at
             LIMIT 1 FOR UPDATE'
        );
        $stmt->execute([$clinicId, $startUtc, $startUtc, $duration]);
        if ($stmt->fetch()) {
            throw new RuntimeException('slot unavailable');
        }
    }

    private function enqueueCalendar(PDO $db, int $clinicId, string $aggregateType, int $aggregateId, string $action, array $payload): void
    {
        $key = hash('sha256', $clinicId . '|' . $aggregateType . '|' . $aggregateId . '|' . $action . '|' . json_encode($payload));
        $db->prepare(
            'INSERT IGNORE INTO calendar_sync_outbox
             (clinic_id, aggregate_type, aggregate_id, action, idempotency_key, payload, status, next_attempt_at)
             VALUES (?, ?, ?, ?, ?, ?, "pending", UTC_TIMESTAMP())'
        )->execute([
            $clinicId,
            $aggregateType,
            $aggregateId,
            $action,
            $key,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ]);
    }

    private function normaliseStart(string $value): string
    {
        try {
            $dt = new DateTimeImmutable($value);
        } catch (\Throwable) {
            throw new RuntimeException('invalid slot start');
        }
        return $dt->setTimezone($this->utc)->format('Y-m-d H:i:s');
    }

    private function validDate(string $date): bool
    {
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $this->utc);
        return $dt !== false && $dt->format('Y-m-d') === $date;
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-'
            . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
