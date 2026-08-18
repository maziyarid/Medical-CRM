<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class OtpService
{
    private const EXPIRY_SECONDS = 300;
    private const RATE_WINDOW_MIN = 10;
    private const RATE_LIMIT_COUNT = 3;
    private const SESSION_TOKEN_DAYS = 30;
    private const RESET_TOKEN_SECONDS = 900;

    public function isRateLimited(string $mobile, string $audience = 'patient', string $purpose = 'login'): bool
    {
        $this->assertAudience($audience);
        $this->assertPurpose($purpose);
        $stmt = Database::conn()->prepare(
            'SELECT COUNT(*) FROM otp_codes
             WHERE mobile = ? AND audience = ? AND purpose = ?
               AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? MINUTE)'
        );
        $stmt->execute([$mobile, $audience, $purpose, self::RATE_WINDOW_MIN]);
        return (int)$stmt->fetchColumn() >= self::RATE_LIMIT_COUNT;
    }

    public function sendSms(string $mobile, string $audience = 'patient', string $purpose = 'login'): bool
    {
        [$code] = $this->createOtp($mobile, $audience, $purpose);
        $result = (new SmsProviderChain())->sendOtp($mobile, $code);
        return (bool)$result['ok'];
    }

    public function sendEmail(string $mobile, string $email, string $audience = 'patient', string $purpose = 'reset'): bool
    {
        [$code, $otpId] = $this->createOtp($mobile, $audience, $purpose);
        $sent = (new EmailService())->sendRecoveryOtp($email, $code);
        if (!$sent) {
            Database::conn()->prepare('UPDATE otp_codes SET used_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$otpId]);
        }
        return $sent;
    }

    /** Backward-compatible alias used by older callers. */
    public function send(string $mobile): bool
    {
        return $this->sendSms($mobile, 'patient', 'login');
    }

    /** @return array{0:string,1:int} */
    private function createOtp(string $mobile, string $audience, string $purpose): array
    {
        $this->assertAudience($audience);
        $this->assertPurpose($purpose);
        $code = str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $expiresAt = gmdate('Y-m-d H:i:s', time() + self::EXPIRY_SECONDS);
        $db = Database::conn();
        $db->prepare(
            'INSERT INTO otp_codes (mobile, code, purpose, audience, expires_at, created_at)
             VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())'
        )->execute([$mobile, password_hash($code, PASSWORD_BCRYPT), $purpose, $audience, $expiresAt]);
        $id = (int)$db->lastInsertId();
        if (($_ENV['APP_ENV'] ?? 'production') !== 'production') {
            error_log('[OtpService][dev] OTP generated for ' . substr($mobile, 0, 7) . '*** audience=' . $audience . ' purpose=' . $purpose);
        }
        return [$code, $id];
    }

    public function verify(string $mobile, string $code, string $audience = 'patient', string $purpose = 'login'): string
    {
        $this->assertAudience($audience);
        $this->assertPurpose($purpose);
        $db = Database::conn();
        $stmt = $db->prepare(
            'SELECT id, code, expires_at
             FROM otp_codes
             WHERE mobile = ? AND audience = ? AND purpose = ? AND used_at IS NULL
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$mobile, $audience, $purpose]);
        $row = $stmt->fetch();
        if (!$row) {
            return 'invalid';
        }
        if (strtotime((string)$row['expires_at']) < time()) {
            return 'expired';
        }
        if (!password_verify($code, (string)$row['code'])) {
            return 'invalid';
        }
        $db->prepare('UPDATE otp_codes SET used_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$row['id']]);
        return 'ok';
    }

    /**
     * @return array{token:string,expires_at:string,user:array}
     */
    public function issueToken(string $mobile, string $audience = 'patient', string $tokenPurpose = 'session'): array
    {
        $this->assertAudience($audience);
        if (!in_array($tokenPurpose, ['session', 'password_reset'], true)) {
            throw new \InvalidArgumentException('Invalid token purpose.');
        }
        $db = Database::conn();

        if ($audience === 'patient') {
            $stmt = $db->prepare(
                'SELECT id, uuid, clinic_id, first_name, last_name, mobile
                 FROM patients WHERE mobile = ? AND deleted_at IS NULL LIMIT 1'
            );
            $stmt->execute([$mobile]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new \RuntimeException('حساب بیمار برای این شماره همراه یافت نشد.');
            }
            $payload = [
                'id' => (int)$user['id'], 'uuid' => $user['uuid'],
                'name' => trim($user['first_name'] . ' ' . $user['last_name']),
                'mobile' => $user['mobile'], 'clinic_id' => (int)$user['clinic_id'],
                'role' => 'patient', 'user_type' => 'patient',
            ];
        } else {
            $stmt = $db->prepare(
                'SELECT u.id, u.uuid, u.full_name, u.mobile, u.clinic_id, r.name AS role_name
                 FROM users u
                 LEFT JOIN role_user ru ON ru.user_id = u.id
                 LEFT JOIN roles r ON r.id = ru.role_id
                 WHERE u.mobile = ? AND u.is_active = 1 AND u.deleted_at IS NULL
                 ORDER BY u.created_at DESC LIMIT 1'
            );
            $stmt->execute([$mobile]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new \RuntimeException('حساب کاربری برای این شماره همراه یافت نشد.');
            }
            $payload = [
                'id' => (int)$user['id'], 'uuid' => $user['uuid'],
                'name' => $user['full_name'], 'mobile' => $user['mobile'],
                'clinic_id' => (int)$user['clinic_id'],
                'role' => $user['role_name'] ?? 'staff', 'user_type' => 'staff',
            ];
        }

        $token = bin2hex(random_bytes(32));
        $ttl = $tokenPurpose === 'password_reset' ? self::RESET_TOKEN_SECONDS : self::SESSION_TOKEN_DAYS * 86400;
        $expiresAt = gmdate('Y-m-d H:i:s', time() + $ttl);
        $db->prepare(
            'INSERT INTO auth_tokens (user_id, user_type, purpose, token_hash, expires_at, created_at)
             VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())'
        )->execute([(int)$user['id'], $audience, $tokenPurpose, hash('sha256', $token), $expiresAt]);

        return ['token' => $token, 'expires_at' => $expiresAt, 'user' => $payload];
    }

    public function resetPassword(string $rawResetToken, string $newPassword): bool
    {
        if (strlen($newPassword) < 10 || strlen($newPassword) > 200) {
            throw new \InvalidArgumentException('رمز عبور باید حداقل ۱۰ کاراکتر باشد.');
        }
        $db = Database::conn();
        $hash = hash('sha256', $rawResetToken);
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'SELECT id, user_id FROM auth_tokens
                 WHERE token_hash = ? AND user_type = "patient" AND purpose = "password_reset"
                   AND revoked_at IS NULL AND expires_at > UTC_TIMESTAMP()
                 LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$hash]);
            $token = $stmt->fetch();
            if (!$token) {
                $db->rollBack();
                return false;
            }
            $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
            $passwordHash = password_hash($newPassword, $algorithm);
            if ($passwordHash === false && $algorithm !== PASSWORD_BCRYPT) {
                $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            }
            if ($passwordHash === false) {
                throw new \RuntimeException('Password hashing is not available on this server.');
            }
            $db->prepare('UPDATE patients SET password_hash = ?, updated_at = UTC_TIMESTAMP() WHERE id = ? AND deleted_at IS NULL')
               ->execute([$passwordHash, (int)$token['user_id']]);
            $db->prepare('UPDATE auth_tokens SET revoked_at = UTC_TIMESTAMP() WHERE id = ?')->execute([(int)$token['id']]);
            $db->prepare(
                'UPDATE auth_tokens SET revoked_at = UTC_TIMESTAMP()
                 WHERE user_id = ? AND user_type = "patient" AND purpose = "session" AND revoked_at IS NULL'
            )->execute([(int)$token['user_id']]);
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function assertAudience(string $audience): void
    {
        if (!in_array($audience, ['patient', 'staff'], true)) {
            throw new \InvalidArgumentException('Invalid OTP audience.');
        }
    }

    private function assertPurpose(string $purpose): void
    {
        if (!in_array($purpose, ['login', 'register', 'reset'], true)) {
            throw new \InvalidArgumentException('Invalid OTP purpose.');
        }
    }
}
