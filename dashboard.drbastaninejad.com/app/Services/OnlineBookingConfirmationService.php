<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Finalises a website booking immediately after a verified gateway payment.
 * Staff-created bookings already bypass payment in createAdminFreeBooking().
 */
final class OnlineBookingConfirmationService
{
    /** @return array<string,mixed> */
    public function confirm(int $bookingId): array
    {
        $db = Database::conn();
        $lookup = $db->prepare('SELECT clinic_id FROM appointment_booking_requests WHERE id = ? LIMIT 1');
        $lookup->execute([$bookingId]);
        $clinicId = (int)$lookup->fetchColumn();
        if ($clinicId < 1) throw new RuntimeException('booking not found');

        $lockName = 'crm:appointments:' . $clinicId;
        $lock = $db->prepare('SELECT GET_LOCK(?, 8)');
        $lock->execute([$lockName]);
        if ((int)$lock->fetchColumn() !== 1) throw new RuntimeException('clinic lock timeout');
        try {
            $db->beginTransaction();
            $stmt = $db->prepare('SELECT * FROM appointment_booking_requests WHERE id = ? AND clinic_id = ? FOR UPDATE');
            $stmt->execute([$bookingId, $clinicId]);
            $booking = $stmt->fetch();
            if (!$booking) throw new RuntimeException('booking not found');
            if ((string)$booking['confirmation_status'] === 'confirmed' && $booking['appointment_id']) {
                $db->commit();
                return ['booking_id'=>$bookingId,'appointment_id'=>(int)$booking['appointment_id'],'status'=>'confirmed','idempotent'=>true];
            }
            if ((string)$booking['source'] !== 'online') throw new RuntimeException('online booking required');
            if ((string)$booking['payment_status'] !== 'paid') throw new RuntimeException('payment not verified');
            if ($booking['slot_claim_key'] === null) throw new RuntimeException('paid slot requires reconciliation');

            $overlap = $db->prepare(
                'SELECT id FROM appointments
                 WHERE clinic_id = ? AND deleted_at IS NULL AND status IN ("scheduled","confirmed")
                   AND scheduled_at < DATE_ADD(?, INTERVAL ? MINUTE)
                   AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?
                 LIMIT 1'
            );
            $overlap->execute([$clinicId,(string)$booking['requested_start_at'],(int)$booking['duration_minutes'],(string)$booking['requested_start_at']]);
            if ($overlap->fetch()) throw new RuntimeException('slot unavailable');

            $appointmentUuid = bin2hex(random_bytes(16));
            $providerId = (int)($_ENV['BOOKING_DEFAULT_PROVIDER_ID'] ?? 0);
            $db->prepare(
                'INSERT INTO appointments
                 (uuid, clinic_id, patient_id, provider_id, scheduled_at, duration_minutes, visit_reason,
                  status, notes, created_at, updated_at)
                 VALUES (?, ?, ?, NULLIF(?,0), ?, ?, "رزرو آنلاین", "confirmed",
                         "Auto-confirmed after verified online payment", UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([$appointmentUuid,$clinicId,(int)$booking['patient_id'],$providerId,$booking['requested_start_at'],(int)$booking['duration_minutes']]);
            $appointmentId = (int)$db->lastInsertId();

            $db->prepare(
                'UPDATE appointment_booking_requests
                 SET appointment_id = ?, confirmation_status = "confirmed", confirmed_at = UTC_TIMESTAMP(),
                     staff_followup_required = 0, sheet_sync_status = "pending", sheet_sync_error = NULL,
                     updated_at = UTC_TIMESTAMP()
                 WHERE id = ?'
            )->execute([$appointmentId,$bookingId]);

            $payload = json_encode([
                'appointment_id'=>$appointmentId,
                'booking_request_id'=>$bookingId,
                'scheduled_at'=>$booking['requested_start_at'],
                'duration_minutes'=>(int)$booking['duration_minutes'],
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $idempotency = hash('sha256','appointment|'.$appointmentId.'|upsert');
            $db->prepare(
                'INSERT IGNORE INTO calendar_sync_outbox
                 (clinic_id,aggregate_type,aggregate_id,action,idempotency_key,payload,status,created_at,updated_at)
                 VALUES (?,"appointment",?,"upsert",?,CAST(? AS JSON),"pending",UTC_TIMESTAMP(),UTC_TIMESTAMP())'
            )->execute([$clinicId,$appointmentId,$idempotency,$payload]);
            $db->commit();
            return ['booking_id'=>$bookingId,'appointment_id'=>$appointmentId,'status'=>'confirmed','idempotent'=>false];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        } finally {
            try { $r=$db->prepare('SELECT RELEASE_LOCK(?)'); $r->execute([$lockName]); } catch (\Throwable) {}
        }
    }
}
