<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\SmsProviderChain;
use App\Validators\ValidatorService;

/**
 * WordPress booking bridge.
 * WordPress remains a marketing UI only: the clinical booking record is written
 * here, into the shared patients/intakes identity, and only non-PII counters are
 * returned by stats(). Provider credentials never cross this boundary.
 */
final class BookingController extends Controller
{
    public function store(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }

        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        $name = trim((string)($req->body['name'] ?? ''));
        $email = trim((string)($req->body['email'] ?? ''));
        $procedure = trim((string)($req->body['procedure'] ?? ''));
        $message = trim((string)($req->body['message'] ?? ''));
        $uuid = trim((string)($req->body['submission_uuid'] ?? ''));
        // Non-Persian bookings (en/ar/tr/ru/fr/de/es) come from international
        // patients whose mobile may not be Iranian. Accept E.164-ish numbers for
        // those and require an email so the clinic can reach them.
        $language = strtolower(trim((string)($req->body['language'] ?? 'fa')));
        $isInternational = $language !== '' && $language !== 'fa';
        if ($isInternational) {
            $mobile = ValidatorService::normalizeMobileInternational(trim((string)($req->body['mobile'] ?? '')));
        }
        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 200) {
            $errors[] = ['field' => 'name', 'message' => 'نام معتبر الزامی است'];
        }
        if ($isInternational) {
            if (!ValidatorService::isValidMobileInternational($mobile)) {
                $errors[] = ['field' => 'mobile', 'message' => 'شماره موبایل معتبر نیست'];
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['field' => 'email', 'message' => 'برای رزرو بین‌المللی ایمیل معتبر الزامی است'];
            }
        } elseif (!ValidatorService::isValidMobile($mobile)) {
            $errors[] = ['field' => 'mobile', 'message' => 'شماره موبایل معتبر نیست'];
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['field' => 'email', 'message' => 'ایمیل معتبر نیست'];
        }
        if ($uuid === '' || strlen($uuid) > 64 || !preg_match('/^[A-Za-z0-9._:-]+$/', $uuid)) {
            $errors[] = ['field' => 'submission_uuid', 'message' => 'شناسه درخواست معتبر نیست'];
        }
        if (mb_strlen($procedure) > 200) {
            $errors[] = ['field' => 'procedure', 'message' => 'نوع خدمت بیش از حد طولانی است'];
        }
        if (mb_strlen($message) > 2000) {
            $errors[] = ['field' => 'message', 'message' => 'توضیحات حداکثر ۲۰۰۰ کاراکتر است'];
        }
        if ($errors) {
            return $this->validationError($errors);
        }

        $db = Database::conn();
        $clinicId = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
        $cooldown = max(1, min(1440, (int)($req->body['cooldown_minutes'] ?? ($_ENV['BOOKING_COOLDOWN_MINUTES'] ?? 30))));

        // Backend rate limit is authoritative even if WordPress cache/transients are bypassed.
        $recent = $db->prepare(
            'SELECT id, submission_uuid, status, sms_status FROM intakes
             WHERE mobile = ? AND source_type = "booking" AND deleted_at IS NULL
               AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? MINUTE)
             ORDER BY id DESC LIMIT 1'
        );
        $recent->execute([$mobile, $cooldown]);
        if ($row = $recent->fetch()) {
            return $this->success([
                'booking_id' => (int)$row['id'],
                'status' => $row['status'],
                'sms_status' => $row['sms_status'],
                'idempotent' => true,
                'cooldown_minutes' => $cooldown,
            ]);
        }

        $existing = $db->prepare('SELECT id, uuid FROM patients WHERE mobile = ? AND deleted_at IS NULL LIMIT 1');
        $existing->execute([$mobile]);
        $patient = $existing->fetch();
        [$firstName, $lastName] = $this->splitName($name);

        $existingPatientId = $patient ? (int)$patient['id'] : null;
        $patientEmail = $this->availablePatientEmail($db, $email, $existingPatientId);
        if ($email !== '' && $patientEmail === null) {
            error_log('[BookingController] optional patient email not attached because it belongs to another patient record.');
        }

        $db->beginTransaction();
        try {
            if ($patient) {
                $patientId = (int)$patient['id'];
                $patientUuid = (string)$patient['uuid'];
                $db->prepare(
                    'UPDATE patients SET clinic_id = ?, first_name = COALESCE(NULLIF(?, ""), first_name),
                        last_name = COALESCE(NULLIF(?, ""), last_name), email = COALESCE(?, email),
                        updated_at = UTC_TIMESTAMP() WHERE id = ?'
                )->execute([$clinicId, $firstName, $lastName, $patientEmail, $patientId]);
            } else {
                $patientUuid = bin2hex(random_bytes(16));
                $db->prepare(
                    'INSERT INTO patients (uuid, clinic_id, first_name, last_name, mobile, email, insurance_status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, "pending", UTC_TIMESTAMP(), UTC_TIMESTAMP())'
                )->execute([$patientUuid, $clinicId, $firstName, $lastName, $mobile, $patientEmail]);
                $patientId = (int)$db->lastInsertId();
            }

            $raw = $req->body;
            unset($raw['sms_template']);
            $stmt = $db->prepare(
                'INSERT INTO intakes
                 (submission_uuid, clinic_id, patient_id, patient_uuid, source_type, first_name, last_name,
                  mobile, national_id, chief_complaint, service_type, email, visit_reason, doctor_request,
                  raw_payload, status, sheets_sync_status, sms_status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, "booking", ?, ?, ?, NULL, ?, NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""),
                         NULLIF(?, ""), ?, "pending", "skipped", "pending", UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            );
            $stmt->execute([
                $uuid, $clinicId, $patientId, $patientUuid, $firstName, $lastName, $mobile,
                $message !== '' ? $message : ($procedure !== '' ? $procedure : 'درخواست نوبت'),
                $procedure, $email, $procedure, $message,
                json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            $bookingId = (int)$db->lastInsertId();
            $db->commit();
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            // submission_uuid is idempotent across a WordPress retry.
            if ((string)$e->getCode() === '23000') {
                $dup = $db->prepare('SELECT id, status, sms_status FROM intakes WHERE submission_uuid = ? LIMIT 1');
                $dup->execute([$uuid]);
                if ($row = $dup->fetch()) {
                    return $this->success([
                        'booking_id' => (int)$row['id'], 'status' => $row['status'],
                        'sms_status' => $row['sms_status'], 'idempotent' => true,
                        'cooldown_minutes' => $cooldown,
                    ]);
                }
            }
            error_log('[BookingController] booking transaction failed: ' . $e->getMessage());
            return $this->error('ثبت درخواست نوبت انجام نشد.', 500);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[BookingController] booking transaction failed: ' . $e->getMessage());
            return $this->error('ثبت درخواست نوبت انجام نشد.', 500);
        }

        $smsStatus = 'skipped';
        if ($this->shouldSendBookingSms($mobile, $isInternational)) {
            $loginUrl = trim((string)($_ENV['PATIENT_LOGIN_URL'] ?? 'https://app.drbastaninejad.com/Frontend/pages/auth/patient-login.html'));
            $template = trim((string)($req->body['sms_template'] ?? ''));
            if ($template === '') {
                $template = (string)($_ENV['BOOKING_SMS_TEMPLATE'] ?? "درخواست نوبت شما ثبت شد. همکاران کلینیک برای هماهنگی تماس می‌گیرند. پنل بیمار: {login_url}");
            }
            $template = mb_substr($template, 0, 800);
            $sms = (new SmsProviderChain())->sendMessage($mobile, strtr($template, [
                '{name}' => $firstName,
                '{login_url}' => $loginUrl,
            ]));
            $smsStatus = !empty($sms['ok']) ? 'sent' : 'failed';
            $this->recordSmsResult($db, $bookingId, $smsStatus, $sms);
            if ($smsStatus !== 'sent') {
                error_log('[BookingController] booking SMS failed for booking ' . $bookingId . ' provider=' . ($sms['provider'] ?? 'none') . ' error=' . ($sms['error'] ?? 'unknown'));
            }
        } else {
            $db->prepare('UPDATE intakes SET sms_status = "skipped", updated_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$bookingId]);
        }

        return $this->success([
            'booking_id' => $bookingId,
            'status' => 'pending',
            'sms_status' => $smsStatus,
            'idempotent' => false,
            'cooldown_minutes' => $cooldown,
        ], 201);
    }

    public function stats(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $clinicId = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
        $stmt = Database::conn()->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)) AS last_7_days,
                    SUM(status = "pending") AS pending,
                    SUM(sms_status = "sent") AS sms_sent,
                    SUM(sms_status = "failed") AS sms_failed
             FROM intakes WHERE clinic_id = ? AND source_type = "booking" AND deleted_at IS NULL'
        );
        $stmt->execute([$clinicId]);
        $r = $stmt->fetch() ?: [];
        return $this->success([
            'total' => (int)($r['total'] ?? 0),
            'last_7_days' => (int)($r['last_7_days'] ?? 0),
            'pending' => (int)($r['pending'] ?? 0),
            'sms_sent' => (int)($r['sms_sent'] ?? 0),
            'sms_failed' => (int)($r['sms_failed'] ?? 0),
        ]);
    }

    private function authorised(Request $req): bool
    {
        $configured = trim((string)($_ENV['WORDPRESS_BRIDGE_SECRET'] ?? ''));
        $provided = trim((string)($req->headers['x-wordpress-bridge-secret'] ?? ''));
        return $configured !== '' && $provided !== '' && hash_equals($configured, $provided);
    }


    private function availablePatientEmail(\PDO $db, string $email, ?int $patientId): ?string
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }
        $sql = 'SELECT id FROM patients WHERE email = ?';
        $params = [$email];
        if ($patientId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $patientId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() ? null : $email;
    }

    /** @return array{0:string,1:string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/u', trim($name), 2) ?: [];
        return [trim((string)($parts[0] ?? $name)), trim((string)($parts[1] ?? ''))];
    }

    private function shouldSendBookingSms(string $mobile, bool $isInternational): bool
    {
        if (($_ENV['BOOKING_SMS_ENABLED'] ?? '1') !== '1') {
            return false;
        }
        if ($isInternational && !preg_match('/^09\d{9}$/', $mobile)) {
            return false;
        }
        return preg_match('/^09\d{9}$/', $mobile) === 1;
    }

    /** @param array{ok?:bool,provider?:?string,message_id?:?string,error?:?string} $sms */
    private function recordSmsResult(\PDO $db, int $bookingId, string $smsStatus, array $sms): void
    {
        $sent = $smsStatus === 'sent' ? 1 : 0;
        try {
            $db->prepare(
                'UPDATE intakes SET sms_status = ?, sms_sent_at = IF(?, UTC_TIMESTAMP(), NULL),
                        sms_provider = ?, sms_provider_id = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?'
            )->execute([
                $smsStatus,
                $sent,
                $sms['provider'] ?? null,
                $sms['message_id'] ?? null,
                $bookingId,
            ]);
        } catch (\PDOException $e) {
            $db->prepare(
                'UPDATE intakes SET sms_status = ?, sms_sent_at = IF(?, UTC_TIMESTAMP(), NULL), updated_at = UTC_TIMESTAMP() WHERE id = ?'
            )->execute([$smsStatus, $sent, $bookingId]);
        }
    }
}
