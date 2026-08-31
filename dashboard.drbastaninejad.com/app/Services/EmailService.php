<?php
declare(strict_types=1);

namespace App\Services;

final class EmailService
{
    public function isConfigured(): bool
    {
        return ($_ENV['BOOKING_EMAIL_ENABLED'] ?? '0') === '1'
            && filter_var(trim((string)($_ENV['MAIL_FROM'] ?? '')), FILTER_VALIDATE_EMAIL) !== false;
    }

    public function sendBookingAcknowledgement(string $email, string $firstName): bool
    {
        if (($_ENV['BOOKING_EMAIL_ENABLED'] ?? '0') !== '1' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $from = trim((string)($_ENV['MAIL_FROM'] ?? ''));
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
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

}
