<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Services\Payments\AppointmentPaymentGateway;
use App\Services\Payments\VandarGateway;
use App\Services\Payments\ZarinpalGateway;
use PDOException;
use RuntimeException;

final class AppointmentPaymentService
{
    public function __construct(private ?AppointmentBookingService $bookings = null)
    {
        $this->bookings ??= new AppointmentBookingService();
    }

    /** @return array{attempt_id:int,gateway:string,authority:string,redirect_url:string} */
    public function start(int $clinicId, int $patientId, int $bookingId): array
    {
        $db = Database::conn();
        $stmt = $db->prepare(
            'SELECT b.*, p.mobile, p.email, p.national_id
             FROM appointment_booking_requests b
             JOIN patients p ON p.id = b.patient_id
             WHERE b.id = ? AND b.clinic_id = ? AND b.patient_id = ? LIMIT 1'
        );
        $stmt->execute([$bookingId, $clinicId, $patientId]);
        $booking = $stmt->fetch();
        if (!$booking) {
            throw new RuntimeException('booking not found');
        }
        if ((string)$booking['source'] !== 'online') {
            throw new RuntimeException('admin booking cannot start patient payment');
        }
        if ((string)$booking['payment_status'] === 'paid') {
            throw new RuntimeException('booking is already paid');
        }
        if (!in_array((string)$booking['confirmation_status'], ['holding', 'awaiting_payment'], true)) {
            throw new RuntimeException('booking hold is no longer payable');
        }
        if ($booking['hold_expires_at'] === null || strtotime((string)$booking['hold_expires_at']) < time()) {
            $this->bookings->releaseExpiredHolds($clinicId);
            throw new RuntimeException('booking hold expired');
        }

        $gatewayName = (string)$booking['payment_gateway'];
        $gateway = $this->gateway($gatewayName);
        $amount = (int)$booking['amount_rials'];
        $idempotencyKey = hash('sha256', 'appointment-payment|' . $bookingId . '|' . $gatewayName . '|' . $amount);

        $existing = $db->prepare(
            'SELECT id, authority, status FROM appointment_payment_attempts
             WHERE idempotency_key = ? LIMIT 1'
        );
        $existing->execute([$idempotencyKey]);
        $attempt = $existing->fetch();
        if ($attempt && !empty($attempt['authority']) && in_array((string)$attempt['status'], ['created', 'redirected'], true)) {
            return [
                'attempt_id' => (int)$attempt['id'],
                'gateway' => $gatewayName,
                'authority' => (string)$attempt['authority'],
                'redirect_url' => $gateway->redirectUrl((string)$attempt['authority']),
            ];
        }

        if (!$attempt) {
            try {
                $db->prepare(
                    'INSERT INTO appointment_payment_attempts
                     (booking_request_id, gateway, amount_rials, status, idempotency_key, created_at, updated_at)
                     VALUES (?, ?, ?, "created", ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
                )->execute([$bookingId, $gatewayName, $amount, $idempotencyKey]);
                $attemptId = (int)$db->lastInsertId();
            } catch (PDOException $e) {
                if ((string)$e->getCode() !== '23000') {
                    throw $e;
                }
                $existing->execute([$idempotencyKey]);
                $attempt = $existing->fetch();
                if (!$attempt) {
                    throw $e;
                }
                $attemptId = (int)$attempt['id'];
            }
        } else {
            $attemptId = (int)$attempt['id'];
        }

        $callbackBase = rtrim(trim((string)($_ENV['BOOKING_PAYMENT_CALLBACK_BASE'] ?? '')), '/');
        if ($callbackBase === '') {
            throw new RuntimeException('payment callback base is not configured');
        }
        $callbackUrl = $callbackBase . '/' . rawurlencode($gatewayName);
        $result = $gateway->request($amount, $callbackUrl, [
            'mobile' => $booking['mobile'] ?? null,
            'email' => $booking['email'] ?? null,
            'national_code' => $booking['national_id'] ?? null,
            'checkout_number' => (string)$bookingId,
            'description' => 'رزرو نوبت شماره ' . $bookingId,
        ]);

        $db->prepare(
            'UPDATE appointment_payment_attempts
             SET authority = ?, status = "redirected", request_payload = ?, response_payload = ?, updated_at = UTC_TIMESTAMP()
             WHERE id = ?'
        )->execute([
            $result['authority'],
            json_encode(['callback_url' => $callbackUrl, 'booking_id' => $bookingId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($result['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $attemptId,
        ]);

        return [
            'attempt_id' => $attemptId,
            'gateway' => $gatewayName,
            'authority' => $result['authority'],
            'redirect_url' => $result['redirect_url'],
        ];
    }

    /** @return array<string,mixed> */
    public function verifyCallback(string $gatewayName, array $callback): array
    {
        $authority = $this->callbackAuthority($gatewayName, $callback);
        if ($authority === '') {
            throw new RuntimeException('payment authority is missing');
        }
        $db = Database::conn();
        $stmt = $db->prepare(
            'SELECT a.*, b.clinic_id, b.payment_status AS booking_payment_status
             FROM appointment_payment_attempts a
             JOIN appointment_booking_requests b ON b.id = a.booking_request_id
             WHERE a.gateway = ? AND a.authority = ? LIMIT 1'
        );
        $stmt->execute([$gatewayName, $authority]);
        $attempt = $stmt->fetch();
        if (!$attempt) {
            throw new RuntimeException('payment attempt not found');
        }

        if ((string)$attempt['status'] === 'verified') {
            $state = $this->bookings->markPaid((int)$attempt['booking_request_id'], $gatewayName, (string)($attempt['transaction_ref'] ?? $authority));
            return ['verified' => true, 'idempotent' => true, 'booking' => $state];
        }

        $gateway = $this->gateway($gatewayName);
        $verification = $gateway->verify($authority, (int)$attempt['amount_rials'], $callback);
        if (!$verification['verified']) {
            $db->prepare(
                'UPDATE appointment_payment_attempts
                 SET status = "failed", gateway_code = ?, response_payload = ?, updated_at = UTC_TIMESTAMP()
                 WHERE id = ? AND status <> "verified"'
            )->execute([
                $verification['code'],
                json_encode($verification['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                (int)$attempt['id'],
            ]);
            $db->prepare(
                'UPDATE appointment_booking_requests
                 SET payment_status = IF(payment_status = "paid", "paid", "failed"), updated_at = UTC_TIMESTAMP()
                 WHERE id = ?'
            )->execute([(int)$attempt['booking_request_id']]);
            return ['verified' => false, 'idempotent' => false, 'code' => $verification['code']];
        }

        $reference = (string)($verification['reference'] ?? $authority);
        $db->prepare(
            'UPDATE appointment_payment_attempts
             SET status = "verified", transaction_ref = ?, gateway_code = ?, response_payload = ?, verified_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
             WHERE id = ?'
        )->execute([
            $reference,
            $verification['code'],
            json_encode($verification['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            (int)$attempt['id'],
        ]);
        $state = $this->bookings->markPaid((int)$attempt['booking_request_id'], $gatewayName, $reference);
        return [
            'verified' => true,
            'idempotent' => false,
            'reference' => $reference,
            'booking' => $state,
        ];
    }

    private function gateway(string $name): AppointmentPaymentGateway
    {
        return match ($name) {
            'zarinpal' => new ZarinpalGateway(),
            'vandar' => new VandarGateway(),
            default => throw new RuntimeException('unsupported payment gateway'),
        };
    }

    private function callbackAuthority(string $gatewayName, array $callback): string
    {
        return match ($gatewayName) {
            'zarinpal' => trim((string)($callback['Authority'] ?? $callback['authority'] ?? '')),
            'vandar' => trim((string)($callback['checkout_id'] ?? '')),
            default => '',
        };
    }
}
