<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

/**
 * Payment safety facade. Before a gateway success is applied, an expired slot
 * claim is released transactionally so a late payment can never leak held_count
 * or be mistaken for a still-reserved appointment.
 */
final class AppointmentPaymentFacadeService
{
    private AppointmentPaymentService $payments;

    public function __construct(?AppointmentPaymentService $payments = null)
    {
        $this->payments = $payments ?? new AppointmentPaymentService();
    }

    /** @return array{attempt_id:int,gateway:string,authority:string,redirect_url:string} */
    public function start(int $clinicId, int $patientId, int $bookingId): array
    {
        return $this->payments->start($clinicId, $patientId, $bookingId);
    }

    /** @return array<string,mixed> */
    public function verifyCallback(string $gateway, array $callback): array
    {
        $authority = $this->callbackAuthority($gateway, $callback);
        if ($authority !== '') {
            $this->releaseExpiredClaimForAttempt($gateway, $authority);
        }
        return $this->payments->verifyCallback($gateway, $callback);
    }

    /** @return array<string,mixed> */
    public function reconcileAttempt(int $clinicId, int $attemptId): array
    {
        $stmt = Database::conn()->prepare(
            'SELECT a.gateway, a.authority
             FROM appointment_payment_attempts a
             JOIN appointment_booking_requests b ON b.id = a.booking_request_id
             WHERE a.id = ? AND b.clinic_id = ? LIMIT 1'
        );
        $stmt->execute([$attemptId, $clinicId]);
        $attempt = $stmt->fetch();
        if (!$attempt) {
            throw new RuntimeException('payment attempt not found');
        }
        $authority = trim((string)($attempt['authority'] ?? ''));
        if ($authority !== '') {
            $this->releaseExpiredClaimForAttempt((string)$attempt['gateway'], $authority);
        }
        return $this->payments->reconcileAttempt($clinicId, $attemptId);
    }

    private function releaseExpiredClaimForAttempt(string $gateway, string $authority): void
    {
        $db = Database::conn();
        $lookup = $db->prepare(
            'SELECT b.id AS booking_id, b.open_day_id
             FROM appointment_payment_attempts a
             JOIN appointment_booking_requests b ON b.id = a.booking_request_id
             WHERE a.gateway = ? AND a.authority = ? LIMIT 1'
        );
        $lookup->execute([$gateway, $authority]);
        $target = $lookup->fetch();
        if (!$target) {
            return;
        }

        $db->beginTransaction();
        try {
            // Match the lock order used by expiry cleanup: day first, booking second.
            $day = $db->prepare('SELECT id FROM appointment_open_days WHERE id = ? FOR UPDATE');
            $day->execute([(int)$target['open_day_id']]);
            if (!$day->fetch()) {
                throw new RuntimeException('open day not found');
            }

            $bookingStmt = $db->prepare(
                'SELECT id, payment_status, slot_claim_key, hold_expires_at
                 FROM appointment_booking_requests WHERE id = ? FOR UPDATE'
            );
            $bookingStmt->execute([(int)$target['booking_id']]);
            $booking = $bookingStmt->fetch();
            if (!$booking) {
                throw new RuntimeException('booking not found');
            }

            $isLate = (string)$booking['payment_status'] !== 'paid'
                && $booking['slot_claim_key'] !== null
                && $booking['hold_expires_at'] !== null
                && strtotime((string)$booking['hold_expires_at']) < time();

            if ($isLate) {
                $db->prepare(
                    'UPDATE appointment_open_days
                     SET held_count = GREATEST(held_count - 1, 0), updated_at = UTC_TIMESTAMP()
                     WHERE id = ?'
                )->execute([(int)$target['open_day_id']]);
                $db->prepare(
                    'UPDATE appointment_booking_requests
                     SET slot_claim_key = NULL, hold_expires_at = NULL, updated_at = UTC_TIMESTAMP()
                     WHERE id = ?'
                )->execute([(int)$target['booking_id']]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function callbackAuthority(string $gateway, array $callback): string
    {
        return match ($gateway) {
            'zarinpal' => trim((string)($callback['Authority'] ?? $callback['authority'] ?? '')),
            'vandar' => trim((string)($callback['checkout_id'] ?? '')),
            default => '',
        };
    }
}
