<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Patient;
use App\Validators\ValidatorService;
use App\Services\PatientService;
use App\Services\PatientCommunicationService;
use App\Services\BookingBlacklistService;
use RuntimeException;

final class PatientController extends Controller
{
    private Patient $patients;
    private PatientService $service;
    private PatientCommunicationService $communication;

    public function __construct()
    {
        $this->patients = new Patient();
        $this->service = new PatientService();
        $this->communication = new PatientCommunicationService();
    }

    public function index(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $q = trim((string)($req->query['q'] ?? ''));
        $page = max(1, (int)($req->query['page'] ?? 1));
        $perPage = min(100, max(1, (int)($req->query['per_page'] ?? 20)));
        $insuranceStatus = trim((string)($req->query['insurance_status'] ?? ''));

        if (!in_array($insuranceStatus, ['', 'active', 'inactive', 'pending', 'unknown'], true)) {
            $insuranceStatus = '';
        }

        $result = $this->patients->search($clinicId, $q, $page, $perPage, $insuranceStatus);
        $blacklist = new BookingBlacklistService();
        $rows = array_map(static function (array $r) use ($blacklist, $clinicId): array {
            return [
                'id' => (int)$r['id'],
                'name' => trim((string)$r['first_name'] . ' ' . (string)$r['last_name']),
                'first_name' => (string)$r['first_name'],
                'last_name' => (string)$r['last_name'],
                'mobile' => $r['mobile'],
                'national_id' => $r['national_id'],
                'insurance_status' => $r['insurance_status'],
                'last_visit' => $r['last_visit'],
                'upcoming_count' => (int)$r['upcoming_count'],
                'booking_blocked' => !empty($r['national_id']) && $blacklist->isBlocked($clinicId, (string)$r['national_id']),
            ];
        }, $result['rows']);

        return $this->success([
            'rows' => $rows,
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
        ]);
    }

    public function show(Request $req, string $id): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $patientId = (int)$id;
        $patient = $this->patients->find($patientId);
        if (!$patient || (int)$patient['clinic_id'] !== $clinicId) {
            return $this->error('بیمار یافت نشد', 404);
        }

        return $this->success([
            'patient' => [
                'id' => (int)$patient['id'],
                'name' => trim((string)$patient['first_name'] . ' ' . (string)$patient['last_name']),
                'first_name' => (string)$patient['first_name'],
                'last_name' => (string)$patient['last_name'],
                'mobile' => $patient['mobile'],
                'national_id' => $patient['national_id'],
                'birth_date' => $patient['birth_date'] ?? null,
                'insurance_status' => $patient['insurance_status'],
                'home_address' => $patient['home_address'] ?? null,
            ],
            'timeline' => $this->patients->timeline($patientId, $clinicId),
        ]);
    }

    public function store(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $in = $req->body;
        $mobile = ValidatorService::normalizeMobile($in['mobile'] ?? '');
        if (!$mobile) {
            return $this->error('شماره موبایل نامعتبر است', 422);
        }
        $nationalId = $in['national_id'] ?? null;
        if ($nationalId && !ValidatorService::isValidCodeMeli($nationalId)) {
            return $this->error('کد ملی نامعتبر است', 422);
        }

        $id = $this->service->createStaffPatient($clinicId, [
            'first_name' => trim((string)($in['first_name'] ?? '')),
            'last_name' => trim((string)($in['last_name'] ?? '')),
            'mobile' => $mobile,
            'national_id' => $nationalId,
            'home_address' => $in['home_address'] ?? null,
        ]);
        return $this->success(['id' => $id], 201);
    }

    public function update(Request $req, string $id): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $patientId = (int)$id;
        $patient = $this->patients->find($patientId);
        if (!$patient || (int)$patient['clinic_id'] !== $clinicId) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $in = $req->body;
        $updates = [];
        foreach (['first_name', 'last_name', 'home_address', 'insurance_status'] as $field) {
            if (array_key_exists($field, $in)) {
                $updates[$field] = is_string($in[$field]) ? trim($in[$field]) : $in[$field];
            }
        }
        if (array_key_exists('mobile', $in)) {
            $mobile = ValidatorService::normalizeMobile((string)$in['mobile']);
            if (!$mobile) {
                return $this->error('شماره موبایل نامعتبر است', 422);
            }
            $updates['mobile'] = $mobile;
        }
        if ($updates === []) {
            return $this->error('تغییری برای ذخیره ارسال نشده است', 422);
        }

        $this->patients->update($patientId, $updates);
        $sms = $this->communication->sendToPatient(
            $clinicId,
            $patientId,
            "اطلاعات پرونده شما توسط پذیرش کلینیک دکتر باستانی‌نژاد به‌روزرسانی شد."
        );
        return $this->success(['id' => $patientId, 'sms' => $sms]);
    }

    public function destroy(Request $req, string $id): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $patientId = (int)$id;
        $patient = $this->patients->find($patientId);
        if (!$patient || (int)$patient['clinic_id'] !== $clinicId) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $sms = $this->communication->sendToPatient(
            $clinicId,
            $patientId,
            "پرونده شما بنا به درخواست پذیرش کلینیک دکتر باستانی‌نژاد از فهرست فعال خارج شد."
        );
        $this->patients->softDelete($patientId);
        return $this->success(['id' => $patientId, 'status' => 'archived', 'sms' => $sms]);
    }

    public function sendSms(Request $req, string $id): array
    {
        try {
            return $this->success($this->communication->sendToPatient(
                (int)($req->user['clinic_id'] ?? 1),
                (int)$id,
                (string)($req->body['message'] ?? '')
            ));
        } catch (RuntimeException $e) {
            return $this->communicationError($e);
        }
    }

    public function bulkSms(Request $req): array
    {
        try {
            $ids = is_array($req->body['patient_ids'] ?? null) ? $req->body['patient_ids'] : [];
            return $this->success($this->communication->sendBulk(
                (int)($req->user['clinic_id'] ?? 1),
                $ids,
                (string)($req->body['message'] ?? '')
            ));
        } catch (RuntimeException $e) {
            return $this->communicationError($e);
        }
    }

    private function communicationError(RuntimeException $e): array
    {
        $status = $e->getMessage() === 'patient not found' ? 404 : 422;
        $message = match ($e->getMessage()) {
            'patient not found' => 'بیمار یافت نشد',
            'invalid SMS message' => 'متن پیامک باید بین ۱ تا ۷۰۰ نویسه باشد',
            'no patients selected' => 'هیچ بیماری انتخاب نشده است',
            'too many patients selected' => 'حداکثر ۲۰۰ بیمار را هم‌زمان انتخاب کنید',
            default => 'ارسال پیامک انجام نشد',
        };
        return $this->error($message, $status);
    }
}
