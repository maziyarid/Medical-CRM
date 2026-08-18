<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\EmrRecord;
use App\Models\Patient;
use App\Services\AiRouterService;

/**
 * EmrController
 * Powers the Dynamic EMR Editor (Medical CRM.md §5.7). Connects directly to the
 * Patient Timeline (emr_note entries) and can be launched from the Calendar's
 * appointment detail ("Write note" action) — this is the module tying Patients
 * and Calendar together, per your instruction to finish the connected module.
 */
final class EmrController extends Controller
{
    private EmrRecord $records;
    private Patient $patients;

    public function __construct()
    {
        $this->records = new EmrRecord();
        $this->patients = new Patient();
    }

    /** GET /api/v1/patients/{id}/emr — all notes for a patient, newest first */
    public function index(Request $req, string $patientId): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $pid = (int)$patientId;

        $patient = $this->patients->find($pid);
        if (!$patient || (int)$patient['clinic_id'] !== $clinicId) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $rows = $this->records->forPatient($pid, $clinicId);
        return $this->success(['records' => $rows]);
    }

    /** GET /api/v1/emr/templates?specialty= — schema-driven form templates */
    public function templates(Request $req): array
    {
        $specialty = $req->query['specialty'] ?? 'general';
        return $this->success(['templates' => $this->records->templatesForSpecialty($specialty)]);
    }

    /** POST /api/v1/patients/{id}/emr — save a new clinical note (staff only, never AI-auto-saved) */
    public function store(Request $req, string $patientId): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $pid = (int)$patientId;
        $authorId = (int)($req->user['id'] ?? 0);
        $in = $req->body;

        $patient = $this->patients->find($pid);
        if (!$patient || (int)$patient['clinic_id'] !== $clinicId) {
            return $this->error('بیمار یافت نشد', 404);
        }

        if (empty($in['chief_complaint'])) {
            return $this->error('شرح شکایت اصلی الزامی است', 422);
        }

        $id = $this->records->create([
            'uuid' => bin2hex(random_bytes(16)),
            'clinic_id' => $clinicId,
            'patient_id' => $pid,
            'appointment_id' => $in['appointment_id'] ?? null,
            'author_id' => $authorId,
            'template_id' => $in['template_id'] ?? null,
            'chief_complaint' => $in['chief_complaint'],
            'diagnosis' => $in['diagnosis'] ?? null,
            'plan' => $in['plan'] ?? null,
            'specialty_fields' => json_encode($in['specialty_fields'] ?? [], JSON_UNESCAPED_UNICODE),
            'ai_draft' => null,
            'ai_accepted' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success(['id' => $id], 201);
    }

    /** POST /api/v1/ai/emr-draft — review-only AI suggestion; never persisted automatically. */
    public function draftNote(Request $req): array
    {
        $in = $req->body;
        $patientId = (int)($in['patient_id'] ?? 0);
        $prompt = trim((string)($in['prompt'] ?? $in['chief_complaint'] ?? ''));
        if ($patientId < 1 || $prompt === '') {
            return $this->validationError([['field' => 'prompt', 'message' => 'شناسه بیمار و درخواست AI الزامی است']]);
        }
        if (mb_strlen($prompt) > 2000) {
            return $this->validationError([['field' => 'prompt', 'message' => 'درخواست AI حداکثر ۲۰۰۰ کاراکتر است']]);
        }
        $patient = $this->patients->find($patientId);
        if (!$patient || (int)$patient['clinic_id'] !== (int)($req->user['clinic_id'] ?? 1)) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $service = new AiRouterService();
        if (!$service->isEnabled()) {
            return $this->error('سرویس AI در محیط سرور فعال نشده است.', 503);
        }
        try {
            $draft = $service->draftClinicalNote($prompt, is_array($in['context'] ?? null) ? $in['context'] : []);
        } catch (\InvalidArgumentException $e) {
            return $this->validationError([['field' => 'prompt', 'message' => $e->getMessage()]]);
        } catch (\Throwable $e) {
            error_log('[EmrController] AI draft failed: ' . $e->getMessage());
            return $this->error('دریافت پیش‌نویس AI ناموفق بود. متن بالینی ذخیره یا خودکار اعمال نشد.', 502);
        }

        return $this->success([
            'draft' => $draft,
            'requires_review' => true,
            'persisted' => false,
        ]);
    }
}
