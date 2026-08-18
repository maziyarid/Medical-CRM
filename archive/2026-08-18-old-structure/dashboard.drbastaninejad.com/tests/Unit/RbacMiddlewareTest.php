<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Core\Request;
use App\Middleware\RbacMiddleware;

/**
 * RbacMiddlewareTest — Unit (no DB required for most cases)
 *
 * Tests the gate logic of RbacMiddleware without relying on database
 * permission lookups for the non-staff paths (super_admin bypass,
 * no-user 403, patient scope guard).
 *
 * Cases that hit staffHasPermission() require a DB and are tagged
 * @group db_optional — they degrade gracefully when no DB is present.
 *
 * SYNTHETIC DATA ONLY.
 */
final class RbacMiddlewareTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function makeRequest(?array $user): Request
    {
        $req          = new Request();
        $req->method  = 'GET';
        $req->path    = '/api/v1/patients';
        $req->body    = [];
        $req->query   = [];
        $req->params  = [];
        $req->headers = [];
        $req->user    = $user;
        return $req;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // No user attached (AuthMiddleware not run)
    // ─────────────────────────────────────────────────────────────────────────

    public function testNullUserReturns403(): void
    {
        $req    = $this->makeRequest(null);
        $result = (new RbacMiddleware('patients.view'))->handle($req);

        $this->assertNotNull($result);
        $this->assertSame(403, $result['status']);
        $this->assertFalse($result['ok']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // super_admin bypasses all checks
    // ─────────────────────────────────────────────────────────────────────────

    public function testSuperAdminPassesThroughAllPermissions(): void
    {
        $user = ['id' => 1, 'clinic_id' => 1, 'role' => 'super_admin', 'user_type' => 'staff'];

        foreach (['patients.view', 'emr.edit', 'billing.manage', 'settings.manage', 'analytics.view'] as $perm) {
            $req    = $this->makeRequest($user);
            $result = (new RbacMiddleware($perm))->handle($req);

            $this->assertNull($result, "super_admin must pass through permission '{$perm}'");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // patient user_type — patient.* allowed, staff.* forbidden
    // ─────────────────────────────────────────────────────────────────────────

    public function testPatientUserPassesPatientScopedPermission(): void
    {
        $user   = ['id' => 10, 'clinic_id' => 1, 'role' => 'patient', 'user_type' => 'patient'];
        $req    = $this->makeRequest($user);
        $result = (new RbacMiddleware('patient.overview'))->handle($req);

        $this->assertNull($result, 'patient user must pass patient.* permission');
    }

    public function testPatientUserBlockedFromStaffPermissions(): void
    {
        $user = ['id' => 10, 'clinic_id' => 1, 'role' => 'patient', 'user_type' => 'patient'];

        foreach (['patients.view', 'emr.edit', 'billing.manage', 'dashboard.view'] as $perm) {
            $req    = $this->makeRequest($user);
            $result = (new RbacMiddleware($perm))->handle($req);

            $this->assertNotNull($result, "patient must be blocked from '{$perm}'");
            $this->assertSame(403, $result['status'], "patient must get 403 for '{$perm}'");
        }
    }

    public function testPatientUserBlockedFromPermissionWithoutPatientPrefix(): void
    {
        $user   = ['id' => 10, 'clinic_id' => 1, 'role' => 'patient', 'user_type' => 'patient'];
        $req    = $this->makeRequest($user);
        $result = (new RbacMiddleware('settings.manage'))->handle($req);

        $this->assertSame(403, $result['status']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // staff role — DB required for permission lookup
    // Gracefully degrades: if DB unreachable, staffHasPermission() returns false
    // and the middleware returns 403 (safe default).
    // ─────────────────────────────────────────────────────────────────────────

    /** @group db_optional */
    public function testStaffWithoutDbGetsSafe403(): void
    {
        // When DB is unavailable, staffHasPermission() catches the exception
        // and returns false — the middleware must return 403, not throw.
        $user = ['id' => 99, 'clinic_id' => 1, 'role' => 'doctor', 'user_type' => 'staff'];

        $req = $this->makeRequest($user);
        try {
            $result = (new RbacMiddleware('patients.view'))->handle($req);
            // DB either available (result is null/403) or unavailable (403)
            // The important invariant: must never throw
            $this->assertTrue(
                $result === null || $result['status'] === 403,
                'Must return null (pass) or 403 (deny) — never throw'
            );
        } catch (\Throwable $e) {
            $this->fail('RbacMiddleware must not throw — caught: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Response shape
    // ─────────────────────────────────────────────────────────────────────────

    public function testForbiddenResponseHasCorrectShape(): void
    {
        $req    = $this->makeRequest(null);
        $result = (new RbacMiddleware('patients.view'))->handle($req);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('ok',     $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertFalse($result['ok']);
        $this->assertSame(403, $result['status']);
        $this->assertIsArray($result['errors']);
        $this->assertCount(1, $result['errors']);
        $this->assertNull($result['errors'][0]['field']);
        $this->assertNotEmpty($result['errors'][0]['message']);
    }
}
