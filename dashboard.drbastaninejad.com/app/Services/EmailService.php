<?php
declare(strict_types=1);

namespace App\Services;

final class EmailService
{
    public function isConfigured(): bool
    {
        return ($_ENV['BOOKING_EMAIL_ENABLED'] ?? '0') === '1' && $this->hasSender();
    }

    private function hasSender(): bool
    {
        return filter_var(trim((string)($_ENV['MAIL_FROM'] ?? '')), FILTER_VALIDATE_EMAIL) !== false;
    }

    public function sendBookingAcknowledgement(string $email, string $firstName): bool
    {
        if (!$this->isConfigured() || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $from = trim((string)($_ENV['MAIL_FROM'] ?? ''));
        $loginUrl = trim((string)($_ENV['PATIENT_LOGIN_URL'] ?? 'https://app.drbastaninejad.com/Frontend/pages/auth/patient-login.html'));
        $subject = 'تأیید دریافت درخواست نوبت';
        $body = "{$firstName} عزیز،\nدرخواست نوبت شما دریافت شد؛ این پیام به معنی قطعی‌شدن زمان نوبت نیست. همکاران کلینیک برای اعلام و تأیید زمان با شما تماس می‌گیرند.\n\nورود و راه‌اندازی حساب بیمار با شماره همراه تأییدشده:\n{$loginUrl}";
        $headers = ['Content-Type: text/plain; charset=UTF-8', 'From: ' . $from];
        return @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    }

    public function sendRecoveryOtp(string $email, string $code): bool
    {
        if (($_ENV['EMAIL_OTP_ENABLED'] ?? '0') !== '1') {
            return false;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $from = trim((string)($_ENV['MAIL_FROM'] ?? ''));
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $subject = 'کد بازیابی پنل بیمار';
        $body = "کد یکبارمصرف بازیابی حساب شما: {$code}\nاین کد تا ۵ دقیقه معتبر است.\nرمز عبور خود را برای هیچ‌کس ارسال نکنید.";
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $from,
        ];
        return @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    }

    public function sendAppointmentReminder(string $email, string $scheduledAt): bool
    {
        if (($_ENV['EMAIL_REMINDER_ENABLED'] ?? '0') !== '1') {
            return false;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $from = trim((string)($_ENV['MAIL_FROM'] ?? ''));
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $when = (new \DateTimeImmutable($scheduledAt, new \DateTimeZone('UTC')))
                ->setTimezone(new \DateTimeZone('Asia/Tehran'))
                ->format('Y-m-d H:i');
        } catch (\Throwable) {
            return false;
        }
        $subject = 'یادآوری نوبت کلینیک';
        $body = "یادآوری نوبت شما: {$when} به وقت تهران.\nبرای تغییر یا لغو نوبت با کلینیک تماس بگیرید.";
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $from,
        ];
        return @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    }
    public function sendStaffInvitation(string $email, string $fullName, string $loginUrl): bool
    {
        if (($_ENV['STAFF_EMAIL_ENABLED'] ?? '1') !== '1' || !$this->hasSender() || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $from = trim((string)($_ENV['MAIL_FROM'] ?? ''));
        $subject = 'دعوت به پنل کارکنان کلینیک';
        $body = "{$fullName} عزیز،\nحساب کارکنان کلینیک برای شما ایجاد شده است.\nبرای ورود، شماره همراه ثبت‌شده خود را وارد و کد یکبارمصرف دریافت کنید. پس از نخستین ورود می‌توانید رمز عبور تعیین کنید.\n\n{$loginUrl}\n\nاین پیام شامل هیچ اطلاعات پزشکی بیمار نیست.";
        $headers = ['Content-Type: text/plain; charset=UTF-8', 'From: ' . $from];
        return @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    }

    public function sendPatientRegistration(string $email, string $firstName, string $loginUrl, bool $withBooking = false): bool
    {
        if (($_ENV['PATIENT_REGISTRATION_EMAIL_ENABLED'] ?? '1') !== '1' || !$this->hasSender() || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $from = trim((string)($_ENV['MAIL_FROM'] ?? ''));
        $subject = $withBooking ? 'ثبت حساب بیمار و دریافت درخواست نوبت' : 'ثبت حساب بیمار';
        $body = "{$firstName} عزیز،\nحساب بیمار شما در سامانه کلینیک ایجاد شد.";
        if ($withBooking) {
            $body .= "\nدرخواست نوبت شما نیز دریافت شد؛ زمان نوبت تا تأیید کلینیک قطعی نیست.";
        }
        $body .= "\n\nورود به پنل بیمار با شماره همراه تأییدشده:\n{$loginUrl}\n\nبرای امنیت، رمز عبور یا کد یکبارمصرف خود را برای هیچ‌کس ارسال نکنید.";
        $headers = ['Content-Type: text/plain; charset=UTF-8', 'From: ' . $from];
        return @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    }

}
