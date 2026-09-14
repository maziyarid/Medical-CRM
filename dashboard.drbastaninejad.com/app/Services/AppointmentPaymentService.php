<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Services\Payments\AppointmentPaymentGateway;
use App\Services\Payments\VandarGateway;
use App\Services\Payments\ZarinpalGateway;
use PDOException;
use RuntimeException;

final class AppointmentPaymentService
{
    public function __construct(private ?AppointmentBookingService $bookings = null)
    {
        $this->bookings ??= new AppointmentBookingService();
    }

    /** @return array{attempt_id:int,gateway:string,authority:string,redirect_url:string} */
    public function start(int $clinicId, int $patientId, int $bookingId): array
    {
        $db = Database::conn();
        $stmt = $db->prepare(
            'SELECT b.*, p.mobile, p.email, p.national_id
             FROM appointment_booking_requests b
             JOIN patients p ON p.id = b.patient_id
             WHERE b.id = ? AND b.clinic_id = ? AND b.patient_id = ? LIMIT 1'
        );
        $stmt->execute([$bookingId, $clinicId, $patientId]);
        $booking = $stmt->fetch();
        if (!$booking) throw new RuntimeException('booking not found');
        if ((string)$booking['payment_status'] === 'paid') throw new RuntimeException('booking is already paid');
        if (!in_array((string)$booking['confirmation_status'], ['holding','awaiting_payment'], true)) throw new RuntimeException('booking hold is no longer payable');
        if ($booking['hold_expires_at'] === null || strtotime((string)$booking['hold_expires_at']) < time()) {
            $this->bookings->releaseExpiredHolds($clinicId);
            throw new RuntimeException('booking hold expired');
        }

        $gatewayName = (string)$booking['payment_gateway'];
        $gateway = $this->gateway($gatewayName);
        $amount = (int)$booking['amount_rials'];
        $idempotencyKey = hash('sha256', 'appointment-payment|' . $bookingId . '|' . $gatewayName . '|' . $amount);
        $existing = $db->prepare('SELECT id, authority, status FROM appointment_payment_attempts WHERE idempotency_key = ? LIMIT 1');
        $existing->execute([$idempotencyKey]);
        $attempt = $existing->fetch();
        if ($attempt && !empty($attempt['authority']) && in_array((string)$attempt['status'], ['created','redirected'], true)) {
            return ['attempt_id'=>(int)$attempt['id'],'gateway'=>$gatewayName,'authority'=>(string)$attempt['authority'],'redirect_url'=>$gateway->redirectUrl((string)$attempt['authority'])];
        }

        if (!$attempt) {
            try {
                $db->prepare(
                    'INSERT INTO appointment_payment_attempts
                     (booking_request_id, gateway, amount_rials, status, idempotency_key, created_at, updated_at)
                     VALUES (?, ?, ?, "created", ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
                )->execute([$bookingId,$gatewayName,$amount,$idempotencyKey]);
                $attemptId = (int)$db->lastInsertId();
            } catch (PDOException $e) {
                if ((string)$e->getCode() !== '23000') throw $e;
                $existing->execute([$idempotencyKey]); $attempt=$existing->fetch();
                if (!$attempt) throw $e;
                $attemptId=(int)$attempt['id'];
            }
        } else $attemptId=(int)$attempt['id'];

        $callbackBase = rtrim(trim((string)($_ENV['BOOKING_PAYMENT_CALLBACK_BASE'] ?? '')), '/');
        if ($callbackBase === '') throw new RuntimeException('payment callback base is not configured');
        $callbackUrl = $callbackBase . '/' . rawurlencode($gatewayName);
        $result = $gateway->request($amount, $callbackUrl, [
            'mobile'=>$booking['mobile']??null,'email'=>$booking['email']??null,'national_code'=>$booking['national_id']??null,
            'checkout_number'=>(string)$bookingId,'description'=>'رزرو نوبت شماره '.$bookingId,
        ]);
        $db->prepare(
            'UPDATE appointment_payment_attempts
             SET authority = ?, status = "redirected", request_payload = ?, response_payload = ?, updated_at = UTC_TIMESTAMP()
             WHERE id = ?'
        )->execute([$result['authority'],json_encode(['callback_url'=>$callbackUrl,'booking_id'=>$bookingId],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),json_encode($result['raw'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$attemptId]);
        return ['attempt_id'=>$attemptId,'gateway'=>$gatewayName,'authority'=>$result['authority'],'redirect_url'=>$result['redirect_url']];
    }

    /** @return array<string,mixed> */
    public function verifyCallback(string $gatewayName, array $callback): array
    {
        $authority = $this->callbackAuthority($gatewayName, $callback);
        if ($authority === '') throw new RuntimeException('payment authority is missing');
        $db = Database::conn();
        $stmt = $db->prepare(
            'SELECT a.*, b.clinic_id, b.payment_status AS booking_payment_status
             FROM appointment_payment_attempts a JOIN appointment_booking_requests b ON b.id = a.booking_request_id
             WHERE a.gateway = ? AND a.authority = ? LIMIT 1'
        );
        $stmt->execute([$gatewayName,$authority]);
        $attempt=$stmt->fetch();
        if (!$attempt) throw new RuntimeException('payment attempt not found');

        if ((string)$attempt['status'] === 'verified') {
            $state = $this->finaliseVerifiedBooking((int)$attempt['booking_request_id'],$gatewayName,(string)($attempt['transaction_ref']??$authority));
            return ['verified'=>true,'idempotent'=>true,'booking'=>$state];
        }

        $gateway=$this->gateway($gatewayName);
        $verification=$gateway->verify($authority,(int)$attempt['amount_rials'],$callback);
        if (!$verification['verified']) {
            $db->prepare(
                'UPDATE appointment_payment_attempts SET status = "failed", gateway_code = ?, response_payload = ?, updated_at = UTC_TIMESTAMP()
                 WHERE id = ? AND status <> "verified"'
            )->execute([$verification['code'],json_encode($verification['raw'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),(int)$attempt['id']]);
            $this->releaseFailedBookingHold((int)$attempt['booking_request_id']);
            return ['verified'=>false,'idempotent'=>false,'code'=>$verification['code'],'booking'=>['id'=>(int)$attempt['booking_request_id'],'booking_id'=>(int)$attempt['booking_request_id']]];
        }

        $reference=(string)($verification['reference']??$authority);
        $db->prepare(
            'UPDATE appointment_payment_attempts
             SET status = "verified", transaction_ref = ?, gateway_code = ?, response_payload = ?, verified_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
             WHERE id = ?'
        )->execute([$reference,$verification['code'],json_encode($verification['raw'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),(int)$attempt['id']]);
        $state=$this->finaliseVerifiedBooking((int)$attempt['booking_request_id'],$gatewayName,$reference);
        return ['verified'=>true,'idempotent'=>false,'reference'=>$reference,'booking'=>$state];
    }

    /** @return array<string,mixed> */
    private function finaliseVerifiedBooking(int $bookingId, string $gateway, string $reference): array
    {
        $state=$this->bookings->markPaid($bookingId,$gateway,$reference);
        if (!empty($state['slot_reserved'])) {
            try {
                $confirmed=(new OnlineBookingConfirmationService())->confirm($bookingId);
                $state=array_merge($state,$confirmed,['status'=>'confirmed']);
            } catch (RuntimeException $e) {
                // Late/raced payments remain paid and visible for staff reconciliation; never silently lose payment state.
                error_log('[AppointmentPaymentService] paid booking auto-confirm needs staff review: '.$e->getMessage());
                $state['confirmation_error']=$e->getMessage();
            }
        }
        try { (new ScheduledVisitSheetService())->queue($bookingId); } catch (\Throwable) {}
        return $state;
    }

    /** @return array<string,mixed> */
    public function reconcileAttempt(int $clinicId, int $attemptId): array
    {
        $stmt=Database::conn()->prepare(
            'SELECT a.gateway, a.authority FROM appointment_payment_attempts a
             JOIN appointment_booking_requests b ON b.id = a.booking_request_id
             WHERE a.id = ? AND b.clinic_id = ? LIMIT 1'
        );
        $stmt->execute([$attemptId,$clinicId]); $attempt=$stmt->fetch();
        if(!$attempt)throw new RuntimeException('payment attempt not found');
        $gateway=(string)$attempt['gateway'];$authority=trim((string)($attempt['authority']??''));
        if($authority==='')throw new RuntimeException('payment authority is missing');
        $callback=$gateway==='zarinpal'?['Authority'=>$authority,'Status'=>'OK']:['checkout_id'=>$authority,'status'=>'SUCCEED'];
        return $this->verifyCallback($gateway,$callback);
    }

    private function releaseFailedBookingHold(int $bookingId): void
    {
        $db=Database::conn(); $db->beginTransaction();
        try {
            $stmt=$db->prepare('SELECT * FROM appointment_booking_requests WHERE id=? FOR UPDATE');$stmt->execute([$bookingId]);$booking=$stmt->fetch();
            if(!$booking){$db->commit();return;}
            if((string)$booking['payment_status']==='paid'||$booking['slot_claim_key']===null){$db->commit();return;}
            $day=$db->prepare('SELECT id FROM appointment_open_days WHERE id=? FOR UPDATE');$day->execute([(int)$booking['open_day_id']]);
            if($day->fetch())$db->prepare('UPDATE appointment_open_days SET held_count=GREATEST(held_count-1,0),updated_at=UTC_TIMESTAMP() WHERE id=?')->execute([(int)$booking['open_day_id']]);
            $db->prepare(
                'UPDATE appointment_booking_requests SET payment_status="failed",confirmation_status="expired",slot_claim_key=NULL,
                 hold_expires_at=NULL,sheet_sync_status="pending",sheet_sync_error=NULL,updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([$bookingId]);
            $db->commit();
        } catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }

    private function gateway(string $name): AppointmentPaymentGateway
    {
        return match($name){'zarinpal'=>new ZarinpalGateway(),'vandar'=>new VandarGateway(),default=>throw new RuntimeException('unsupported payment gateway')};
    }

    private function callbackAuthority(string $gatewayName,array $callback): string
    {
        return match($gatewayName){'zarinpal'=>trim((string)($callback['Authority']??$callback['authority']??'')),'vandar'=>trim((string)($callback['checkout_id']??'')),default=>''};
    }
}
