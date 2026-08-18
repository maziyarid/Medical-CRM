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

        $role = $user['role'] ?? '';

        // super_admin bypasses all permission checks
        if ($role === 'super_admin') {
            return null;
        }

        // Patients may only access patient-scoped endpoints
        if ($user['user_type'] === 'patient') {
            if (!str_starts_with($this->requiredPermission, 'patient.')) {
                return $this->forbidden('دسترسی کافی ندارید');
            }
            return null; // patient portal permissions are granted by user_type alone
        }

        // Staff roles: look up actual permissions from the DB
        if (!$this->staffHasPermission($role, $this->requiredPermission)) {
            return $this->forbidden('دسترسی کافی ندارید');
        }

        return null; // pass through
    }

    // -------------------------------------------------------------------------

    private function staffHasPermission(string $roleName, string $permission): bool
    {
        try {
            $db   = Database::conn();
            $stmt = $db->prepare(
                'SELECT 1
                   FROM roles r
                   JOIN role_user ru            ON ru.role_id = r.id
                   JOIN permission_role pr      ON pr.role_id = r.id
                   JOIN permissions p           ON p.id = pr.permission_id
                   WHERE r.name = ? AND p.name = ?
                   LIMIT 1'
            );
            $stmt->execute([$roleName, $permission]);
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
