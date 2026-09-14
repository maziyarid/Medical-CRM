<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

/**
 * Projects appointment_booking_requests into the operational ScheduledVisits
 * Google Sheet. MySQL remains authoritative; this mirror is retriable and
 * idempotent by the booking UUID (VisitUUID).
 */
final class ScheduledVisitSheetService
{
    private DateTimeZone $utc;
    private DateTimeZone $tehran;

    public function __construct()
    {
        $this->utc = new DateTimeZone('UTC');
        $this->tehran = new DateTimeZone('Asia/Tehran');
    }

    public function queue(int $bookingId): void
    {
        if ($bookingId < 1) return;
        try {
            (new AppointmentSchemaBootstrapService())->ensure();
            Database::conn()->prepare(
                'UPDATE appointment_booking_requests SET sheet_sync_status = "pending", sheet_sync_error = NULL, updated_at = UTC_TIMESTAMP() WHERE id = ?'
            )->execute([$bookingId]);
        } catch (\Throwable $e) {
            error_log('[ScheduledVisitSheetService] queue failed for booking ' . $bookingId . ': ' . $e->getMessage());
        }
    }

    /** @return 'synced'|'pending'|'skipped'|'error' */
    public function syncBooking(int $bookingId): string
    {
        if ($bookingId < 1) return 'error';
        (new AppointmentSchemaBootstrapService())->ensure();
        $db = Database::conn();
        $projection = $this->projection($db, $bookingId);
        if ($projection === null) return 'error';

        if (($_ENV['BOOKING_SHEET_WRITE_ENABLED'] ?? '0') !== '1') {
            $this->mark($db, $bookingId, 'skipped', 'ScheduledVisits sync disabled');
            return 'skipped';
        }

        $bridgeUrl = rtrim(trim((string)($_ENV['BOOKING_SHEET_BRIDGE_URL'] ?? $_ENV['GOOGLE_BRIDGE_URL'] ?? '')), '/');
        $bridgeToken = trim((string)($_ENV['BOOKING_SHEET_BRIDGE_TOKEN'] ?? $_ENV['GOOGLE_BRIDGE_TOKEN'] ?? ''));
        $webhookUrl = trim((string)($_ENV['BOOKING_SHEET_WEBHOOK_URL'] ?? ''));
        $webhookSecret = trim((string)($_ENV['BOOKING_SHEET_SHARED_SECRET'] ?? ''));
        $headers = ['Content-Type: application/json; charset=UTF-8', 'Accept: application/json'];
        if ($bridgeUrl !== '' && $bridgeToken !== '') {
            $url = $bridgeUrl . '/sheet/upsert';
            $headers[] = 'Authorization: Bearer ' . $bridgeToken;
            $payload = ['visit_uuid' => (string)$projection['VisitUUID'], 'row' => $projection];
        } elseif ($webhookUrl !== '' && $webhookSecret !== '') {
            $url = $webhookUrl;
            $payload = [
                'secret' => $webhookSecret,
                'action' => 'scheduled_visit_upsert',
                'sheet_name' => trim((string)($_ENV['BOOKING_VISIT_SHEET_NAME'] ?? 'ScheduledVisits')) ?: 'ScheduledVisits',
                'visit_uuid' => (string)$projection['VisitUUID'],
                'row' => $projection,
            ];
        } else {
            $this->mark($db, $bookingId, 'pending', 'ScheduledVisits transport is not configured');
            return 'pending';
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $this->mark($db, $bookingId, 'error', 'Failed to encode ScheduledVisits payload');
            return 'error';
        }
        try {
            $ctx = stream_context_create(['http' => ['method'=>'POST','header'=>implode("\r\n",$headers)."\r\n",'content'=>$json,'timeout'=>15,'ignore_errors'=>true]]);
            $response = @file_get_contents($url, false, $ctx);
            if ($response === false) {
                $this->mark($db, $bookingId, 'error', 'ScheduledVisits transport failure');
                return 'error';
            }
            $statusLine = $http_response_header[0] ?? '';
            $status = preg_match('/\s(\d{3})\s/', $statusLine, $m) ? (int)$m[1] : 0;
            $decoded = json_decode($response, true);
            if ($status >= 200 && $status < 300 && is_array($decoded) && !empty($decoded['ok'])) {
                $this->mark($db, $bookingId, 'synced', null);
                return 'synced';
            }
            $error = is_array($decoded) && isset($decoded['error']) ? (string)$decoded['error'] : 'ScheduledVisits transport rejected the update';
            $this->mark($db, $bookingId, 'error', $error);
            return 'error';
        } catch (\Throwable $e) {
            $this->mark($db, $bookingId, 'error', $e->getMessage());
            return 'error';
        }
    }

    /** @return array{synced:int,pending:int,skipped:int,error:int} */
    public function syncPending(int $limit = 25): array
    {
        (new AppointmentSchemaBootstrapService())->ensure();
        $limit = max(1, min(100, $limit));
        $statuses = (($_ENV['BOOKING_SHEET_WRITE_ENABLED'] ?? '0') === '1') ? '("pending","error","skipped")' : '("pending","error")';
        $stmt = Database::conn()->query('SELECT id FROM appointment_booking_requests WHERE sheet_sync_status IN ' . $statuses . ' ORDER BY updated_at ASC, id ASC LIMIT ' . $limit);
        $summary = ['synced'=>0,'pending'=>0,'skipped'=>0,'error'=>0];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) $summary[$this->syncBooking((int)$id)]++;
        return $summary;
    }

    /** @return array<string,string|int>|null */
    private function projection(PDO $db, int $bookingId): ?array
    {
        $stmt = $db->prepare(
            'SELECT b.id, b.uuid, b.appointment_id, b.patient_id, b.requested_start_at,
                    b.duration_minutes, b.source, b.receptionist_user_id, b.payment_status,
                    b.payment_gateway, b.amount_rials, b.confirmation_status,
                    b.staff_followup_required, b.followup_completed_at, b.followup_completed_by,
                    b.open_day_id, b.created_at, b.confirmed_at, b.updated_at,
                    p.first_name, p.last_name, p.mobile,
                    u.full_name AS registered_by_name, u.staff_code AS registered_by_code,
                    GROUP_CONCAT(DISTINCT r.name ORDER BY r.id SEPARATOR ",") AS registered_by_roles,
                    pa.transaction_ref, pa.verified_at,
                    cel.google_event_id, cel.last_synced_at
             FROM appointment_booking_requests b
             JOIN patients p ON p.id = b.patient_id
             LEFT JOIN users u ON u.id = b.receptionist_user_id
             LEFT JOIN role_user ru ON ru.user_id = u.id
             LEFT JOIN roles r ON r.id = ru.role_id
             LEFT JOIN appointment_payment_attempts pa ON pa.id = (
                 SELECT pa2.id FROM appointment_payment_attempts pa2 WHERE pa2.booking_request_id = b.id ORDER BY pa2.id DESC LIMIT 1
             )
             LEFT JOIN calendar_event_links cel ON cel.booking_request_id = b.id
             WHERE b.id = ?
             GROUP BY b.id, b.uuid, b.appointment_id, b.patient_id, b.requested_start_at,
                      b.duration_minutes, b.source, b.receptionist_user_id, b.payment_status,
                      b.payment_gateway, b.amount_rials, b.confirmation_status,
                      b.staff_followup_required, b.followup_completed_at, b.followup_completed_by,
                      b.open_day_id, b.created_at, b.confirmed_at, b.updated_at,
                      p.first_name, p.last_name, p.mobile, u.full_name, u.staff_code,
                      pa.transaction_ref, pa.verified_at, cel.google_event_id, cel.last_synced_at
             LIMIT 1'
        );
        $stmt->execute([$bookingId]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $source = (string)$row['source'];
        $patientName = trim((string)$row['first_name'] . ' ' . (string)$row['last_name']);
        $registeredId = $source === 'online' ? (int)$row['patient_id'] : (int)($row['receptionist_user_id'] ?? 0);
        $registeredName = $source === 'online' ? $patientName : trim((string)($row['registered_by_name'] ?? ''));
        $registeredCode = $source === 'online' ? 'WEB' : trim((string)($row['registered_by_code'] ?? ''));
        $registeredRole = $source === 'online' ? 'patient' : trim((string)($row['registered_by_roles'] ?? 'staff'));

        return [
            'VisitUUID'=>(string)$row['uuid'], 'BookingID'=>(int)$row['id'], 'AppointmentID'=>(int)($row['appointment_id']??0),
            'PatientID'=>(int)$row['patient_id'], 'PatientName'=>$patientName, 'Mobile'=>(string)$row['mobile'],
            'ScheduledAtTehran'=>$this->tehranTime((string)$row['requested_start_at']), 'DurationMinutes'=>(int)$row['duration_minutes'],
            'BookingSource'=>$source, 'RegistrationChannel'=>$source==='online'?'website-self-service':'reception-dashboard',
            'RegisteredByUserID'=>$registeredId, 'RegisteredByCode'=>$registeredCode, 'RegisteredByName'=>$registeredName, 'RegisteredByRole'=>$registeredRole,
            'PaymentStatus'=>(string)$row['payment_status'], 'PaymentGateway'=>(string)($row['payment_gateway']??'none'), 'AmountRials'=>(int)$row['amount_rials'],
            'PaymentReference'=>(string)($row['transaction_ref']??''), 'PaymentVerifiedAt'=>(string)($row['verified_at']??''),
            'ConfirmationStatus'=>(string)$row['confirmation_status'], 'StaffFollowupRequired'=>(int)$row['staff_followup_required'],
            'OpenDayID'=>(int)$row['open_day_id'], 'CalendarEventID'=>(string)($row['google_event_id']??''),
            'CreatedAtUTC'=>(string)$row['created_at'], 'ConfirmedAtUTC'=>(string)($row['confirmed_at']??''),
            'UpdatedAtUTC'=>(string)$row['updated_at'], 'LastSyncedAtUTC'=>(string)($row['last_synced_at']??''),
            'Notes'=>$row['followup_completed_at']!==null
                ? 'followup_completed_by='.(int)($row['followup_completed_by']??0).'; followup_completed_at='.(string)$row['followup_completed_at'] : '',
        ];
    }

    private function tehranTime(string $utc): string
    {
        if ($utc === '') return '';
        try { return (new DateTimeImmutable($utc, $this->utc))->setTimezone($this->tehran)->format('Y-m-d H:i:s'); }
        catch (\Throwable) { return $utc; }
    }

    private function mark(PDO $db, int $bookingId, string $status, ?string $error): void
    {
        $error = $error === null ? null : mb_substr($error, 0, 500);
        if ($status === 'synced') {
            $db->prepare('UPDATE appointment_booking_requests SET sheet_sync_status = "synced", sheet_synced_at = UTC_TIMESTAMP(), sheet_sync_error = NULL WHERE id = ?')->execute([$bookingId]);
            return;
        }
        $db->prepare('UPDATE appointment_booking_requests SET sheet_sync_status = ?, sheet_sync_error = ? WHERE id = ?')->execute([$status, $error, $bookingId]);
    }
}
