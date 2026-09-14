<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\OtpService;
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
            'SELECT patient_id FROM intakes
             WHERE id = ? AND mobile = ? AND source_type = "booking" AND deleted_at IS NULL
               AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 MINUTE)
             LIMIT 1'
        );
        $stmt->execute([$bookingId, $mobile]);
        if (!$stmt->fetchColumn()) {
            return $this->error('نشست ادامه رزرو منقضی یا نامعتبر است.', 410);
        }
        try {
            $session = (new OtpService())->issueToken($mobile, 'patient', 'session');
            return $this->success([
                'patient_session_token' => (string)($session['token'] ?? ''),
                'patient_session_expires_at' => (string)($session['expires_at'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            error_log('[BookingSessionController] session exchange failed: ' . $e->getMessage());
            return $this->error('ایجاد نشست رزرو انجام نشد.', 500);
        }
    }
}
