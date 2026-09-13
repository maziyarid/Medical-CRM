<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class PatientRegistrationNotificationService
{
    /** @return array{sms:string,email:string} */
    public function notify(int $patientId, bool $withBooking = false, bool $force = false): array
    {
        (new StaffSchemaBootstrapService())->ensure();
        $db=Database::conn();
        $q=$db->prepare('SELECT id,first_name,mobile,email,registration_sms_status,registration_email_status,registration_notified_at FROM patients WHERE id=? AND deleted_at IS NULL LIMIT 1');
        $q->execute([$patientId]);
        $p=$q->fetch();
        if (!$p) throw new RuntimeException('patient not found');
        if (!$force && $p['registration_notified_at'] !== null) {
            return ['sms'=>(string)$p['registration_sms_status'],'email'=>(string)$p['registration_email_status']];
        }

        $login=trim((string)($_ENV['PATIENT_LOGIN_URL']??'https://app.drbastaninejad.com/Frontend/pages/auth/patient-login.html'));
        $smsText=$withBooking
            ? 'حساب بیمار شما در سامانه کلینیک ایجاد شد و درخواست نوبت شما دریافت شد. زمان نوبت پس از تأیید کلینیک قطعی می‌شود. پنل بیمار: '.$login
            : 'حساب بیمار شما در سامانه کلینیک ایجاد شد. برای ورود با شماره همراه تأییدشده: '.$login;
        $sms=(new SmsProviderChain())->sendMessage((string)$p['mobile'],mb_substr($smsText,0,800));
        $smsStatus=!empty($sms['ok'])?'sent':'failed';
        $emailStatus='skipped';
        if (!empty($p['email'])) {
            $emailStatus=(new EmailService())->sendPatientRegistration((string)$p['email'],(string)$p['first_name'],$login,$withBooking)?'sent':'failed';
        }
        $notified=($smsStatus==='sent'||$emailStatus==='sent');
        $db->prepare('UPDATE patients SET registration_sms_status=?,registration_email_status=?,registration_notified_at=IF(?,UTC_TIMESTAMP(),registration_notified_at),updated_at=UTC_TIMESTAMP() WHERE id=?')
            ->execute([$smsStatus,$emailStatus,$notified?1:0,$patientId]);
        return ['sms'=>$smsStatus,'email'=>$emailStatus];
    }
}
