<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\OtpService;
use App\Validators\ValidatorService;

final class OtpController extends Controller
{
    private OtpService $otpService;

    public function __construct()
    {
        $this->otpService = new OtpService();
    }

    public function send(Request $req): array
    {
        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        $audience = strtolower(trim((string)($req->body['audience'] ?? 'patient')));
        if (!ValidatorService::isValidMobile($mobile)) {
            return $this->validationError([['field' => 'mobile', 'message' => 'شماره موبایل معتبر نیست']]);
        }
        if (!in_array($audience, ['patient', 'staff'], true)) {
            return $this->validationError([['field' => 'audience', 'message' => 'نوع حساب معتبر نیست']]);
        }
        if ($this->otpService->isRateLimited($mobile, $audience, 'login')) {
            return $this->error('تعداد درخواست‌های کد تایید بیش از حد مجاز است. ۱۰ دقیقه صبر کنید.', 429);
        }
        if (!$this->otpService->sendSms($mobile, $audience, 'login')) {
            return $this->error('ارسال کد تایید با خطا مواجه شد. لطفاً دوباره تلاش کنید.', 503);
        }
        return $this->success(['message' => 'کد تایید ارسال شد', 'expires_in' => 300, 'audience' => $audience]);
    }

    public function verify(Request $req): array
    {
        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        $otp = ValidatorService::normalizePersianDigits(trim((string)($req->body['otp'] ?? '')));
        $audience = strtolower(trim((string)($req->body['audience'] ?? 'patient')));
        if (!ValidatorService::isValidMobile($mobile)) {
            return $this->validationError([['field' => 'mobile', 'message' => 'شماره موبایل معتبر نیست']]);
        }
        if (!preg_match('/^\d{5}$/', $otp)) {
            return $this->validationError([['field' => 'otp', 'message' => 'کد تایید باید ۵ رقم باشد']]);
        }
        if (!in_array($audience, ['patient', 'staff'], true)) {
            return $this->validationError([['field' => 'audience', 'message' => 'نوع حساب معتبر نیست']]);
        }
        $result = $this->otpService->verify($mobile, $otp, $audience, 'login');
        if ($result === 'expired') {
            return $this->error('کد تایید منقضی شده است. لطفاً مجدداً درخواست کنید.', 410);
        }
        if ($result !== 'ok') {
            return $this->error('کد تایید نامعتبر است.', 401);
        }
        try {
            $tokenData = $this->otpService->issueToken($mobile, $audience, 'session');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 404);
        }
        return $this->success($tokenData);
    }
}
