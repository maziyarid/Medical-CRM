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
