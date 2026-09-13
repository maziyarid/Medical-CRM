<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Validators\ValidatorService;
use RuntimeException;

final class PasswordAuthService
{
    private const DUMMY_HASH = '$2y$10$XAx0ZxGeIonbcmnNHg318OV8e6L.elZPP8vwrMmJ7dl/0TuF3BjTK';
    private const WINDOW_MINUTES = 15;
    private const MAX_FAILURES = 5;

    /** @return array<string,mixed> */
    public function loginStaff(string $mobile, string $password, string $ip = ''): array
    {
        (new StaffSchemaBootstrapService())->ensure();
        $mobile = ValidatorService::normalizeMobile($mobile);
        if (!ValidatorService::isValidMobile($mobile) || strlen($password) < 1 || strlen($password) > 200) {
            throw new RuntimeException('invalid credentials');
        }
        if ($this->isRateLimited($mobile, $ip)) {
            throw new RuntimeException('too many login attempts');
        }

        $db = Database::conn();
        $stmt = $db->prepare(
            'SELECT id, password_hash, is_active FROM users
             WHERE mobile = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$mobile]);
        $user = $stmt->fetch();
        $hash = $user && !empty($user['password_hash']) ? (string)$user['password_hash'] : self::DUMMY_HASH;
        $verified = password_verify($password, $hash);
        if (!$user || !(bool)$user['is_active'] || empty($user['password_hash']) || !$verified) {
            $this->recordAttempt($mobile, $ip, false);
            throw new RuntimeException('invalid credentials');
        }

        $this->recordAttempt($mobile, $ip, true);
        $db->prepare('UPDATE users SET activated_at = COALESCE(activated_at, UTC_TIMESTAMP()), last_login_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = ?')
            ->execute([(int)$user['id']]);
        return (new OtpService())->issueToken($mobile, 'staff', 'session');
    }

    /** @return array<string,mixed> */
    public function setStaffPassword(int $userId, string $newPassword): array
    {
        (new StaffSchemaBootstrapService())->ensure();
        if ($userId < 1) throw new RuntimeException('staff user not found');
        if (strlen($newPassword) < 10 || strlen($newPassword) > 200) {
            throw new RuntimeException('password must be at least 10 characters');
        }
        if (!preg_match('/[A-Za-z\x{0600}-\x{06FF}]/u', $newPassword) || !preg_match('/\d/', $newPassword)) {
            throw new RuntimeException('password must include a letter and a number');
        }
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $hash = password_hash($newPassword, $algo);
        if ($hash === false) throw new RuntimeException('password hashing failed');

        $db = Database::conn();
        $stmt=$db->prepare('SELECT mobile FROM users WHERE id=? AND is_active=1 AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$userId]);
        $mobile=(string)($stmt->fetchColumn() ?: '');
        if ($mobile==='') throw new RuntimeException('staff user not found');

        $db->prepare('UPDATE users SET password_hash=?, activated_at=COALESCE(activated_at,UTC_TIMESTAMP()), updated_at=UTC_TIMESTAMP() WHERE id=?')
            ->execute([$hash,$userId]);
        (new OtpService())->revokeAllSessions($userId,'staff');
        return (new OtpService())->issueToken($mobile,'staff','session');
    }

    public function markOtpLogin(int $userId): void
    {
        if ($userId < 1) return;
        try {
            (new StaffSchemaBootstrapService())->ensure();
            Database::conn()->prepare('UPDATE users SET activated_at=COALESCE(activated_at,UTC_TIMESTAMP()), last_login_at=UTC_TIMESTAMP(), updated_at=UTC_TIMESTAMP() WHERE id=?')
                ->execute([$userId]);
        } catch (\Throwable $e) {
            error_log('[PasswordAuthService] markOtpLogin failed: '.$e->getMessage());
        }
    }

    public function staffExists(string $mobile): bool
    {
        (new StaffSchemaBootstrapService())->ensure();
        $mobile=ValidatorService::normalizeMobile($mobile);
        if (!ValidatorService::isValidMobile($mobile)) return false;
        $stmt=Database::conn()->prepare('SELECT 1 FROM users WHERE mobile=? AND is_active=1 AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$mobile]);
        return (bool)$stmt->fetchColumn();
    }

    private function isRateLimited(string $mobile,string $ip): bool
    {
        $mh=hash('sha256',$mobile);
        $ih=hash('sha256',$ip !== '' ? $ip : 'unknown');
        $stmt=Database::conn()->prepare(
            'SELECT COUNT(*) FROM staff_login_attempts
             WHERE success=0 AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? MINUTE)
               AND (mobile_hash=? OR ip_hash=?)'
        );
        $stmt->execute([self::WINDOW_MINUTES,$mh,$ih]);
        return (int)$stmt->fetchColumn() >= self::MAX_FAILURES;
    }

    private function recordAttempt(string $mobile,string $ip,bool $success): void
    {
        try {
            Database::conn()->prepare('INSERT INTO staff_login_attempts (mobile_hash,ip_hash,success,created_at) VALUES (?,?,?,UTC_TIMESTAMP())')
                ->execute([hash('sha256',$mobile),hash('sha256',$ip !== '' ? $ip : 'unknown'),$success ? 1 : 0]);
        } catch (\Throwable $e) {
            error_log('[PasswordAuthService] login audit failed: '.$e->getMessage());
        }
    }
}
