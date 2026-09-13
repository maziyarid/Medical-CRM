<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\BookingSlotService;
use App\Services\BookingVerificationService;
use App\Services\BookingWorkflowService;
use App\Validators\ValidatorService;

/** Public scheduled-booking API, reachable only through the WordPress bridge. */
final class ScheduledBookingController extends Controller
{
    public function config(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $workflow = new BookingWorkflowService();
        return $this->success([
            'payment_enabled' => $workflow->paymentEnabled(),
            'fee_rial' => $workflow->bookingFeeRial(),
            'gateways' => $workflow->configuredGateways(),
            'hold_minutes' => max(5, min(45, (int)($_ENV['BOOKING_HOLD_MINUTES'] ?? 20))),
            'timezone' => 'Asia/Tehran',
        ]);
    }

    public function availability(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        $token = trim((string)($req->body['verification_token'] ?? ''));
        if (!ValidatorService::isValidMobile($mobile) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return $this->validationError([['field' => 'verification_token', 'message' => 'تأیید شماره همراه معتبر نیست']]);
        }
        if (!(new BookingVerificationService())->isValid($mobile, $token)) {
            return $this->error('تأیید شماره همراه منقضی یا استفاده شده است. کد جدید دریافت کنید.', 401);
        }
        $workflow = new BookingWorkflowService();
        if (!$workflow->paymentEnabled()) {
            return $this->success([
                'payment_enabled' => false,
                'slots' => [],
                'gateways' => [],
                'fee_rial' => 0,
            ]);
        }
        $clinicId = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
        $slots = (new BookingSlotService())->available(
            $clinicId,
            isset($req->body['from']) ? (string)$req->body['from'] : null,
            isset($req->body['to']) ? (string)$req->body['to'] : null
        );
        return $this->success([
            'payment_enabled' => true,
            'slots' => $slots,
            'gateways' => $workflow->configuredGateways(),
            'fee_rial' => $workflow->bookingFeeRial(),
        ]);
    }

    public function start(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $mobile = ValidatorService::normalizeMobile(trim((string)($req->body['mobile'] ?? '')));
        $first = trim((string)($req->body['first_name'] ?? ''));
        $last = trim((string)($req->body['last_name'] ?? ''));
        $email = trim((string)($req->body['email'] ?? ''));
        $birthJalali = str_replace('-', '/', ValidatorService::normalizePersianDigits(trim((string)($req->body['birth_date_jalali'] ?? ''))));
        $nationalId = ValidatorService::normalizePersianDigits(trim((string)($req->body['national_id'] ?? '')));
        $medical = trim((string)($req->body['medical_history'] ?? ''));
        $medications = trim((string)($req->body['medications'] ?? ''));
        $doctorRequest = trim((string)($req->body['doctor_request'] ?? ''));
        $uuid = trim((string)($req->body['submission_uuid'] ?? ''));
        $otpToken = trim((string)($req->body['otp_token'] ?? ''));
        $slotStart = trim((string)($req->body['slot_start'] ?? ''));
        $gateway = strtolower(trim((string)($req->body['gateway'] ?? '')));
        $procedure = trim((string)($req->body['procedure'] ?? ''));

        $errors = [];
        if (mb_strlen($first) < 2 || mb_strlen($first) > 100) {
            $errors[] = ['field' => 'first_name', 'message' => 'نام معتبر الزامی است'];
        }
        if (mb_strlen($last) < 2 || mb_strlen($last) > 100) {
            $errors[] = ['field' => 'last_name', 'message' => 'نام خانوادگی معتبر الزامی است'];
        }
        if (!ValidatorService::isValidMobile($mobile)) {
            $errors[] = ['field' => 'mobile', 'message' => 'شماره موبایل معتبر نیست'];
        }
        if (!ValidatorService::isValidJalaliDate($birthJalali)) {
            $errors[] = ['field' => 'birth_date_jalali', 'message' => 'تاریخ تولد شمسی معتبر نیست'];
        }
        if (!ValidatorService::isValidNationalId($nationalId)) {
            $errors[] = ['field' => 'national_id', 'message' => 'کد ملی معتبر نیست'];
        }
        foreach (['medical_history' => $medical, 'medications' => $medications, 'doctor_request' => $doctorRequest] as $field => $value) {
            if (mb_strlen($value) < 2 || mb_strlen($value) > 2000) {
                $errors[] = ['field' => $field, 'message' => 'این فیلد باید بین ۲ تا ۲۰۰۰ کاراکتر باشد'];
            }
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['field' => 'email', 'message' => 'ایمیل معتبر نیست'];
        }
        if (!preg_match('/^[A-Za-z0-9._:-]{1,64}$/', $uuid)) {
            $errors[] = ['field' => 'submission_uuid', 'message' => 'شناسه درخواست معتبر نیست'];
        }
        if (!preg_match('/^[a-f0-9]{64}$/', $otpToken)) {
            $errors[] = ['field' => 'otp_token', 'message' => 'تأیید شماره همراه الزامی است'];
        }
        if ($slotStart === '') {
            $errors[] = ['field' => 'slot_start', 'message' => 'زمان نوبت را انتخاب کنید'];
        }
        if (!in_array($gateway, ['zarinpal', 'vandar'], true)) {
            $errors[] = ['field' => 'gateway', 'message' => 'درگاه پرداخت معتبر انتخاب کنید'];
        }
        if ($errors) {
            return $this->validationError($errors);
        }

        $birthDate = ValidatorService::jalaliToGregorian($birthJalali);
        $raw = $req->body;
        unset($raw['otp_token']);
        $result = (new BookingWorkflowService())->startOnline([
            'clinic_id' => (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1),
            'submission_uuid' => $uuid,
            'first_name' => $first,
            'last_name' => $last,
            'mobile' => $mobile,
            'email' => $email,
            'national_id' => $nationalId,
            'birth_date' => $birthDate,
            'birth_date_jalali' => $birthJalali,
            'medical_history' => $medical,
            'medications' => $medications,
            'doctor_request' => $doctorRequest,
            'procedure' => $procedure,
            'raw_payload' => $raw,
            'verification_token' => $otpToken,
            'slot_start' => $slotStart,
            'gateway' => $gateway,
        ]);
        return $this->fromWorkflow($result);
    }

    public function verifyPayment(Request $req): array
    {
        if (!$this->authorised($req)) {
            return $this->error('دسترسی پل رزرو معتبر نیست.', 401);
        }
        $paymentId = (int)($req->body['payment_id'] ?? 0);
        $gateway = strtolower(trim((string)($req->body['gateway'] ?? '')));
        $nonce = trim((string)($req->body['nonce'] ?? ''));
        $authority = trim((string)($req->body['authority'] ?? ''));
        $callbackStatus = trim((string)($req->body['callback_status'] ?? ''));
        if ($paymentId < 1 || !in_array($gateway, ['zarinpal', 'vandar'], true) || $nonce === '' || $authority === '') {
            return $this->validationError([['field' => null, 'message' => 'اطلاعات بازگشت پرداخت ناقص است']]);
        }
        return $this->fromWorkflow(
            (new BookingWorkflowService())->verifyOnline($paymentId, $gateway, $nonce, $authority, $callbackStatus)
        );
    }

    private function fromWorkflow(array $result): array
    {
        if (!empty($result['ok'])) {
            return $this->success((array)($result['data'] ?? []), (int)($result['status'] ?? 200));
        }
        return $this->error((string)($result['message'] ?? 'انجام عملیات ممکن نشد.'), (int)($result['status'] ?? 400));
    }

    private function authorised(Request $req): bool
    {
        $configured = trim((string)($_ENV['WORDPRESS_BRIDGE_SECRET'] ?? ''));
        if ($configured === '' || str_starts_with($configured, 'CHANGE_ME')) {
            return false;
        }
        $provided = trim((string)($req->headers['x-wordpress-bridge-secret'] ?? ''));
        return $provided !== '' && hash_equals($configured, $provided);
    }
}
