<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class BookingFinalisationService
{
    /** @return array<int,array<string,mixed>> */
    public function pending(int $clinicId, int $limit = 100): array
    {
        $limit = max(1, min(250, $limit));
        $stmt = Database::conn()->prepare(
            'SELECT i.id, i.patient_id, i.first_name, i.last_name, i.mobile, i.created_at, i.status,
                    i.finalization_status, i.finalization_sms_status, i.finalization_sms_sent_at,
                    i.appointment_booking_request_id
             FROM intakes i
             WHERE i.clinic_id = ? AND i.source_type = "booking" AND i.deleted_at IS NULL
               AND i.status <> "rejected"
               AND i.appointment_booking_request_id IS NULL
             ORDER BY i.created_at DESC LIMIT ' . $limit
        );
        $stmt->execute([$clinicId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['patient_id'] = $row['patient_id'] === null ? null : (int)$row['patient_id'];
            $row['patient_name'] = trim((string)$row['first_name'] . ' ' . (string)$row['last_name']);
            unset($row['first_name'], $row['last_name']);
        }
        unset($row);
        return $rows;
    }

    /**
     * Resolve the unfinished booking intake that is allowed to create a patient hold.
     * A caller-supplied id is treated only as a preference and is revalidated.
     */
    public function resolveUnfinishedIntake(int $clinicId, int $patientId, int $preferredIntakeId = 0): int
    {
        if ($clinicId < 1 || $patientId < 1) throw new RuntimeException('booking intake required');
        $db = Database::conn();

        if ($preferredIntakeId > 0) {
            $stmt = $db->prepare(
                'SELECT id FROM intakes
                 WHERE id=? AND clinic_id=? AND patient_id=? AND source_type="booking"
                   AND deleted_at IS NULL AND status<>"rejected"
                   AND appointment_booking_request_id IS NULL
                 LIMIT 1'
            );
            $stmt->execute([$preferredIntakeId,$clinicId,$patientId]);
            $id=(int)($stmt->fetchColumn()?:0);
            if ($id > 0) return $id;
            throw new RuntimeException('booking intake mismatch');
        }

        $stmt = $db->prepare(
            'SELECT id FROM intakes
             WHERE clinic_id=? AND patient_id=? AND source_type="booking"
               AND deleted_at IS NULL AND status<>"rejected"
               AND appointment_booking_request_id IS NULL
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$clinicId,$patientId]);
        $id=(int)($stmt->fetchColumn()?:0);
        if ($id < 1) throw new RuntimeException('booking intake required');
        return $id;
    }

    /** @param array<int,int|string> $ids @return array<string,mixed> */
    public function notify(int $clinicId, int $staffId, array $ids): array
    {
        $unique = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0)));
        if (!$unique) throw new RuntimeException('no pending bookings selected');
        if (count($unique) > 100) throw new RuntimeException('too many pending bookings selected');
        $result = ['requested'=>count($unique),'sent'=>0,'failed'=>0,'skipped'=>0,'items'=>[]];
        foreach ($unique as $id) {
            try {
                $item = $this->notifyOne($clinicId, $staffId, $id);
                $result[$item['status'] === 'sent' ? 'sent' : ($item['status'] === 'failed' ? 'failed' : 'skipped')]++;
                $result['items'][] = $item;
            } catch (\Throwable $e) {
                $result['failed']++;
                $result['items'][] = ['id'=>$id,'status'=>'failed','error'=>$e->getMessage()];
            }
        }
        return $result;
    }

    /** @return array<string,mixed> */
    private function notifyOne(int $clinicId, int $staffId, int $intakeId): array
    {
        $db = Database::conn();
        $stmt = $db->prepare(
            'SELECT i.id, i.patient_id, i.first_name, i.mobile
             FROM intakes i
             WHERE i.id = ? AND i.clinic_id = ? AND i.source_type = "booking" AND i.deleted_at IS NULL
               AND i.status <> "rejected" AND i.appointment_booking_request_id IS NULL LIMIT 1'
        );
        $stmt->execute([$intakeId, $clinicId]);
        $row = $stmt->fetch();
        if (!$row || (int)($row['patient_id'] ?? 0) < 1) {
            return ['id'=>$intakeId,'status'=>'skipped'];
        }
        if ((new BookingBlacklistService())->isIntakeBlocked($clinicId,$intakeId,(int)$row['patient_id'])) {
            return ['id'=>$intakeId,'status'=>'skipped'];
        }
        // No new SMS resume token is created here. Patients return to the booking page manually and, after OTP verification, the latest unfinished request is recovered by mobile number. Existing historical resume tokens remain valid for backward compatibility.
        $name = trim((string)($row['first_name'] ?? ''));
        $message = ($name !== '' ? $name . " عزیز،\n" : '')
            . "درخواست نوبت شما در کلینیک دکتر باستانی‌نژاد ثبت شده اما هنوز نهایی نشده است.\n"
            . "برای انتخاب تاریخ و ساعت آزاد و تکمیل نوبت، لطفاً به بخش نوبت‌دهی سایت دکتر باستانی‌نژاد مراجعه کنید و با همان شماره همراه ادامه دهید.";
        $sms = (new SmsProviderChain())->sendMessage((string)$row['mobile'], $message);
        $status = !empty($sms['ok']) ? 'sent' : 'failed';
        $db->prepare(
            'UPDATE intakes SET finalization_status = "notified", finalization_sms_status = ?,
                 finalization_sms_sent_at = IF(? = "sent", UTC_TIMESTAMP(), finalization_sms_sent_at), updated_at = UTC_TIMESTAMP()
             WHERE id = ? AND clinic_id = ?'
        )->execute([$status, $status, $intakeId, $clinicId]);
        if ($status !== 'sent') {
            error_log('[BookingFinalisationService] completion SMS failed for intake ' . $intakeId . ': ' . (string)($sms['error'] ?? 'unknown'));
        }
        return ['id'=>$intakeId,'status'=>$status];
    }

    /** @return array<string,mixed> */
    public function resume(string $rawToken): array
    {
        $rawToken = trim($rawToken);
        if ($rawToken === '' || strlen($rawToken) > 200) throw new RuntimeException('invalid resume token');
        $hash = hash('sha256', $rawToken);
        $db = Database::conn();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'SELECT t.id token_id, t.intake_id, t.patient_id, t.expires_at, t.used_at,
                        i.clinic_id, i.first_name, i.last_name, i.mobile, i.appointment_booking_request_id
                 FROM booking_resume_tokens t JOIN intakes i ON i.id = t.intake_id
                 WHERE t.token_hash = ? FOR UPDATE'
            );
            $stmt->execute([$hash]);
            $row = $stmt->fetch();
            if (!$row || $row['used_at'] !== null || strtotime((string)$row['expires_at']) < time()) {
                throw new RuntimeException('resume token expired');
            }
            if ((int)$row['patient_id'] < 1) throw new RuntimeException('patient not found');
            if ((int)($row['appointment_booking_request_id'] ?? 0) > 0) {
                throw new RuntimeException('resume token expired');
            }
            if ((new BookingBlacklistService())->isIntakeBlocked(
                (int)$row['clinic_id'], (int)$row['intake_id'], (int)$row['patient_id']
            )) throw new RuntimeException('booking blocked');
            // Do not consume the SMS link merely by opening the page. A mobile browser
            // may reload, suspend or reopen the URL before the patient chooses a slot.
            // The token is consumed only after a real appointment hold is linked below.
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
        $session = (new OtpService())->issueToken((string)$row['mobile'], 'patient', 'session');
        return [
            'patient_session_token'=>(string)($session['token'] ?? ''),
            'patient_session_expires_at'=>(string)($session['expires_at'] ?? ''),
            'intake_id'=>(int)$row['intake_id'],
            'patient_id'=>(int)$row['patient_id'],
            'name'=>trim((string)$row['first_name'].' '.(string)$row['last_name']),
            'mobile'=>(string)$row['mobile'],
        ];
    }

    public function linkBooking(int $clinicId, int $patientId, int $intakeId, int $bookingId): void
    {
        if ($intakeId < 1 || $bookingId < 1) throw new RuntimeException('booking intake mismatch');

        $db = Database::conn();
        $db->beginTransaction();
        try {
            $intakeStmt = $db->prepare(
                'SELECT id,appointment_booking_request_id
                 FROM intakes
                 WHERE id=? AND clinic_id=? AND patient_id=? AND source_type="booking"
                   AND deleted_at IS NULL AND status<>"rejected"
                 FOR UPDATE'
            );
            $intakeStmt->execute([$intakeId,$clinicId,$patientId]);
            $intake=$intakeStmt->fetch();
            if (!$intake) throw new RuntimeException('booking intake mismatch');

            $existingBooking=(int)($intake['appointment_booking_request_id']??0);
            if ($existingBooking > 0 && $existingBooking !== $bookingId) {
                throw new RuntimeException('booking intake already linked');
            }

            $bookingStmt=$db->prepare(
                'SELECT id,intake_id FROM appointment_booking_requests
                 WHERE id=? AND clinic_id=? AND patient_id=? FOR UPDATE'
            );
            $bookingStmt->execute([$bookingId,$clinicId,$patientId]);
            $booking=$bookingStmt->fetch();
            if (!$booking) throw new RuntimeException('booking not found');

            $existingIntake=(int)($booking['intake_id']??0);
            if ($existingIntake > 0 && $existingIntake !== $intakeId) {
                throw new RuntimeException('booking intake mismatch');
            }

            $db->prepare(
                'UPDATE intakes
                 SET appointment_booking_request_id=?,finalization_status="scheduled",updated_at=UTC_TIMESTAMP()
                 WHERE id=? AND clinic_id=? AND patient_id=?'
            )->execute([$bookingId,$intakeId,$clinicId,$patientId]);

            $db->prepare(
                'UPDATE appointment_booking_requests SET intake_id=? WHERE id=? AND clinic_id=? AND patient_id=?'
            )->execute([$intakeId,$bookingId,$clinicId,$patientId]);

            $db->prepare(
                'UPDATE booking_resume_tokens
                 SET used_at=COALESCE(used_at,UTC_TIMESTAMP())
                 WHERE intake_id=? AND clinic_id=? AND patient_id=? AND used_at IS NULL'
            )->execute([$intakeId,$clinicId,$patientId]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }
}