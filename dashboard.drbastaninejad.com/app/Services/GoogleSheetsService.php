<?php
declare(strict_types=1);

namespace App\Services;

/**
 * GoogleSheetsService — Phase A deliverable
 *
 * Dual-writes every accepted intake to the clinic's Google Sheet so
 * reception staff can see new submissions in real time while the
 * Laravel / MySQL backend is still being rolled out.
 *
 * Uses the Google Sheets API v4 via a Service Account JSON key.
 * The key file path is set in .env as GOOGLE_SA_KEY_PATH.
 * The target spreadsheet ID is GOOGLE_SHEET_ID.
 * The target tab name (sheet name) is GOOGLE_SHEET_TAB (default: "Intakes").
 *
 * COLUMNS (A→K):
 *   A: intake_id  B: submission_uuid  C: name  D: mobile
 *   E: national_id  F: birth_date  G: service_type  H: chief_complaint
 *   I: preferred_date  J: submitted_at  K: db_id
 *
 * Failure is non-fatal — IntakeController catches any exception from this
 * service and logs it without rolling back the DB transaction.
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
     * @param int   $intakeId  The auto-increment DB id (for cross-reference)
     * @param array $data      Normalised intake fields (see COLUMNS above)
     * @throws \RuntimeException if the API call fails
     */
    public function appendIntake(int $intakeId, array $data): void
    {
        if ($this->spreadsheetId === '' || $this->saKeyPath === '') {
            // Not configured — skip silently (dev / test environment)
            return;
        }

        $token  = $this->getAccessToken();
        $range  = urlencode("{$this->tabName}!A:K");
        $url    = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheetId}/values/{$range}:append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS";

        $row = [
            $intakeId,
            $data['submission_uuid'] ?? '',
            $data['name']            ?? '',
            $data['mobile']          ?? '',
            $data['national_id']     ?? '',
            $data['birth_date']      ?? '',
            $data['service_type']    ?? '',
            $data['chief_complaint'] ?? '',
            $data['preferred_date']  ?? '',
            $data['submitted_at']    ?? '',
            $intakeId, // db_id duplicate for easy VLOOKUP
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
            throw new \RuntimeException('Google Sheets API request failed (network error)');
        }

        $json = json_decode($response, true);
        if (!isset($json['updates'])) {
            throw new \RuntimeException('Google Sheets API unexpected response: ' . $response);
        }
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
