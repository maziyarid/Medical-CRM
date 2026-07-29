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
        $uuid                       = bin2hex(random_bytes(16));
        $this->uuidsToCleanUp[]     = $uuid;
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
