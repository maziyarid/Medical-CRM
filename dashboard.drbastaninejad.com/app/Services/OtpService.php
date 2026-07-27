<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * OtpService — Phase B deliverable
 *
 * Manages the full OTP lifecycle:
 *   send()        — generate 5-digit code, persist in otp_codes, fire SMS
 *   isRateLimited() — max 3 sends per 10 minutes per mobile
 *   verify()      — return 'ok' | 'invalid' | 'expired'
 *   issueToken()  — create or refresh a bearer token row, return user payload
 *
 * SMS dispatch delegates to SmsService (existing or stub).
 * Table: otp_codes (mobile, code, purpose, expires_at, used_at, created_at)
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

        // Dispatch SMS — try real service, fall back gracefully
        try {
            $sms = new SmsService();
            $sms->sendOtp($mobile, $code);
        } catch (\Throwable $e) {
            error_log('[OtpService] SMS dispatch failed: ' . $e->getMessage());
            // In development/staging mode, log OTP to error log
            if (($_ENV['APP_ENV'] ?? 'production') !== 'production') {
                error_log("[OtpService][DEV] OTP for $mobile: $code");
            }
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

        // Expiry check
        if (strtotime($row['expires_at']) < time()) {
            return 'expired';
        }

        // Hash check
        if (!password_verify($code, $row['code'])) {
            return 'invalid';
        }

        // Mark as used
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

        // Find or create user row (patients table, by mobile)
        $stmt = $db->prepare(
            'SELECT id, uuid, first_name, last_name, mobile FROM patients
             WHERE mobile = ? AND deleted_at IS NULL
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$mobile]);
        $user = $stmt->fetch();

        if (!$user) {
            // Unregistered patient — create a minimal stub; clinic staff will complete later
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

        // Generate a secure random token (128 bits hex = 32 chars)
        $token     = bin2hex(random_bytes(32));
        $expiresAt = gmdate('Y-m-d H:i:s', strtotime('+' . self::TOKEN_DAYS . ' days'));

        // Store token in a simple auth_tokens table (see migration below)
        // If the table doesn't exist yet, falls back to patients.remember_token
        try {
            $db->prepare(
                'INSERT INTO auth_tokens (user_id, user_type, token_hash, expires_at, created_at)
                 VALUES (?, "patient", ?, ?, UTC_TIMESTAMP())'
            )->execute([$user['id'], hash('sha256', $token), $expiresAt]);
        } catch (\Throwable $e) {
            // Fallback: store in patients.remember_token (pre-migration)
            error_log('[OtpService] auth_tokens table missing, using remember_token fallback: ' . $e->getMessage());
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
