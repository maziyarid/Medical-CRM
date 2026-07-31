<?php
declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\Database;
use App\Core\Request;
use App\Middleware\AuthMiddleware;

/**
 * AuthMiddlewareTest — Integration (@group db)
 *
 * Tests the full token round-trip against a real test database:
 *   1. Insert a staff user + token row directly into auth_tokens.
 *   2. Build a Request with the matching Bearer header.
 *   3. Assert AuthMiddleware.handle() returns null (pass-through) and
 *      populates $req->user with the expected fields.
 *
 * REQUIRES:
 *   - .env.testing with DB_* credentials pointing at mazcrm_test
 *   - Migrations 010, 012, 014 applied to mazcrm_test
 *   - Run: ./vendor/bin/phpunit --testsuite Integration
 *
 * SKIPS automatically if .env.testing is absent or DB is unreachable.
 *
 * SYNTHETIC DATA ONLY — all mobile numbers, names, and tokens are
 * randomly generated for the test and deleted in tearDown().
 */
final class AuthMiddlewareTest extends TestCase
{
    private ?\PDO $db = null;

    /** @var list<array{table:string, id:int}> */
    private array $cleanup = [];

    // ─────────────────────────────────────────────────────────────────────────
    // Setup / Teardown
    // ─────────────────────────────────────────────────────────────────────────

    protected function setUp(): void
    {
        if (!isset($_ENV['DB_HOST'])) {
            $this->markTestSkipped('Integration tests require .env.testing with DB credentials.');
        }

        try {
            $this->db = Database::conn();
            $this->db->query('SELECT 1');
        } catch (\Throwable) {
            $this->markTestSkipped('Database unreachable — skipping integration tests.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->db === null) {
            return;
        }
        // Clean up in reverse insertion order to respect FK constraints
        foreach (array_reverse($this->cleanup) as $item) {
            $this->db->prepare("DELETE FROM {$item['table']} WHERE id = ?")
                     ->execute([$item['id']]);
        }
        // Reset singleton so the next test gets a fresh connection state
        Database::reset();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Happy path — valid staff token
    // ─────────────────────────────────────────────────────────────────────────

    /** @group db */
    public function testValidStaffTokenPassesThroughAndPopulatesUser(): void
    {
        [$userId, $rawToken] = $this->insertStaffUserAndToken();

        $req = $this->makeRequest("Bearer {$rawToken}");
        $result = (new AuthMiddleware())->handle($req);

        $this->assertNull($result, 'AuthMiddleware must return null (pass-through) for a valid token');
        $this->assertNotNull($req->user, '$req->user must be populated after a valid token');
        $this->assertSame($userId, $req->user['id']);
        $this->assertSame('staff', $req->user['user_type']);
        $this->assertSame(1, $req->user['clinic_id']);
        $this->assertArrayHasKey('role', $req->user);
        $this->assertArrayHasKey('name', $req->user);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Missing / malformed token
    // ─────────────────────────────────────────────────────────────────────────

    /** @group db */
    public function testMissingAuthorizationHeaderReturns401(): void
    {
        $req    = $this->makeRequest('');
        $result = (new AuthMiddleware())->handle($req);

        $this->assertNotNull($result);
        $this->assertSame(401, $result['status']);
        $this->assertFalse($result['ok']);
    }

    /** @group db */
    public function testBearerPrefixMissingReturns401(): void
    {
        $req    = $this->makeRequest('Token abc123');
        $result = (new AuthMiddleware())->handle($req);

        $this->assertNotNull($result);
        $this->assertSame(401, $result['status']);
    }

    /** @group db */
    public function testEmptyTokenAfterBearerReturns401(): void
    {
        $req    = $this->makeRequest('Bearer ');
        $result = (new AuthMiddleware())->handle($req);

        $this->assertNotNull($result);
        $this->assertSame(401, $result['status']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Unknown token
    // ─────────────────────────────────────────────────────────────────────────

    /** @group db */
    public function testUnknownTokenReturns401(): void
    {
        $req    = $this->makeRequest('Bearer ' . bin2hex(random_bytes(32)));
        $result = (new AuthMiddleware())->handle($req);

        $this->assertNotNull($result);
        $this->assertSame(401, $result['status']);
        $this->assertNull($req->user, '$req->user must remain null for an unknown token');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Expired token
    // ─────────────────────────────────────────────────────────────────────────

    /** @group db */
    public function testExpiredTokenReturns401(): void
    {
        [$userId, $rawToken] = $this->insertStaffUserAndToken(expiresInSeconds: -3600);

        $req    = $this->makeRequest("Bearer {$rawToken}");
        $result = (new AuthMiddleware())->handle($req);

        $this->assertNotNull($result);
        $this->assertSame(401, $result['status']);
        $this->assertNull($req->user);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Revoked token
    // ─────────────────────────────────────────────────────────────────────────

    /** @group db */
    public function testRevokedTokenReturns401(): void
    {
        [$userId, $rawToken] = $this->insertStaffUserAndToken(revoked: true);

        $req    = $this->makeRequest("Bearer {$rawToken}");
        $result = (new AuthMiddleware())->handle($req);

        $this->assertNotNull($result);
        $this->assertSame(401, $result['status']);
        $this->assertNull($req->user);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Deleted / inactive staff user
    // ─────────────────────────────────────────────────────────────────────────

    /** @group db */
    public function testInactiveStaffUserReturns401(): void
    {
        [$userId, $rawToken] = $this->insertStaffUserAndToken(isActive: false);

        $req    = $this->makeRequest("Bearer {$rawToken}");
        $result = (new AuthMiddleware())->handle($req);

        $this->assertNotNull($result);
        $this->assertSame(401, $result['status']);
        $this->assertNull($req->user);
    }

    /** @group db */
    public function testSoftDeletedStaffUserReturns401(): void
    {
        [$userId, $rawToken] = $this->insertStaffUserAndToken(deleted: true);

        $req    = $this->makeRequest("Bearer {$rawToken}");
        $result = (new AuthMiddleware())->handle($req);

        $this->assertNotNull($result);
        $this->assertSame(401, $result['status']);
        $this->assertNull($req->user);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Insert a synthetic staff user + one role + auth_token row.
     * Returns [userId, rawToken].  All rows are registered for cleanup.
     *
     * @return array{int, string}
     */
    private function insertStaffUserAndToken(
        int  $expiresInSeconds = 86400,
        bool $revoked          = false,
        bool $isActive         = true,
        bool $deleted          = false
    ): array {
        $db = $this->db;

        // Ensure at least one role exists (id=1, name='doctor')
        $db->exec("INSERT IGNORE INTO roles (id, name, label) VALUES (1, 'doctor', 'پزشک')");

        // Synthetic staff user
        $uuid   = bin2hex(random_bytes(8));
        $mobile = '090' . rand(10000000, 99999999);
        $stmt   = $db->prepare(
            "INSERT INTO users (uuid, clinic_id, full_name, mobile, is_active, deleted_at, created_at, updated_at)
             VALUES (?, 1, 'کاربر آزمایشی', ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $uuid,
            $mobile,
            $isActive ? 1 : 0,
            $deleted ? date('Y-m-d H:i:s') : null,
        ]);
        $userId = (int)$db->lastInsertId();
        $this->cleanup[] = ['table' => 'users', 'id' => $userId];

        // Assign role
        $db->prepare("INSERT INTO role_user (user_id, role_id) VALUES (?, 1)")->execute([$userId]);

        // Insert auth token
        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresInSeconds);
        $revokedAt = $revoked ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare(
            "INSERT INTO auth_tokens (user_id, user_type, token_hash, expires_at, revoked_at, created_at)
             VALUES (?, 'staff', ?, ?, ?, NOW())"
        );
        $stmt->execute([$userId, $tokenHash, $expiresAt, $revokedAt]);
        $this->cleanup[] = ['table' => 'auth_tokens', 'id' => (int)$db->lastInsertId()];

        return [$userId, $rawToken];
    }

    /** Build a Request with the given Authorization header value. */
    private function makeRequest(string $authHeader): Request
    {
        $req          = new Request();
        $req->method  = 'GET';
        $req->path    = '/api/v1/dashboard/overview';
        $req->body    = [];
        $req->query   = [];
        $req->params  = [];
        $req->headers = $authHeader !== '' ? ['authorization' => $authHeader] : [];
        $req->user    = null;
        return $req;
    }
}
