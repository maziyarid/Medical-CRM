<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * OtpService — dashboard.drbastaninejad.com
 *
 * Self-contained copy of the canonical OTP service.
 * Previously this file used a fragile relative require_once to load
 * app.drbastaninejad.com/Backend/app/Services/OtpService.php — that
 * pattern breaks whenever the two subdomains are deployed to separate
 * document roots (the normal production layout).
 *
 * RULE: Keep this file in sync with the app-backend canonical by hand
 * whenever OTP logic changes. Do NOT re-introduce the require_once pattern.
 *
 * Lifecycle:
 *   send()          — generate 5-digit code, persist hashed, fire SMS via SmsProviderChain
 *   isRateLimited() — max 3 sends per 10 minutes per mobile
 *   verify()        — return 'ok' | 'invalid' | 'expired'
 *   issueToken()    — create/refresh auth_tokens row, return staff user payload
 *
 * Tables: otp_codes, auth_tokens, users
 * SMS: delegates to SmsProviderChain (Kavenegar→Ghasedak→FarazSMS→TSMS→Log)
 */
final class OtpService
{
    private const EXPIRY_SECONDS   = 300; // 5 minutes
    private const RATE_WINDOW_MIN  = 10;
    private const RATE_LIMIT_COUNT = 3;
    private const TOKEN_DAYS       = 30;

    // -------------------------------------------------------------------------
    // Rate limit check
    // -------------------------------------------------------------------------
    public function isRateLimited(string $mobile): bool
    {
        $db   = Database::conn();
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM otp_codes
             WHERE mobile = ? AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? MINUTE)'
        );
        $stmt->execute([$mobile, self::RATE_WINDOW_MIN]);
        return (int)$stmt->fetchColumn() >= self::RATE_LIMIT_COUNT;
    }

    // -------------------------------------------------------------------------
    // Send OTP
    // -------------------------------------------------------------------------
    public function send(string $mobile): bool
    {
        $code      = str_pad((string)random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        $expiresAt = gmdate('Y-m-d H:i:s', time() + self::EXPIRY_SECONDS);

        $db = Database::conn();
        $db->prepare(
            'INSERT INTO otp_codes (mobile, code, purpose, expires_at, created_at)
             VALUES (?, ?, "login", ?, UTC_TIMESTAMP())'
        )->execute([$mobile, password_hash($code, PASSWORD_BCRYPT), $expiresAt]);

        // Dispatch via provider chain — never throws (LogSmsProvider is the final fallback).
        (new SmsProviderChain())->sendOtp($mobile, $code);

        // In non-production environments log the OTP plaintext for development convenience.
        if (($_ENV['APP_ENV'] ?? 'production') !== 'production') {
            error_log('[OtpService][dev] OTP for ' . $mobile . ': ' . $code);
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Verify OTP
    // -------------------------------------------------------------------------
    public function verify(string $mobile, string $code): string
    {
        $db   = Database::conn();
        $stmt = $db->prepare(
            'SELECT id, code, expires_at, used_at
             FROM otp_codes
             WHERE mobile = ? AND used_at IS NULL
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $stmt->execute([$mobile]);
        $row = $stmt->fetch();

        if (!$row) {
            return 'invalid';
        }

        if (strtotime($row['expires_at']) < time()) {
            return 'expired';
        }

        if (!password_verify($code, $row['code'])) {
            return 'invalid';
        }

        $db->prepare('UPDATE otp_codes SET used_at = UTC_TIMESTAMP() WHERE id = ?')
           ->execute([$row['id']]);

        return 'ok';
    }

    // -------------------------------------------------------------------------
    // Issue bearer token (staff variant — looks up users table, not patients)
    // -------------------------------------------------------------------------
    public function issueToken(string $mobile): array
    {
        $db = Database::conn();

        // Dashboard OTP authenticates staff users (users table, not patients).
        $stmt = $db->prepare(
            'SELECT u.id, u.uuid, u.first_name, u.last_name, u.mobile,
                    u.clinic_id, r.name AS role_name
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.mobile = ? AND u.deleted_at IS NULL
             ORDER BY u.created_at DESC LIMIT 1'
        );
        $stmt->execute([$mobile]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new \RuntimeException('شماره همراه در سیستم ثبت نشده است.');
        }

        $token     = bin2hex(random_bytes(32));
        $expiresAt = gmdate('Y-m-d H:i:s', strtotime('+' . self::TOKEN_DAYS . ' days'));

        $db->prepare(
            'INSERT INTO auth_tokens (user_id, user_type, token_hash, expires_at, created_at)
             VALUES (?, "staff", ?, ?, UTC_TIMESTAMP())'
        )->execute([$user['id'], hash('sha256', $token), $expiresAt]);

        return [
            'token'      => $token,
            'expires_at' => $expiresAt,
            'user'       => [
                'id'         => (int)$user['id'],
                'uuid'       => $user['uuid'],
                'first_name' => $user['first_name'],
                'last_name'  => $user['last_name'],
                'mobile'     => $user['mobile'],
                'clinic_id'  => (int)$user['clinic_id'],
                'role'       => $user['role_name'] ?? 'staff',
                'user_type'  => 'staff',
            ],
        ];
    }
}
