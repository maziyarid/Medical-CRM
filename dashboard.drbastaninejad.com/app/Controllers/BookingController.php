<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\BookingBlacklistService;
use App\Services\BookingSheetRowBuilder;
use App\Services\BookingVerificationService;
use App\Services\EmailService;
use App\Services\GoogleSheetsService;
use App\Services\OtpService;
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
    public function sendOtp(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        if (!ValidatorService::isValidMobile($mobile)) {
            return $this->validationError([['field' => 'mobile', 'message' => 'شماره موبایل معتبر نیست']]);
        }
        $otp = new OtpService();
        if ($otp->isGloballyRateLimited() || $otp->isRateLimited($mobile, 'patient', 'register')) {
            return $this->error('تعداد درخواست‌های کد تأیید بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.', 429);
        }
        if (!$otp->sendSms($mobile, 'patient', 'register')) {
            return $this->error('ارسال کد تأیید با خطا مواجه شد. لطفاً دوباره تلاش کنید.', 503);
        }
        return $this->success(['message' => 'کد تأیید ارسال شد.', 'expires_in' => 300]);
    }

    public function verifyOtp(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        $code = ValidatorService::normalizePersianDigits(trim((string)($req->body['otp'] ?? '')));
        if (!ValidatorService::isValidMobile($mobile) || !preg_match('/^\d{5}$/', $code)) {
            return $this->validationError([['field' => 'otp', 'message' => 'شماره همراه یا کد تأیید معتبر نیست']]);
        }
        $result = (new OtpService())->verify($mobile, $code, 'patient', 'register');
        if ($result === 'expired') {
            return $this->error('کد تأیید منقضی شده است. کد جدید دریافت کنید.', 410);
        }
        if ($result !== 'ok') {
            return $this->error('کد تأیید نامعتبر است.', $result === 'locked' ? 429 : 401);
        }
        return $this->success((new BookingVerificationService())->issue($mobile));
    }

    public function eligibility(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $identifier = trim((string)($req->body['national_id'] ?? $req->body['passport_number'] ?? $req->body['identifier'] ?? ''));
        $type = trim((string)($req->body['identifier_type'] ?? '')) ?: null;
        if ($identifier === '') {
            return $this->validationError([['field' => 'identifier', 'message' => 'شناسه معتبر الزامی است']]);
        }
        try {
            $blocked = (new BookingBlacklistService())->isBlocked(
                (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1),
                $identifier,
                $type
            );
        } catch (\RuntimeException $e) {
            return $this->validationError([['field' => 'identifier', 'message' => 'شناسه معتبر نیست']]);
        }
        return $this->success([
            'eligible' => !$blocked,
            'message' => $blocked ? 'شما واجد شرایط نیستید.' : '',
        ]);
    }

    public function blacklistIndex(Request $req): array
    {
        return $this->success((new BookingBlacklistService())->listing((int)($req->user['clinic_id'] ?? 1)));
    }

    public function blacklistStore(Request $req): array
    {
        try {
            $row = (new BookingBlacklistService())->add(
                (int)($req->user['clinic_id'] ?? 1),
                (int)($req->user['id'] ?? 0),
                $req->body
            );
            return $this->success($row, 201);
        } catch (\RuntimeException $e) {
            $message = match ($e->getMessage()) {
                'patient not found' => 'بیمار یافت نشد.',
                'patient identifier missing' => 'برای این بیمار کد ملی ثبت نشده است؛ شناسه را در بخش لیست سیاه وارد کنید.',
                'invalid national id' => 'کد ملی معتبر نیست.',
                'invalid passport' => 'شماره گذرنامه معتبر نیست.',
                default => 'افزودن به لیست سیاه انجام نشد.',
            };
            return $this->error($message, 422);
        }
    }

    public function blacklistDelete(Request $req, string $id): array
    {
        $removed = (new BookingBlacklistService())->remove(
            (int)($req->user['clinic_id'] ?? 1),
            (int)($req->user['id'] ?? 0),
            (int)$id
        );
        if (!$removed) return $this->error('رکورد لیست سیاه یافت نشد.', 404);
        return $this->success(['id' => (int)$id, 'removed' => true]);
    }

    public function store(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }

        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        $firstName = trim((string)($req->body['first_name'] ?? ''));
        $lastName = trim((string)($req->body['last_name'] ?? ''));
        $name = trim((string)($req->body['name'] ?? trim($firstName . ' ' . $lastName)));
        $email = trim((string)($req->body['email'] ?? ''));
        $procedure = trim((string)($req->body['procedure'] ?? ''));
        $message = trim((string)($req->body['message'] ?? ''));
        $uuid = trim((string)($req->body['submission_uuid'] ?? ''));
        $birthDateJalali = ValidatorService::normalizePersianDigits(trim((string)($req->body['birth_date_jalali'] ?? '')));
        $birthDateJalali = str_replace('-', '/', $birthDateJalali);
        $nationalId = ValidatorService::normalizePersianDigits(trim((string)($req->body['national_id'] ?? '')));
        $passportNumber = trim((string)($req->body['passport_number'] ?? $req->body['passportNumber'] ?? ''));
        $medicalHistory = trim((string)($req->body['medical_history'] ?? ''));
        $medications = trim((string)($req->body['medications'] ?? ''));
        $doctorRequest = trim((string)($req->body['doctor_request'] ?? $message));
        $verificationToken = trim((string)($req->body['otp_token'] ?? ''));
        // Non-Persian bookings (en/ar/tr/ru/fr/de/es) come from international
        // patients whose mobile may not be Iranian. Accept E.164-ish numbers for
        // those and require an email so the clinic can reach them.
        $language = strtolower(trim((string)($req->body['language'] ?? 'fa')));
        $isInternational = $language !== '' && $language !== 'fa';
        if ($isInternational) {
            $mobile = ValidatorService::normalizeMobileInternational(trim((string)($req->body['mobile'] ?? '')));
        }
        $errors = [];
        if (!$isInternational && (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 100)) {
            $errors[] = ['field' => 'first_name', 'message' => 'نام معتبر الزامی است'];
        }
        if (!$isInternational && (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 100)) {
            $errors[] = ['field' => 'last_name', 'message' => 'نام خانوادگی معتبر الزامی است'];
        }
        if ($isInternational && (mb_strlen($name) < 2 || mb_strlen($name) > 200)) {
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
        if (!$isInternational && !ValidatorService::isValidJalaliDate($birthDateJalali)) {
            $errors[] = ['field' => 'birth_date_jalali', 'message' => 'تاریخ تولد شمسی معتبر نیست'];
        }
        if (!$isInternational && !ValidatorService::isValidNationalId($nationalId)) {
            $errors[] = ['field' => 'national_id', 'message' => 'کد ملی معتبر نیست'];
        }
        if (!$isInternational && ValidatorService::isValidJalaliDate($birthDateJalali)) {
            $bookingAge = ValidatorService::ageFromJalaliDate($birthDateJalali);
            if ($bookingAge === null || $bookingAge < 18 || $bookingAge > 45) {
                $errors[] = ['field' => 'birth_date_jalali', 'message' => 'شما واجد شرایط نیستید.'];
            }
        }
        if ($isInternational) {
            $bookingAge = (int)ValidatorService::normalizePersianDigits((string)($req->body['age'] ?? '0'));
            if ($bookingAge < 18 || $bookingAge > 45) {
                $errors[] = ['field' => 'age', 'message' => 'شما واجد شرایط نیستید.'];
            }
        }
        foreach ([
            'medical_history' => $medicalHistory,
            'medications' => $medications,
            'doctor_request' => $doctorRequest,
        ] as $field => $value) {
            if (mb_strlen($value) > 2000) {
                $errors[] = ['field' => $field, 'message' => 'این فیلد حداکثر ۲۰۰۰ کاراکتر است'];
            }
        }
        if (!$isInternational && !preg_match('/^[a-f0-9]{64}$/', $verificationToken)) {
            $errors[] = ['field' => 'otp_token', 'message' => 'تأیید شماره همراه الزامی است'];
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

        $clinicId = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
        $blacklistIdentifier = $isInternational ? $passportNumber : $nationalId;
        if ($blacklistIdentifier !== '') {
            try {
                $blacklistType = $isInternational ? 'passport' : 'national_id';
                if ((new BookingBlacklistService())->isBlocked($clinicId, $blacklistIdentifier, $blacklistType)) {
                    return $this->error('شما واجد شرایط نیستید.', 422);
                }
            } catch (\RuntimeException $e) {
                if ($isInternational) {
                    return $this->validationError([['field' => 'passport_number', 'message' => 'شماره گذرنامه معتبر نیست']]);
                }
            }
        }

        $db = Database::conn();
        // The API is authoritative for abuse controls; the bridge cannot lower this value.
        $cooldown = max(1, min(1440, (int)($_ENV['BOOKING_COOLDOWN_MINUTES'] ?? 30)));

        // A transport retry of the exact same request is safe even though its
        // one-time grant was already consumed by the successful transaction.
        $sameRequest = $db->prepare(
            'SELECT id, status, sms_status, booking_sheet_status, booking_email_status
             FROM intakes WHERE submission_uuid = ? AND source_type = "booking" LIMIT 1'
        );
        $sameRequest->execute([$uuid]);
        if ($row = $sameRequest->fetch()) {
            return $this->success(array_merge([
                'booking_id' => (int)$row['id'], 'status' => $row['status'],
                'sms_status' => $row['sms_status'], 'sheet_status' => $row['booking_sheet_status'],
                'email_status' => $row['booking_email_status'], 'idempotent' => true,
                'cooldown_minutes' => $cooldown,
            ], $this->patientSessionPayload($mobile)));
        }

        // Backend rate limit is authoritative even if WordPress cache/transients are bypassed.
        $recent = $db->prepare(
            'SELECT id, submission_uuid, status, sms_status FROM intakes
             WHERE mobile = ? AND source_type = "booking" AND deleted_at IS NULL
               AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? MINUTE)
             ORDER BY id DESC LIMIT 1'
        );
        $recent->execute([$mobile, $cooldown]);
        if ($row = $recent->fetch()) {
            return $this->error('درخواست این شماره به‌تازگی ثبت شده است. زمان نوبت پس از تماس کلینیک قطعی می‌شود.', 429);
        }

        $existing = $db->prepare('SELECT id, uuid FROM patients WHERE mobile = ? AND deleted_at IS NULL LIMIT 1');
        $existing->execute([$mobile]);
        $patient = $existing->fetch();
        // Persian booking already supplies distinct fields. Only the legacy international
        // form sends a single full name that needs splitting.
        if ($isInternational) {
            [$firstName, $lastName] = $this->splitName($name);
        }

        $patientCreated = !$patient;
        $existingPatientId = $patient ? (int)$patient['id'] : null;
        $patientEmail = $this->availablePatientEmail($db, $email, $existingPatientId);
        if ($email !== '' && $patientEmail === null) {
            error_log('[BookingController] optional patient email not attached because it belongs to another patient record.');
        }

        $db->beginTransaction();
        try {
            if (!$isInternational && !(new BookingVerificationService())->consume($db, $mobile, $verificationToken)) {
                throw new \RuntimeException('BOOKING_OTP_INVALID');
            }
            $birthDate = $isInternational ? null : ValidatorService::jalaliToGregorian($birthDateJalali);
            if ($patient) {
                $patientId = (int)$patient['id'];
                $patientUuid = (string)$patient['uuid'];
                $db->prepare(
                    'UPDATE patients SET clinic_id = ?, first_name = COALESCE(NULLIF(?, ""), first_name),
                        last_name = COALESCE(NULLIF(?, ""), last_name), email = COALESCE(?, email),
                        national_id = COALESCE(NULLIF(?, ""), national_id),
                        birth_date = COALESCE(?, birth_date), birth_date_jalali = COALESCE(NULLIF(?, ""), birth_date_jalali),
                        updated_at = UTC_TIMESTAMP() WHERE id = ?'
                )->execute([$clinicId, $firstName, $lastName, $patientEmail, $nationalId, $birthDate, $birthDateJalali, $patientId]);
            } else {
                $patientUuid = bin2hex(random_bytes(16));
                $db->prepare(
                    'INSERT INTO patients (uuid, clinic_id, first_name, last_name, mobile, email, national_id,
                                           birth_date, birth_date_jalali, insurance_status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ""), ?, NULLIF(?, ""), "pending", UTC_TIMESTAMP(), UTC_TIMESTAMP())'
                )->execute([$patientUuid, $clinicId, $firstName, $lastName, $mobile, $patientEmail, $nationalId, $birthDate, $birthDateJalali]);
                $patientId = (int)$db->lastInsertId();
            }

            $raw = $req->body;
            unset($raw['sms_template'], $raw['otp_token'], $raw['cooldown_minutes']);
            $stmt = $db->prepare(
                'INSERT INTO intakes
                 (submission_uuid, clinic_id, patient_id, patient_uuid, source_type, first_name, last_name,
                  mobile, national_id, birth_date, birth_date_jalali, chief_complaint, service_type, email,
                  visit_reason, doctor_request, raw_payload, status, sheets_sync_status, booking_sheet_status,
                  booking_email_status, sms_status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, "booking", ?, ?, ?, NULLIF(?, ""), ?, NULLIF(?, ""), ?, NULLIF(?, ""),
                         NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""), ?, "pending", "pending", "pending",
                         "pending", "pending", UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            );
            $stmt->execute([
                $uuid, $clinicId, $patientId, $patientUuid, $firstName, $lastName, $mobile,
                $nationalId, $birthDate, $birthDateJalali,
                $medicalHistory !== '' ? $medicalHistory : ($message !== '' ? $message : 'درخواست نوبت'),
                $procedure, $email, $procedure, $doctorRequest,
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
                $dup = $db->prepare(
                    'SELECT id, status, sms_status, booking_sheet_status, booking_email_status
                     FROM intakes WHERE submission_uuid = ? LIMIT 1'
                );
                $dup->execute([$uuid]);
                if ($row = $dup->fetch()) {
                    return $this->success([
                        'booking_id' => (int)$row['id'], 'status' => $row['status'],
                        'sms_status' => $row['sms_status'], 'sheet_status' => $row['booking_sheet_status'],
                        'email_status' => $row['booking_email_status'], 'idempotent' => true,
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
            if ($e->getMessage() === 'BOOKING_OTP_INVALID') {
                return $this->error('تأیید شماره همراه منقضی یا قبلاً استفاده شده است. کد جدید دریافت کنید.', 401);
            }
            error_log('[BookingController] booking transaction failed: ' . $e->getMessage());
            return $this->error('ثبت درخواست نوبت انجام نشد.', 500);
        }

        $sheetData = [
            'first_name' => $firstName, 'last_name' => $lastName,
            'birth_date_jalali' => $birthDateJalali, 'mobile' => $mobile,
            'national_id' => $nationalId, 'email' => $email,
            'medical_history' => $medicalHistory, 'medications' => $medications,
            'doctor_request' => $doctorRequest,
        ];
        $db->prepare('UPDATE intakes SET booking_sheet_status = "attempting", updated_at = UTC_TIMESTAMP() WHERE id = ?')
            ->execute([$bookingId]);
        $sheetStatus = (new GoogleSheetsService())->appendBooking($bookingId, $uuid, $sheetData);
        $legacySheetStatus = $sheetStatus === 'submitted' ? 'ok' : ($sheetStatus === 'skipped' ? 'skipped' : 'failed');
        $db->prepare('UPDATE intakes SET booking_sheet_status = ?, sheets_sync_status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?')
            ->execute([$sheetStatus, $legacySheetStatus, $bookingId]);

        $emailStatus = 'skipped';
        $smsStatus = 'skipped';
        if ($patientCreated) {
            try {
                $registration = (new \App\Services\PatientRegistrationNotificationService())->notify($patientId, true, false);
                $emailStatus = (string)($registration['email'] ?? 'skipped');
                $smsStatus = (string)($registration['sms'] ?? 'failed');
            } catch (\Throwable $e) {
                error_log('[BookingController] patient registration notification failed for booking ' . $bookingId . ': ' . $e->getMessage());
                $emailStatus = 'failed';
                $smsStatus = 'failed';
            }
            $db->prepare('UPDATE intakes SET booking_email_status = ?, sms_status = ?, sms_sent_at = IF(? = "sent", UTC_TIMESTAMP(), NULL), updated_at = UTC_TIMESTAMP() WHERE id = ?')
                ->execute([$emailStatus, $smsStatus, $smsStatus, $bookingId]);
        } else {
            if ($email !== '') {
                $emailService = new EmailService();
                if ($emailService->isConfigured()) {
                    $emailStatus = $emailService->sendBookingAcknowledgement($email, $firstName) ? 'sent' : 'failed';
                }
            }
            $db->prepare('UPDATE intakes SET booking_email_status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?')
                ->execute([$emailStatus, $bookingId]);

            if ($this->shouldSendBookingSms($mobile, $isInternational)) {
                $loginUrl = trim((string)($_ENV['PATIENT_LOGIN_URL'] ?? 'https://app.drbastaninejad.com/Frontend/pages/auth/patient-login.html'));
                $template = trim((string)($_ENV['BOOKING_SMS_TEMPLATE'] ?? "درخواست نوبت شما دریافت شد؛ زمان نوبت هنوز قطعی نیست. همکاران کلینیک برای اعلام و تأیید زمان تماس می‌گیرند. پنل بیمار: {login_url}"));
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
        }

        return $this->success(array_merge([
            'booking_id' => $bookingId,
            'patient_id' => $patientId,
            'status' => 'pending',
            'sms_status' => $smsStatus,
            'sheet_status' => $sheetStatus,
            'email_status' => $emailStatus,
            'idempotent' => false,
            'cooldown_minutes' => $cooldown,
        ], $this->patientSessionPayload($mobile)), 201);
    }

    /** @return array{patient_session_token:string,patient_session_expires_at:string} */
    private function patientSessionPayload(string $mobile): array
    {
        try {
            $session = (new OtpService())->issueToken($mobile, 'patient', 'session');
            return [
                'patient_session_token' => (string)($session['token'] ?? ''),
                'patient_session_expires_at' => (string)($session['expires_at'] ?? ''),
            ];
        } catch (\Throwable $e) {
            // The clinical intake is already committed; login bootstrap failure must
            // not make the browser retry and create a duplicate booking request.
            error_log('[BookingController] patient session bootstrap failed: ' . $e->getMessage());
            return ['patient_session_token' => '', 'patient_session_expires_at' => ''];
        }
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
                    SUM(sms_status = "failed") AS sms_failed,
                    SUM(booking_sheet_status = "submitted") AS sheet_submitted,
                    SUM(booking_sheet_status IN ("failed_confirmed","outcome_unknown")) AS sheet_attention,
                    SUM(booking_email_status = "sent") AS email_sent,
                    SUM(booking_email_status = "failed") AS email_failed
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
            'sheet_submitted' => (int)($r['sheet_submitted'] ?? 0),
            'sheet_attention' => (int)($r['sheet_attention'] ?? 0),
            'email_sent' => (int)($r['email_sent'] ?? 0),
            'email_failed' => (int)($r['email_failed'] ?? 0),
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
