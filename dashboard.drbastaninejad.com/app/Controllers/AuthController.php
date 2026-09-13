<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\OtpService;
use App\Services\PasswordAuthService;
use App\Validators\ValidatorService;
use RuntimeException;

final class AuthController extends Controller
{
    public function passwordLogin(Request $req): array
    {
        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        $password = (string)($req->body['password'] ?? '');
        if (!ValidatorService::isValidMobile($mobile) || $password === '') {
            return $this->error('شماره همراه یا رمز عبور نادرست است.', 401);
        }
        try {
            return $this->success((new PasswordAuthService())->loginStaff($mobile, $password, $this->clientIp($req)));
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'too many login attempts') {
                return $this->error('تلاش‌های ورود بیش از حد مجاز است. ۱۵ دقیقه بعد دوباره تلاش کنید.', 429);
            }
            if ($e->getMessage() === 'staff schema migration lock timeout') {
                return $this->error('سامانه در حال آماده‌سازی است. دوباره تلاش کنید.', 503);
            }
            return $this->error('شماره همراه یا رمز عبور نادرست است.', 401);
        }
    }

    public function activateStaff(Request $req): array
    {
        $token = strtolower(trim((string)($req->body['activation_token'] ?? '')));
        $password = (string)($req->body['new_password'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return $this->error('لینک فعال‌سازی نامعتبر یا منقضی شده است.', 401);
        }
        try {
            $session = (new PasswordAuthService())->activateStaff($token, $password);
            return $this->success(['activated' => true, 'session' => $session]);
        } catch (RuntimeException $e) {
            return $this->error(match ($e->getMessage()) {
                'password must be at least 10 characters' => 'رمز عبور باید حداقل ۱۰ کاراکتر باشد.',
                'password must include a letter and a number' => 'رمز عبور باید حداقل یک حرف و یک عدد داشته باشد.',
                'invalid activation token' => 'لینک فعال‌سازی نامعتبر یا منقضی شده است.',
                default => 'فعال‌سازی حساب انجام نشد.',
            }, $e->getMessage() === 'invalid activation token' ? 401 : 422);
        }
    }

    public function setPassword(Request $req): array
    {
        $user = $req->user ?? [];
        if (($user['user_type'] ?? '') !== 'staff') {
            return $this->error('این عملیات فقط برای کارکنان مجاز است.', 403);
        }
        $password = (string)($req->body['new_password'] ?? '');
        try {
            $session = (new PasswordAuthService())->setStaffPassword((int)$user['id'], $password);
            return $this->success(['updated' => true, 'session' => $session]);
        } catch (RuntimeException $e) {
            return $this->error(match ($e->getMessage()) {
                'password must be at least 10 characters' => 'رمز عبور باید حداقل ۱۰ کاراکتر باشد.',
                'password must include a letter and a number' => 'رمز عبور باید حداقل یک حرف و یک عدد داشته باشد.',
                default => 'ذخیره رمز عبور انجام نشد.',
            }, 422);
        }
    }

    public function me(Request $req): array
    {
        $user = $req->user ?? [];
        unset($user['_token_id'], $user['_token_hash']);
        return $this->success($user);
    }

    public function logout(Request $req): array
    {
        $tokenId = (int)($req->user['_token_id'] ?? 0);
        if ($tokenId > 0) {
            (new OtpService())->revokeTokenById($tokenId);
        }
        return $this->success(['revoked' => true]);
    }

    public function logoutAll(Request $req): array
    {
        $user = $req->user ?? [];
        $userId = (int)($user['id'] ?? 0);
        $userType = (string)($user['user_type'] ?? '');
        if ($userId < 1 || !in_array($userType, ['patient', 'staff'], true)) {
            return $this->error('نشست نامعتبر است.', 401);
        }
        (new OtpService())->revokeAllSessions($userId, $userType);
        return $this->success(['revoked' => true]);
    }

    private function clientIp(Request $req): string
    {
        $forwarded = trim((string)($req->headers['x-forwarded-for'] ?? ''));
        if ($forwarded !== '' && trim((string)($_ENV['TRUSTED_PROXY_IPS'] ?? '')) !== '') {
            $first = trim(explode(',', $forwarded)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
        $real = trim((string)($req->headers['x-real-ip'] ?? ''));
        if (filter_var($real, FILTER_VALIDATE_IP)) {
            return $real;
        }
        $server = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        return filter_var($server, FILTER_VALIDATE_IP) ? $server : '';
    }
}
