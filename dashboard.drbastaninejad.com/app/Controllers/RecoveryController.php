<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\OtpService;
use App\Validators\ValidatorService;

final class RecoveryController extends Controller
{
    private OtpService $otp;

    public function __construct()
    {
        $this->otp = new OtpService();
    }

    public function request(Request $req): array
    {
        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        $channel = strtolower(trim((string)($req->body['channel'] ?? 'sms')));
        $emailInput = strtolower(trim((string)($req->body['email'] ?? '')));
        if (!ValidatorService::isValidMobile($mobile)) {
            return $this->validationError([['field' => 'mobile', 'message' => 'شماره موبایل معتبر نیست']]);
        }
        if (!in_array($channel, ['sms', 'email'], true)) {
            return $this->validationError([['field' => 'channel', 'message' => 'کانال بازیابی معتبر نیست']]);
        }
        if ($channel === 'email' && !filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
            return $this->validationError([['field' => 'email', 'message' => 'ایمیل معتبر الزامی است']]);
        }
        if ($this->otp->isRateLimited($mobile, 'patient', 'reset')) {
            return $this->error('تعداد درخواست‌های بازیابی بیش از حد مجاز است. ۱۰ دقیقه صبر کنید.', 429);
        }

        $stmt = Database::conn()->prepare(
            'SELECT id, email FROM patients WHERE mobile = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$mobile]);
        $patient = $stmt->fetch();

        // Anti-enumeration: unknown accounts and email mismatches return the same neutral response.
        if (!$patient) {
            return $this->success(['message' => 'اگر حسابی با این اطلاعات وجود داشته باشد، کد بازیابی ارسال می‌شود.', 'expires_in' => 300]);
        }

        $sent = false;
        if ($channel === 'sms') {
            $sent = $this->otp->sendSms($mobile, 'patient', 'reset');
        } else {
            $storedEmail = strtolower(trim((string)($patient['email'] ?? '')));
            if ($storedEmail !== '' && hash_equals($storedEmail, $emailInput)) {
                $sent = $this->otp->sendEmail($mobile, $storedEmail, 'patient', 'reset');
            }
        }

        if (!$sent && $channel === 'sms') {
            return $this->error('ارسال کد بازیابی با خطا مواجه شد. لطفاً دوباره تلاش کنید.', 503);
        }
        return $this->success([
            'message' => 'اگر حسابی با این اطلاعات وجود داشته باشد، کد بازیابی ارسال می‌شود.',
            'expires_in' => 300,
            'channel' => $channel,
        ]);
    }

    public function verify(Request $req): array
    {
        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        $code = ValidatorService::normalizePersianDigits(trim((string)($req->body['otp'] ?? '')));
        if (!ValidatorService::isValidMobile($mobile)) {
            return $this->validationError([['field' => 'mobile', 'message' => 'شماره موبایل معتبر نیست']]);
        }
        if (!preg_match('/^\d{5}$/', $code)) {
            return $this->validationError([['field' => 'otp', 'message' => 'کد بازیابی باید ۵ رقم باشد']]);
        }
        $result = $this->otp->verify($mobile, $code, 'patient', 'reset');
        if ($result === 'expired') {
            return $this->error('کد بازیابی منقضی شده است.', 410);
        }
        if ($result !== 'ok') {
            return $this->error('کد بازیابی نامعتبر است.', 401);
        }
        try {
            $token = $this->otp->issueToken($mobile, 'patient', 'password_reset');
        } catch (\RuntimeException $e) {
            return $this->error('حساب بیمار یافت نشد.', 404);
        }
        return $this->success([
            'reset_token' => $token['token'],
            'expires_at' => $token['expires_at'],
        ]);
    }

    public function setPassword(Request $req): array
    {
        $token = trim((string)($req->body['reset_token'] ?? ''));
        $password = (string)($req->body['new_password'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return $this->validationError([['field' => 'reset_token', 'message' => 'توکن بازیابی معتبر نیست']]);
        }
        if (strlen($password) < 10 || strlen($password) > 200) {
            return $this->validationError([['field' => 'new_password', 'message' => 'رمز عبور باید حداقل ۱۰ کاراکتر باشد']]);
        }
        try {
            if (!$this->otp->resetPassword($token, $password)) {
                return $this->error('توکن بازیابی نامعتبر یا منقضی شده است.', 401);
            }
        } catch (\Throwable $e) {
            error_log('[RecoveryController] setPassword failed: ' . $e->getMessage());
            return $this->error('ذخیره رمز عبور انجام نشد.', 500);
        }
        return $this->success(['updated' => true, 'message' => 'رمز عبور با موفقیت به‌روزرسانی شد.']);
    }
}
