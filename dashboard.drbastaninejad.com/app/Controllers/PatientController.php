<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Patient;
use App\Services\ValidatorService;
use App\Services\PatientService;

/**
 * PatientController
 * Powers the "Patient Master Index" and "Patient Detail Timeline" screens —
 * the most important screen per the design brief (Medical CRM.md §5.4).
 * All endpoints are clinic-scoped and RBAC-gated (patients.view / patients.manage).
 */
final class PatientController extends Controller
{
    private Patient $patients;
    private PatientService $service;

    public function __construct()
    {
        $this->patients = new Patient();
        $this->service = new PatientService();
    }

    /** GET /api/v1/patients?q=&page=&per_page= */
    public function index(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $q = trim((string)($req->query['q'] ?? ''));
        $page = max(1, (int)($req->query['page'] ?? 1));
        $perPage = min(100, max(1, (int)($req->query['per_page'] ?? 20)));

        $result = $this->patients->search($clinicId, $q, $page, $perPage);

        $rows = array_map(function ($r) {
            return [
                'id' => (int)$r['id'],
                'name' => trim($r['first_name'] . ' ' . $r['last_name']),
                'mobile' => $r['mobile'],
                'national_id' => $r['national_id'],
                'insurance_status' => $r['insurance_status'],
                'last_visit' => $r['last_visit'],
                'upcoming_count' => (int)$r['upcoming_count'],
            ];
        }, $result['rows']);

        return $this->success([
            'rows' => $rows,
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
        ]);
    }

    /** GET /api/v1/patients/{id} — header + full timeline */
    public function show(Request $req, string $id): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $patientId = (int)$id;

        $patient = $this->patients->find($patientId);
        if (!$patient || (int)$patient['clinic_id'] !== $clinicId) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $timeline = $this->patients->timeline($patientId, $clinicId);

        return $this->success([
            'patient' => [
                'id' => (int)$patient['id'],
                'name' => trim($patient['first_name'] . ' ' . $patient['last_name']),
                'mobile' => $patient['mobile'],
                'national_id' => $patient['national_id'],
                'birth_date' => $patient['birth_date'] ?? null,
                'insurance_status' => $patient['insurance_status'],
                'home_address' => $patient['home_address'] ?? null,
            ],
            'timeline' => $timeline,
        ]);
    }

    /** POST /api/v1/patients — staff-created patient (outside the public intake flow) */
    public function store(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $in = $req->body;

        $mobile = ValidatorService::normalizeMobile($in['mobile'] ?? '');
        if (!$mobile) {
            return $this->error('شماره موبایل نامعتبر است', 422);
        }

        $nationalId = $in['national_id'] ?? null;
        if ($nationalId && !ValidatorService::isValidNationalId($nationalId)) {
            return $this->error('کد ملی نامعتبر است', 422);
        }

        $id = $this->service->createStaffPatient($clinicId, [
            'first_name' => trim($in['first_name'] ?? ''),
            'last_name' => trim($in['last_name'] ?? ''),
            'mobile' => $mobile,
            'national_id' => $nationalId,
            'home_address' => $in['home_address'] ?? null,
        ]);

        return $this->success(['id' => $id], 201);
    }

    /** PUT /api/v1/patients/{id} */
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
                $updates[$field] = $in[$field];
            }
        }
        if (isset($in['mobile'])) {
            $mobile = ValidatorService::normalizeMobile($in['mobile']);
            if (!$mobile) {
                return $this->error('شماره موبایل نامعتبر است', 422);
            }
            $updates['mobile'] = $mobile;
        }

        if (!empty($updates)) {
            $this->patients->update($patientId, $updates);
        }

        return $this->success(['id' => $patientId]);
    }
}
