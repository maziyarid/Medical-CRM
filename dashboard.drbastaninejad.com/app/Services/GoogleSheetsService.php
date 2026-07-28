<?php
declare(strict_types=1);

namespace App\Services;

/**
 * GoogleSheetsService — Phase A deliverable, updated Phase C
 *
 * Dual-writes every accepted intake to the clinic's Google Sheet so
 * reception staff can see new submissions in real time while the
 * MySQL backend is being rolled out.
 *
 * Uses the Google Sheets API v4 via a Service Account JSON key.
 * The key file path is set in .env as GOOGLE_SA_KEY_PATH.
 * The target spreadsheet ID is GOOGLE_SHEET_ID.
 * The target tab name is GOOGLE_SHEET_TAB (default: "Intakes").
 *
 * FROZEN COLUMNS (A–K) — never change order or meaning:
 *   A: intake_id  B: submission_uuid  C: name  D: mobile
 *   E: national_id  F: birth_date  G: service_type  H: chief_complaint
 *   I: preferred_date  J: submitted_at  K: db_id
 *
 * EXTENSION COLUMNS (L–M) — added Phase C, appended after K, non-breaking:
 *   L: email  M: visit_reason
 *
 * Per §6.2 of UNIFIED_MASTER_PLAN.md: A–K must never be reordered.
 * New fields must use column N onward.
 *
 * appendIntake() now returns a string status instead of throwing:
 *   'ok'      — Sheets append confirmed
 *   'failed'  — network or API error (logged; caller records in DB)
 *   'skipped' — env vars not configured (dev/staging)
 *
 * This allows IntakeController to record sheets_sync_status on the
 * intakes row without coupling the HTTP response to the Sheets outcome.
 */
final class GoogleSheetsService
{
    private string $spreadsheetId;
    private string $tabName;
    private string $saKeyPath;

    public function __construct()
    {
        $this->spreadsheetId = $_ENV['GOOGLE_SHEET_ID'] ?? '';
        $this->tabName       = $_ENV['GOOGLE_SHEET_TAB'] ?? 'Intakes';
        $this->saKeyPath     = $_ENV['GOOGLE_SA_KEY_PATH'] ?? '';
    }

    /**
     * Append one row to the configured Google Sheet.
     *
     * Returns 'ok' | 'failed' | 'skipped' — never throws.
     * Failures are logged to PHP error_log.
     *
     * @param int   $intakeId  The auto-increment DB id (for cross-reference)
     * @param array $data      Normalised intake fields (see COLUMNS above)
     * @return string 'ok' | 'failed' | 'skipped'
     */
    public function appendIntake(int $intakeId, array $data): string
    {
        if ($this->spreadsheetId === '' || $this->saKeyPath === '') {
            // Not configured — skip silently (dev / test environment)
            return 'skipped';
        }

        try {
            $token = $this->getAccessToken();
        } catch (\Throwable $e) {
            error_log('[GoogleSheets] getAccessToken failed: ' . $e->getMessage());
            return 'failed';
        }

        // Range covers A:M — frozen cols A–K + extension cols L–M.
        // valueInputOption=USER_ENTERED so dates/numbers format correctly.
        $range = urlencode("{$this->tabName}!A:M");
        $url   = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheetId}"
               . "/values/{$range}:append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS";

        // ── Frozen cols A–K (never change order) ──────────────────────────────
        $row = [
            $intakeId,                           // A: intake_id
            $data['submission_uuid'] ?? '',      // B: submission_uuid
            $data['name']            ?? '',      // C: name (first + last)
            $data['mobile']          ?? '',      // D: mobile
            $data['national_id']     ?? '',      // E: national_id
            $data['birth_date']      ?? '',      // F: birth_date (Gregorian Y-m-d)
            $data['service_type']    ?? '',      // G: service_type
            $data['chief_complaint'] ?? '',      // H: chief_complaint
            $data['preferred_date']  ?? '',      // I: preferred_date
            $data['submitted_at']    ?? '',      // J: submitted_at (UTC ISO)
            $intakeId,                           // K: db_id (duplicate for VLOOKUP)
        // ── Extension cols L–M (appended Phase C, §6.2 UNIFIED_MASTER_PLAN) ──
            $data['email']           ?? '',      // L: email
            $data['visit_reason']    ?? '',      // M: visit_reason
        ];

        $payload = json_encode(['values' => [$row]]);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload),
                ],
                'content' => $payload,
                'timeout' => 8,
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);

        if ($response === false) {
            error_log('[GoogleSheets] appendIntake: network error for intake_id=' . $intakeId);
            return 'failed';
        }

        $json = json_decode($response, true);
        if (!isset($json['updates'])) {
            error_log('[GoogleSheets] appendIntake: unexpected response for intake_id='
                . $intakeId . ' — ' . $response);
            return 'failed';
        }

        return 'ok';
    }

    // -------------------------------------------------------------------------
    // OAuth2 — Service Account JWT flow (no external library required)
    // -------------------------------------------------------------------------

    private function getAccessToken(): string
    {
        // Cache token in APCu for its lifetime to avoid signing a new JWT every request
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch('gsheets_token');
            if ($cached) {
                return $cached;
            }
        }

        $key = json_decode((string)file_get_contents($this->saKeyPath), true);
        if (!$key) {
            throw new \RuntimeException('Could not read Google Service Account key: ' . $this->saKeyPath);
        }

        $now = time();
        $header  = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->b64url(json_encode([
            'iss'   => $key['client_email'],
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        openssl_sign(
            "$header.$payload",
            $signature,
            openssl_pkey_get_private($key['private_key']),
            'SHA256'
        );

        $jwt = "$header.$payload." . $this->b64url($signature);

        $body = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $body,
                'timeout' => 8,
            ],
        ]);

        $response = file_get_contents('https://oauth2.googleapis.com/token', false, $ctx);
        $json     = json_decode($response, true);

        if (empty($json['access_token'])) {
            throw new \RuntimeException('Failed to obtain Google access token: ' . $response);
        }

        if (function_exists('apcu_store')) {
            apcu_store('gsheets_token', $json['access_token'], 3500); // expire slightly before 1h
        }

        return $json['access_token'];
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
