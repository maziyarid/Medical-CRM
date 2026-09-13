<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Appointment;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;

/**
 * Transactional booking state machine.
 *
 * Financial state and clinic confirmation are intentionally independent:
 * verified payment creates a scheduled appointment and marks the intake paid,
 * while clinic_confirmation_status remains pending until staff explicitly
 * confirms the appointment after their follow-up call.
 */
final class BookingWorkflowService
{
    public function __construct(
        private ?BookingSlotService $slots = null,
        private ?PaymentGatewayService $payments = null,
        private ?BookingVerificationService $verification = null,
        private ?AppointmentService $appointments = null,
    ) {
        $this->slots ??= new BookingSlotService();
        $this->payments ??= new PaymentGatewayService();
        $this->verification ??= new BookingVerificationService();
        $this->appointments ??= new AppointmentService();
    }

    public function paymentEnabled(): bool
    {
        return ($_ENV['BOOKING_PAYMENT_ENABLED'] ?? '0') === '1'
            && $this->bookingFeeRial() > 0
            && ($this->payments->isConfigured(PaymentGatewayService::ZARINPAL)
                || $this->payments->isConfigured(PaymentGatewayService::VANDAR));
    }

    public function bookingFeeRial(): int
    {
        return max(0, (int)($_ENV['BOOKING_FEE_RIAL'] ?? 0));
    }

    /** @return list<string> */
    public function configuredGateways(): array
    {
        $out = [];
        foreach ([PaymentGatewayService::ZARINPAL, PaymentGatewayService::VANDAR] as $gateway) {
            if ($this->payments->isConfigured($gateway)) {
                $out[] = $gateway;
            }
        }
        return $out;
    }

    /**
     * Start a paid public booking after OTP verification and slot selection.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function startOnline(array $data): array
    {
        if (!$this->paymentEnabled()) {
            return $this->error(503, 'پرداخت آنلاین نوبت هنوز فعال نشده است.', 'payment_not_configured');
        }
        $gateway = strtolower(trim((string)($data['gateway'] ?? '')));
        if (!in_array($gateway, $this->configuredGateways(), true)) {
            return $this->error(422, 'درگاه پرداخت انتخاب‌شده در دسترس نیست.', 'gateway_unavailable');
        }
        $slotStart = trim((string)($data['slot_start'] ?? ''));
        if (!$this->validUtcSqlDate($slotStart)) {
            return $this->error(422, 'زمان نوبت معتبر نیست.', 'invalid_slot');
        }
        $submissionUuid = trim((string)($data['submission_uuid'] ?? ''));
        if ($submissionUuid === '' || strlen($submissionUuid) > 64) {
            return $this->error(422, 'شناسه ثبت درخواست معتبر نیست.', 'invalid_submission_uuid');
        }
        $verificationToken = trim((string)($data['verification_token'] ?? ''));
        $clinicId = (int)($data['clinic_id'] ?? ($_ENV['DEFAULT_CLINIC_ID'] ?? 1));
        $amount = $this->bookingFeeRial();

        try {
            $created = $this->appointments->withClinicLock($clinicId, function (PDO $db) use (
                $data, $gateway, $slotStart, $submissionUuid, $verificationToken, $clinicId, $amount
            ): array {
                $duplicate = $db->prepare(
                    "SELECT i.id, i.payment_id, p.status AS payment_status, p.redirect_url, p.gateway
                     FROM intakes i
                     LEFT JOIN booking_payments p ON p.id = i.payment_id
                     WHERE i.submission_uuid = ? LIMIT 1 FOR UPDATE"
                );
                $duplicate->execute([$submissionUuid]);
                if ($row = $duplicate->fetch()) {
                    return [
                        'idempotent' => true,
                        'booking_id' => (int)$row['id'],
                        'payment_id' => $row['payment_id'] !== null ? (int)$row['payment_id'] : null,
                        'payment_status' => $row['payment_status'] ?? null,
                        'payment_url' => $row['redirect_url'] ?? null,
                        'gateway' => $row['gateway'] ?? null,
                    ];
                }

                $mobile = trim((string)$data['mobile']);
                if (!$this->verification->consume($db, $mobile, $verificationToken)) {
                    throw new RuntimeException('booking verification invalid');
                }
                $patient = $this->upsertPatient($db, $clinicId, $data);
                $intakeId = $this->insertScheduledIntake($db, $clinicId, $patient, $data, $gateway, $slotStart);
                $hold = $this->slots->reserve($db, $clinicId, $intakeId, $mobile, $slotStart);

                $paymentUuid = $this->uuidV4();
                $callbackNonce = bin2hex(random_bytes(24));
                $nonceHash = hash('sha256', $callbackNonce);
                $insert = $db->prepare(
                    "INSERT INTO booking_payments
                     (uuid, clinic_id, intake_id, slot_hold_id, gateway, amount_rial, status,
                      callback_nonce_hash, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, 'initiated', ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())"
                );
                $insert->execute([$paymentUuid, $clinicId, $intakeId, $hold['hold_id'], $gateway, $amount, $nonceHash]);
                $paymentId = (int)$db->lastInsertId();
                $db->prepare(
                    "UPDATE intakes SET payment_id = ?, slot_duration_minutes = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?"
                )->execute([$paymentId, $hold['duration_minutes'], $intakeId]);
                $this->event($db, $clinicId, $intakeId, $paymentId, 'payment_initiated', 'ok', null, [
                    'gateway' => $gateway,
                    'slot_start' => $slotStart,
                    'hold_expires_at' => $hold['expires_at'],
                ]);
                return [
                    'idempotent' => false,
                    'booking_id' => $intakeId,
                    'payment_id' => $paymentId,
                    'gateway' => $gateway,
                    'callback_nonce' => $callbackNonce,
                    'hold_id' => $hold['hold_id'],
                    'hold_expires_at' => $hold['expires_at'],
                    'patient_id' => $patient['id'],
                ];
            });
        } catch (RuntimeException $e) {
            return match ($e->getMessage()) {
                'booking verification invalid' => $this->error(401, 'تأیید شماره همراه منقضی یا استفاده شده است. کد جدید دریافت کنید.', 'verification_expired'),
                'slot unavailable' => $this->error(409, 'این زمان دیگر آزاد نیست. زمان دیگری انتخاب کنید.', 'slot_unavailable'),
                'day full' => $this->error(409, 'ظرفیت این روز تکمیل شده است.', 'day_full'),
                'invalid slot' => $this->error(422, 'زمان نوبت معتبر نیست.', 'invalid_slot'),
                'clinic lock timeout' => $this->error(503, 'تقویم در حال به‌روزرسانی است؛ دوباره تلاش کنید.', 'calendar_busy'),
                default => throw $e,
            };
        }

        if (!empty($created['idempotent'])) {
            return [
                'ok' => true,
                'status' => 200,
                'data' => $created,
            ];
        }

        $callback = $this->callbackUrl(
            (string)$created['gateway'],
            (int)$created['payment_id'],
            (string)$created['callback_nonce']
        );
        $description = 'هزینه رزرو نوبت کلینیک باستانی نژاد - ' . (int)$created['booking_id'];
        $gatewayResult = $this->payments->create(
            (string)$created['gateway'],
            $amount,
            $callback,
            trim((string)$data['mobile']),
            trim((string)($data['email'] ?? '')),
            $description
        );

        $db = Database::conn();
        if (!$gatewayResult['ok']) {
            $status = $gatewayResult['retryable'] ? 'outcome_unknown' : 'failed';
            $db->beginTransaction();
            try {
                $db->prepare(
                    "UPDATE booking_payments SET status = ?, gateway_payload = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?"
                )->execute([$status, $this->json($gatewayResult['payload']), (int)$created['payment_id']]);
                if (!$gatewayResult['retryable']) {
                    $this->slots->release($db, (int)$created['hold_id']);
                }
                $this->event($db, $clinicId, (int)$created['booking_id'], (int)$created['payment_id'],
                    'payment_request_failed', $gatewayResult['retryable'] ? 'unknown' : 'failed',
                    (string)$gatewayResult['error'], []);
                $db->commit();
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }
            return $this->error(
                $gatewayResult['retryable'] ? 503 : 502,
                $gatewayResult['retryable']
                    ? 'وضعیت اتصال به درگاه نامشخص است. پذیرش نتیجه را بررسی می‌کند.'
                    : 'ایجاد پرداخت انجام نشد. دوباره تلاش کنید.',
                $gatewayResult['retryable'] ? 'payment_outcome_unknown' : 'payment_request_failed'
            );
        }

        $db->prepare(
            "UPDATE booking_payments
             SET status = 'pending', gateway_authority = ?, redirect_url = ?, gateway_payload = ?, updated_at = UTC_TIMESTAMP()
             WHERE id = ?"
        )->execute([
            $gatewayResult['authority'],
            $gatewayResult['redirect_url'],
            $this->json($gatewayResult['payload']),
            (int)$created['payment_id'],
        ]);

        return [
            'ok' => true,
            'status' => 201,
            'data' => [
                'booking_id' => (int)$created['booking_id'],
                'payment_id' => (int)$created['payment_id'],
                'payment_status' => 'pending',
                'gateway' => (string)$created['gateway'],
                'payment_url' => $gatewayResult['redirect_url'],
                'hold_expires_at' => (string)$created['hold_expires_at'],
                'idempotent' => false,
            ],
        ];
    }

    /**
     * Verify a gateway callback. Callback query state is merely a precondition;
     * payment truth comes from the gateway verify API.
     *
     * @return array<string,mixed>
     */
    public function verifyOnline(int $paymentId, string $gateway, string $nonce, string $authority, string $callbackStatus): array
    {
        $db = Database::conn();
        $stmt = $db->prepare('SELECT * FROM booking_payments WHERE id = ? LIMIT 1');
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        if (!$payment || !hash_equals((string)$payment['gateway'], $gateway)) {
            return $this->error(404, 'پرداخت یافت نشد.', 'payment_not_found');
        }
        if (!hash_equals((string)$payment['callback_nonce_hash'], hash('sha256', $nonce))) {
            return $this->error(401, 'اعتبار بازگشت پرداخت نامعتبر است.', 'invalid_callback');
        }
        if ((string)$payment['status'] === 'paid') {
            return $this->paymentSuccessData($db, $payment, true);
        }
        if ((string)$payment['gateway_authority'] === '' || !hash_equals((string)$payment['gateway_authority'], $authority)) {
            return $this->error(409, 'شناسه تراکنش با درخواست ثبت‌شده مطابقت ندارد.', 'authority_mismatch');
        }

        $statusUpper = strtoupper(trim($callbackStatus));
        $callbackOk = $gateway === PaymentGatewayService::ZARINPAL
            ? $statusUpper === 'OK'
            : in_array($statusUpper, ['OK', 'SUCCESS', 'SUCCEED'], true);
        if (!$callbackOk) {
            $this->markPaymentFailed($payment, 'cancelled', 'gateway_callback_cancelled');
            return $this->error(402, 'پرداخت تکمیل نشد یا توسط کاربر لغو شد.', 'payment_cancelled');
        }

        $verified = $this->payments->verify($gateway, $authority, (int)$payment['amount_rial']);
        if (!$verified['ok']) {
            if ($verified['retryable']) {
                $db->prepare("UPDATE booking_payments SET status = 'outcome_unknown', gateway_payload = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?")
                    ->execute([$this->json($verified['payload']), $paymentId]);
                $this->event($db, (int)$payment['clinic_id'], (int)$payment['intake_id'], $paymentId,
                    'payment_verify_unknown', 'unknown', (string)$verified['error'], []);
                return $this->error(503, 'تأیید بانکی هنوز قطعی نیست. پرداخت شما در حال بررسی است.', 'payment_verify_unknown');
            }
            $this->markPaymentFailed($payment, 'failed', (string)$verified['error']);
            return $this->error(402, 'پرداخت توسط درگاه تأیید نشد.', 'payment_failed');
        }

        return $this->finaliseVerifiedPayment($paymentId, $verified);
    }

    /**
     * Receptionist booking, usually free of charge. It occupies quota
     * immediately and logs the authenticated staff user as receptionist.
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed> $staff
     * @return array<string,mixed>
     */
    public function createAdminFree(array $data, array $staff): array
    {
        $clinicId = (int)($staff['clinic_id'] ?? ($_ENV['DEFAULT_CLINIC_ID'] ?? 1));
        $mobile = trim((string)($data['mobile'] ?? ''));
        $fullName = trim((string)($data['full_name'] ?? ''));
        $slotStart = trim((string)($data['slot_start'] ?? ''));
        if (!preg_match('/^09\d{9}$/', $mobile) || mb_strlen($fullName) < 2 || !$this->validUtcSqlDate($slotStart)) {
            return $this->error(422, 'نام، شماره همراه و زمان معتبر الزامی است.', 'invalid_admin_booking');
        }
        [$first, $last] = $this->splitName($fullName);
        $staffId = (int)($staff['id'] ?? 0);
        if ($staffId < 1) {
            return $this->error(401, 'کاربر پذیرش معتبر نیست.', 'invalid_receptionist');
        }

        try {
            $created = $this->appointments->withClinicLock($clinicId, function (PDO $db) use (
                $clinicId, $mobile, $first, $last, $slotStart, $staffId, $data
            ): array {
                $patient = $this->upsertPatient($db, $clinicId, [
                    'mobile' => $mobile,
                    'first_name' => $first,
                    'last_name' => $last,
                    'email' => trim((string)($data['email'] ?? '')),
                    'national_id' => '',
                    'birth_date' => null,
                    'birth_date_jalali' => '',
                ]);
                $uuid = $this->uuidV4();
                $raw = $this->json(['admin_note' => trim((string)($data['notes'] ?? ''))]);
                $stmt = $db->prepare(
                    "INSERT INTO intakes
                     (submission_uuid, clinic_id, patient_id, patient_uuid, source_type, booking_flow, booking_source,
                      paid_status, payment_gateway, first_name, last_name, mobile, national_id, chief_complaint,
                      email, visit_reason, raw_payload, status, sheets_sync_status, booking_sheet_status,
                      booking_ledger_status, booking_email_status, sms_status, slot_start, receptionist_user_id,
                      clinic_confirmation_status, clinic_confirmed_by, clinic_confirmed_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 'booking', 'scheduled_payment', 'admin', 'free', 'admin_free', ?, ?, ?, NULL,
                             ?, NULLIF(?, ''), ?, ?, 'pending', 'pending', 'pending', 'pending', 'pending', 'pending', ?, ?,
                             'confirmed', ?, UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP())"
                );
                $reason = trim((string)($data['visit_reason'] ?? 'رزرو پذیرش')) ?: 'رزرو پذیرش';
                $stmt->execute([
                    $uuid, $clinicId, $patient['id'], $patient['uuid'], $first, $last, $mobile,
                    $reason, trim((string)($data['email'] ?? '')), $reason, $raw, $slotStart, $staffId, $staffId,
                ]);
                $intakeId = (int)$db->lastInsertId();
                $hold = $this->slots->reserve($db, $clinicId, $intakeId, $mobile, $slotStart);
                $this->slots->consume($db, $hold['hold_id']);

                $paymentUuid = $this->uuidV4();
                $db->prepare(
                    "INSERT INTO booking_payments
                     (uuid, clinic_id, intake_id, slot_hold_id, gateway, amount_rial, status, callback_nonce_hash,
                      verified_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 'admin_free', 0, 'free', ?, UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP())"
                )->execute([$paymentUuid, $clinicId, $intakeId, $hold['hold_id'], hash('sha256', random_bytes(24))]);
                $paymentId = (int)$db->lastInsertId();

                $appointmentId = $this->createAppointmentRow(
                    $clinicId,
                    (int)$patient['id'],
                    $intakeId,
                    $slotStart,
                    (int)$hold['duration_minutes'],
                    'confirmed',
                    'staff',
                    $reason,
                    trim((string)($data['notes'] ?? ''))
                );
                $db->prepare('UPDATE appointment_open_days SET booked_count = booked_count + 1, updated_at = UTC_TIMESTAMP() WHERE id = ?')
                    ->execute([(int)$hold['open_day_id']]);
                $db->prepare(
                    'UPDATE intakes SET payment_id = ?, appointment_id = ?, slot_duration_minutes = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?'
                )->execute([$paymentId, $appointmentId, (int)$hold['duration_minutes'], $intakeId]);
                $this->event($db, $clinicId, $intakeId, $paymentId, 'admin_free_booking_created', 'ok', null, [
                    'appointment_id' => $appointmentId,
                    'receptionist_user_id' => $staffId,
                ]);
                return [
                    'booking_id' => $intakeId,
                    'appointment_id' => $appointmentId,
                    'payment_id' => $paymentId,
                    'paid_status' => 'free',
                    'slot_start' => $slotStart,
                ];
            });
        } catch (RuntimeException $e) {
            return match ($e->getMessage()) {
                'slot unavailable' => $this->error(409, 'این زمان دیگر آزاد نیست.', 'slot_unavailable'),
                'day full' => $this->error(409, 'ظرفیت این روز تکمیل شده است.', 'day_full'),
                'clinic lock timeout' => $this->error(503, 'تقویم در حال به‌روزرسانی است.', 'calendar_busy'),
                default => throw $e,
            };
        }
        $this->afterAppointmentCreated((int)$created['appointment_id'], (int)$created['booking_id']);
        return ['ok' => true, 'status' => 201, 'data' => $created];
    }

    /** @return array<string,mixed> */
    public function confirmByStaff(int $bookingId, array $staff): array
    {
        $clinicId = (int)($staff['clinic_id'] ?? 1);
        $staffId = (int)($staff['id'] ?? 0);
        $db = Database::conn();
        $stmt = $db->prepare('SELECT id, appointment_id, clinic_confirmation_status FROM intakes WHERE id = ? AND clinic_id = ? AND source_type = \'booking\' LIMIT 1');
        $stmt->execute([$bookingId, $clinicId]);
        $row = $stmt->fetch();
        if (!$row || !$row['appointment_id']) {
            return $this->error(404, 'رزرو قابل تأیید یافت نشد.', 'booking_not_found');
        }
        $appointment = (new Appointment())->find((int)$row['appointment_id']);
        if (!$appointment || (int)$appointment['clinic_id'] !== $clinicId) {
            return $this->error(404, 'نوبت یافت نشد.', 'appointment_not_found');
        }
        $result = $this->appointments->updateStatusLocked($clinicId, $appointment, 'confirmed');
        if (!$result['ok']) {
            return ['ok' => false, 'status' => $result['status'], 'code' => 'appointment_conflict', 'message' => $result['message']];
        }
        $db->prepare(
            "UPDATE intakes SET clinic_confirmation_status = 'confirmed', clinic_confirmed_by = ?, clinic_confirmed_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = ?"
        )->execute([$staffId, $bookingId]);
        $this->event($db, $clinicId, $bookingId, null, 'clinic_confirmed', 'ok', null, ['staff_id' => $staffId]);
        $this->afterAppointmentCreated((int)$row['appointment_id'], $bookingId);
        return ['ok' => true, 'status' => 200, 'data' => ['booking_id' => $bookingId, 'confirmation_status' => 'confirmed']];
    }

    /** @param array<string,mixed> $verified */
    private function finaliseVerifiedPayment(int $paymentId, array $verified): array
    {
        $db = Database::conn();
        $lookup = $db->prepare('SELECT clinic_id FROM booking_payments WHERE id = ? LIMIT 1');
        $lookup->execute([$paymentId]);
        $clinicId = (int)$lookup->fetchColumn();
        if ($clinicId < 1) {
            return $this->error(404, 'پرداخت یافت نشد.', 'payment_not_found');
        }

        $result = $this->appointments->withClinicLock($clinicId, function (PDO $db) use ($paymentId, $clinicId, $verified): array {
            $stmt = $db->prepare('SELECT * FROM booking_payments WHERE id = ? LIMIT 1 FOR UPDATE');
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();
            if (!$payment) {
                throw new RuntimeException('payment not found');
            }
            if ((string)$payment['status'] === 'paid') {
                return ['already_paid' => true, 'intake_id' => (int)$payment['intake_id']];
            }
            $intakeStmt = $db->prepare('SELECT * FROM intakes WHERE id = ? LIMIT 1 FOR UPDATE');
            $intakeStmt->execute([(int)$payment['intake_id']]);
            $intake = $intakeStmt->fetch();
            if (!$intake) {
                throw new RuntimeException('booking not found');
            }
            $holdStmt = $db->prepare('SELECT * FROM appointment_slot_holds WHERE id = ? LIMIT 1 FOR UPDATE');
            $holdStmt->execute([(int)$payment['slot_hold_id']]);
            $hold = $holdStmt->fetch();
            if (!$hold) {
                throw new RuntimeException('hold not found');
            }

            // Remove our own hold from conflict queries while clinic-wide lock is held.
            $this->slots->consume($db, (int)$hold['id']);
            $duration = max(5, (int)($intake['slot_duration_minutes'] ?? 20));
            $slotConflict = $this->slots->hasConflict($db, $clinicId, (string)$intake['slot_start'], $duration);

            $dayStmt = $db->prepare('SELECT id, capacity, booked_count FROM appointment_open_days WHERE id = ? LIMIT 1 FOR UPDATE');
            $dayStmt->execute([(int)$hold['open_day_id']]);
            $day = $dayStmt->fetch();
            $dayFull = !$day || (int)$day['booked_count'] >= (int)$day['capacity'];

            $db->prepare(
                "UPDATE booking_payments SET status = 'paid', reference_id = ?, gateway_payload = ?, verified_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = ?"
            )->execute([
                $verified['reference_id'],
                $this->json($verified['payload']),
                $paymentId,
            ]);
            $db->prepare(
                "UPDATE intakes SET paid_status = 'paid', payment_gateway = ?, payment_id = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?"
            )->execute([(string)$payment['gateway'], $paymentId, (int)$intake['id']]);

            $appointmentId = null;
            $needsAction = $slotConflict || $dayFull;
            if (!$needsAction) {
                $appointmentId = $this->createAppointmentRow(
                    $clinicId,
                    (int)$intake['patient_id'],
                    (int)$intake['id'],
                    (string)$intake['slot_start'],
                    $duration,
                    'scheduled',
                    'online',
                    (string)($intake['visit_reason'] ?? 'رزرو آنلاین'),
                    null
                );
                $db->prepare('UPDATE appointment_open_days SET booked_count = booked_count + 1, updated_at = UTC_TIMESTAMP() WHERE id = ?')
                    ->execute([(int)$day['id']]);
                $db->prepare('UPDATE intakes SET appointment_id = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?')
                    ->execute([$appointmentId, (int)$intake['id']]);
            }
            $this->event(
                $db,
                $clinicId,
                (int)$intake['id'],
                $paymentId,
                $needsAction ? 'payment_paid_slot_conflict' : 'payment_verified',
                $needsAction ? 'attention' : 'ok',
                $needsAction ? 'Payment verified but selected slot could not be committed; staff action required.' : null,
                ['appointment_id' => $appointmentId, 'reference_id' => $verified['reference_id']]
            );
            return [
                'already_paid' => false,
                'intake_id' => (int)$intake['id'],
                'appointment_id' => $appointmentId,
                'needs_staff_action' => $needsAction,
                'reference_id' => $verified['reference_id'],
            ];
        });

        if (!empty($result['already_paid'])) {
            $stmt = $db->prepare('SELECT * FROM booking_payments WHERE id = ? LIMIT 1');
            $stmt->execute([$paymentId]);
            return $this->paymentSuccessData($db, $stmt->fetch(), true);
        }
        if (!empty($result['appointment_id'])) {
            $this->afterAppointmentCreated((int)$result['appointment_id'], (int)$result['intake_id']);
        }
        $this->notifyPaymentSuccess((int)$result['intake_id']);
        return [
            'ok' => true,
            'status' => 200,
            'data' => [
                'booking_id' => (int)$result['intake_id'],
                'appointment_id' => $result['appointment_id'],
                'payment_status' => 'paid',
                'reference_id' => $result['reference_id'],
                'clinic_confirmation_status' => 'pending',
                'needs_staff_action' => (bool)$result['needs_staff_action'],
                'message' => $result['needs_staff_action']
                    ? 'پرداخت تأیید شد. پذیرش برای تعیین زمان جایگزین با شما تماس می‌گیرد.'
                    : 'پرداخت تأیید شد و زمان انتخابی رزرو شد. پذیرش برای تأیید نهایی با شما تماس می‌گیرد.',
            ],
        ];
    }

    private function markPaymentFailed(array $payment, string $status, string $reason): void
    {
        $db = Database::conn();
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE booking_payments SET status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ? AND status <> \'paid\'')
                ->execute([$status, (int)$payment['id']]);
            if (!empty($payment['slot_hold_id'])) {
                $this->slots->release($db, (int)$payment['slot_hold_id']);
            }
            $this->event($db, (int)$payment['clinic_id'], (int)$payment['intake_id'], (int)$payment['id'],
                'payment_not_completed', 'failed', $reason, []);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** @param array<string,mixed> $data @return array{id:int,uuid:string} */
    private function upsertPatient(PDO $db, int $clinicId, array $data): array
    {
        $mobile = trim((string)$data['mobile']);
        $stmt = $db->prepare('SELECT id, uuid, email FROM patients WHERE mobile = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE');
        $stmt->execute([$mobile]);
        $patient = $stmt->fetch();
        $email = trim((string)($data['email'] ?? ''));
        $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        if ($patient) {
            $db->prepare(
                "UPDATE patients SET clinic_id = ?, first_name = COALESCE(NULLIF(?, ''), first_name),
                 last_name = COALESCE(NULLIF(?, ''), last_name), email = COALESCE(?, email),
                 national_id = COALESCE(NULLIF(?, ''), national_id), birth_date = COALESCE(?, birth_date),
                 birth_date_jalali = COALESCE(NULLIF(?, ''), birth_date_jalali), updated_at = UTC_TIMESTAMP() WHERE id = ?"
            )->execute([
                $clinicId,
                trim((string)($data['first_name'] ?? '')),
                trim((string)($data['last_name'] ?? '')),
                $email,
                trim((string)($data['national_id'] ?? '')),
                $data['birth_date'] ?? null,
                trim((string)($data['birth_date_jalali'] ?? '')),
                (int)$patient['id'],
            ]);
            return ['id' => (int)$patient['id'], 'uuid' => (string)$patient['uuid']];
        }
        $uuid = bin2hex(random_bytes(16));
        $db->prepare(
            "INSERT INTO patients
             (uuid, clinic_id, first_name, last_name, mobile, email, national_id, birth_date, birth_date_jalali,
              insurance_status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ''), ?, NULLIF(?, ''), 'pending', UTC_TIMESTAMP(), UTC_TIMESTAMP())"
        )->execute([
            $uuid, $clinicId,
            trim((string)($data['first_name'] ?? '')),
            trim((string)($data['last_name'] ?? '')),
            $mobile, $email,
            trim((string)($data['national_id'] ?? '')),
            $data['birth_date'] ?? null,
            trim((string)($data['birth_date_jalali'] ?? '')),
        ]);
        return ['id' => (int)$db->lastInsertId(), 'uuid' => $uuid];
    }

    /** @param array{id:int,uuid:string} $patient @param array<string,mixed> $data */
    private function insertScheduledIntake(PDO $db, int $clinicId, array $patient, array $data, string $gateway, string $slotStart): int
    {
        $stmt = $db->prepare(
            "INSERT INTO intakes
             (submission_uuid, clinic_id, patient_id, patient_uuid, source_type, booking_flow, booking_source,
              paid_status, payment_gateway, first_name, last_name, mobile, national_id, birth_date, birth_date_jalali,
              chief_complaint, service_type, email, visit_reason, doctor_request, raw_payload, status,
              sheets_sync_status, booking_sheet_status, booking_ledger_status, booking_email_status, sms_status,
              slot_start, clinic_confirmation_status, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'booking', 'scheduled_payment', 'online', 'unpaid', ?, ?, ?, ?, NULLIF(?, ''), ?,
                     NULLIF(?, ''), ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?, 'pending',
                     'pending', 'pending', 'pending', 'pending', 'pending', ?, 'pending', UTC_TIMESTAMP(), UTC_TIMESTAMP())"
        );
        $medical = trim((string)($data['medical_history'] ?? ''));
        $reason = trim((string)($data['procedure'] ?? ''));
        $stmt->execute([
            trim((string)$data['submission_uuid']), $clinicId, $patient['id'], $patient['uuid'], $gateway,
            trim((string)$data['first_name']), trim((string)$data['last_name']), trim((string)$data['mobile']),
            trim((string)($data['national_id'] ?? '')), $data['birth_date'] ?? null,
            trim((string)($data['birth_date_jalali'] ?? '')),
            $medical !== '' ? $medical : 'درخواست نوبت', $reason, trim((string)($data['email'] ?? '')), $reason,
            trim((string)($data['doctor_request'] ?? '')), $this->json($data['raw_payload'] ?? []), $slotStart,
        ]);
        return (int)$db->lastInsertId();
    }

    private function createAppointmentRow(
        int $clinicId,
        int $patientId,
        int $intakeId,
        string $slotStart,
        int $duration,
        string $status,
        string $source,
        string $reason,
        ?string $notes
    ): int {
        $providerId = (int)($_ENV['BOOKING_PROVIDER_ID'] ?? 0);
        return (new Appointment())->create([
            'uuid' => bin2hex(random_bytes(16)),
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'booking_intake_id' => $intakeId,
            'source_type' => $source,
            'provider_id' => $providerId > 0 ? $providerId : null,
            'scheduled_at' => $slotStart,
            'duration_minutes' => $duration,
            'visit_reason' => $reason !== '' ? $reason : 'رزرو نوبت',
            'room' => null,
            'status' => $status,
            'notes' => $notes,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    private function callbackUrl(string $gateway, int $paymentId, string $nonce): string
    {
        $base = trim((string)($_ENV['BOOKING_PAYMENT_CALLBACK_URL'] ?? 'https://www.drbastaninejad.com/'));
        $separator = str_contains($base, '?') ? '&' : '?';
        return $base . $separator . http_build_query([
            'drb_booking_payment_callback' => '1',
            'gateway' => $gateway,
            'payment_id' => $paymentId,
            'nonce' => $nonce,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function afterAppointmentCreated(int $appointmentId, int $bookingId): void
    {
        try {
            (new GoogleCalendarService())->pushAppointment($appointmentId);
        } catch (\Throwable $e) {
            error_log('[BookingWorkflow] Calendar push failed for appointment ' . $appointmentId . ': ' . $e->getMessage());
        }
        try {
            (new BookingLedgerService())->syncBooking($bookingId);
        } catch (\Throwable $e) {
            error_log('[BookingWorkflow] Booking ledger sync failed for booking ' . $bookingId . ': ' . $e->getMessage());
        }
    }

    private function notifyPaymentSuccess(int $bookingId): void
    {
        try {
            $db = Database::conn();
            $stmt = $db->prepare('SELECT mobile, first_name, email FROM intakes WHERE id = ? LIMIT 1');
            $stmt->execute([$bookingId]);
            $row = $stmt->fetch();
            if (!$row) {
                return;
            }
            if (preg_match('/^09\d{9}$/', (string)$row['mobile'])) {
                $message = 'پرداخت نوبت شما تأیید شد و زمان انتخابی ثبت شد. پذیرش کلینیک برای تأیید نهایی با شما تماس می‌گیرد.';
                $sms = (new SmsProviderChain())->sendMessage((string)$row['mobile'], $message);
                if (empty($sms['ok'])) {
                    error_log('[BookingWorkflow] payment success SMS failed for booking ' . $bookingId);
                }
            }
            $email = trim((string)($row['email'] ?? ''));
            $emailService = new EmailService();
            if ($email !== '' && $emailService->isConfigured()) {
                $emailService->sendBookingAcknowledgement($email, (string)$row['first_name']);
            }
        } catch (\Throwable $e) {
            error_log('[BookingWorkflow] post-payment notification failed for booking ' . $bookingId . ': ' . $e->getMessage());
        }
    }

    private function paymentSuccessData(PDO $db, array $payment, bool $idempotent): array
    {
        $stmt = $db->prepare('SELECT appointment_id, clinic_confirmation_status FROM intakes WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$payment['intake_id']]);
        $intake = $stmt->fetch() ?: [];
        return [
            'ok' => true,
            'status' => 200,
            'data' => [
                'booking_id' => (int)$payment['intake_id'],
                'appointment_id' => isset($intake['appointment_id']) ? (int)$intake['appointment_id'] : null,
                'payment_status' => 'paid',
                'reference_id' => $payment['reference_id'] ?? null,
                'clinic_confirmation_status' => $intake['clinic_confirmation_status'] ?? 'pending',
                'idempotent' => $idempotent,
            ],
        ];
    }

    private function event(
        PDO $db,
        int $clinicId,
        ?int $intakeId,
        ?int $paymentId,
        string $type,
        string $status,
        ?string $message,
        array $context
    ): void {
        $db->prepare(
            'INSERT INTO booking_event_log (clinic_id, intake_id, payment_id, event_type, event_status, message, context_json, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())'
        )->execute([
            $clinicId, $intakeId, $paymentId, mb_substr($type, 0, 80), mb_substr($status, 0, 32),
            $message !== null ? mb_substr($message, 0, 500) : null,
            $context ? $this->json($context) : null,
        ]);
    }

    private function validUtcSqlDate(string $value): bool
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('UTC'));
        return $d !== false && $d->format('Y-m-d H:i:s') === $value;
    }

    /** @return array{0:string,1:string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/u', trim($name), 2) ?: [];
        return [trim((string)($parts[0] ?? $name)), trim((string)($parts[1] ?? ''))];
    }

    private function uuidV4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        $h = bin2hex($b);
        return substr($h, 0, 8) . '-' . substr($h, 8, 4) . '-' . substr($h, 12, 4) . '-' . substr($h, 16, 4) . '-' . substr($h, 20);
    }

    private function json(mixed $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? '{}' : $json;
    }

    /** @return array{ok:false,status:int,code:string,message:string} */
    private function error(int $status, string $message, string $code): array
    {
        return ['ok' => false, 'status' => $status, 'code' => $code, 'message' => $message];
    }
}
