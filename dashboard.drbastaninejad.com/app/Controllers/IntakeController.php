<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\IntakeModel;
use App\Services\GoogleSheetsService;
use App\Services\SmsProviderChain;
use App\Validators\ValidatorService;

final class IntakeController extends Controller
{
    private IntakeModel $model;
    private GoogleSheetsService $sheets;

    public function __construct()
    {
        $this->model = new IntakeModel();
        $this->sheets = new GoogleSheetsService();
    }

    public function store(Request $req): array
    {
        $in = $this->normalisePayload($req->body);
        $uuid = trim((string)($in['submission_uuid'] ?? ''));
        if ($uuid === '' || strlen($uuid) > 64 || !preg_match('/^[A-Za-z0-9._:-]+$/', $uuid)) {
            return $this->validationError([['field' => 'submission_uuid', 'message' => 'submission_uuid معتبر الزامی است']]);
        }

        $bridge = $this->isBridgeRequest($req);
        $bridgeStatus = $bridge ? strtolower(trim((string)($in['_bridge_sheet_status'] ?? ''))) : '';
        if ($bridgeStatus !== '' && !in_array($bridgeStatus, ['pending', 'ok', 'failed'], true)) {
            return $this->validationError([['field' => '_bridge_sheet_status', 'message' => 'وضعیت شیت معتبر نیست']]);
        }

        $existing = $this->model->findByUuid($uuid);
        if ($existing) {
            $syncStatus = (string)($existing['sheets_sync_status'] ?? 'pending');
            if ($bridge && $bridgeStatus !== '') {
                $syncStatus = $bridgeStatus;
                $this->model->updateSyncStatus((int)$existing['id'], $syncStatus);
            } elseif (!$bridge && !in_array($syncStatus, ['ok', 'skipped'], true)) {
                $syncStatus = $this->runSheetsSync((int)$existing['id'], $existing);
                $this->model->updateSyncStatus((int)$existing['id'], $syncStatus);
            }

            $smsStatus = (string)($existing['sms_status'] ?? 'pending');
            if ((!$bridge || $syncStatus === 'ok') && $smsStatus !== 'sent' && $smsStatus !== 'skipped') {
                $smsStatus = $this->notifyPatient((int)$existing['id'], (string)$existing['mobile'], (string)$existing['first_name']);
            }
            return $this->success([
                'intake_id' => (int)$existing['id'],
                'patient_uuid' => $existing['patient_uuid'],
                'status' => $existing['status'],
                'sheets_sync_status' => $syncStatus,
                'sms_status' => $smsStatus,
                'idempotent' => true,
            ]);
        }

        // A bridge status-only call is valid only after the first full write exists.
        if ($bridge && $bridgeStatus !== '' && count($req->body) <= 2) {
            return $this->error('intake not found for bridge status update', 404);
        }

        $errors = $this->validateIntakePayload($in);
        if ($errors) {
            return $this->validationError($errors);
        }

        $mobile = ValidatorService::normalizeMobile((string)$in['mobile']);
        $nationalId = ValidatorService::normalizePersianDigits((string)$in['national_id']);
        $birthJalali = ValidatorService::normalizePersianDigits((string)$in['birth_date_jalali']);
        $birthJalali = str_replace('-', '/', $birthJalali);
        $birthDate = ValidatorService::jalaliToGregorian($birthJalali);
        $email = trim((string)($in['email'] ?? '')) ?: null;
        $doctorRequest = trim((string)($in['doctor_request'] ?? ''));
        $clinicId = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
        $db = Database::conn();

        $db->beginTransaction();
        try {
            $patientId = $this->upsertPatient($db, $clinicId, [
                'first_name' => trim((string)$in['first_name']),
                'last_name' => trim((string)$in['last_name']),
                'father_name' => trim((string)($in['father_name'] ?? '')),
                'mobile' => $mobile,
                'national_id' => $nationalId,
                'email' => $email,
                'birth_date' => $birthDate,
                'birth_date_jalali' => $birthJalali,
                'gender' => trim((string)($in['gender'] ?? '')) ?: null,
                'home_tel' => trim((string)($in['home_tel'] ?? '')),
                'home_address' => trim((string)($in['home_address'] ?? '')),
            ]);
            $patientUuid = $this->getPatientUuid($db, $patientId);
            $syncStatus = $bridge ? ($bridgeStatus ?: 'pending') : 'pending';
            $intakeId = $this->model->createIntake([
                'submission_uuid' => $uuid,
                'clinic_id' => $clinicId,
                'patient_id' => $patientId,
                'patient_uuid' => $patientUuid,
                'source_type' => 'intake',
                'first_name' => trim((string)$in['first_name']),
                'last_name' => trim((string)$in['last_name']),
                'mobile' => $mobile,
                'national_id' => $nationalId,
                'birth_date' => $birthDate,
                'birth_date_jalali' => $birthJalali,
                'chief_complaint' => trim((string)$in['chief_complaint']),
                'service_type' => trim((string)($in['service_type'] ?? '')) ?: null,
                'preferred_date' => trim((string)($in['preferred_date'] ?? '')) ?: null,
                'insurance_type' => trim((string)($in['insurance_type'] ?? '')) ?: null,
                'email' => $email,
                'visit_reason' => trim((string)($in['visit_reason'] ?? '')) ?: null,
                'doctor_request' => $doctorRequest !== '' ? $doctorRequest : null,
                'raw_payload' => json_encode($req->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => 'pending',
                'sheets_sync_status' => $syncStatus,
                'sms_status' => 'pending',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[IntakeController] transaction failed: ' . $e->getMessage());
            return $this->error('خطای داخلی سرور. لطفاً دوباره تلاش کنید.', 500);
        }

        if (!$bridge) {
            $syncStatus = $this->runSheetsSync($intakeId, [
                'submission_uuid' => $uuid,
                'first_name' => $in['first_name'], 'last_name' => $in['last_name'],
                'mobile' => $mobile, 'national_id' => $nationalId, 'birth_date' => $birthDate,
                'service_type' => $in['service_type'] ?? '', 'chief_complaint' => $in['chief_complaint'],
                'preferred_date' => $in['preferred_date'] ?? '', 'email' => $email ?? '',
                'visit_reason' => $in['visit_reason'] ?? '', 'doctor_request' => $doctorRequest,
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->model->updateSyncStatus($intakeId, $syncStatus);
        }

        $smsStatus = 'pending';
        if (!$bridge || $syncStatus === 'ok') {
            $smsStatus = $this->notifyPatient($intakeId, $mobile, trim((string)$in['first_name']));
        }

        return $this->success([
            'intake_id' => $intakeId,
            'patient_uuid' => $patientUuid,
            'status' => 'pending',
            'sheets_sync_status' => $syncStatus,
            'sms_status' => $smsStatus,
            'idempotent' => false,
        ], 201);
    }

    public function index(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $page = max(1, (int)($req->query['page'] ?? 1));
        $perPage = min(100, max(1, (int)($req->query['per_page'] ?? 20)));
        $status = isset($req->query['status']) ? trim((string)$req->query['status']) : null;
        $q = trim((string)($req->query['q'] ?? ''));
        $sourceType = isset($req->query['source_type']) ? trim((string)$req->query['source_type']) : null;
        $result = $this->model->list($clinicId, $page, $perPage, $status, $q, $sourceType);
        return $this->success($result['rows'], 200, [
            'page' => $page, 'per_page' => $perPage, 'total' => $result['total'],
            'last_page' => max(1, (int)ceil($result['total'] / $perPage)),
        ]);
    }

    public function show(Request $req, string $id): array
    {
        $intakeId = (int)$id;
        if ($intakeId < 1) {
            return $this->error('پذیرش/درخواست نوبت یافت نشد.', 404);
        }
        $row = $this->model->findForClinic($intakeId, (int)($req->user['clinic_id'] ?? 1));
        if (!$row) {
            return $this->error('پذیرش/درخواست نوبت یافت نشد.', 404);
        }
        $raw = json_decode((string)($row['raw_payload'] ?? ''), true);
        $row['medical_history'] = is_array($raw)
            ? trim((string)($raw['medical_history'] ?? $raw['medicalHistory'] ?? $row['chief_complaint'] ?? ''))
            : trim((string)($row['chief_complaint'] ?? ''));
        $row['medications'] = is_array($raw)
            ? trim((string)($raw['medications'] ?? ''))
            : '';
        unset($row['raw_payload']);
        return $this->success($row);
    }

    public function updateStatus(Request $req, string $id): array
    {
        $intakeId = (int)$id;
        $status = strtolower(trim((string)($req->body['status'] ?? '')));
        if ($intakeId < 1 || !in_array($status, ['pending', 'reviewed', 'converted', 'rejected'], true)) {
            return $this->validationError([['field' => 'status', 'message' => 'وضعیت بررسی معتبر نیست']]);
        }
        if (!$this->model->updateReviewStatus($intakeId, (int)$req->user['clinic_id'], $status, (int)$req->user['id'])) {
            return $this->error('پذیرش/درخواست نوبت یافت نشد.', 404);
        }
        return $this->success(['id' => $intakeId, 'status' => $status]);
    }

    private function normalisePayload(array $raw): array
    {
        $map = [
            'firstName' => 'first_name', 'lastName' => 'last_name', 'fatherName' => 'father_name',
            'nationalId' => 'national_id', 'birthDate' => 'birth_date_jalali', 'homeTel' => 'home_tel',
            'homeAd' => 'home_address', 'visitReason' => 'visit_reason', 'doctorRequest' => 'doctor_request',
            'isTransfer' => 'is_transfer',
        ];
        $out = $raw;
        foreach ($map as $from => $to) {
            if (array_key_exists($from, $raw) && !array_key_exists($to, $out)) {
                $out[$to] = $raw[$from];
            }
        }
        if (array_key_exists('description', $raw) && !array_key_exists('chief_complaint', $out)) {
            $out['chief_complaint'] = $raw['description'];
        }
        return $out;
    }

    private function validateIntakePayload(array $in): array
    {
        $errors = [];
        foreach (['first_name', 'last_name', 'mobile', 'national_id', 'birth_date_jalali', 'chief_complaint'] as $field) {
            if (trim((string)($in[$field] ?? '')) === '') {
                $errors[] = ['field' => $field, 'message' => 'این فیلد الزامی است'];
            }
        }
        if ($errors) {
            return $errors;
        }
        $mobile = ValidatorService::normalizeMobile((string)$in['mobile']);
        if (!ValidatorService::isValidMobile($mobile)) {
            $errors[] = ['field' => 'mobile', 'message' => 'شماره موبایل معتبر نیست'];
        }
        $nationalId = ValidatorService::normalizePersianDigits((string)$in['national_id']);
        if (!ValidatorService::isValidNationalId($nationalId)) {
            $errors[] = ['field' => 'national_id', 'message' => 'کد ملی معتبر نیست'];
        }
        if (!ValidatorService::isValidJalaliDate((string)$in['birth_date_jalali'])) {
            $errors[] = ['field' => 'birth_date_jalali', 'message' => 'تاریخ تولد معتبر نیست'];
        }
        $email = trim((string)($in['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['field' => 'email', 'message' => 'ایمیل معتبر نیست'];
        }
        if (mb_strlen(trim((string)($in['doctor_request'] ?? ''))) > 2000) {
            $errors[] = ['field' => 'doctor_request', 'message' => 'درخواست از دکتر حداکثر ۲۰۰۰ کاراکتر است'];
        }
        return $errors;
    }

    private function isBridgeRequest(Request $req): bool
    {
        $configured = (string)($_ENV['INTAKE_BRIDGE_SECRET'] ?? '');
        $provided = (string)($req->headers['x-intake-bridge-secret'] ?? '');
        return $configured !== '' && $provided !== '' && hash_equals($configured, $provided);
    }

    private function runSheetsSync(int $intakeId, array $row): string
    {
        return $this->sheets->appendIntake($intakeId, $row);
    }

    private function notifyPatient(int $intakeId, string $mobile, string $firstName): string
    {
        if (($_ENV['INTAKE_SUCCESS_SMS_ENABLED'] ?? '1') !== '1') {
            $this->model->updateSmsStatus($intakeId, 'skipped');
            return 'skipped';
        }
        $loginUrl = trim((string)($_ENV['PATIENT_LOGIN_URL'] ?? 'https://app.drbastaninejad.com/Frontend/pages/auth/patient-login.html'));
        $template = (string)($_ENV['INTAKE_SUCCESS_SMS_TEMPLATE'] ?? "پرونده شما ثبت شد. برای ورود به پنل بیمار:\n{login_url}\nبا همان شماره موبایل کد یکبارمصرف دریافت کنید.");
        $message = strtr($template, ['{login_url}' => $loginUrl, '{name}' => $firstName]);
        $result = (new SmsProviderChain())->sendMessage($mobile, $message);
        $status = $result['ok'] ? 'sent' : 'failed';
        $this->model->updateSmsStatus($intakeId, $status, $result['ok']);
        if (!$result['ok']) {
            error_log('[IntakeController] post-submit SMS failed for intake ' . $intakeId . ': ' . ($result['error'] ?? 'unknown'));
        }
        return $status;
    }

    private function upsertPatient(\PDO $db, int $clinicId, array $data): int
    {
        $stmt = $db->prepare('SELECT id FROM patients WHERE mobile = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$data['mobile']]);
        $existing = $stmt->fetchColumn();
        $existingId = $existing ? (int)$existing : null;
        $patientEmail = $this->availableUniquePatientValue($db, 'email', $data['email'], $existingId);
        $patientNationalId = $this->availableUniquePatientValue($db, 'national_id', $data['national_id'], $existingId);
        if ($data['email'] !== null && $patientEmail === null) {
            error_log('[IntakeController] optional patient email not attached because it belongs to another patient record.');
        }
        if (($data['national_id'] ?? '') !== '' && $patientNationalId === null) {
            error_log('[IntakeController] patient national_id not attached because it belongs to another patient record.');
        }

        if ($existingId !== null) {
            $db->prepare(
                'UPDATE patients SET clinic_id = ?, first_name = ?, last_name = ?,
                    father_name = COALESCE(NULLIF(?, ""), father_name),
                    national_id = COALESCE(NULLIF(?, ""), national_id),
                    email = COALESCE(?, email), birth_date = COALESCE(?, birth_date),
                    birth_date_jalali = COALESCE(NULLIF(?, ""), birth_date_jalali),
                    gender = COALESCE(?, gender), home_tel = COALESCE(NULLIF(?, ""), home_tel),
                    home_address = COALESCE(NULLIF(?, ""), home_address), updated_at = UTC_TIMESTAMP()
                 WHERE id = ?'
            )->execute([
                $clinicId, $data['first_name'], $data['last_name'], $data['father_name'], $patientNationalId,
                $patientEmail, $data['birth_date'], $data['birth_date_jalali'], $data['gender'],
                $data['home_tel'], $data['home_address'], $existingId,
            ]);
            return $existingId;
        }
        $uuid = bin2hex(random_bytes(16));
        $db->prepare(
            'INSERT INTO patients
             (uuid, clinic_id, first_name, last_name, father_name, mobile, email, national_id,
              birth_date, birth_date_jalali, gender, home_tel, home_address, insurance_status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        )->execute([
            $uuid, $clinicId, $data['first_name'], $data['last_name'], $data['father_name'] ?: null,
            $data['mobile'], $patientEmail, $patientNationalId, $data['birth_date'], $data['birth_date_jalali'],
            $data['gender'], $data['home_tel'] ?: null, $data['home_address'] ?: null,
        ]);
        return (int)$db->lastInsertId();
    }

    private function availableUniquePatientValue(\PDO $db, string $column, mixed $value, ?int $patientId): ?string
    {
        $value = $value === null ? '' : trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (!in_array($column, ['email', 'national_id'], true)) {
            throw new \InvalidArgumentException('Unsupported patient unique field.');
        }
        $sql = "SELECT id FROM patients WHERE {$column} = ?";
        $params = [$value];
        if ($patientId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $patientId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() ? null : $value;
    }

    private function getPatientUuid(\PDO $db, int $patientId): string
    {
        $stmt = $db->prepare('SELECT uuid FROM patients WHERE id = ? LIMIT 1');
        $stmt->execute([$patientId]);
        return (string)$stmt->fetchColumn();
    }
}
