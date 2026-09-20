<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\OtpService;
use App\Services\BookingFinalisationService;
use App\Services\BookingBlacklistService;
use App\Services\BookingVerificationService;
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
    public function recover(Request $req): array
    {
        $configured = trim((string)($_ENV['WORDPRESS_BRIDGE_SECRET'] ?? ''));
        $provided = trim((string)($req->headers['x-wordpress-bridge-secret'] ?? ''));
        if ($configured === '' || $provided === '' || !hash_equals($configured, $provided)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        $verification = trim((string)($req->body['verification_token'] ?? ''));
        if (!ValidatorService::isValidMobile($mobile) || !preg_match('/^[a-f0-9]{64}$/', $verification)) {
            return $this->error('تأیید شماره همراه معتبر نیست.', 422);
        }
        $db = Database::conn();
        $stmt = $db->prepare(
            'SELECT id,clinic_id,patient_id,first_name,last_name,mobile FROM intakes
             WHERE mobile=? AND source_type="booking" AND deleted_at IS NULL
               AND status<>"rejected" AND appointment_booking_request_id IS NULL
               AND patient_id IS NOT NULL
               AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$mobile]);
        $row = $stmt->fetch();
        if (!$row) return $this->success(['found'=>false]);
        $clinicId=(int)$row['clinic_id']; $patientId=(int)$row['patient_id']; $intakeId=(int)$row['id'];
        if ((new BookingBlacklistService())->isIntakeBlocked($clinicId,$intakeId,$patientId)) {
            return $this->error('شما واجد شرایط نیستید.', 403);
        }
        $db->beginTransaction();
        try {
            if (!(new BookingVerificationService())->consume($db,$mobile,$verification)) {
                throw new \RuntimeException('verification invalid');
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            if ($e->getMessage()==='verification invalid') return $this->error('تأیید شماره همراه منقضی یا استفاده شده است.',401);
            error_log('[BookingSessionController] recover verification failed: '.$e->getMessage());
            return $this->error('بازیابی درخواست نوبت انجام نشد.',500);
        }
        try {
            $session=(new OtpService())->issueToken($mobile,'patient','session');
            return $this->success([
                'found'=>true,
                'patient_session_token'=>(string)($session['token'] ?? ''),
                'patient_session_expires_at'=>(string)($session['expires_at'] ?? ''),
                'intake_id'=>$intakeId,
                'patient_id'=>$patientId,
                'name'=>trim((string)$row['first_name'].' '.(string)$row['last_name']),
                'mobile'=>$mobile,
            ]);
        } catch (\Throwable $e) {
            error_log('[BookingSessionController] recover session failed: '.$e->getMessage());
            return $this->error('ایجاد نشست ادامه نوبت انجام نشد.',500);
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