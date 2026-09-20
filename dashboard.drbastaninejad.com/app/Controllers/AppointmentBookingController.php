<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\AppointmentAvailabilityAdminService;
use App\Services\AppointmentBookingAdminService;
use App\Services\AppointmentBookingGuardService;
use App\Services\BookingBlacklistService;
use App\Services\AppointmentPatientResolverService;
use App\Services\AppointmentIntegrationSettingsService;
use App\Services\AppointmentPaymentFacadeService;
use App\Services\AppointmentSchemaBootstrapService;
use App\Services\AppointmentSlotAvailabilityService;
use App\Services\ScheduledVisitSheetService;
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
            (new AppointmentSchemaBootstrapService())->ensure();
            $days = (new AppointmentSlotAvailabilityService())->availability($clinicId, $from, $to);
            $enabled = array_filter(array_map('trim', explode(',', strtolower((string)($_ENV['BOOKING_PAYMENT_GATEWAYS'] ?? 'zarinpal,vandar')))));
            $configuredGateways = [];
            if (in_array('zarinpal', $enabled, true) && trim((string)($_ENV['ZARINPAL_MERCHANT_ID'] ?? '')) !== '') {
                $configuredGateways[] = 'zarinpal';
            }
            if (in_array('vandar', $enabled, true)
                && trim((string)($_ENV['VANDAR_API_KEY'] ?? $_ENV['VANDAR_API_TOKEN'] ?? '')) !== '') {
                $configuredGateways[] = 'vandar';
            }
            $depositRials = max(0, (int)($_ENV['BOOKING_APPOINTMENT_DEPOSIT_RIALS'] ?? 0));
            $paymentReady = $depositRials > 0 && $configuredGateways !== [];
            return $this->success([
                'days' => $days,
                'timezone' => 'Asia/Tehran',
                'payment' => [
                    'ready' => $paymentReady,
                    'gateways' => $paymentReady ? $configuredGateways : [],
                    'configured_gateways' => $configuredGateways,
                    'deposit_rials' => $depositRials,
                ],
            ]);
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
        if ((new BookingBlacklistService())->isPatientBlocked($clinicId, (int)$req->user['id'])) {
            return $this->error('شما واجد شرایط نیستید.', 422);
        }
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
            $this->syncSheet((int)$booking['id']);
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
            $clinicId = (int)$req->user['clinic_id'];
            $patientId = (int)$req->user['id'];
            if ((new BookingBlacklistService())->isPatientBlocked($clinicId, $patientId)) {
                return $this->error('شما واجد شرایط نیستید.', 422);
            }
            $result = (new AppointmentPaymentFacadeService())->start(
                $clinicId,
                $patientId,
                (int)$id
            );
            $this->syncSheet((int)$id);
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
            $bookingId = (int)($result['booking']['id'] ?? $result['booking']['booking_id'] ?? 0);
            if ($bookingId > 0) {
                $this->syncSheet($bookingId);
            }
            $returnBase = trim((string)($_ENV['BOOKING_PAYMENT_RETURN_URL'] ?? 'https://drbastaninejad.com/booking/'));
            $separator = str_contains($returnBase, '?') ? '&' : '?';
            $result['return_url'] = $returnBase . $separator . 'payment=' . ($result['verified'] ? 'success' : 'failed');
            if ($bookingId > 0) {
                $result['return_url'] .= '&booking=' . $bookingId;
            }
            $response = $this->success($result, $result['verified'] ? 200 : 402);
            $response['_redirect'] = $result['return_url'];
            return $response;
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    public function adminCreate(Request $req): array
    {
        if (($req->user['user_type'] ?? null) !== 'staff') {
            return $this->error('دسترسی کارکنان الزامی است', 403);
        }
        $clinicId = (int)$req->user['clinic_id'];
        $staffId = (int)$req->user['id'];
        $patientId = (new AppointmentPatientResolverService())->resolve($clinicId, $req->body);
        if ((new BookingBlacklistService())->isPatientBlocked($clinicId, $patientId)) {
            return $this->error('این بیمار در لیست سیاه رزرو قرار دارد.', 409);
        }
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
                $this->syncSheet((int)$booking['id']);
                return $this->success($booking, 201);
            }
            if (!in_array($mode, ['zarinpal', 'vandar'], true)) {
                return $this->error('روش پرداخت نامعتبر است', 422);
            }
            $amount = (int)($_ENV['BOOKING_APPOINTMENT_DEPOSIT_RIALS'] ?? 0);
            if ($amount <= 0) {
                return $this->error('مبلغ رزرو در سامانه تنظیم نشده است', 503);
            }
            $booking = $guard->createPatientHold(
                $clinicId, $patientId, $openDayId, $startAt, $amount, $mode, null, 'admin', $staffId
            );
            $payment = (new AppointmentPaymentFacadeService())->start($clinicId, $patientId, (int)$booking['id']);
            $this->syncSheet((int)$booking['id']);
            return $this->success(['booking' => $booking, 'payment' => $payment], 201);
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    public function reconcilePaidSlot(Request $req, string $id): array
    {
        if (($req->user['user_type'] ?? null) !== 'staff') {
            return $this->error('دسترسی کارکنان الزامی است', 403);
        }
        try {
            $result = (new AppointmentBookingGuardService())->reconcilePaidSlot(
                (int)$req->user['clinic_id'],
                (int)$id,
                (int)$req->user['id'],
                (int)($req->body['open_day_id'] ?? 0),
                (string)($req->body['start_at'] ?? '')
            );
            $this->syncSheet((int)$id);
            return $this->success($result);
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
            $this->syncSheet((int)$id);
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


    public function adminBookings(Request $req): array
    {
        return $this->success((new AppointmentBookingAdminService())->bookings(
            (int)$req->user['clinic_id'], $req->query
        ));
    }

    public function adminOpenDays(Request $req): array
    {
        return $this->success((new AppointmentBookingAdminService())->openDays(
            (int)$req->user['clinic_id'],
            trim((string)($req->query['from'] ?? '')),
            trim((string)($req->query['to'] ?? ''))
        ));
    }

    public function adminPayments(Request $req): array
    {
        return $this->success((new AppointmentBookingAdminService())->payments(
            (int)$req->user['clinic_id'], $req->query
        ));
    }

    public function integrationStatus(Request $req): array
    {
        return $this->success((new AppointmentBookingAdminService())->integrationStatus(
            (int)$req->user['clinic_id']
        ));
    }

    public function updateIntegrationSettings(Request $req): array
    {
        try {
            $updated = (new AppointmentIntegrationSettingsService())->update($req->body);
            $status = (new AppointmentBookingAdminService())->integrationStatus((int)$req->user['clinic_id']);
            return $this->success(['update' => $updated, 'status' => $status]);
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    public function completeFollowup(Request $req, string $id): array
    {
        try {
            $result = (new AppointmentBookingAdminService())->completeFollowup(
                (int)$req->user['clinic_id'], (int)$id, (int)$req->user['id']
            );
            $this->syncSheet((int)$id);
            return $this->success($result);
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    public function reconcilePayment(Request $req, string $id): array
    {
        try {
            $result = (new AppointmentPaymentFacadeService())->reconcileAttempt(
                (int)$req->user['clinic_id'], (int)$id
            );
            $bookingId = (int)($result['booking']['id'] ?? $result['booking']['booking_id'] ?? 0);
            if ($bookingId > 0) {
                $this->syncSheet($bookingId);
            }
            return $this->success($result);
        } catch (RuntimeException $e) {
            return $this->domainError($e);
        }
    }

    public function syncScheduledVisit(Request $req, string $id): array
    {
        $service = new ScheduledVisitSheetService();
        $service->queue((int)$id);
        return $this->success(['booking_id' => (int)$id, 'sheet_sync_status' => $service->syncBooking((int)$id)]);
    }

    private function syncSheet(int $bookingId): void
    {
        try {
            $sheet = new ScheduledVisitSheetService();
            $sheet->queue($bookingId);
            $sheet->syncBooking($bookingId);
        } catch (\Throwable $e) {
            error_log('[AppointmentBookingController] ScheduledVisits sync failed: ' . $e->getMessage());
        }
    }

    private function domainError(RuntimeException $e): array
    {
        $message = $e->getMessage();
        $status = match ($message) {
            'booking not found', 'open day not found', 'payment attempt not found', 'patient not found' => 404,
            'invalid patient mobile', 'invalid patient name', 'patient belongs to another clinic' => 422,
            'slot unavailable', 'daily quota reached', 'open day closed', 'booking hold expired',
            'paid slot requires reconciliation', 'booking slot already reserved',
            'booking is not awaiting paid-slot reconciliation',
            'weekly open-day limit reached', 'monthly open-day limit reached' => 409,
            'Zarinpal is not configured', 'Vandar is not configured',
            'payment callback base is not configured', 'cURL extension is required for payment gateways',
            'clinic lock timeout', 'appointment schema migration lock timeout' => 503,
            default => 422,
        };
        $public = match ($message) {
            'slot unavailable' => 'این زمان دیگر در دسترس نیست',
            'daily quota reached' => 'ظرفیت این روز تکمیل شده است',
            'booking hold expired' => 'مهلت نگهداری این زمان تمام شده است؛ دوباره زمان را انتخاب کنید',
            'paid slot requires reconciliation' => 'پرداخت ثبت شده اما زمان نیاز به بررسی پذیرش دارد',
            'weekly open-day limit reached' => 'حداکثر دو روز کاری در این هفته قابل تنظیم است',
            'monthly open-day limit reached' => 'حداکثر هشت روز کاری در این ماه قابل تنظیم است',
            'clinic lock timeout', 'appointment schema migration lock timeout' => 'سامانه نوبت در حال آماده‌سازی است؛ دوباره تلاش کنید',
            default => $message,
        };
        return $this->error($public, $status);
    }
}
