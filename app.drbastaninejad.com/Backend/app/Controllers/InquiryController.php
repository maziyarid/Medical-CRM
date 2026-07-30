<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InquiryModel;
use App\Services\ValidatorService;

/**
 * InquiryController — handles POST /api/v1/inquiries
 *
 * Public endpoint — no Bearer token required.
 * Backs the contact form on drbastaninejad.com/contact.html.
 *
 * Rules:
 *   - Fields: name (required), phone (required, Iranian mobile), message (required)
 *   - Rate limit: max 3 submissions per phone number per 30 minutes
 *   - IP address stored for audit only; never returned to client
 *   - No OTP, no national-ID, no medical data collected here
 *
 * Per docs/API_CONTRACT.md §POST /api/v1/inquiries (added this session)
 */
final class InquiryController extends Controller
{
    private InquiryModel     $model;
    private ValidatorService $validator;

    // Max submissions per phone per 30-minute window
    private const RATE_LIMIT      = 3;
    private const RATE_WINDOW_MIN = 30;

    public function __construct()
    {
        $this->model     = new InquiryModel();
        $this->validator = new ValidatorService();
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/inquiries
    // -------------------------------------------------------------------------

    public function store(): void
    {
        $body = $this->jsonBody();

        // Validate
        $errors = $this->validateInquiry($body);
        if ($errors !== []) {
            $this->validationError($errors);
        }

        $name    = trim((string)($body['name']    ?? ''));
        $phone   = $this->validator->normaliseMobile((string)($body['phone'] ?? ''));
        $message = trim((string)($body['message'] ?? ''));

        // Rate-limit check: per normalised phone number
        if ($this->model->countRecentByPhone($phone, self::RATE_WINDOW_MIN) >= self::RATE_LIMIT) {
            // Emit Retry-After header + retry_after in body per docs/API_CONTRACT.md §429
            $retryAfterSec = self::RATE_WINDOW_MIN * 60;
            header('Retry-After: ' . $retryAfterSec);
            $this->errorWithData(
                'INQUIRY_RATE_LIMITED',
                'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً ۳۰ دقیقه صبر کنید.',
                ['retry_after' => $retryAfterSec],
                429
            );
        }

        // Collect IP for audit — never returned to client
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? null;
        // Strip any port or multiple IPs from X-Forwarded-For
        if ($ip !== null) {
            $ip = trim(explode(',', $ip)[0]);
        }

        $id = $this->model->insert([
            'name'       => $name,
            'phone'      => $phone,
            'message'    => $message,
            'ip_address' => $ip,
        ]);

        $this->json(['inquiry_id' => $id], 201);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function validateInquiry(array $data): array
    {
        $errors = [];

        if (empty(trim((string)($data['name'] ?? '')))) {
            $errors['name'] = 'نام الزامی است';
        } elseif (mb_strlen(trim((string)$data['name'])) > 200) {
            $errors['name'] = 'نام نباید بیشتر از ۲۰۰ کاراکتر باشد';
        }

        $phone = $this->validator->normaliseMobile(
            $this->validator->normaliseDigits((string)($data['phone'] ?? ''))
        );
        if ($phone === null) {
            $errors['phone'] = 'شماره تلفن معتبر نیست';
        }

        $message = trim((string)($data['message'] ?? ''));
        if ($message === '') {
            $errors['message'] = 'متن پیام الزامی است';
        } elseif (mb_strlen($message) < 10) {
            $errors['message'] = 'پیام باید حداقل ۱۰ کاراکتر داشته باشد';
        } elseif (mb_strlen($message) > 2000) {
            $errors['message'] = 'پیام نباید بیشتر از ۲۰۰۰ کاراکتر باشد';
        }

        return $errors;
    }
}
