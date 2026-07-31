<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AppointmentModel;
use App\Models\PatientMediaModel;
use App\Models\NotificationPreferenceModel;
use App\Models\PatientModel;
use App\Validators\ValidatorService;

/**
 * PatientPortalController — Phase C patient portal endpoints.
 *
 * All methods require a valid Bearer token resolved by AuthMiddleware.
 * $_REQUEST['_auth_user'] is populated by AuthMiddleware::handle() and
 * contains: user_id, uuid, first_name, last_name, mobile, user_type.
 *
 * Response envelope: docs/API_CONTRACT.md ("success"/"data"/"error")
 *
 * Routes (registered in config/routes.php):
 *   GET   /api/v1/patient/overview                  → overview()
 *   GET   /api/v1/patient/profile                   → profile()
 *   PATCH /api/v1/patient/profile                   → updateProfile()
 *   GET   /api/v1/patient/appointments              → appointments()
 *   GET   /api/v1/patient/documents                 → documents()
 *   GET   /api/v1/patient/notification-preferences  → getNotificationPrefs()
 *   PATCH /api/v1/patient/notification-preferences  → patchNotificationPrefs()
 *   GET   /api/v1/patient/records                   → records()
 */
final class PatientPortalController extends Controller
{
    private PatientModel                 $patientModel;
    private AppointmentModel             $appointmentModel;
    private PatientMediaModel            $mediaModel;
    private NotificationPreferenceModel  $notifModel;
    private ValidatorService             $validator;

    public function __construct()
    {
        $this->patientModel     = new PatientModel();
        $this->appointmentModel = new AppointmentModel();
        $this->mediaModel       = new PatientMediaModel();
        $this->notifModel       = new NotificationPreferenceModel();
        $this->validator        = new ValidatorService();
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/patient/overview
    // -------------------------------------------------------------------------

    /**
     * Summary card: patient name, next appointment, intake/document counts.
     * Per docs/API_CONTRACT.md §GET /patient/overview
     */
    public function overview(): void
    {
        $user      = $this->authUser();
        $patientId = (int)$user['user_id'];
        $patient   = $this->patientModel->findById($patientId);

        if ($patient === null) {
            $this->error('PATIENT_NOT_FOUND', 'بیمار یافت نشد.', 404);
        }

        $next          = $this->appointmentModel->nextForPatient($patientId);
        $totalIntakes  = $this->patientModel->countIntakes($patientId);
        $totalDocs     = count($this->mediaModel->listForPatient($patientId));
        $lastIntake    = $this->patientModel->lastIntakeDate($patientId);

        $this->json([
            'patient_name'      => trim($patient['first_name'] . ' ' . $patient['last_name']),
            'next_appointment'  => $next ? [
                'date_jalali' => $next['date_jalali'] ?? null,
                'time'        => $next['time'] ?? null,
                'reason'      => $next['reason'] ?? null,
                'status'      => $next['status'] ?? null,
            ] : null,
            'total_intakes'     => $totalIntakes,
            'total_documents'   => $totalDocs,
            'last_intake_date'  => $lastIntake,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/patient/profile
    // -------------------------------------------------------------------------

    /**
     * Return the patient's own editable profile.
     * Identity fields (national_id, mobile) are returned read-only.
     * Per docs/API_CONTRACT.md §GET /patient/profile
     */
    public function profile(): void
    {
        $patientId = (int)$this->authUser()['user_id'];
        $patient   = $this->patientModel->findById($patientId);

        if ($patient === null) {
            $this->error('PATIENT_NOT_FOUND', 'بیمار یافت نشد.', 404);
        }

        $this->json([
            'first_name'   => $patient['first_name'],
            'last_name'    => $patient['last_name'],
            'father_name'  => $patient['father_name'],
            'national_id'  => $patient['national_id'],
            'birth_date'   => $patient['birth_date_jalali'] ?? $patient['birth_date'],
            'mobile'       => $patient['mobile'],
            'email'        => $patient['email'],
            'home_tel'     => $patient['home_tel'] ?? null,
            'home_address' => $patient['home_address'],
        ]);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/patient/profile
    // -------------------------------------------------------------------------

    /**
     * Partial update of the patient's own editable contact fields.
     * Allowed: email, home_tel, home_address.
     * Identity fields (national_id, mobile, first/last name) are NOT writable.
     * Per docs/API_CONTRACT.md §PATCH /patient/profile (v1.2)
     */
    public function updateProfile(): void
    {
        $patientId = (int)$this->authUser()['user_id'];
        $body      = $this->jsonBody();

        $allowed = ['email', 'home_tel', 'home_address'];
        $patch   = [];
        $errors  = [];

        foreach ($allowed as $col) {
            if (!array_key_exists($col, $body)) {
                continue;
            }
            $val = $body[$col];
            if ($val === null || $val === '') {
                // Allow clearing optional fields
                $patch[$col] = null;
                continue;
            }
            $val = (string)$val;
            if ($col === 'email' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                $errors[$col] = 'آدرس ایمیل معتبر نیست.';
                continue;
            }
            if ($col === 'email' && mb_strlen($val) > 120) {
                $errors[$col] = 'ایمیل نباید بیشتر از ۱۲۰ کاراکتر باشد.';
                continue;
            }
            if ($col === 'home_tel' && !preg_match('/^\d{1,15}$/', $val)) {
                $errors[$col] = 'تلفن منزل باید عددی و حداکثر ۱۵ رقم باشد.';
                continue;
            }
            if ($col === 'home_address' && mb_strlen($val) > 255) {
                $errors[$col] = 'آدرس نباید بیشتر از ۲۵۵ کاراکتر باشد.';
                continue;
            }
            $patch[$col] = $val;
        }

        if ($errors !== []) {
            $this->validationError($errors);
        }

        if ($patch === []) {
            $this->error('EMPTY_PATCH', 'هیچ فیلد قابل‌ویرایشی ارسال نشد.', 400);
        }

        $this->patientModel->updateProfile($patientId, $patch);

        // Return the full updated profile (same shape as GET /patient/profile)
        $patient = $this->patientModel->findById($patientId);
        if ($patient === null) {
            $this->error('PATIENT_NOT_FOUND', 'بیمار یافت نشد.', 404);
        }

        $this->json([
            'first_name'   => $patient['first_name'],
            'last_name'    => $patient['last_name'],
            'father_name'  => $patient['father_name'],
            'national_id'  => $patient['national_id'],
            'birth_date'   => $patient['birth_date_jalali'] ?? $patient['birth_date'],
            'mobile'       => $patient['mobile'],
            'email'        => $patient['email'],
            'home_tel'     => $patient['home_tel'] ?? null,
            'home_address' => $patient['home_address'],
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/patient/appointments
    // -------------------------------------------------------------------------

    /**
     * Paginated list of this patient's appointments (read-only).
     * Query params: page (int, default 1), per_page (int, default 10, max 50).
     * Per docs/API_CONTRACT.md §GET /patient/appointments
     */
    public function appointments(): void
    {
        $patientId = (int)$this->authUser()['user_id'];
        $page      = max(1, (int)($_GET['page']     ?? 1));
        $perPage   = min(50, max(1, (int)($_GET['per_page'] ?? 10)));

        $result = $this->appointmentModel->listForPatient($patientId, $page, $perPage);
        $this->json($result);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/patient/documents
    // -------------------------------------------------------------------------

    /**
     * List patient's media documents with short-lived signed URLs (1 hour TTL).
     * Per docs/API_CONTRACT.md §GET /patient/documents
     *
     * SECURITY: signed_url is generated server-side. Raw storage paths are
     * never exposed to the client. CDN_BASE_URL must be set in .env.
     */
    public function documents(): void
    {
        $patientId = (int)$this->authUser()['user_id'];
        $items     = $this->mediaModel->listForPatient($patientId);

        $this->json(['items' => $items]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/patient/notification-preferences
    // -------------------------------------------------------------------------

    /**
     * Retrieve this patient's notification preferences.
     * Returns defaults if no row exists yet (upsert-on-write design).
     * Per docs/API_CONTRACT.md §GET /patient/notification-preferences
     */
    public function getNotificationPrefs(): void
    {
        $patientId = (int)$this->authUser()['user_id'];
        $prefs     = $this->notifModel->getForPatient($patientId);
        $this->json($prefs);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/patient/notification-preferences
    // -------------------------------------------------------------------------

    /**
     * Partial update of notification preferences.
     * Only the keys present in the request body are changed.
     * Per docs/API_CONTRACT.md §PATCH /patient/notification-preferences
     */
    public function patchNotificationPrefs(): void
    {
        $patientId = (int)$this->authUser()['user_id'];
        $body      = $this->jsonBody();

        $allowed = [
            'sms_appointment_reminder',
            'sms_status_change',
            'email_appointment_reminder',
            'email_marketing',
        ];

        // Validate: every supplied key must be in $allowed and be a boolean
        $patch  = [];
        $errors = [];
        foreach ($body as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                $errors[$key] = 'کلید نامعتبر است.';
                continue;
            }
            if (!is_bool($value) && !in_array($value, [0, 1, '0', '1'], true)) {
                $errors[$key] = 'مقدار باید true یا false باشد.';
                continue;
            }
            $patch[$key] = (bool)$value;
        }

        if ($errors !== []) {
            $this->validationError($errors);
        }

        if ($patch === []) {
            $this->error('EMPTY_PATCH', 'هیچ فیلد قابل‌ویرایشی ارسال نشد.', 400);
        }

        $this->notifModel->patchForPatient($patientId, $patch);

        // Return full updated state
        $prefs = $this->notifModel->getForPatient($patientId);
        $this->json($prefs);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    // GET /api/v1/patient/records
    // -------------------------------------------------------------------------

    /**
     * Paginated read-only timeline of this patient's EMR records (signed notes only).
     * Query params: page (int, default 1), per_page (int, default 10, max 50).
     * Per docs/API_CONTRACT.md §GET /patient/records (Phase E)
     *
     * Response data:
     *   items[]: { id, visit_type, author_name, subjective, assessment, plan,
     *              is_signed, signed_at, created_at }
     *   pagination: { total, per_page, current_page, last_page }
     */
    public function records(): void
    {
        $patientId = (int)$this->authUser()['user_id'];
        $page      = max(1, (int)($_GET['page']     ?? 1));
        $perPage   = min(50, max(1, (int)($_GET['per_page'] ?? 10)));

        $db     = \App\Core\Database::getInstance();
        $offset = ($page - 1) * $perPage;

        $total = (int)$db->query(
            'SELECT COUNT(*) FROM emr_records WHERE patient_id = ? AND is_draft = 0',
            [$patientId]
        )->fetchColumn();

        $rows = $db->query(
            'SELECT id, visit_type, author_name, subjective, assessment, plan,
                    is_signed, signed_at, created_at
             FROM emr_records
             WHERE patient_id = ? AND is_draft = 0
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?',
            [$patientId, $perPage, $offset]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->json([
            'items'      => $rows,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int)ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Return the resolved auth user from AuthMiddleware.
     * Throws 401 if AuthMiddleware was not run (should not happen in normal routing).
     */
    private function authUser(): array
    {
        $user = $_REQUEST['_auth_user'] ?? null;
        if (!is_array($user) || empty($user['user_id'])) {
            $this->error('UNAUTHORIZED', 'احراز هویت الزامی است.', 401);
        }
        return $user;
    }
}
