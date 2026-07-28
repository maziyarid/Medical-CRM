<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;

/**
 * AuthMiddleware — Phase C
 *
 * Validates the Bearer token from the Authorization header against the
 * auth_tokens table (SHA-256 hash, not expired, not revoked).
 *
 * On success: populates $req->user with { id, uuid, clinic_id, role, user_type }.
 * On failure: returns a 401 JSON response immediately.
 */
final class AuthMiddleware
{
    public function handle(Request $req): ?array
    {
        $header = $req->headers['Authorization'] ?? $req->headers['authorization'] ?? '';

        if (!str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized('توکن احراز هویت ارائه نشده است');
        }

        $rawToken = trim(substr($header, 7));
        if ($rawToken === '') {
            return $this->unauthorized('توکن احراز هویت ارائه نشده است');
        }

        $tokenHash = hash('sha256', $rawToken);

        try {
            $db   = Database::conn();
            $stmt = $db->prepare(
                'SELECT t.id, t.user_id, t.user_type, t.expires_at, t.revoked_at
                 FROM auth_tokens t
                 WHERE t.token_hash = ?
                 LIMIT 1'
            );
            $stmt->execute([$tokenHash]);
            $token = $stmt->fetch();
        } catch (\Throwable $e) {
            error_log('[AuthMiddleware] DB error: ' . $e->getMessage());
            return $this->unauthorized('خطای داخلی در احراز هویت');
        }

        if (!$token) {
            return $this->unauthorized('توکن نامعتبر است');
        }

        if ($token['revoked_at'] !== null) {
            return $this->unauthorized('نشست شما پایان یافته است. دوباره وارد شوید');
        }

        if (strtotime($token['expires_at']) < time()) {
            return $this->unauthorized('توکن منقضی شده است. دوباره وارد شوید');
        }

        // Resolve user record (patients table for 'patient', users table for 'staff')
        $userId   = (int)$token['user_id'];
        $userType = $token['user_type']; // 'patient' | 'staff'

        try {
            $user = $this->resolveUser($db, $userId, $userType);
        } catch (\Throwable $e) {
            error_log('[AuthMiddleware] resolveUser error: ' . $e->getMessage());
            return $this->unauthorized('خطای داخلی در احراز هویت');
        }

        if (!$user) {
            return $this->unauthorized('کاربر یافت نشد');
        }

        // Attach resolved user to the request for downstream handlers
        $req->user = $user;

        return null; // null = pass through to next handler
    }

    // -------------------------------------------------------------------------

    private function resolveUser(\PDO $db, int $userId, string $userType): ?array
    {
        if ($userType === 'patient') {
            $stmt = $db->prepare(
                'SELECT id, uuid, clinic_id, first_name, last_name, mobile, deleted_at
                 FROM patients WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if (!$row || $row['deleted_at'] !== null) {
                return null;
            }
            return [
                'id'        => (int)$row['id'],
                'uuid'      => $row['uuid'],
                'clinic_id' => (int)$row['clinic_id'],
                'name'      => trim($row['first_name'] . ' ' . $row['last_name']),
                'mobile'    => $row['mobile'],
                'role'      => 'patient',
                'user_type' => 'patient',
            ];
        }

        // staff — from the users table
        $stmt = $db->prepare(
            'SELECT u.id, u.uuid, u.clinic_id, u.full_name, u.mobile, u.is_active, u.deleted_at,
                    r.name AS role
             FROM users u
             LEFT JOIN role_user ru ON ru.user_id = u.id
             LEFT JOIN roles r ON r.id = ru.role_id
             WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || $row['deleted_at'] !== null || !(bool)$row['is_active']) {
            return null;
        }

        return [
            'id'        => (int)$row['id'],
            'uuid'      => $row['uuid'],
            'clinic_id' => (int)$row['clinic_id'],
            'name'      => $row['full_name'],
            'mobile'    => $row['mobile'],
            'role'      => $row['role'] ?? 'staff',
            'user_type' => 'staff',
        ];
    }

    private function unauthorized(string $message): array
    {
        return ['ok' => false, 'status' => 401, 'data' => null, 'errors' => [['field' => null, 'message' => $message]]];
    }
}
