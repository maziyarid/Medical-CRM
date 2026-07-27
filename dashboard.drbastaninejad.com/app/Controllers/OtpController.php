<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Services\OtpService;
use App\Services\ValidatorService;

/**
 * OtpController — Phase B deliverable
 *
 * POST /api/v1/auth/otp/send
 *   - Rate-limited: 3 sends per 10 minutes per mobile
 *   - Accepts Persian digits; normalises before storing
 *
 * POST /api/v1/auth/otp/verify
 *   - 5-digit OTP, 5-minute expiry
 *   - On success: returns JWT token + user payload
 */
final class OtpController extends Controller
{
    private OtpService $otpService;

    public function __construct()
    {
        $this->otpService = new OtpService();
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/auth/otp/send
    // -------------------------------------------------------------------------
    public function send(Request $req): array
    {
        $raw    = trim((string)($req->body['mobile'] ?? ''));
        $mobile = ValidatorService::normalizeMobile($raw);

        if (!ValidatorService::isValidMobile($mobile)) {
            return $this->validationError([[
                'field'   => 'mobile',
                'message' => 'شماره موبایل معتبر نیست (مثال: ۰۹۱۲۳۴۵۶۷۸۹)',
            ]]);
        }

        // Rate-limit check: max 3 sends per 10 minutes per mobile
        if ($this->otpService->isRateLimited($mobile)) {
            return $this->error('تعداد درخواست‌های کد تایید بیش از حد مجاز است. ۱۰ دقیقه صبر کنید.', 429);
        }

        $sent = $this->otpService->send($mobile);

        if (!$sent) {
            return $this->error('ارسال کد تایید با خطا مواجه شد. لطفاً دوباره تلاش کنید.', 503);
        }

        return $this->success(['message' => 'کد تایید ارسال شد', 'expires_in' => 300]);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/auth/otp/verify
    // -------------------------------------------------------------------------
    public function verify(Request $req): array
    {
        $raw    = trim((string)($req->body['mobile'] ?? ''));
        $mobile = ValidatorService::normalizeMobile($raw);
        $otp    = ValidatorService::normalizePersianDigits(trim((string)($req->body['otp'] ?? '')));

        if (!ValidatorService::isValidMobile($mobile)) {
            return $this->validationError([['field' => 'mobile', 'message' => 'شماره موبایل معتبر نیست']]);
        }

        if (!preg_match('/^\d{5}$/', $otp)) {
            return $this->validationError([['field' => 'otp', 'message' => 'کد تایید باید ۵ رقم باشد']]);
        }

        $result = $this->otpService->verify($mobile, $otp);

        if ($result === 'expired') {
            return $this->error('کد تایید منقضی شده است. لطفاً مجدداً درخواست کنید.', 410);
        }

        if ($result === 'invalid') {
            return $this->error('کد تایید نامعتبر است.', 401);
        }

        // $result === 'ok' — issue token
        $tokenData = $this->otpService->issueToken($mobile);

        return $this->success([
            'token'      => $tokenData['token'],
            'expires_at' => $tokenData['expires_at'],
            'user'       => $tokenData['user'],
        ]);
    }
}
