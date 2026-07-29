<?php
declare(strict_types=1);

namespace App\Services;

/**
 * ValidatorService — server-side input validation for app.drbastaninejad.com
 *
 * Validates Iranian mobile numbers, Code Meli (mod-11 checksum), Jalali dates,
 * and general required-field presence.  Returns an array of field-level errors
 * (empty = valid) matching the docs/API_CONTRACT.md error.fields contract.
 */
final class ValidatorService
{
    /**
     * Validate the intake POST body.
     * Returns ['field_name' => 'Persian error message', ...]
     */
    public function validateIntake(array $data): array
    {
        $errors = [];

        // Required text fields
        foreach (['firstName', 'lastName', 'mobile', 'nationalId', 'birthDate', 'description'] as $field) {
            if (empty(trim((string)($data[$field] ?? $data[$this->toSnake($field)] ?? '')))) {
                $errors[$this->toSnake($field)] = 'این فیلد الزامی است';
            }
        }

        // Mobile
        $mobile = $this->normaliseMobile((string)($data['mobile'] ?? ''));
        if ($mobile === null) {
            $errors['mobile'] = 'شماره موبایل معتبر نیست';
        }

        // National ID
        $nationalId = $this->normaliseDigits((string)($data['nationalId'] ?? $data['national_id'] ?? ''));
        if (!$this->isValidNationalId($nationalId)) {
            $errors['national_id'] = 'کد ملی معتبر نیست';
        }

        // Birth date (Jalali YYYY/MM/DD)
        $birthDate = $this->normaliseDigits((string)($data['birthDate'] ?? $data['birth_date'] ?? ''));
        if (!$this->isValidJalaliDate($birthDate)) {
            $errors['birth_date'] = 'تاریخ تولد معتبر نیست (فرمت: ۱۳XX/MM/DD)';
        }

        return $errors;
    }

    /**
     * Validate OTP send body.
     */
    public function validateOtpSend(array $data): array
    {
        $errors = [];
        $mobile = $this->normaliseMobile((string)($data['mobile'] ?? ''));
        if ($mobile === null) {
            $errors['mobile'] = 'شماره موبایل معتبر نیست';
        }
        return $errors;
    }

    /**
     * Validate OTP verify body.
     */
    public function validateOtpVerify(array $data): array
    {
        $errors = [];
        $mobile = $this->normaliseMobile((string)($data['mobile'] ?? ''));
        if ($mobile === null) {
            $errors['mobile'] = 'شماره موبایل معتبر نیست';
        }
        $otp = $this->normaliseDigits((string)($data['otp'] ?? ''));
        if (!preg_match('/^\d{5}$/', $otp)) {
            $errors['otp'] = 'کد تایید باید ۵ رقم باشد';
        }
        return $errors;
    }

    // -------------------------------------------------------------------------
    // Normalisation helpers (public — usable in controllers)
    // -------------------------------------------------------------------------

    /** Convert Persian/Arabic-Indic digits to ASCII digits */
    public function normaliseDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    /**
     * Normalise any standard Iranian mobile format → 09XXXXXXXXX (11 digits)
     * Returns null if the number is invalid.
     */
    public function normaliseMobile(string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', $this->normaliseDigits($raw));
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0' . $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '98')) {
            $digits = '0' . substr($digits, 2);
        }
        if (preg_match('/^09[0-9]{9}$/', $digits)) {
            return $digits;
        }
        return null;
    }

    /**
     * Code Meli (National ID) mod-11 validation.
     */
    public function isValidNationalId(string $id): bool
    {
        $id = $this->normaliseDigits($id);
        $id = preg_replace('/\D/', '', $id);
        if (strlen($id) !== 10) {
            return false;
        }
        if (preg_match('/^(.)\1{9}$/', $id)) {
            return false; // all-same-digit
        }
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$id[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $check     = (int)$id[9];
        return $remainder < 2
            ? $check === $remainder
            : $check === (11 - $remainder);
    }

    /**
     * Validate Jalali date in YYYY/MM/DD format.
     * Only checks structural validity and Jalali calendar bounds.
     */
    public function isValidJalaliDate(string $date): bool
    {
        $date = $this->normaliseDigits($date);
        if (!preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date, $m)) {
            return false;
        }
        [, $year, $month, $day] = array_map('intval', $m);
        if ($year < 1200 || $year > 1500) {
            return false;
        }
        if ($month < 1 || $month > 12) {
            return false;
        }
        $maxDay = $month <= 6 ? 31 : ($month <= 11 ? 30 : 29);
        return $day >= 1 && $day <= $maxDay;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /** camelCase → snake_case (simple, ASCII-safe) */
    private function toSnake(string $camel): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($camel)));
    }
}
