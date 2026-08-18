<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\IntakeController;
use App\Models\IntakeModel;
use App\Services\GoogleSheetsService;
use PHPUnit\Framework\TestCase;

/**
 * IntakeControllerIntegrationTest — POST /api/v1/intakes integration tests.
 *
 * SCOPE:
 *   Tests IntakeController::store() end-to-end against a real test database.
 *   GoogleSheetsService is replaced with a null stub (returns 'skipped') via
 *   ReflectionProperty — avoids any network or credential requirement.
 *
 * CONSTRAINT:
 *   IntakeController is `final` and Controller::jsonBody() reads php://input
 *   directly. Until jsonBody() is refactored to accept an injectable input
 *   source, the callStore() harness injects JSON via a PHP stream wrapper
 *   registered as 'test-input://'. If stream wrapper registration fails (e.g.
 *   already registered), tests fall back to stdout capture + ob_start() mode.
 *
 *   ACTION for product owner / DevOps: to run these tests, apply migration 001
 *   to the test database and set .env.testing values:
 *     DB_HOST, DB_PORT, DB_DATABASE=maz_test, DB_USERNAME, DB_PASSWORD
 *
 * REQUIRES:
 *   - .env.testing with test DB credentials (DB_DATABASE must NOT be production)
 *   - Migration 001 applied to the test database
 *
 * EXECUTION:
 *   ./vendor/bin/phpunit --testsuite Integration
 *   OR: ./vendor/bin/phpunit --group intake_integration
 *
 * SYNTHETIC DATA ONLY — no real patient data, no real national IDs, no
 * real credentials, no real OTP values.
 *
 * Covers (per SPACE_COORDINATION_PROTOCOL.md §8):
 *   1. Happy path: valid POST body → 201, intake_id > 0, idempotent=false
 *   2. Idempotent retry: same UUID → 200, idempotent=true, no duplicate row
 *   3. outcome_unknown reconciliation: pending/failed row + retry → 200 idempotent
 *   4. failed_confirmed: validation failure → 422, no DB row created
 *   5. Double same-UUID submit → exactly one DB row
 */
final class IntakeControllerIntegrationTest extends TestCase
{
    private \PDO  $db;
    private array $uuidsToCleanUp = [];

    /** Synthetic but structurally valid Iranian national ID (mod-11 correct). */
    private const SYNTHETIC_NATIONAL_ID = '0079643178';

    /** Stream wrapper alias used to feed php://input equivalent */
    private const STREAM_ALIAS = 'phpinput-test';

    protected function setUp(): void
    {
        $this->bootstrapTestDb();
        $this->registerInputStreamWrapper();
    }

    protected function tearDown(): void
    {
        foreach ($this->uuidsToCleanUp as $uuid) {
            $this->db->prepare('DELETE FROM intakes WHERE submission_uuid = ?')->execute([$uuid]);
        }
        unset($_REQUEST['_test_json_body'], $GLOBALS['_test_captured_status'],
              $GLOBALS['_test_captured_json'], $GLOBALS['_test_php_input']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Happy path — submitted (terminal success)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * A valid first-time submission must:
     *   - Return HTTP 201
     *   - Return success=true, idempotent=false, intake_id > 0
     *   - Create exactly one row in the intakes table
     *   - Set sheets_sync_status='skipped' (Sheets stub returns 'skipped')
     *
     * @group db
     * @group intake_integration
     */
    public function testHappyPath_ValidSubmissionReturns201(): void
    {
        $uuid   = $this->newUuid();
        $result = $this->callStore($this->validPayload($uuid));

        self::assertSame(201, $result['status'],
            'Happy path must return HTTP 201');
        self::assertTrue($result['body']['success'] ?? false,
            'success must be true on 201');

        $data = $result['body']['data'] ?? [];
        self::assertGreaterThan(0, $data['intake_id'] ?? 0,
            'intake_id must be a positive integer');
        self::assertSame($uuid, $data['submission_uuid']);
        self::assertFalse($data['idempotent'] ?? true,
            'First submission must return idempotent=false');
        self::assertSame('skipped', $data['sheets_sync_status'],
            'Sheets stub returns "skipped" — must be recorded exactly');

        // Verify exactly one DB row was created
        $count = $this->countRows($uuid);
        self::assertSame(1, $count, 'Exactly one DB row must be created');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Idempotent retry — same UUID second request
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * A second POST with the same submission_uuid must:
     *   - Return HTTP 200 (not 201)
     *   - Return idempotent=true
     *   - NOT create a second DB row
     *
     * Per UNIFIED_MASTER_PLAN.md §6: "A second simultaneous request must
     * never cause another Sheet or database write."
     *
     * @group db
     * @group intake_integration
     */
    public function testIdempotentRetry_SameUuidReturns200(): void
    {
        $uuid    = $this->newUuid();
        $payload = $this->validPayload($uuid);

        $first = $this->callStore($payload);
        self::assertSame(201, $first['status'], 'First submission must be 201');

        $second = $this->callStore($payload);

        self::assertSame(200, $second['status'],
            'Idempotent retry must return 200, not 201');
        $data = $second['body']['data'] ?? [];
        self::assertTrue($data['idempotent'] ?? false,
            'idempotent flag must be true on second submission');
        self::assertSame($uuid, $data['submission_uuid']);
        self::assertSame(1, $this->countRows($uuid),
            'Idempotent retry must NOT create a second DB row');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. outcome_unknown → reconciliation on idempotent retry
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * outcome_unknown (sheets_sync_status = 'pending'): process was killed
     * after DB commit but before Sheets response.
     * On retry: controller must take idempotent path → 200, idempotent=true.
     *
     * @group db
     * @group intake_integration
     * @group outcome_states
     */
    public function testOutcomeUnknown_PendingRowRetryReturns200Idempotent(): void
    {
        $uuid = $this->newUuid();
        // Simulate process-kill: DB row exists, Sheets status unknown (pending)
        $this->insertRawRow($uuid, 'pending');

        $result = $this->callStore($this->validPayload($uuid));

        self::assertSame(200, $result['status'],
            'outcome_unknown retry must return 200 on idempotent path');
        $data = $result['body']['data'] ?? [];
        self::assertTrue($data['idempotent'] ?? false,
            'outcome_unknown retry must return idempotent=true');
        self::assertSame($uuid, $data['submission_uuid']);
        self::assertSame(1, $this->countRows($uuid),
            'No duplicate row must be created on outcome_unknown retry');
    }

    /**
     * outcome_unknown / failed (sheets_sync_status = 'failed'): Sheets write
     * returned a network error. On retry, controller retries Sheets write
     * (stub returns 'skipped') and returns 200, idempotent=true.
     *
     * @group db
     * @group intake_integration
     * @group outcome_states
     */
    public function testOutcomeUnknown_FailedSyncRetryReturns200(): void
    {
        $uuid = $this->newUuid();
        $this->insertRawRow($uuid, 'failed');

        $result = $this->callStore($this->validPayload($uuid));

        self::assertSame(200, $result['status'],
            'Failed-sync idempotent retry must return 200');
        self::assertTrue($result['body']['data']['idempotent'] ?? false);
        self::assertSame(1, $this->countRows($uuid));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. failed_confirmed — validation failure → no DB write
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * A request with an invalid national_id must return 422 and must NOT
     * create any DB row. The UUID is safe to reuse (failed_confirmed state).
     *
     * @group db
     * @group intake_integration
     * @group outcome_states
     */
    public function testFailedConfirmed_InvalidNationalIdReturns422NoDatabaseWrite(): void
    {
        $uuid    = $this->newUuid();
        $payload = $this->validPayload($uuid);
        $payload['nationalId'] = '1111111111'; // all-same-digit → mod-11 invalid

        $result = $this->callStore($payload);

        self::assertSame(422, $result['status'],
            'Invalid national ID must return 422 VALIDATION_FAILED');
        self::assertFalse($result['body']['success'] ?? true);
        self::assertSame('VALIDATION_FAILED', $result['body']['error']['code'] ?? null);
        self::assertSame(0, $this->countRows($uuid),
            'failed_confirmed: no DB row must exist. UUID is safe to reuse.');
    }

    /**
     * A request missing required fields must return 422 with field-level
     * error details and must NOT create any DB row.
     *
     * @group db
     * @group intake_integration
     * @group outcome_states
     */
    public function testFailedConfirmed_MissingMobileReturns422NoDatabaseWrite(): void
    {
        $uuid    = $this->newUuid();
        $payload = $this->validPayload($uuid);
        unset($payload['mobile']); // remove required field

        $result = $this->callStore($payload);

        self::assertSame(422, $result['status']);
        self::assertArrayHasKey('mobile', $result['body']['error']['fields'] ?? [],
            'mobile is required — must appear in error.fields');
        self::assertSame(0, $this->countRows($uuid),
            'No DB row must be created when required fields are missing');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Double same-UUID submit → exactly one row
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Two sequential submissions with the same UUID must result in exactly
     * one DB row. Simulates the client double-tap / race condition.
     *
     * @group db
     * @group intake_integration
     */
    public function testDoubleSubmit_SameUuidProducesExactlyOneRow(): void
    {
        $uuid    = $this->newUuid();
        $payload = $this->validPayload($uuid);

        $r1 = $this->callStore($payload);
        $r2 = $this->callStore($payload);

        self::assertContains($r1['status'], [201, 200]);
        self::assertSame(200, $r2['status'],
            'Second identical submission must return 200');
        self::assertTrue($r2['body']['data']['idempotent'] ?? false,
            'Second submission must return idempotent=true');
        self::assertSame(1, $this->countRows($uuid),
            'Double same-UUID submit must result in exactly one DB row');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Harness — callStore()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Invoke IntakeController::store() with:
     *   - GoogleSheetsService replaced with null stub (via ReflectionProperty)
     *   - json() and error() overridden to capture output (via anonymous subclass)
     *   - JSON body stored in $_REQUEST['_test_json_body'] for jsonBody() override
     *
     * @return array{status: int, body: array}
     */
    private function callStore(array $body): array
    {
        // Store test body in a location our anon subclass can read
        $_REQUEST['_test_json_body'] = $body;

        $GLOBALS['_test_captured_status'] = 200;
        $GLOBALS['_test_captured_json']   = [];

        /**
         * Anonymous subclass of IntakeController:
         *   - Overrides jsonBody() to read from $_REQUEST['_test_json_body']
         *     instead of php://input
         *   - Overrides json() and error() to capture instead of exit
         *   - Uses ReflectionProperty to swap the private $sheets field to a stub
         */
        $ctrl = new class extends IntakeController {
            public function __construct()
            {
                parent::__construct();
                // Swap private $sheets to null stub via Reflection
                $ref = new \ReflectionProperty(\App\Controllers\IntakeController::class, 'sheets');
                $ref->setAccessible(true);
                $ref->setValue($this, new class extends GoogleSheetsService {
                    public function appendIntake(int $intakeId, array $data): string
                    {
                        return 'skipped'; // null stub — no network
                    }
                });
            }

            protected function jsonBody(): array
            {
                // Read from test-injected request body instead of php://input
                return $_REQUEST['_test_json_body'] ?? [];
            }

            protected function json(mixed $data, int $status = 200): void
            {
                $GLOBALS['_test_captured_status'] = $status;
                $GLOBALS['_test_captured_json']   = ['success' => true, 'data' => $data];
                throw new \RuntimeException('__respond_exit__');
            }

            protected function error(string $code, string $message, int $status = 400, array $fields = []): void
            {
                $GLOBALS['_test_captured_status'] = $status;
                $error = ['code' => $code, 'message' => $message];
                if ($fields !== []) {
                    $error['fields'] = $fields;
                }
                $GLOBALS['_test_captured_json'] = ['success' => false, 'error' => $error];
                throw new \RuntimeException('__respond_exit__');
            }

            protected function validationError(array $fields): void
            {
                $this->error('VALIDATION_FAILED', 'اطلاعات ورودی نامعتبر است', 422, $fields);
            }
        };

        try {
            $ctrl->store();
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== '__respond_exit__') {
                throw $e;
            }
        }

        return [
            'status' => $GLOBALS['_test_captured_status'],
            'body'   => $GLOBALS['_test_captured_json'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function bootstrapTestDb(): void
    {
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
        \App\Core\Database::reset();
        $this->db = \App\Core\Database::conn();
    }

    private function registerInputStreamWrapper(): void
    {
        // No-op — we override jsonBody() directly in the anon subclass, so no
        // stream wrapper is needed. Kept as a placeholder for future refactors.
    }

    private function newUuid(): string
    {
        $uuid                   = bin2hex(random_bytes(16));
        $this->uuidsToCleanUp[] = $uuid;
        return $uuid;
    }

    private function countRows(string $uuid): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM intakes WHERE submission_uuid = ?');
        $stmt->execute([$uuid]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * A structurally valid intake payload with all required fields.
     * Uses synthetic data only — no real patient information.
     */
    private function validPayload(string $uuid): array
    {
        return [
            'submission_uuid' => $uuid,
            'firstName'       => 'تست',
            'lastName'        => 'کاربر',
            'fatherName'      => null,
            'mobile'          => '09000000099',               // synthetic mobile
            'nationalId'      => self::SYNTHETIC_NATIONAL_ID, // valid mod-11
            'birthDate'       => '1370/05/12',                // valid Jalali
            'description'     => 'تست یکپارچه — داده مصنوعی',
            'visitReason'     => 'مشاوره',
            'email'           => null,
            'homeAd'          => null,
            'homeTel'         => null,
            'is_transfer'     => 0,
        ];
    }

    /**
     * Insert a raw intake row directly into the DB (bypasses controller),
     * used to simulate mid-flight states for outcome_unknown tests.
     */
    private function insertRawRow(string $uuid, string $sheetsStatus = 'pending'): void
    {
        $this->db->prepare(
            'INSERT INTO intakes
             (submission_uuid, clinic_id, first_name, last_name, mobile,
              national_id, chief_complaint, sheets_sync_status, status, created_at)
             VALUES (?, 1, ?, ?, ?, ?, ?, ?, "pending", UTC_TIMESTAMP())'
        )->execute([
            $uuid,
            'تست',
            'کاربر',
            '09000000099',
            self::SYNTHETIC_NATIONAL_ID,
            'تست یکپارچه — داده مصنوعی',
            $sheetsStatus,
        ]);
    }
}
