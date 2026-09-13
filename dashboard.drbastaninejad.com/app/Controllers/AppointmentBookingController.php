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
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class AppointmentBookingController extends Controller
{
    public function availability(Request $req): array
    {
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

    public function hold(Request $req): array
    {
        if (($req->user['user_type'] ?? null) !== 'patient') {
            return $this->error('ورود بیمار برای رزرو آنلاین الزامی است', 403);
        }
        $clinicId = (int)$req->user['clinic_id'];
        $gateway = strtolower(trim((string)($req->body['gateway'] ?? '')));
        $enabled = array_filter(array_map('trim', explode(',', strtolower((string)($_ENV['BOOKING_PAYMENT_GATEWAYS'] ?? 'zarinpal,vandar')))));
        if (!in_array($gateway, $enabled, true)) {
            return $this->error('درگاه پرداخت انتخاب‌شده فعال نیست', 422);
        }
        $amount = (int)($_ENV['BOOKING_APPOINTMENT_DEPOSIT_RIALS'] ?? 0);
        if ($amount <= 0) {
            return $this->error('مبلغ رزرو در سامانه تنظیم نشده است', 503);
        }
        try {
            $booking = (new AppointmentBookingGuardService())->createPatientHold(
                $clinicId,
                (int)$req->user['id'],
                (int)($req->body['open_day_id'] ?? 0),
                (string)($req->body['start_at'] ?? ''),
                $amount,
                $gateway,
                trim((string)($req->body['submission_uuid'] ?? '')) ?: null
            );
            return $this->success($booking, 201);
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    public function startPayment(Request $req, string $id): array
    {
        if (($req->user['user_type'] ?? null) !== 'patient') {
            return $this->error('ورود بیمار برای پرداخت الزامی است', 403);
        }
        try {
            $result = (new AppointmentPaymentFacadeService())->start(
                (int)$req->user['clinic_id'],
                (int)$req->user['id'],
                (int)$id
            );
            return $this->success($result);
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    public function paymentCallback(Request $req, string $gateway): array
    {
        $gateway = strtolower(trim($gateway));
        if (!in_array($gateway, ['zarinpal', 'vandar'], true)) {
            return $this->error('درگاه پرداخت نامعتبر است', 404);
        }

        try {
            $result = (new AppointmentPaymentFacadeService())->verifyCallback($gateway, $req->query);
            $verified = !empty($result['verified']);
            $slotReserved = $verified && !empty($result['booking']['slot_reserved']);
            $state = $verified ? ($slotReserved ? 'success' : 'review') : 'failed';
            return $this->paymentRedirect($state);
        } catch (RuntimeException $e) {
            error_log('[AppointmentBookingController] payment callback failed: ' . $e->getMessage());
            return $this->paymentRedirect('failed');
        }
    }

    public function adminCreate(Request $req): array
    {
        if (($req->user['user_type'] ?? null) !== 'staff') {
            return $this->error('دسترسی کارکنان الزامی است', 403);
        }
        $clinicId = (int)$req->user['clinic_id'];
        $staffId = (int)$req->user['id'];
        $patientId = (int)($req->body['patient_id'] ?? 0);
        $openDayId = (int)($req->body['open_day_id'] ?? 0);
        $startAt = (string)($req->body['start_at'] ?? '');
        $mode = strtolower(trim((string)($req->body['payment_mode'] ?? 'free')));
        try {
            $guard = new AppointmentBookingGuardService();
            if ($mode === 'free') {
                $booking = $guard->createAdminFreeBooking(
                    $clinicId,
                    $patientId,
                    $staffId,
                    $openDayId,
                    $startAt,
                    trim((string)($req->body['visit_reason'] ?? '')) ?: null,
                    trim((string)($req->body['notes'] ?? '')) ?: null
                );
                return $this->success($booking, 201);
            }
            if (!in_array($mode, ['zarinpal', 'vandar'], true)) {
                return $this->error('روش پرداخت نامعتبر است', 422);
            }
            $amount = (int)($_ENV['BOOKING_APPOINTMENT_DEPOSIT_RIALS'] ?? 0);
            if ($amount <= 0) {
                return $this->error('مبلغ رزرو در سامانه تنظیم نشده است', 503);
            }
            $booking = $guard->createPatientHold($clinicId, $patientId, $openDayId, $startAt, $amount, $mode);
            $payment = (new AppointmentPaymentFacadeService())->start($clinicId, $patientId, (int)$booking['id']);
            Database::conn()->prepare(
                'UPDATE appointment_booking_requests SET source = "admin", receptionist_user_id = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?'
            )->execute([$staffId, (int)$booking['id']]);
            return $this->success(['booking' => $booking, 'payment' => $payment], 201);
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    public function confirm(Request $req, string $id): array
    {
        if (($req->user['user_type'] ?? null) !== 'staff') {
            return $this->error('دسترسی کارکنان الزامی است', 403);
        }
        try {
            $result = (new AppointmentBookingGuardService())->confirmPaidByStaff(
                (int)$req->user['clinic_id'],
                (int)$id,
                (int)$req->user['id']
            );
            return $this->success($result);
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    public function saveOpenDay(Request $req): array
    {
        if (($req->user['user_type'] ?? null) !== 'staff') {
            return $this->error('دسترسی کارکنان الزامی است', 403);
        }
        try {
            $row = (new AppointmentAvailabilityAdminService())->upsert(
                (int)$req->user['clinic_id'],
                (int)$req->user['id'],
                $req->body
            );
            return $this->success($row, 201);
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    /** @return array<string,mixed> */
    private function paymentRedirect(string $state): array
    {
        $base = trim((string)($_ENV['BOOKING_PAYMENT_RETURN_URL'] ?? 'https://drbastaninejad.com/booking/'));
        $parts = parse_url($base);
        $host = strtolower((string)($parts['host'] ?? ''));
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if ($scheme !== 'https' || !in_array($host, ['drbastaninejad.com', 'www.drbastaninejad.com'], true)) {
            $base = 'https://drbastaninejad.com/booking/';
        }
        $separator = str_contains($base, '?') ? '&' : '?';
        return [
            '_redirect' => $base . $separator . http_build_query(['booking_payment' => $state]),
            'status' => 303,
        ];
    }

    private function domainError(RuntimeException $e): array
    {
        $message = $e->getMessage();
        $status = match ($message) {
            'booking not found', 'open day not found', 'payment attempt not found', 'patient not found' => 404,
            'slot unavailable', 'daily quota reached', 'open day closed', 'booking hold expired',
            'paid slot requires reconciliation', 'weekly open-day limit reached', 'monthly open-day limit reached' => 409,
            'Zarinpal is not configured', 'Vandar is not configured',
            'payment callback base is not configured', 'cURL extension is required for payment gateways',
            'clinic lock timeout' => 503,
            default => 422,
        };
        $public = match ($message) {
            'slot unavailable' => 'این زمان دیگر در دسترس نیست',
            'daily quota reached' => 'ظرفیت این روز تکمیل شده است',
            'booking hold expired' => 'مهلت نگهداری این زمان تمام شده است؛ دوباره زمان را انتخاب کنید',
            'paid slot requires reconciliation' => 'پرداخت ثبت شده اما زمان نیاز به بررسی پذیرش دارد',
            'weekly open-day limit reached' => 'حداکثر دو روز کاری در این هفته قابل تنظیم است',
            'monthly open-day limit reached' => 'حداکثر هشت روز کاری در این ماه قابل تنظیم است',
            'clinic lock timeout' => 'تقویم در حال به‌روزرسانی است؛ دوباره تلاش کنید',
            default => $message,
        };
        return $this->error($public, $status);
    }
}
