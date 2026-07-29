<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\OtpService;
use PHPUnit\Framework\TestCase;

/**
 * OtpServiceTest — unit tests for OTP send/verify/rate-limit flow.
 *
 * These tests require a test database (configured in .env.testing).
 * Each test method resets the otp_codes table to avoid cross-test pollution.
 *
 * NOTE: Marked as unit tests but require DB — they are integration-light.
 * Pure unit extraction (DB-injected) is tracked as a future refactor.
 *
 * Covers:
 *   - send() returns true when not rate-limited
 *   - isRateLimited() returns true after 3 sends within 10 minutes
 *   - verify() returns 'ok' for a valid code
 *   - verify() returns 'invalid' for a wrong code
 *   - verify() returns 'expired' for an expired code
 *   - A used code cannot be verified a second time (replay prevention)
 *
 * SYNTHETIC DATA ONLY — never use real mobile numbers, real OTP values,
 * real patient records, or credentials in tests.
 */
final class OtpServiceTest extends TestCase
{
    private OtpService $service;
    private \PDO       $db;

    private const TEST_MOBILE = '09000000001'; // synthetic number in test range

    protected function setUp(): void
    {
        // Bootstrap the app's database singleton with the test DSN
        // .env.testing must set DB_DATABASE=maz_test (separate test database)
        $this->bootstrapTestDb();
        $this->service = new OtpService();

        // Isolate each test
        $this->db->exec('DELETE FROM otp_codes WHERE mobile = ' . $this->db->quote(self::TEST_MOBILE));
        $this->db->exec('DELETE FROM auth_tokens WHERE user_id IN (
            SELECT id FROM patients WHERE mobile = ' . $this->db->quote(self::TEST_MOBILE) . '
        )');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // send()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @group db
     */
    public function testSendReturnsTrueWhenNotRateLimited(): void
    {
        // Insert a matching patient row (required for issueToken later)
        $this->ensureTestPatient();

        $result = $this->service->send(self::TEST_MOBILE);
        self::assertTrue($result, 'send() should return true on first OTP request');
    }

    /**
     * @group db
     */
    public function testIsRateLimitedAfterThreeRequests(): void
    {
        $this->ensureTestPatient();

        // Manually insert 3 otp_codes rows with created_at = now
        $expiry = gmdate('Y-m-d H:i:s', time() + 300);
        for ($i = 0; $i < 3; $i++) {
            $this->db->prepare(
                'INSERT INTO otp_codes (mobile, code_hash, expires_at, created_at)
                 VALUES (?, ?, ?, UTC_TIMESTAMP())'
            )->execute([self::TEST_MOBILE, password_hash('00000', PASSWORD_BCRYPT), $expiry]);
        }

        self::assertTrue(
            $this->service->isRateLimited(self::TEST_MOBILE),
            'Should be rate-limited after 3 OTP sends within 10 minutes'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // verify()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @group db
     */
    public function testVerifyReturnsOkForValidCode(): void
    {
        $this->ensureTestPatient();

        // Seed a known code
        $plainCode = '54321';
        $this->seedOtpCode($plainCode, time() + 300);

        $result = $this->service->verify(self::TEST_MOBILE, $plainCode);
        self::assertSame('ok', $result, 'verify() should return "ok" for a correct, unexpired code');
    }

    /**
     * @group db
     */
    public function testVerifyReturnsInvalidForWrongCode(): void
    {
        $this->ensureTestPatient();
        $this->seedOtpCode('54321', time() + 300);

        $result = $this->service->verify(self::TEST_MOBILE, '00000');
        self::assertSame('invalid', $result);
    }

    /**
     * @group db
     */
    public function testVerifyReturnsExpiredForStaleCode(): void
    {
        $this->ensureTestPatient();
        // expires_at in the past
        $this->seedOtpCode('54321', time() - 10);

        $result = $this->service->verify(self::TEST_MOBILE, '54321');
        self::assertSame('expired', $result);
    }

    /**
     * @group db
     */
    public function testVerifiedCodeCannotBeUsedTwice(): void
    {
        $this->ensureTestPatient();
        $this->seedOtpCode('54321', time() + 300);

        $first  = $this->service->verify(self::TEST_MOBILE, '54321');
        $second = $this->service->verify(self::TEST_MOBILE, '54321');

        self::assertSame('ok',      $first,  'First verify should succeed');
        self::assertSame('invalid', $second, 'Replay should fail — used_at is set');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function bootstrapTestDb(): void
    {
        // Load .env.testing overrides (if file exists)
        $envFile = dirname(__DIR__, 2) . '/.env.testing';
        if (file_exists($envFile)) {
            foreach (file($envFile) as $line) {
                $line = trim($line);
                if ($line !== '' && $line[0] !== '#' && str_contains($line, '=')) {
                    [$k, $v] = explode('=', $line, 2);
                    $_ENV[trim($k)] = trim($v);
                }
            }
        }

        // Let the Database singleton pick up test credentials from $_ENV
        \App\Core\Database::reset();
        $this->db = \App\Core\Database::conn();
    }

    private function ensureTestPatient(): void
    {
        $exists = $this->db->prepare('SELECT id FROM patients WHERE mobile = ? LIMIT 1');
        $exists->execute([self::TEST_MOBILE]);
        if (!$exists->fetch()) {
            $this->db->prepare(
                'INSERT INTO patients (uuid, mobile, created_at)
                 VALUES (?, ?, UTC_TIMESTAMP())'
            )->execute([bin2hex(random_bytes(16)), self::TEST_MOBILE]);
        }
    }

    private function seedOtpCode(string $plainCode, int $expiresUnix): void
    {
        $expiresAt = gmdate('Y-m-d H:i:s', $expiresUnix);
        $this->db->prepare(
            'INSERT INTO otp_codes (mobile, code_hash, expires_at, created_at)
             VALUES (?, ?, ?, UTC_TIMESTAMP())'
        )->execute([self::TEST_MOBILE, password_hash($plainCode, PASSWORD_BCRYPT), $expiresAt]);
    }
}
