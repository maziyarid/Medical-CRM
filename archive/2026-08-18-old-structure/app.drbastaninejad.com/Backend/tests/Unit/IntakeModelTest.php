<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Models\IntakeModel;
use PHPUnit\Framework\TestCase;

/**
 * IntakeModelTest — unit tests for IntakeModel persistence and idempotency.
 *
 * Requires the test database with migration 001 applied.
 * Each test cleans up its own rows by submission_uuid.
 *
 * SYNTHETIC DATA ONLY — no real patient data, no real national IDs.
 *
 * Covers:
 *   - insert() creates a new row and returns an auto-increment id
 *   - findByUuid() retrieves the row by submission_uuid
 *   - findByUuid() returns null for an unknown uuid
 *   - updateSyncStatus() changes sheets_sync_status
 *   - DB-level UNIQUE constraint on submission_uuid (idempotency)
 *
 * @group outcome_states — backend counterparts of intake lifecycle states:
 *   failed_confirmed:
 *     - A confirmed server-side failure (4xx/5xx) before any DB write means
 *       no row exists → findByUuid() returns null → UUID is safe to reuse.
 *     - sheets_sync_status = 'failed' on an existing row means the DB write
 *       succeeded but Sheets write failed → updateSyncStatus() must update
 *       to 'ok' on retry, and findByUuid() must return the updated status.
 *   outcome_unknown:
 *     - DB write succeeded but the process was killed before Sheets response.
 *       On retry: findByUuid() finds the row, sheets_sync_status = 'pending'.
 *       IntakeController retries the Sheets write and calls updateSyncStatus().
 *     - Race-condition duplicate (concurrent same-UUID insert) must raise a
 *       PDOException (error 1062) caught by IntakeController — not silently
 *       creating a second row.
 */
final class IntakeModelTest extends TestCase
{
    private IntakeModel $model;
    private \PDO        $db;
    private array       $uuidsToCleanUp = [];

    protected function setUp(): void
    {
        $this->bootstrapTestDb();
        $this->model = new IntakeModel();
    }

    protected function tearDown(): void
    {
        // Always clean synthetic rows regardless of test outcome
        foreach ($this->uuidsToCleanUp as $uuid) {
            $this->db->prepare('DELETE FROM intakes WHERE submission_uuid = ?')->execute([$uuid]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // insert()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @group db
     */
    public function testInsertReturnsPositiveId(): void
    {
        $uuid    = $this->newUuid();
        $payload = $this->syntheticPayload($uuid);

        $id = $this->model->insert($payload);

        self::assertGreaterThan(0, $id, 'insert() must return a positive auto-increment id');
    }

    /**
     * @group db
     */
    public function testInsertedRowIsRetrievableByUuid(): void
    {
        $uuid = $this->newUuid();
        $this->model->insert($this->syntheticPayload($uuid));

        $row = $this->model->findByUuid($uuid);

        self::assertNotNull($row, 'findByUuid() must return the inserted row');
        self::assertSame($uuid, $row['submission_uuid']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // findByUuid()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @group db
     */
    public function testFindByUuidReturnsNullForUnknownUuid(): void
    {
        $row = $this->model->findByUuid('00000000000000000000000000000000');
        self::assertNull($row);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // updateSyncStatus()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @group db
     */
    public function testUpdateSyncStatusChangesTheColumn(): void
    {
        $uuid = $this->newUuid();
        $id   = $this->model->insert($this->syntheticPayload($uuid));

        $this->model->updateSyncStatus($id, 'ok');

        $row = $this->model->findByUuid($uuid);
        self::assertSame('ok', $row['sheets_sync_status']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Idempotency — UNIQUE constraint
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @group db
     */
    public function testDuplicateSubmissionUuidThrowsPdoException(): void
    {
        $uuid    = $this->newUuid();
        $payload = $this->syntheticPayload($uuid);

        $this->model->insert($payload);

        $this->expectException(\PDOException::class);
        $this->model->insert($payload); // same uuid — must trigger UNIQUE violation
    }

    // ─────────────────────────────────────────────────────────────────────────
    // failed_confirmed — backend pattern
    //
    // Definition (UNIFIED_MASTER_PLAN.md §6, SPACE_COORDINATION_PROTOCOL.md §8):
    //   A confirmed failure where no write reached the database.
    //   The UUID is safe to reuse — findByUuid() returns null.
    //   Backend path: 4xx/5xx response before insert() is called.
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * If no DB write occurred (e.g. validation failure → 422 returned before
     * insert()), findByUuid() must return null for that UUID.
     * The client may safely retry with the same UUID.
     *
     * @group db
     * @group outcome_states
     */
    public function testFailedConfirmed_NoRowExistsWhenNoInsertOccurred(): void
    {
        $uuid = $this->newUuid();

        // Simulate a failed_confirmed state: we never call insert() — as would
        // happen if validation failed and the controller returned 422 before
        // reaching the model.
        $row = $this->model->findByUuid($uuid);

        self::assertNull($row,
            'failed_confirmed: UUID must not exist in DB when insert() was never called. ' .
            'Client may retry safely with the same submission_uuid.');
    }

    /**
     * sheets_sync_status = 'failed': the DB write succeeded but the Sheets
     * write failed. On retry (idempotent path), updateSyncStatus() must
     * transition the status to 'ok', making the row consistent.
     *
     * This is the backend half of "failed_confirmed" for the dual-write path —
     * the DB commit succeeded, so the intake is not lost, but the Google Sheet
     * entry is missing and must be retried.
     *
     * @group db
     * @group outcome_states
     */
    public function testFailedConfirmed_SheetsSyncStatusTransitionsFromFailedToOk(): void
    {
        $uuid = $this->newUuid();
        $id   = $this->model->insert($this->syntheticPayload($uuid));

        // Simulate: Sheets write returned 'failed' after successful DB insert
        $this->model->updateSyncStatus($id, 'failed');

        $rowBeforeRetry = $this->model->findByUuid($uuid);
        self::assertSame('failed', $rowBeforeRetry['sheets_sync_status'],
            'Precondition: sheets_sync_status must be "failed" before retry');

        // Simulate: IntakeController retries the Sheets write on idempotent path
        // and updateSyncStatus() is called with 'ok'
        $this->model->updateSyncStatus($id, 'ok');

        $rowAfterRetry = $this->model->findByUuid($uuid);
        self::assertSame('ok', $rowAfterRetry['sheets_sync_status'],
            'failed_confirmed retry: sheets_sync_status must transition "failed" → "ok"');
    }

    /**
     * A 'failed' row is still retrievable by UUID — the idempotent path in
     * IntakeController::store() must detect it via findByUuid() returning
     * non-null, and must NOT create a duplicate row.
     *
     * @group db
     * @group outcome_states
     */
    public function testFailedConfirmed_ExistingRowWithFailedSyncIsFoundByUuid(): void
    {
        $uuid = $this->newUuid();
        $id   = $this->model->insert($this->syntheticPayload($uuid));
        $this->model->updateSyncStatus($id, 'failed');

        $found = $this->model->findByUuid($uuid);

        self::assertNotNull($found, 'A row with sheets_sync_status=failed must still be found by UUID');
        self::assertSame((string)$id, (string)$found['id']);
        self::assertSame('failed', $found['sheets_sync_status']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // outcome_unknown — backend pattern
    //
    // Definition (UNIFIED_MASTER_PLAN.md §6, SPACE_COORDINATION_PROTOCOL.md §8):
    //   DB write succeeded, but the Sheets write result is ambiguous —
    //   process killed mid-flight, network timeout before response, etc.
    //   sheets_sync_status remains 'pending'.
    //   On client retry: findByUuid() finds the row → idempotent 200 path.
    //   IntakeController retries the Sheets write on this idempotent path.
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * outcome_unknown: the DB row exists with sheets_sync_status = 'pending'
     * (process was killed after DB commit but before the Sheets response was
     * recorded). findByUuid() must return the existing row so IntakeController
     * can take the idempotent path and retry the Sheets write.
     *
     * @group db
     * @group outcome_states
     */
    public function testOutcomeUnknown_RowExistsWithPendingSyncStatus(): void
    {
        $uuid = $this->newUuid();
        $id   = $this->model->insert($this->syntheticPayload($uuid));

        // After insert(), sheets_sync_status defaults to 'pending' (the
        // outcome_unknown state: DB write done, Sheets write unknown).
        $row = $this->model->findByUuid($uuid);

        self::assertNotNull($row, 'outcome_unknown: row must exist after insert()');
        self::assertSame('pending', $row['sheets_sync_status'],
            'outcome_unknown: sheets_sync_status must default to "pending" after insert()');
    }

    /**
     * outcome_unknown → reconciliation: after the Sheets write is retried
     * on the idempotent path and succeeds, updateSyncStatus() must resolve
     * sheets_sync_status to 'ok'.
     *
     * @group db
     * @group outcome_states
     */
    public function testOutcomeUnknown_ReconciliationResolvesToOk(): void
    {
        $uuid = $this->newUuid();
        $id   = $this->model->insert($this->syntheticPayload($uuid));

        // Verify starting state
        $rowBefore = $this->model->findByUuid($uuid);
        self::assertSame('pending', $rowBefore['sheets_sync_status']);

        // Simulate IntakeController idempotent retry resolving the ambiguous state
        $this->model->updateSyncStatus($id, 'ok');

        $rowAfter = $this->model->findByUuid($uuid);
        self::assertSame('ok', $rowAfter['sheets_sync_status'],
            'outcome_unknown reconciliation: pending → ok after successful Sheets retry');
    }

    /**
     * outcome_unknown: a second insert() attempt with the same UUID must NOT
     * succeed — the UNIQUE constraint prevents a duplicate row, forcing the
     * caller (IntakeController) onto the idempotent path.
     * This is the DB-level guarantee that prevents double-writes.
     *
     * @group db
     * @group outcome_states
     */
    public function testOutcomeUnknown_SecondInsertWithSameUuidThrows(): void
    {
        $uuid    = $this->newUuid();
        $payload = $this->syntheticPayload($uuid);

        $this->model->insert($payload);

        // A retry that somehow bypasses IntakeController's idempotency check
        // (e.g. race condition) must still be blocked at DB level.
        $this->expectException(\PDOException::class);
        $this->model->insert($payload);
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

    private function newUuid(): string
    {
        $uuid                   = bin2hex(random_bytes(16));
        $this->uuidsToCleanUp[] = $uuid;
        return $uuid;
    }

    private function syntheticPayload(string $uuid): array
    {
        return [
            'submission_uuid'    => $uuid,
            'clinic_id'          => 1,
            'first_name'         => 'تست',
            'last_name'          => 'کاربر',
            'father_name'        => null,
            'mobile'             => '09000000099', // synthetic range
            'national_id'        => '0079643178',
            'birth_date'         => null,
            'birth_date_jalali'  => '1370/05/12',
            'email'              => null,
            'home_address'       => null,
            'chief_complaint'    => 'تست واحد — داده مصنوعی',
            'visit_reason'       => 'مشاوره',
            'is_transfer'        => 0,
            'raw_payload'        => ['_test' => true],
        ];
    }
}
