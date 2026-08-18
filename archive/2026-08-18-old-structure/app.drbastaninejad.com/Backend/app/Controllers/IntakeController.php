<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\IntakeModel;
use App\Services\GoogleSheetsService;
use App\Services\JalaliConverter;
use App\Validators\ValidatorService;

/**
 * IntakeController — handles POST /api/v1/intakes and GET /api/v1/intakes
 *
 * Uses docs/API_CONTRACT.md §SECTION 2 envelope ("success"/"data"/"error").
 *
 * Idempotency: a duplicate submission_uuid returns 200 DUPLICATE_SUBMISSION
 * with the original data. The UNIQUE constraint on intakes.submission_uuid is
 * enforced at DB level — see migration 001.
 *
 * Dual-write: Google Sheets write happens AFTER the DB transaction commits and
 * is non-fatal. sheets_sync_status is recorded and retried on the idempotent
 * 200 path if it was not 'ok'.
 */
final class IntakeController extends Controller
{
    private IntakeModel        $model;
    private GoogleSheetsService $sheets;
    private ValidatorService   $validator;

    public function __construct()
    {
        $this->model     = new IntakeModel();
        $this->sheets    = new GoogleSheetsService();
        $this->validator = new ValidatorService();
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/intakes
    // -------------------------------------------------------------------------
    public function store(): void
    {
        $body = $this->jsonBody();

        // Validate
        $errors = $this->validator->validateIntake($body);
        if ($errors !== []) {
            $this->validationError($errors);
        }

        $normalised = $this->normalisePayload($body);

        // Idempotency check
        $existing = $this->model->findByUuid($normalised['submission_uuid'] ?? '');
        if ($existing !== null) {
            // Retry Sheets write if it failed previously
            if (in_array($existing['sheets_sync_status'], ['failed', 'pending'], true)) {
                $syncStatus = $this->sheets->appendIntake((int)$existing['id'], $normalised);
                if ($syncStatus === 'ok') {
                    $this->model->updateSyncStatus((int)$existing['id'], 'ok');
                    $existing['sheets_sync_status'] = 'ok';
                }
            }
            $this->json([
                'intake_id'          => (int)$existing['id'],
                'submission_uuid'    => $existing['submission_uuid'],
                'status'             => $existing['status'],
                'sheets_sync_status' => $existing['sheets_sync_status'],
                'idempotent'         => true,
            ]);
        }

        // Insert
        try {
            $intakeId = $this->model->insert($normalised);
        } catch (\PDOException $e) {
            // UNIQUE constraint: race-condition duplicate
            if (str_contains($e->getMessage(), '1062')) {
                $row = $this->model->findByUuid($normalised['submission_uuid']);
                $this->json([
                    'intake_id'          => $row ? (int)$row['id'] : 0,
                    'submission_uuid'    => $normalised['submission_uuid'],
                    'status'             => 'pending',
                    'sheets_sync_status' => $row['sheets_sync_status'] ?? 'unknown',
                    'idempotent'         => true,
                ]);
            }
            error_log('[IntakeController] DB insert failed: ' . $e->getMessage());
            $this->error('SERVER_ERROR', 'خطای داخلی سرور. لطفاً مجدداً امتحان کنید.', 500);
        }

        // Dual-write to Google Sheets (non-fatal)
        $syncStatus = $this->sheets->appendIntake($intakeId, $normalised);
        if ($syncStatus !== 'skipped') {
            $this->model->updateSyncStatus($intakeId, $syncStatus);
        }

        $this->json([
            'intake_id'          => $intakeId,
            'submission_uuid'    => $normalised['submission_uuid'],
            'status'             => 'pending',
            'sheets_sync_status' => $syncStatus,
            'idempotent'         => false,
        ], 201);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/intakes  (staff only — protected by AuthMiddleware + RbacMiddleware)
    // -------------------------------------------------------------------------
    public function index(): void
    {
        $page    = max(1, (int)($_GET['page']     ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $status  = $_GET['status'] ?? null;
        $q       = trim($_GET['q'] ?? '');

        $db     = \App\Core\Database::conn();
        $where  = ['1=1'];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[]  = 'status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $like     = '%' . $q . '%';
            $where[]  = '(first_name LIKE ? OR last_name LIKE ? OR mobile LIKE ? OR national_id LIKE ?)';
            $params   = array_merge($params, [$like, $like, $like, $like]);
        }

        $whereClause = implode(' AND ', $where);
        $offset      = ($page - 1) * $perPage;

        $total = (int)$db->prepare("SELECT COUNT(*) FROM intakes WHERE {$whereClause}")
                         ->execute($params) && ($count = $db->query("SELECT COUNT(*) FROM intakes WHERE {$whereClause}")->fetchColumn());

        // Proper count query
        $countStmt = $db->prepare("SELECT COUNT(*) FROM intakes WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT id, submission_uuid, first_name, last_name, mobile, national_id,
                    chief_complaint, visit_reason, status, sheets_sync_status, created_at
             FROM intakes
             WHERE {$whereClause}
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute(array_merge($params, [$perPage, $offset]));
        $rows = $stmt->fetchAll();

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data'    => $rows,
            'meta'    => ['page' => $page, 'per_page' => $perPage, 'total' => $total],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -------------------------------------------------------------------------
    // Payload normalisation — camelCase (from intake.html) → snake_case
    // -------------------------------------------------------------------------
    private function normalisePayload(array $body): array
    {
        $v = $this->validator;

        $mobile     = $v->normaliseMobile((string)($body['mobile'] ?? '')) ?? '';
        $nationalId = $v->normaliseDigits((string)($body['nationalId'] ?? $body['national_id'] ?? ''));
        $birthDate  = $v->normaliseDigits((string)($body['birthDate'] ?? $body['birth_date'] ?? ''));
        $uuid       = trim((string)($body['submission_uuid'] ?? ''));

        // Convert Jalali YYYY/MM/DD to Gregorian for birth_date column.
        // JalaliConverter::jalaliStringToGregorian() returns null for invalid input.
        $birthDateGregorian = $birthDate !== '' ? JalaliConverter::jalaliStringToGregorian($birthDate) : null;
        $birthDateJalali    = $birthDate ?: null;

        return [
            'submission_uuid'   => $uuid !== '' ? $uuid : bin2hex(random_bytes(16)),
            'first_name'        => trim((string)($body['firstName']  ?? $body['first_name']  ?? '')),
            'last_name'         => trim((string)($body['lastName']   ?? $body['last_name']   ?? '')),
            'father_name'       => trim((string)($body['fatherName'] ?? $body['father_name'] ?? '')) ?: null,
            'mobile'            => $mobile,
            'national_id'       => $nationalId,
            'birth_date'        => $birthDateGregorian,
            'birth_date_jalali' => $birthDateJalali,
            'email'             => trim((string)($body['email'] ?? '')) ?: null,
            'home_address'      => trim((string)($body['homeAd']    ?? $body['home_address']  ?? '')) ?: null,
            'home_tel'          => trim((string)($body['homeTel']   ?? $body['home_tel']      ?? '')) ?: null,
            'chief_complaint'   => trim((string)($body['description'] ?? $body['chief_complaint'] ?? '')),
            'visit_reason'      => trim((string)($body['visitReason'] ?? $body['visit_reason']    ?? '')) ?: null,
            'is_transfer'       => (int)($body['isTransfer'] ?? $body['is_transfer'] ?? 0),
            'raw_payload'       => $body,
        ];
    }
}
