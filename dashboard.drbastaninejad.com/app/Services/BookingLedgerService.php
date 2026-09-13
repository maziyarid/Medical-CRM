<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Rich booking ledger writer. This is deliberately separate from the legacy
 * 19-column SmartFormat export consumed by the clinic desktop tooling.
 */
final class BookingLedgerService
{
    /** @return 'submitted'|'failed_confirmed'|'outcome_unknown'|'skipped' */
    public function syncBooking(int $bookingId): string
    {
        if (($_ENV['BOOKING_LEDGER_WRITE_ENABLED'] ?? '0') !== '1') {
            $this->record($bookingId, 'skipped');
            return 'skipped';
        }
        $url = trim((string)($_ENV['BOOKING_SHEET_WEBHOOK_URL'] ?? ''));
        $secret = trim((string)($_ENV['BOOKING_SHEET_SHARED_SECRET'] ?? ''));
        if ($url === '' || $secret === '' || str_starts_with($secret, 'CHANGE_ME')) {
            $this->record($bookingId, 'skipped');
            return 'skipped';
        }

        $db = Database::conn();
        $stmt = $db->prepare(
            "SELECT i.id, CONCAT_WS(' ', i.first_name, i.last_name) AS patient_name, i.mobile,
                    i.slot_start, i.payment_gateway, i.paid_status, i.booking_source,
                    i.clinic_confirmation_status, i.appointment_id, i.updated_at,
                    u.full_name AS receptionist_name, p.reference_id
             FROM intakes i
             LEFT JOIN users u ON u.id = i.receptionist_user_id
             LEFT JOIN booking_payments p ON p.id = i.payment_id
             WHERE i.id = ? AND i.source_type = 'booking' LIMIT 1"
        );
        $stmt->execute([$bookingId]);
        $row = $stmt->fetch();
        if (!$row) {
            return 'failed_confirmed';
        }

        $payload = [
            'secret' => $secret,
            'mode' => 'booking_ledger',
            'booking_id' => (int)$row['id'],
            'row' => [
                'BookingID' => (int)$row['id'],
                'PatientName' => (string)$row['patient_name'],
                'Mobile' => (string)$row['mobile'],
                'SlotDateTimeUTC' => (string)($row['slot_start'] ?? ''),
                'PaymentGateway' => (string)($row['payment_gateway'] ?? ''),
                'PaidStatus' => (string)($row['paid_status'] ?? 'unpaid'),
                'Receptionist' => (string)($row['receptionist_name'] ?? ''),
                'BookingSource' => (string)($row['booking_source'] ?? 'online'),
                'ClinicConfirmation' => (string)($row['clinic_confirmation_status'] ?? 'pending'),
                'AppointmentID' => $row['appointment_id'] !== null ? (int)$row['appointment_id'] : '',
                'PaymentReference' => (string)($row['reference_id'] ?? ''),
                'UpdatedAtUTC' => (string)($row['updated_at'] ?? gmdate('Y-m-d H:i:s')),
            ],
        ];
        $result = $this->post($url, $payload);
        $this->record($bookingId, $result);
        return $result;
    }

    /** @return 'submitted'|'failed_confirmed'|'outcome_unknown' */
    private function post(string $url, array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return 'failed_confirmed';
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json; charset=UTF-8'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($raw === false || $errno !== 0) {
            return 'outcome_unknown';
        }
        $decoded = json_decode((string)$raw, true);
        if ($status >= 200 && $status < 300 && is_array($decoded) && !empty($decoded['ok'])) {
            return 'submitted';
        }
        if (is_array($decoded) && array_key_exists('ok', $decoded) && !$decoded['ok']) {
            return !empty($decoded['retryable']) ? 'outcome_unknown' : 'failed_confirmed';
        }
        return 'outcome_unknown';
    }

    private function record(int $bookingId, string $status): void
    {
        try {
            Database::conn()->prepare('UPDATE intakes SET booking_ledger_status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?')
                ->execute([$status, $bookingId]);
        } catch (\Throwable $e) {
            error_log('[BookingLedgerService] failed to record status for booking ' . $bookingId . ': ' . $e->getMessage());
        }
    }
}
