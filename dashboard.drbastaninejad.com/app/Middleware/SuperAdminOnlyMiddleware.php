<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

/**
 * Fail-closed gate for the first DrB recorder pilot.
 * RbacMiddleware permissions can be broadened later, but the pilot is
 * intentionally restricted to authenticated super_admin staff.
 */
final class SuperAdminOnlyMiddleware
{
    public function handle(Request $req): ?array
    {
        $user = $req->user ?? null;
        if (!$user || ($user['user_type'] ?? '') !== 'staff') {
            return $this->forbidden();
        }

        $clinicId = (int)($user['clinic_id'] ?? 0);
        if ($clinicId < 1) {
            return $this->forbidden();
        }

        $roles = $user['roles'] ?? [];
        if (!is_array($roles) || $roles === []) {
            $roles = [(string)($user['role'] ?? '')];
        }
        $roles = array_map('strval', $roles);

        if (!in_array('super_admin', $roles, true)) {
            return $this->forbidden();
        }

        $systemTenant = (int)($_ENV['SYSTEM_TENANT_ID'] ?? $clinicId);
        if ($systemTenant < 1 || $clinicId !== $systemTenant) {
            return $this->forbidden();
        }

        return null;
    }

    private function forbidden(): array
    {
        return [
            'ok' => false,
            'status' => 403,
            'data' => null,
            'errors' => [['field' => null, 'message' => 'دسترسی ضبط جلسه فقط برای مدیر کل فعال است']],
            'meta' => null,
        ];
    }
}
