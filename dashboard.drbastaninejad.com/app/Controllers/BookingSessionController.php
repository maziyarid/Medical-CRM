<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\OtpService;
use App\Services\BookingFinalisationService;
use App\Services\BookingBlacklistService;
use App\Validators\ValidatorService;

final class BookingSessionController extends Controller
{
    public function create(Request $req): array
    {
        $configured = trim((string)($_ENV['WORDPRESS_BRIDGE_SECRET'] ?? ''));
        $provided = trim((string)($req->headers['x-wordpress-bridge-secret'] ?? ''));
        if ($configured === '' || $provided === '' || !hash_equals($configured, $provided)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $bookingId = (int)($req->body['booking_id'] ?? 0);
        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        if ($bookingId < 1 || !ValidatorService::isValidMobile($mobile)) {
            return $this->error('درخواست ادامه رزرو معتبر نیست.', 422);
        }
        $stmt = Database::conn()->prepare(
            'SELECT clinic_id,patient_id FROM intakes
             WHERE id = ? AND mobile = ? AND source_type = "booking" AND deleted_at IS NULL
               AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 MINUTE)
             LIMIT 1'
        );
        $stmt->execute([$bookingId, $mobile]);
        $intake = $stmt->fetch();
        if (!$intake || (int)($intake['patient_id'] ?? 0) < 1) {
            return $this->error('نشست ادامه رزرو منقضی یا نامعتبر است.', 410);
        }
        if ((new BookingBlacklistService())->isIntakeBlocked(
            (int)$intake['clinic_id'], $bookingId, (int)$intake['patient_id']
        )) {
            return $this->error('شما واجد شرایط نیستید.', 403);
        }
        try {
            $session = (new OtpService())->issueToken($mobile, 'patient', 'session');
            return $this->success([
                'patient_session_token' => (string)($session['token'] ?? ''),
                'patient_session_expires_at' => (string)($session['expires_at'] ?? ''),
                'intake_id' => $bookingId,
            ]);
        } catch (\Throwable $e) {
            error_log('[BookingSessionController] session exchange failed: ' . $e->getMessage());
            return $this->error('ایجاد نشست رزرو انجام نشد.', 500);
        }
    }
    public function resume(Request $req): array
    {
        $configured = trim((string)($_ENV['WORDPRESS_BRIDGE_SECRET'] ?? ''));
        $provided = trim((string)($req->headers['x-wordpress-bridge-secret'] ?? ''));
        if ($configured === '' || $provided === '' || !hash_equals($configured, $provided)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        try {
            return $this->success((new BookingFinalisationService())->resume(trim((string)($req->body['resume_token'] ?? ''))));
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'booking blocked') {
                return $this->error('شما واجد شرایط نیستید.', 403);
            }
            $status = $e->getMessage() === 'resume token expired' ? 410 : 422;
            return $this->error('لینک تکمیل نوبت نامعتبر یا منقضی شده است.', $status);
        } catch (\Throwable $e) {
            error_log('[BookingSessionController] resume failed: ' . $e->getMessage());
            return $this->error('بازیابی درخواست نوبت انجام نشد.', 500);
        }
    }

}
