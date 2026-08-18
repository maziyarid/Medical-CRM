<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\OtpService;
use App\Validators\ValidatorService;

/**
 * OtpController — handles POST /api/v1/auth/otp/send and /verify
 *
 * Uses docs/API_CONTRACT.md §SECTION 1 envelope ("success"/"data"/"error").
 */
final class OtpController extends Controller
{
    private OtpService       $otp;
    private ValidatorService $validator;

    public function __construct()
    {
        $this->otp       = new OtpService();
        $this->validator = new ValidatorService();
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/auth/otp/send
    // -------------------------------------------------------------------------
    public function send(): void
    {
        $body   = $this->jsonBody();
        $errors = $this->validator->validateOtpSend($body);
        if ($errors !== []) {
            $this->validationError($errors);
        }

        $mobile = $this->validator->normaliseMobile((string)($body['mobile'] ?? ''));

        if ($this->otp->isRateLimited($mobile)) {
            $this->error('OTP_RATE_LIMITED', 'درخواست‌های شما بیش از حد مجاز است. لطفاً ۱۰ دقیقه صبر کنید.', 429);
        }

        $this->otp->send($mobile);
        $this->json(['message' => 'کد تایید ارسال شد', 'expires_in' => 300]);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/auth/otp/verify
    // -------------------------------------------------------------------------
    public function verify(): void
    {
        $body   = $this->jsonBody();
        $errors = $this->validator->validateOtpVerify($body);
        if ($errors !== []) {
            $this->validationError($errors);
        }

        $mobile = $this->validator->normaliseMobile((string)($body['mobile'] ?? ''));
        $otp    = $this->validator->normaliseDigits((string)($body['otp'] ?? ''));

        $result = $this->otp->verify($mobile, $otp);
        match ($result) {
            'expired' => $this->error('OTP_EXPIRED',  'کد تایید منقضی شده است. مجدداً درخواست کنید.', 422),
            'invalid' => $this->error('OTP_INVALID',  'کد تایید اشتباه است.', 422),
            default   => null,
        };

        $payload = $this->otp->issueToken($mobile);
        $this->json($payload);
    }
}
