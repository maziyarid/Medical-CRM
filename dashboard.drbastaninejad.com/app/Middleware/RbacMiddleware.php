<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;

/**
 * RbacMiddleware — Phase C
 *
 * Checks that the authenticated user (set by AuthMiddleware) holds the
 * required named permission (e.g. 'patients.view', 'emr.edit').
 *
 * Rules:
 *   - super_admin bypasses all checks.
 *   - 'patient' user_type only passes permission checks named 'patient.*'.
 *   - All other roles pass if their role has the permission in permission_role.
 *
 * Usage in route files:
 *     fn() => new RbacMiddleware('patients.view')
 */
final class RbacMiddleware
{
    public function __construct(private readonly string $requiredPermission) {}

    public function handle(Request $req): ?array
    {
        $user = $req->user ?? null;

        if (!$user) {
            // AuthMiddleware must run before RbacMiddleware
            return $this->forbidden('احراز هویت الزامی است');
        }

        $roles = $user['roles'] ?? [];
        if (!is_array($roles) || $roles === []) {
            $roles = [$user['role'] ?? ''];
        }

        if (in_array('super_admin', $roles, true) && (int)($user['clinic_id'] ?? 0) === (int)($_ENV['SYSTEM_TENANT_ID'] ?? ($user['clinic_id'] ?? 0))) {
            return null;
        }

        if (($user['user_type'] ?? '') === 'patient') {
            if (!str_starts_with($this->requiredPermission, 'patient.')) {
                return $this->forbidden('دسترسی کافی ندارید');
            }
            return null;
        }

        if (!$this->staffHasPermission($user, $this->requiredPermission)) {
            return $this->forbidden('دسترسی کافی ندارید');
        }

        return null;
    }

    private function staffHasPermission(array $user, string $permission): bool
    {
        $userId = (int)($user['id'] ?? 0);
        $clinicId = (int)($user['clinic_id'] ?? 0);
        if ($userId < 1 || $clinicId < 1) {
            return false;
        }
        try {
            $db   = Database::conn();
            $stmt = $db->prepare(
                'SELECT 1
                   FROM users u
                   JOIN role_user ru            ON ru.user_id = u.id
                   JOIN roles r                 ON r.id = ru.role_id
                   JOIN permission_role pr      ON pr.role_id = r.id
                   JOIN permissions p           ON p.id = pr.permission_id
                   WHERE u.id = ? AND u.clinic_id = ? AND u.is_active = 1 AND u.deleted_at IS NULL
                     AND p.name = ?
                   LIMIT 1'
            );
            $stmt->execute([$userId, $clinicId, $permission]);
            return (bool)$stmt->fetch();
        } catch (\Throwable $e) {
            error_log('[RbacMiddleware] DB error: ' . $e->getMessage());
            return false;
        }
    }

    private function forbidden(string $message): array
    {
        return ['ok' => false, 'status' => 403, 'data' => null,
                'errors' => [['field' => null, 'message' => $message]], 'meta' => null];
    }
}
