<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Google Sheets is owned by the live WorkingVersion intake during transition.
 * The dashboard must not duplicate that write. Direct dashboard writes are
 * therefore opt-in and require an already-shaped SmartFormat row.
 */
final class GoogleSheetsService
{
    /** @return 'submitted'|'failed_confirmed'|'outcome_unknown'|'skipped' */
    public function appendBooking(int $bookingId, string $submissionUuid, array $data): string
    {
        if (($_ENV['BOOKING_SHEET_WRITE_ENABLED'] ?? '0') !== '1') {
            return 'skipped';
        }
        $url = trim((string)($_ENV['BOOKING_SHEET_WEBHOOK_URL'] ?? ''));
        $secret = trim((string)($_ENV['BOOKING_SHEET_SHARED_SECRET'] ?? ''));
        if ($url === '' || $secret === '') {
            return 'skipped';
        }
        $payload = [
            'secret' => $secret,
            'submission_uuid' => $submissionUuid,
            'row' => BookingSheetRowBuilder::build($data),
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return 'failed_confirmed';
        }
        $headers = "Content-Type: application/json; charset=UTF-8\r\nAccept: application/json\r\n";
        $ctx = stream_context_create(['http' => [
            'method' => 'POST', 'header' => $headers, 'content' => $json,
            'timeout' => 15, 'ignore_errors' => true,
        ]]);
        try {
            $response = @file_get_contents($url, false, $ctx);
            if ($response === false) {
                return 'outcome_unknown';
            }
            $statusLine = $http_response_header[0] ?? '';
            $status = preg_match('/\s(\d{3})\s/', $statusLine, $m) ? (int)$m[1] : 0;
            $decoded = json_decode($response, true);
            if ($status >= 200 && $status < 300 && is_array($decoded) && !empty($decoded['ok'])) {
                return 'submitted';
            }
            // Apps Script ContentService replies with HTTP 200 even when the
            // application-level response is a confirmed rejection.
            if (is_array($decoded) && array_key_exists('ok', $decoded) && !$decoded['ok']) {
                if (!empty($decoded['retryable'])) {
                    return 'outcome_unknown';
                }
                return 'failed_confirmed';
            }
            // A transport/server error does not prove whether the write happened.
              // Only an explicit application rejection is confirmed above.
              return 'outcome_unknown';
        } catch (\Throwable $e) {
            error_log('[GoogleSheetsService] booking ' . $bookingId . ' outcome unknown: ' . $e->getMessage());
            return 'outcome_unknown';
        }
    }

    /** @return 'ok'|'failed'|'skipped' */
    public function appendIntake(int $intakeId, array $data): string
    {
        if (($_ENV['DASHBOARD_SHEETS_WRITE_ENABLED'] ?? '0') !== '1') {
            return 'skipped';
        }
        $url = trim((string)($_ENV['SHEET_WEBHOOK_URL'] ?? ''));
        $secret = (string)($_ENV['SHEET_SHARED_SECRET'] ?? '');
        $row = $data['sheet_row'] ?? null;
        if (!is_array($row) || $url === '' || $secret === '') {
            error_log('[GoogleSheetsService] direct dashboard Sheets write skipped: configuration/sheet_row missing.');
            return 'skipped';
        }
        try {
            $payload = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $body = http_build_query(['secret' => $secret, 'payload' => $payload], '', '&', PHP_QUERY_RFC3986);
            $ctx = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\nAccept: application/json\r\n",
                'content' => $body,
                'timeout' => 15,
                'ignore_errors' => true,
            ]]);
            $response = @file_get_contents($url, false, $ctx);
            if ($response === false) {
                return 'failed';
            }
            $decoded = json_decode($response, true);
            return is_array($decoded) && ($decoded['ok'] ?? false) ? 'ok' : 'failed';
        } catch (\Throwable $e) {
            error_log('[GoogleSheetsService] intake ' . $intakeId . ' failed: ' . $e->getMessage());
            return 'failed';
        }
    }
}
