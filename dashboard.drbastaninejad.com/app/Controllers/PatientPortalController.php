<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

/**
 * PatientPortalController — Phase C
 *
 * Serves the 7 patient-facing endpoints consumed by api.js in the frontend:
 *
 *   GET    /api/v1/patient/overview
 *   GET    /api/v1/patient/profile
 *   PATCH  /api/v1/patient/profile
 *   GET    /api/v1/patient/appointments
 *   GET    /api/v1/patient/documents
 *   GET    /api/v1/patient/notification-preferences
 *   PATCH  /api/v1/patient/notification-preferences
 *
 * All endpoints require a valid patient-scoped bearer token.
 * $req->user is populated by AuthMiddleware and contains at minimum:
 *   { id, uuid, clinic_id, role = 'patient', user_type = 'patient' }
 */
final class PatientPortalController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /api/v1/patient/overview
    // -------------------------------------------------------------------------
    public function overview(Request $req): array
    {
        $patientId = (int)$req->user['id'];
        $clinicId  = (int)$req->user['clinic_id'];

        $db = Database::conn();

        // Next upcoming appointment (soonest future booked/confirmed)
        $stmt = $db->prepare(
            'SELECT uuid, starts_at, ends_at, status, reason
             FROM appointments
             WHERE patient_id = ? AND clinic_id = ?
               AND starts_at > UTC_TIMESTAMP()
               AND status IN ("booked","confirmed")
               AND deleted_at IS NULL
             ORDER BY starts_at ASC
             LIMIT 1'
        );
        $stmt->execute([$patientId, $clinicId]);
        $nextAppt = $stmt->fetch() ?: null;

        // Last intake summary
        $stmt = $db->prepare(
            'SELECT uuid, service_type, chief_complaint, status, created_at
             FROM intakes
             WHERE patient_id = ? AND clinic_id = ? AND deleted_at IS NULL
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $stmt->execute([$patientId, $clinicId]);
        $lastIntake = $stmt->fetch() ?: null;

        return $this->success([
            'next_appointment' => $nextAppt ? [
                'uuid'      => $nextAppt['uuid'],
                'starts_at' => $nextAppt['starts_at'],
                'ends_at'   => $nextAppt['ends_at'],
                'status'    => $nextAppt['status'],
                'reason'    => $nextAppt['reason'],
            ] : null,
            'last_intake' => $lastIntake ? [
                'uuid'            => $lastIntake['uuid'],
                'service_type'    => $lastIntake['service_type'],
                'chief_complaint' => $lastIntake['chief_complaint'],
                'status'          => $lastIntake['status'],
                'submitted_at'    => $lastIntake['created_at'],
            ] : null,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/patient/profile
    // -------------------------------------------------------------------------
    public function profile(Request $req): array
    {
        $patientId = (int)$req->user['id'];

        $db   = Database::conn();
        $stmt = $db->prepare(
            'SELECT uuid, first_name, last_name, father_name, mobile, email,
                    national_id, birth_date, birth_date_jalali, gender,
                    insurance_number, insurance_status, home_address
             FROM patients
             WHERE id = ? AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$patientId]);
        $row = $stmt->fetch();

        if (!$row) {
            return $this->error('بیمار یافت نشد', 404);
        }

        return $this->success([
            'uuid'             => $row['uuid'],
            'first_name'       => $row['first_name'],
            'last_name'        => $row['last_name'],
            'father_name'      => $row['father_name'],
            'mobile'           => $row['mobile'],
            'email'            => $row['email'],
            'national_id'      => $row['national_id'],
            'birth_date'       => $row['birth_date'],
            'birth_date_jalali'=> $row['birth_date_jalali'],
            'gender'           => $row['gender'],
            'insurance_number' => $row['insurance_number'],
            'insurance_status' => $row['insurance_status'],
            'home_address'     => $row['home_address'],
        ]);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/patient/profile
    // Patients may update a limited set of their own fields.
    // -------------------------------------------------------------------------
    public function updateProfile(Request $req): array
    {
        $patientId = (int)$req->user['id'];
        $in        = $req->body;

        // Whitelist — patients cannot change medical or RBAC fields
        $allowed = ['first_name', 'last_name', 'father_name', 'home_address', 'email'];
        $updates = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $in) && $in[$field] !== null) {
                $updates[$field] = trim((string)$in[$field]);
            }
        }

        if (empty($updates)) {
            return $this->error('هیچ فیلد قابل ویرایشی ارائه نشده است', 422);
        }

        $db  = Database::conn();
        $set = implode(', ', array_map(fn($col) => "$col = ?", array_keys($updates)));
        $db->prepare("UPDATE patients SET $set, updated_at = UTC_TIMESTAMP() WHERE id = ?")
           ->execute([...array_values($updates), $patientId]);

        return $this->success(['updated' => true]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/patient/appointments
    // -------------------------------------------------------------------------
    public function appointments(Request $req): array
    {
        $patientId = (int)$req->user['id'];
        $clinicId  = (int)$req->user['clinic_id'];
        $page      = max(1, (int)($req->query['page'] ?? 1));
        $perPage   = min(50, max(1, (int)($req->query['per_page'] ?? 20)));
        $offset    = ($page - 1) * $perPage;

        $db = Database::conn();

        $countStmt = $db->prepare(
            'SELECT COUNT(*) FROM appointments
             WHERE patient_id = ? AND clinic_id = ? AND deleted_at IS NULL'
        );
        $countStmt->execute([$patientId, $clinicId]);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            'SELECT uuid, starts_at, ends_at, status, reason, reminder_sent_at
             FROM appointments
             WHERE patient_id = ? AND clinic_id = ? AND deleted_at IS NULL
             ORDER BY starts_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$patientId, $clinicId, $perPage, $offset]);
        $rows = $stmt->fetchAll();

        return $this->success(
            array_map(fn($r) => [
                'uuid'             => $r['uuid'],
                'starts_at'        => $r['starts_at'],
                'ends_at'          => $r['ends_at'],
                'status'           => $r['status'],
                'reason'           => $r['reason'],
                'reminder_sent_at' => $r['reminder_sent_at'],
            ], $rows),
            200,
            ['page' => $page, 'per_page' => $perPage, 'total' => $total]
        );
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/patient/documents
    // Returns patient's media files with short-TTL signed read URLs.
    // Signed URL generation is stubbed here (ArvanCloud S3 integration is Phase 6).
    // -------------------------------------------------------------------------
    public function documents(Request $req): array
    {
        $patientId = (int)$req->user['id'];
        $clinicId  = (int)$req->user['clinic_id'];

        $db   = Database::conn();
        $stmt = $db->prepare(
            'SELECT uuid, type, tag, mime_type, size_bytes, created_at
             FROM media
             WHERE patient_id = ? AND clinic_id = ? AND deleted_at IS NULL
             ORDER BY created_at DESC'
        );
        $stmt->execute([$patientId, $clinicId]);
        $rows = $stmt->fetchAll();

        return $this->success(
            array_map(fn($r) => [
                'uuid'       => $r['uuid'],
                'type'       => $r['type'],
                'tag'        => $r['tag'],
                'mime_type'  => $r['mime_type'],
                'size_bytes' => (int)$r['size_bytes'],
                'created_at' => $r['created_at'],
                // signed_url is fetched separately via GET /api/v1/media/{uuid}/url
                // to keep this list response cacheable (no per-file TTL URLs here).
                'download_endpoint' => '/api/v1/media/' . $r['uuid'] . '/url',
            ], $rows)
        );
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/patient/notification-preferences
    // -------------------------------------------------------------------------
    public function notificationPreferences(Request $req): array
    {
        $patientId = (int)$req->user['id'];

        $db   = Database::conn();
        $stmt = $db->prepare(
            'SELECT marketing_email_optin FROM patients
             WHERE id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$patientId]);
        $row = $stmt->fetch();

        if (!$row) {
            return $this->error('بیمار یافت نشد', 404);
        }

        return $this->success([
            'marketing_email_optin' => (bool)$row['marketing_email_optin'],
        ]);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/patient/notification-preferences
    // -------------------------------------------------------------------------
    public function updateNotificationPreferences(Request $req): array
    {
        $patientId = (int)$req->user['id'];
        $in        = $req->body;

        if (!array_key_exists('marketing_email_optin', $in)) {
            return $this->error('فیلد marketing_email_optin الزامی است', 422);
        }

        $optIn = (bool)$in['marketing_email_optin'];

        $db = Database::conn();
        $db->prepare(
            'UPDATE patients SET marketing_email_optin = ?, updated_at = UTC_TIMESTAMP()
             WHERE id = ?'
        )->execute([$optIn ? 1 : 0, $patientId]);

        return $this->success(['marketing_email_optin' => $optIn]);
    }
}
