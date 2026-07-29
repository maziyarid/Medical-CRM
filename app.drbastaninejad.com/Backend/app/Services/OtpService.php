<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * OtpService — canonical implementation for app.drbastaninejad.com
 *
 * DECISION (2026-07-29, Blackbox AI — see PROGRESS_LOG.md):
 *   This is the ONE canonical OtpService for the platform.
 *   It is a copy of dashboard.drbastaninejad.com/app/Services/OtpService.php with
 *   namespace App\Services unchanged and no logic differences.
 *
 *   The "app_private/src/OtpService.php" referenced in the original brief was NOT
 *   found in the git repository (it is excluded by .gitignore or never committed).
 *   If it surfaces locally, compare against this file; retire the one with less
 *   capability (fewer rate-limit headers, no SmsProviderChain, no bcrypt storage).
 *
 * Lifecycle:
 *   send()          — generate 5-digit code, persist hashed, fire SMS via SmsProviderChain
 *   isRateLimited() — max 3 sends per 10 minutes per mobile
 *   verify()        — return 'ok' | 'invalid' | 'expired'
 *   issueToken()    — create/refresh auth_tokens row, return user payload
 *
 * Tables: otp_codes, auth_tokens, patients
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
    // Issue bearer token
    // -------------------------------------------------------------------------
    public function issueToken(string $mobile): array
    {
        $db = Database::conn();

        $stmt = $db->prepare(
            'SELECT id, uuid, first_name, last_name, mobile FROM patients
             WHERE mobile = ? AND deleted_at IS NULL
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$mobile]);
        $user = $stmt->fetch();

        if (!$user) {
            // Unknown patient — create minimal stub; staff will complete profile later.
            $uuid = bin2hex(random_bytes(16));
            $db->prepare(
                'INSERT INTO patients (uuid, clinic_id, mobile, insurance_status, created_at)
                 VALUES (?, ?, ?, "pending", UTC_TIMESTAMP())'
            )->execute([$uuid, (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1), $mobile]);
            $user = [
                'id'         => (int)$db->lastInsertId(),
                'uuid'       => $uuid,
                'first_name' => '',
                'last_name'  => '',
                'mobile'     => $mobile,
            ];
        }

        $token     = bin2hex(random_bytes(32));
        $expiresAt = gmdate('Y-m-d H:i:s', strtotime('+' . self::TOKEN_DAYS . ' days'));

        try {
            $db->prepare(
                'INSERT INTO auth_tokens (user_id, user_type, token_hash, expires_at, created_at)
                 VALUES (?, "patient", ?, ?, UTC_TIMESTAMP())'
            )->execute([$user['id'], hash('sha256', $token), $expiresAt]);
        } catch (\Throwable $e) {
            // Pre-migration fallback: store in patients.remember_token
            error_log('[OtpService] auth_tokens missing, using remember_token fallback: ' . $e->getMessage());
            $db->prepare('UPDATE patients SET remember_token = ? WHERE id = ?')
               ->execute([hash('sha256', $token), $user['id']]);
        }

        return [
            'token'      => $token,
            'expires_at' => $expiresAt,
            'user'       => [
                'uuid'       => $user['uuid'],
                'first_name' => $user['first_name'],
                'last_name'  => $user['last_name'],
                'mobile'     => $user['mobile'],
                'role'       => 'patient',
            ],
        ];
    }
}
