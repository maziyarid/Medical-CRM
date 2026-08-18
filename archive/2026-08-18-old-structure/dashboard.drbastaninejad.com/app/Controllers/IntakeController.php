<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Models\IntakeModel;
use App\Validators\ValidatorService;
use App\Services\GoogleSheetsService;

/**
 * IntakeController — Phase A deliverable, updated Phase C
 *
 * POST /api/v1/intakes (public, no auth)
 *   - Normalises camelCase frontend keys → snake_case (§6.1 UNIFIED_MASTER_PLAN)
 *   - Validates Code Meli (mod-11), Iranian mobile, Jalali birth date
 *   - Idempotent via submission_uuid (UNIQUE on intakes.submission_uuid)
 *   - Dual-writes to MySQL then to Google Sheets (async-isolated, non-fatal)
 *   - Records sheets_sync_status = ok/failed/skipped/pending on the intakes row
 *   - Idempotent 200 path re-tries Sheets if sheets_sync_status != 'ok'
 *   - Persists email + visit_reason into the new intakes columns (migration 004)
 *   - Returns { data: { intake_id, patient_uuid, status, sheets_sync_status } }
 *
 * GET /api/v1/intakes (staff auth required — routed in routes.intake.php)
 *   - Returns paginated review queue
 */
final class IntakeController extends Controller
{
    private IntakeModel $model;
    private GoogleSheetsService $sheets;

    public function __construct()
    {
        $this->model  = new IntakeModel();
        $this->sheets = new GoogleSheetsService();
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/intakes
    // -------------------------------------------------------------------------
    public function store(Request $req): array
    {
        // 0. Normalise field names: camelCase → snake_case (§6.1 UNIFIED_MASTER_PLAN).
        //    The frontend (intake.html) sends camelCase; this is the ONLY place
        //    the mapping lives. The frontend contract is never changed.
        $in = $this->normalisePayload($req->body);

        // 1. submission_uuid idempotency — must be supplied by the frontend
        $uuid = trim((string)($in['submission_uuid'] ?? ''));
        if ($uuid === '' || strlen($uuid) > 64) {
            return $this->error('submission_uuid is required (max 64 chars)', 422);
        }

        // 2. Deterministic idempotency check
        $existing = $this->model->findByUuid($uuid);
        if ($existing) {
            // Re-try Sheets if first attempt failed or was never recorded
            $syncStatus = $existing['sheets_sync_status'] ?? 'failed';
            if ($syncStatus !== 'ok' && $syncStatus !== 'skipped') {
                $syncStatus = $this->runSheetsSync((int)$existing['id'], $existing);
                $this->model->updateSyncStatus((int)$existing['id'], $syncStatus);
            }
            return $this->success([
                'intake_id'          => (int)$existing['id'],
                'patient_uuid'       => $existing['patient_uuid'],
                'status'             => $existing['status'],
                'sheets_sync_status' => $syncStatus,
                'idempotent'         => true,
            ]);
        }

        // 3. Validate required fields
        $errors = $this->validateIntakePayload($in);
        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // 4. Normalise field values
        $mobile     = ValidatorService::normalizeMobile($in['mobile']);
        $nationalId = ValidatorService::normalizePersianDigits($in['national_id']);
        $birthDate  = ValidatorService::jalaliToGregorian($in['birth_date_jalali']);

        // 5. DB transaction — MySQL is the authoritative source of truth
        $db = Database::conn();
        $db->beginTransaction();
        try {
            $clinicId  = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
            $patientId = $this->upsertPatient($db, $clinicId, [
                'first_name'   => trim($in['first_name']),
                'last_name'    => trim($in['last_name']),
                'mobile'       => $mobile,
                'national_id'  => $nationalId,
                'birth_date'   => $birthDate,
                'gender'       => $in['gender'] ?? null,
                'home_address' => $in['home_address'] ?? null,
            ]);
            $patientUuid = $this->getPatientUuid($db, $patientId);

            $intakeId = $this->model->createIntake([
                'submission_uuid'    => $uuid,
                'clinic_id'          => $clinicId,
                'patient_id'         => $patientId,
                'patient_uuid'       => $patientUuid,
                'first_name'         => trim($in['first_name']),
                'last_name'          => trim($in['last_name']),
                'mobile'             => $mobile,
                'national_id'        => $nationalId,
                'birth_date'         => $birthDate,
                'birth_date_jalali'  => $in['birth_date_jalali'] ?? null, // migration 007 — original Jalali string
                'chief_complaint'    => trim($in['chief_complaint'] ?? ''),
                'service_type'       => $in['service_type'] ?? null,
                'preferred_date'     => $in['preferred_date'] ?? null,
                'insurance_type'     => $in['insurance_type'] ?? null,
                'email'              => $in['email'] ?? null,          // migration 004
                'visit_reason'       => $in['visit_reason'] ?? null,   // migration 004
                'raw_payload'        => json_encode($req->body, JSON_UNESCAPED_UNICODE), // original keys preserved
                'status'             => 'pending',
                'sheets_sync_status' => 'pending',                     // migration 006
                'created_at'         => gmdate('Y-m-d H:i:s'),
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[IntakeController] transaction failed: ' . $e->getMessage());
            return $this->error('خطای داخلی سرور. لطفاً دوباره تلاش کنید.', 500);
        }

        // 6. Sheets dual-write — runs AFTER commit, isolated from the DB transaction.
        //    Outcome is recorded on the intakes row; HTTP response is never blocked.
        $sheetsData = [
            'submission_uuid' => $uuid,
            'name'            => trim($in['first_name']) . ' ' . trim($in['last_name']),
            'mobile'          => $mobile,
            'national_id'     => $nationalId,
            'birth_date'      => $birthDate,
            'service_type'    => $in['service_type'] ?? '',
            'chief_complaint' => $in['chief_complaint'] ?? '',
            'preferred_date'  => $in['preferred_date'] ?? '',
            'submitted_at'    => gmdate('Y-m-d H:i:s'),
            'email'           => $in['email'] ?? '',          // col L
            'visit_reason'    => $in['visit_reason'] ?? '',   // col M
        ];
        $syncStatus = $this->sheets->appendIntake($intakeId, $sheetsData);
        $this->model->updateSyncStatus($intakeId, $syncStatus);

        return $this->success([
            'intake_id'          => $intakeId,
            'patient_uuid'       => $patientUuid,
            'status'             => 'pending',
            'sheets_sync_status' => $syncStatus,
            'idempotent'         => false,
        ], 201);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/intakes  (staff)
    // -------------------------------------------------------------------------
    public function index(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $page     = max(1, (int)($req->query['page'] ?? 1));
        $perPage  = min(100, max(1, (int)($req->query['per_page'] ?? 20)));
        $status   = $req->query['status'] ?? null;
        $q        = $req->query['q'] ?? '';

        $result = $this->model->list($clinicId, $page, $perPage, $status, $q);

        return $this->success($result['rows'], 200, [
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $result['total'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Map the live intake.html camelCase payload to backend snake_case keys.
     * Per §6.1 of UNIFIED_MASTER_PLAN.md — this is the ONE canonical mapping.
     * The frontend payload contract is never changed.
     * Both camelCase and snake_case keys are accepted (snake_case wins if both present).
     */
    private function normalisePayload(array $raw): array
    {
        static $map = [
            'firstName'   => 'first_name',
            'lastName'    => 'last_name',
            'fatherName'  => 'father_name',
            'nationalId'  => 'national_id',
            'birthDate'   => 'birth_date_jalali',  // intake.html sends 'birthDate'
            'homeTel'     => 'home_tel',
            'homeAd'      => 'home_address',
            'visitReason' => 'visit_reason',
            'isTransfer'  => 'is_transfer',
        ];

        $out = $raw;
        foreach ($map as $camel => $snake) {
            if (array_key_exists($camel, $raw) && !array_key_exists($snake, $out)) {
                $out[$snake] = $raw[$camel];
            }
        }

        // 'description' from the frontend maps to 'chief_complaint' (§6.1 note)
        if (array_key_exists('description', $raw) && !array_key_exists('chief_complaint', $out)) {
            $out['chief_complaint'] = $raw['description'];
        }

        return $out;
    }

    private function validateIntakePayload(array $in): array
    {
        $errors = [];

        foreach (['first_name', 'last_name', 'mobile', 'national_id', 'birth_date_jalali'] as $f) {
            if (empty(trim((string)($in[$f] ?? '')))) {
                $errors[] = ['field' => $f, 'message' => "فیلد $f الزامی است"];
            }
        }
        // chief_complaint is required but has a friendly label
        if (empty(trim((string)($in['chief_complaint'] ?? '')))) {
            $errors[] = ['field' => 'chief_complaint', 'message' => 'علت مراجعه الزامی است'];
        }
        if (!empty($errors)) {
            return $errors;
        }

        $mobile = ValidatorService::normalizeMobile($in['mobile']);
        if (!ValidatorService::isValidMobile($mobile)) {
            $errors[] = ['field' => 'mobile', 'message' => 'شماره موبایل معتبر نیست (مثال: ۰۹۱۲۳۴۵۶۷۸۹)'];
        }

        $nationalId = ValidatorService::normalizePersianDigits($in['national_id']);
        if (!ValidatorService::isValidNationalId($nationalId)) {
            $errors[] = ['field' => 'national_id', 'message' => 'کد ملی معتبر نیست'];
        }

        if (!ValidatorService::isValidJalaliDate($in['birth_date_jalali'])) {
            $errors[] = ['field' => 'birth_date_jalali', 'message' => 'تاریخ تولد معتبر نیست (فرمت: ۱۳۷۰/۰۱/۰۱)'];
        }

        return $errors;
    }

    /**
     * Execute the Sheets sync for an already-committed intake row.
     * Used both on the fresh write path and the idempotent re-try path.
     * Returns the status string ('ok' | 'failed' | 'skipped').
     */
    private function runSheetsSync(int $intakeId, array $row): string
    {
        return $this->sheets->appendIntake($intakeId, [
            'submission_uuid' => $row['submission_uuid'] ?? '',
            'name'            => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            'mobile'          => $row['mobile']          ?? '',
            'national_id'     => $row['national_id']     ?? '',
            'birth_date'      => $row['birth_date']      ?? '',
            'service_type'    => $row['service_type']    ?? '',
            'chief_complaint' => $row['chief_complaint'] ?? '',
            'preferred_date'  => $row['preferred_date']  ?? '',
            'submitted_at'    => $row['created_at']      ?? gmdate('Y-m-d H:i:s'),
            'email'           => $row['email']           ?? '',
            'visit_reason'    => $row['visit_reason']    ?? '',
        ]);
    }

    private function upsertPatient(\PDO $db, int $clinicId, array $data): int
    {
        $stmt = $db->prepare(
            'SELECT id FROM patients WHERE mobile = ? AND clinic_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$data['mobile'], $clinicId]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $db->prepare(
                'UPDATE patients SET
                    first_name   = COALESCE(NULLIF(?, ""), first_name),
                    last_name    = COALESCE(NULLIF(?, ""), last_name),
                    national_id  = COALESCE(NULLIF(?, ""), national_id),
                    birth_date   = COALESCE(NULLIF(?, ""), birth_date),
                    updated_at   = UTC_TIMESTAMP()
                 WHERE id = ?'
            )->execute([
                $data['first_name'],
                $data['last_name'],
                $data['national_id'],
                $data['birth_date'],
                (int)$existing,
            ]);
            return (int)$existing;
        }

        $patientUuid = bin2hex(random_bytes(16));
        $db->prepare(
            'INSERT INTO patients
                (uuid, clinic_id, first_name, last_name, mobile, national_id,
                 birth_date, gender, home_address, insurance_status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", UTC_TIMESTAMP())'
        )->execute([
            $patientUuid, $clinicId,
            $data['first_name'], $data['last_name'],
            $data['mobile'],     $data['national_id'],
            $data['birth_date'], $data['gender'],
            $data['home_address'],
        ]);
        return (int)$db->lastInsertId();
    }

    private function getPatientUuid(\PDO $db, int $patientId): string
    {
        $stmt = $db->prepare('SELECT uuid FROM patients WHERE id = ? LIMIT 1');
        $stmt->execute([$patientId]);
        return (string)$stmt->fetchColumn();
    }
}
