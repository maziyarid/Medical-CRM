<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\AppointmentAvailabilityAdminService;
use App\Services\AppointmentBookingGuardService;
use App\Services\AppointmentPaymentFacadeService;
use App\Services\AppointmentSlotAvailabilityService;
use App\Services\AppointmentSystemSetupService;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/**
 * Server-to-server bridge used only by the WordPress booking surface/admin.
 * The shared secret never reaches the browser; WordPress validates its own
 * nonce/capability before calling these routes.
 */
final class WordPressAppointmentBridgeController extends Controller
{
    public function status(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $clinicId = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
        return $this->success((new AppointmentSystemSetupService())->readiness($clinicId));
    }

    public function setup(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $clinicId = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
        $result = (new AppointmentSystemSetupService())->ensureSchema($clinicId);
        return $this->success($result, $result['ok'] ? 200 : 207);
    }

    public function openDays(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $clinicId = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
        $tz = new DateTimeZone('Asia/Tehran');
        $today = new DateTimeImmutable('now', $tz);
        $from = trim((string)($req->query['from'] ?? $today->format('Y-m-d')));
        $to = trim((string)($req->query['to'] ?? $today->modify('+60 days')->format('Y-m-d')));
        try {
            $days = (new AppointmentSlotAvailabilityService())->availability($clinicId, $from, $to);
            return $this->success(['days' => $days, 'timezone' => 'Asia/Tehran']);
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    public function saveOpenDay(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        try {
            $row = (new AppointmentAvailabilityAdminService())->upsert(
                (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1),
                null,
                $req->body
            );
            return $this->success($row, 201);
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    public function checkout(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }

        $clinicId = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
        $intakeId = (int)($req->body['intake_id'] ?? 0);
        $openDayId = (int)($req->body['open_day_id'] ?? 0);
        $startAt = trim((string)($req->body['start_at'] ?? ''));
        $gateway = strtolower(trim((string)($req->body['gateway'] ?? '')));
        if ($intakeId <= 0 || $openDayId <= 0 || $startAt === '') {
            return $this->error('اطلاعات زمان رزرو کامل نیست', 422);
        }

        try {
            $this->assertGatewayReady($gateway);
            $db = Database::conn();
            $stmt = $db->prepare(
                'SELECT id, patient_id, submission_uuid
                 FROM intakes
                 WHERE id = ? AND clinic_id = ? AND source_type = "booking" AND deleted_at IS NULL
                 LIMIT 1'
            );
            $stmt->execute([$intakeId, $clinicId]);
            $intake = $stmt->fetch();
            if (!$intake || empty($intake['patient_id']) || empty($intake['submission_uuid'])) {
                throw new RuntimeException('booking intake not found');
            }

            // Idempotency belongs to the selected checkout, not the intake itself.
            // A retry of the same slot/gateway reuses its hold/payment attempt,
            // while a later different slot can create a fresh attempt safely.
            $checkoutSubmission = hash(
                'sha256',
                'wp-v2|' . (string)$intake['submission_uuid'] . '|' . $openDayId . '|' . $startAt . '|' . $gateway
            );

            $amount = (int)($_ENV['BOOKING_APPOINTMENT_DEPOSIT_RIALS'] ?? 0);
            $guard = new AppointmentBookingGuardService();
            $booking = $guard->createPatientHold(
                $clinicId,
                (int)$intake['patient_id'],
                $openDayId,
                $startAt,
                $amount,
                $gateway,
                $checkoutSubmission
            );

            $db->prepare(
                'UPDATE appointment_booking_requests SET intake_id = COALESCE(intake_id, ?), updated_at = UTC_TIMESTAMP()
                 WHERE id = ? AND clinic_id = ?'
            )->execute([$intakeId, (int)$booking['id'], $clinicId]);

            $payment = (new AppointmentPaymentFacadeService())->start(
                $clinicId,
                (int)$intake['patient_id'],
                (int)$booking['id']
            );

            return $this->success([
                'booking' => $booking,
                'payment' => $payment,
            ], 201);
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    private function assertGatewayReady(string $gateway): void
    {
        $enabled = array_values(array_filter(array_map(
            'trim',
            explode(',', strtolower((string)($_ENV['BOOKING_PAYMENT_GATEWAYS'] ?? 'zarinpal,vandar')))
        )));
        if (!in_array($gateway, ['zarinpal', 'vandar'], true) || !in_array($gateway, $enabled, true)) {
            throw new RuntimeException('unsupported payment gateway');
        }
        if ((int)($_ENV['BOOKING_APPOINTMENT_DEPOSIT_RIALS'] ?? 0) <= 0) {
            throw new RuntimeException('booking deposit is not configured');
        }
        if (trim((string)($_ENV['BOOKING_PAYMENT_CALLBACK_BASE'] ?? '')) === '') {
            throw new RuntimeException('payment callback base is not configured');
        }
        if ($gateway === 'zarinpal' && trim((string)($_ENV['ZARINPAL_MERCHANT_ID'] ?? '')) === '') {
            throw new RuntimeException('Zarinpal is not configured');
        }
        if ($gateway === 'vandar' && trim((string)($_ENV['VANDAR_API_TOKEN'] ?? '')) === '') {
            throw new RuntimeException('Vandar is not configured');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is required for payment gateways');
        }
    }

    private function authorised(Request $req): bool
    {
        $configured = trim((string)($_ENV['WORDPRESS_BRIDGE_SECRET'] ?? ''));
        $provided = trim((string)($req->headers['x-wordpress-bridge-secret'] ?? ''));
        return $configured !== '' && $provided !== '' && hash_equals($configured, $provided);
    }

    private function domainError(RuntimeException $e): array
    {
        $message = $e->getMessage();
        $status = match ($message) {
            'booking intake not found', 'patient not found', 'booking not found', 'open day not found' => 404,
            'slot unavailable', 'daily quota reached', 'open day closed', 'booking hold expired',
            'weekly open-day limit reached', 'monthly open-day limit reached' => 409,
            'Zarinpal is not configured', 'Vandar is not configured', 'booking deposit is not configured',
            'payment callback base is not configured', 'cURL extension is required for payment gateways',
            'clinic lock timeout' => 503,
            default => 422,
        };
        $public = match ($message) {
            'slot unavailable' => 'این زمان دیگر در دسترس نیست',
            'daily quota reached' => 'ظرفیت این روز تکمیل شده است',
            'open day closed' => 'این روز برای رزرو بسته شده است',
            'booking hold expired' => 'مهلت نگهداری این زمان تمام شده است؛ دوباره زمان را انتخاب کنید',
            'weekly open-day limit reached' => 'حداکثر دو روز کاری در این هفته قابل تنظیم است',
            'monthly open-day limit reached' => 'حداکثر هشت روز کاری در این ماه قابل تنظیم است',
            'clinic lock timeout' => 'تقویم در حال به‌روزرسانی است؛ دوباره تلاش کنید',
            'booking deposit is not configured' => 'مبلغ بیعانه رزرو هنوز تنظیم نشده است',
            'Zarinpal is not configured', 'Vandar is not configured' => 'درگاه پرداخت انتخاب‌شده هنوز آماده نیست',
            'working hours are required' => 'ساعت شروع و پایان برای روز باز الزامی است',
            'working hours are invalid' => 'ساعت کاری واردشده معتبر نیست',
            'invalid open date' => 'تاریخ روز نوبت‌دهی معتبر نیست',
            default => $message,
        };
        return $this->error($public, $status);
    }
}
