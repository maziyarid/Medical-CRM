<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Models\IntakeModel;
use App\Services\ValidatorService;
use App\Services\GoogleSheetsService;
use App\Services\OtpService;

/**
 * IntakeController — Phase A deliverable
 *
 * POST /api/v1/intakes (public, no auth)
 *   - Validates Code Meli (mod-11), Iranian mobile, Jalali birth date
 *   - Idempotent via submission_uuid (UNIQUE on intakes.submission_uuid)
 *   - Dual-writes to MariaDB + Google Sheets inside a DB transaction
 *   - Returns { data: { intake_id, patient_uuid, status } } on success
 *
 * GET  /api/v1/intakes  (staff auth required — routed in routes.intake.php)
 *   - Returns paginated review queue
 */
final class IntakeController extends Controller
{
    private IntakeModel $model;
    private ValidatorService $validator;
    private GoogleSheetsService $sheets;

    public function __construct()
    {
        $this->model     = new IntakeModel();
        $this->validator = new ValidatorService();
        $this->sheets    = new GoogleSheetsService();
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/intakes
    // -------------------------------------------------------------------------
    public function store(Request $req): array
    {
        $in = $req->body;

        // 1. submission_uuid idempotency — must be supplied by the frontend
        $uuid = trim((string)($in['submission_uuid'] ?? ''));
        if ($uuid === '' || strlen($uuid) > 64) {
            return $this->error('submission_uuid is required (max 64 chars)', 422);
        }

        // 2. Check for duplicate submission (idempotency)
        $existing = $this->model->findByUuid($uuid);
        if ($existing) {
            // 200 with original data — safe to retry
            return $this->success([
                'intake_id'    => (int)$existing['id'],
                'patient_uuid' => $existing['patient_uuid'],
                'status'       => $existing['status'],
                'idempotent'   => true,
            ]);
        }

        // 3. Validate required fields
        $errors = $this->validateIntakePayload($in);
        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // 4. Normalize inputs
        $mobile     = ValidatorService::normalizeMobile($in['mobile']);
        $nationalId = ValidatorService::normalizePersianDigits($in['national_id']);
        $birthDate  = ValidatorService::jalaliToGregorian($in['birth_date_jalali']); // YYYY-MM-DD

        // 5. Transactional dual-write
        $db = Database::conn();
        $db->beginTransaction();

        try {
            // 5a. Upsert patient (match on mobile within clinic, else create)
            $clinicId  = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
            $patientId = $this->upsertPatient($db, $clinicId, [
                'first_name'  => trim($in['first_name']),
                'last_name'   => trim($in['last_name']),
                'mobile'      => $mobile,
                'national_id' => $nationalId,
                'birth_date'  => $birthDate,
                'gender'      => $in['gender'] ?? null,
                'home_address'=> $in['home_address'] ?? null,
            ]);

            $patientUuid = $this->getPatientUuid($db, $patientId);

            // 5b. Build raw_payload
            $rawPayload = json_encode($in, JSON_UNESCAPED_UNICODE);

            // 5c. Insert intake row
            $intakeId = $this->model->createIntake([
                'submission_uuid' => $uuid,
                'clinic_id'       => $clinicId,
                'patient_id'      => $patientId,
                'patient_uuid'    => $patientUuid,
                'first_name'      => trim($in['first_name']),
                'last_name'       => trim($in['last_name']),
                'mobile'          => $mobile,
                'national_id'     => $nationalId,
                'birth_date'      => $birthDate,
                'chief_complaint' => trim($in['chief_complaint'] ?? ''),
                'service_type'    => $in['service_type'] ?? null,
                'preferred_date'  => $in['preferred_date'] ?? null,
                'insurance_type'  => $in['insurance_type'] ?? null,
                'raw_payload'     => $rawPayload,
                'status'          => 'pending',
                'created_at'      => gmdate('Y-m-d H:i:s'),
            ]);

            $db->commit();

            // 5d. Dual-write to Google Sheets (non-fatal — never block the patient)
            try {
                $this->sheets->appendIntake($intakeId, [
                    'submission_uuid' => $uuid,
                    'name'            => trim($in['first_name']) . ' ' . trim($in['last_name']),
                    'mobile'          => $mobile,
                    'national_id'     => $nationalId,
                    'birth_date'      => $birthDate,
                    'service_type'    => $in['service_type'] ?? '',
                    'chief_complaint' => $in['chief_complaint'] ?? '',
                    'preferred_date'  => $in['preferred_date'] ?? '',
                    'submitted_at'    => gmdate('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $sheetsErr) {
                // Log but do not rollback — DB write is the source of truth
                error_log('[GoogleSheets] dual-write failed: ' . $sheetsErr->getMessage());
            }

            return $this->success([
                'intake_id'    => $intakeId,
                'patient_uuid' => $patientUuid,
                'status'       => 'pending',
                'idempotent'   => false,
            ], 201);

        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[IntakeController] transaction failed: ' . $e->getMessage());
            return $this->error('خطای داخلی سرور. لطفاً دوباره تلاش کنید.', 500);
        }
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
    private function validateIntakePayload(array $in): array
    {
        $errors = [];

        // Required presence
        foreach (['first_name', 'last_name', 'mobile', 'national_id', 'birth_date_jalali', 'chief_complaint'] as $f) {
            if (empty(trim((string)($in[$f] ?? '')))) {
                $errors[] = ['field' => $f, 'message' => "فیلد $f الزامی است"];
            }
        }
        if (!empty($errors)) {
            return $errors; // bail early before format checks
        }

        // Mobile
        $mobile = ValidatorService::normalizeMobile($in['mobile']);
        if (!ValidatorService::isValidMobile($mobile)) {
            $errors[] = ['field' => 'mobile', 'message' => 'شماره موبایل معتبر نیست (مثال: ۰۹۱۲۳۴۵۶۷۸۹)'];
        }

        // Code Meli (mod-11)
        $nationalId = ValidatorService::normalizePersianDigits($in['national_id']);
        if (!ValidatorService::isValidNationalId($nationalId)) {
            $errors[] = ['field' => 'national_id', 'message' => 'کد ملی معتبر نیست'];
        }

        // Jalali birth date
        if (!ValidatorService::isValidJalaliDate($in['birth_date_jalali'])) {
            $errors[] = ['field' => 'birth_date_jalali', 'message' => 'تاریخ تولد معتبر نیست (فرمت: ۱۳۷۰/۰۱/۰۱)'];
        }

        return $errors;
    }

    private function upsertPatient(\PDO $db, int $clinicId, array $data): int
    {
        $stmt = $db->prepare(
            'SELECT id FROM patients WHERE mobile = ? AND clinic_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$data['mobile'], $clinicId]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            // Update only non-empty fields without overwriting known-good data
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

        // New patient
        $patientUuid = bin2hex(random_bytes(16));
        $db->prepare(
            'INSERT INTO patients
                (uuid, clinic_id, first_name, last_name, mobile, national_id, birth_date, gender, home_address, insurance_status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", UTC_TIMESTAMP())'
        )->execute([
            $patientUuid,
            $clinicId,
            $data['first_name'],
            $data['last_name'],
            $data['mobile'],
            $data['national_id'],
            $data['birth_date'],
            $data['gender'],
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
