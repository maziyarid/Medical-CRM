<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;

/**
 * Safety boundary shared by patient and receptionist booking writes.
 * Uses the same MySQL advisory lock name as AppointmentService so a public
 * hold cannot race a staff/Calendar appointment write for the same clinic.
 */
final class AppointmentBookingGuardService
{
    private AppointmentBookingService $bookings;
    private DateTimeZone $utc;

    public function __construct(?AppointmentBookingService $bookings = null)
    {
        $this->bookings = $bookings ?? new AppointmentBookingService();
        $this->utc = new DateTimeZone('UTC');
    }

    /** @return array<string,mixed> */
    public function createPatientHold(
        int $clinicId,
        int $patientId,
        int $openDayId,
        string $requestedStart,
        int $amountRials,
        string $gateway,
        ?string $submissionUuid = null
    ): array {
        return $this->withClinicLock($clinicId, function (PDO $db) use (
            $clinicId,
            $patientId,
            $openDayId,
            $requestedStart,
            $amountRials,
            $gateway,
            $submissionUuid
        ): array {
            $this->assertPatientClinic($db, $clinicId, $patientId);

            if ($submissionUuid !== null && $submissionUuid !== '') {
                $existing = $this->existingSubmission($db, $clinicId, $patientId, $submissionUuid);
                if ($existing !== null) {
                    return $existing;
                }
            }

            $duration = $this->openDayDuration($db, $clinicId, $openDayId);
            $startUtc = $this->normaliseStart($requestedStart);
            $this->assertNoAppointmentOverlap($db, $clinicId, $startUtc, $duration);

            $created = $this->bookings->createPatientHold(
                $clinicId,
                $patientId,
                $openDayId,
                $requestedStart,
                $amountRials,
                $gateway,
                $submissionUuid
            );
            $created['idempotent'] = false;
            return $created;
        });
    }

    /** @return array<string,mixed> */
    public function createAdminFreeBooking(
        int $clinicId,
        int $patientId,
        int $staffUserId,
        int $openDayId,
        string $requestedStart,
        ?string $visitReason = null,
        ?string $notes = null
    ): array {
        return $this->withClinicLock($clinicId, function (PDO $db) use (
            $clinicId,
            $patientId,
            $staffUserId,
            $openDayId,
            $requestedStart,
            $visitReason,
            $notes
        ): array {
            $this->assertPatientClinic($db, $clinicId, $patientId);
            $duration = $this->openDayDuration($db, $clinicId, $openDayId);
            $startUtc = $this->normaliseStart($requestedStart);
            $this->assertNoAppointmentOverlap($db, $clinicId, $startUtc, $duration);
            return $this->bookings->createAdminFreeBooking(
                $clinicId,
                $patientId,
                $staffUserId,
                $openDayId,
                $requestedStart,
                $visitReason,
                $notes
            );
        });
    }

    /** @return array<string,mixed> */
    public function confirmPaidByStaff(int $clinicId, int $bookingId, int $staffUserId): array
    {
        return $this->withClinicLock($clinicId, function (PDO $db) use ($clinicId, $bookingId, $staffUserId): array {
            $stmt = $db->prepare(
                'SELECT patient_id FROM appointment_booking_requests WHERE id = ? AND clinic_id = ? LIMIT 1'
            );
            $stmt->execute([$bookingId, $clinicId]);
            $patientId = $stmt->fetchColumn();
            if ($patientId === false) {
                throw new RuntimeException('booking not found');
            }
            $this->assertPatientClinic($db, $clinicId, (int)$patientId);
            return $this->bookings->confirmPaidByStaff($clinicId, $bookingId, $staffUserId);
        });
    }

    /** @return array<string,mixed>|null */
    private function existingSubmission(PDO $db, int $clinicId, int $patientId, string $submissionUuid): ?array
    {
        $stmt = $db->prepare(
            'SELECT id, uuid, requested_start_at, payment_status, confirmation_status, hold_expires_at
             FROM appointment_booking_requests
             WHERE submission_uuid = ? AND clinic_id = ? AND patient_id = ? LIMIT 1'
        );
        $stmt->execute([$submissionUuid, $clinicId, $patientId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $remaining = 0;
        if ($row['hold_expires_at'] !== null) {
            $remaining = max(0, (int)ceil((strtotime((string)$row['hold_expires_at']) - time()) / 60));
        }
        return [
            'id' => (int)$row['id'],
            'uuid' => (string)$row['uuid'],
            'requested_start_at' => (string)$row['requested_start_at'],
            'payment_status' => (string)$row['payment_status'],
            'confirmation_status' => (string)$row['confirmation_status'],
            'hold_minutes' => $remaining,
            'idempotent' => true,
        ];
    }

    private function assertPatientClinic(PDO $db, int $clinicId, int $patientId): void
    {
        if ($patientId <= 0) {
            throw new RuntimeException('patient not found');
        }
        $stmt = $db->prepare(
            'SELECT id FROM patients WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$patientId, $clinicId]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('patient not found');
        }
    }

    private function openDayDuration(PDO $db, int $clinicId, int $openDayId): int
    {
        $stmt = $db->prepare(
            'SELECT slot_duration_minutes FROM appointment_open_days WHERE id = ? AND clinic_id = ? LIMIT 1'
        );
        $stmt->execute([$openDayId, $clinicId]);
        $duration = $stmt->fetchColumn();
        if ($duration === false) {
            throw new RuntimeException('open day not found');
        }
        return max(1, (int)$duration);
    }

    private function assertNoAppointmentOverlap(PDO $db, int $clinicId, string $startUtc, int $duration): void
    {
        $stmt = $db->prepare(
            'SELECT id FROM appointments
             WHERE clinic_id = ? AND deleted_at IS NULL AND status IN ("scheduled","confirmed")
               AND ? < DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)
               AND DATE_ADD(?, INTERVAL ? MINUTE) > scheduled_at
             LIMIT 1'
        );
        $stmt->execute([$clinicId, $startUtc, $startUtc, $duration]);
        if ($stmt->fetch()) {
            throw new RuntimeException('slot unavailable');
        }
    }

    /** @template T @param callable(PDO):T $fn @return T */
    private function withClinicLock(int $clinicId, callable $fn): mixed
    {
        $db = Database::conn();
        $lockName = 'crm:appointments:' . $clinicId;
        $stmt = $db->prepare('SELECT GET_LOCK(?, 8)');
        $stmt->execute([$lockName]);
        if ((int)$stmt->fetchColumn() !== 1) {
            throw new RuntimeException('clinic lock timeout');
        }
        try {
            return $fn($db);
        } finally {
            try {
                $release = $db->prepare('SELECT RELEASE_LOCK(?)');
                $release->execute([$lockName]);
            } catch (\Throwable $e) {
                error_log('[AppointmentBookingGuardService] failed to release clinic lock: ' . $e->getMessage());
            }
        }
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
}
