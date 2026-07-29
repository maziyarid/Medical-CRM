<?php
declare(strict_types=1);

namespace App\Services;

/**
 * GoogleSheetsService — canonical implementation for app.drbastaninejad.com
 *
 * DECISION (2026-07-29, Blackbox AI — see PROGRESS_LOG.md):
 *   This is the ONE canonical Google Sheets client for the platform.
 *   It mirrors dashboard.drbastaninejad.com/app/Services/GoogleSheetsService.php
 *   but extends the column mapping to match the frozen SmartFormat column order
 *   defined in UNIFIED_MASTER_PLAN.md §4 (the intake transition contract).
 *
 *   The "app_private/src/SheetClient.php" referenced in the original brief was
 *   NOT found in the git repository. If it surfaces locally, compare against this
 *   file; retire the one with less capability (no APCu token cache, no retry
 *   error recording, narrower column mapping).
 *
 * COLUMNS — SmartFormat frozen order (UNIFIED_MASTER_PLAN.md §4):
 *   A: FirstName  B: LastName   C: FatherName   D: TavalodDay  E: TavalodMonth
 *   F: TavalodYear  G: HomeTel  H: Mobile       I: Mobile2     J: CodeAshnaei
 *   K: CodeBimeh    L: CodeMeli  M: CodeJob     N: HomeAd      O: Description
 *   P: IsTransfer   Q: drugs    R: difficult    S: morefmob
 *   T: Email (application field, nullable)
 *   U: VisitReason (application field, nullable)
 *   V: submission_uuid (idempotency reference)
 *   W: intake_db_id  (MariaDB cross-reference)
 *
 * Returns string: 'ok' | 'skipped' | 'failed'
 * Never throws — caller records the returned status in sheets_sync_status column.
 */
final class GoogleSheetsService
{
    private string $spreadsheetId;
    private string $tabName;
    private string $saKeyPath;

    public function __construct()
    {
        $this->spreadsheetId = $_ENV['GOOGLE_SHEET_ID']      ?? '';
        $this->tabName       = $_ENV['GOOGLE_SHEET_TAB']     ?? 'Intakes';
        $this->saKeyPath     = $_ENV['GOOGLE_SA_KEY_PATH']   ?? '';
    }

    /**
     * Append one intake row to the Google Sheet.
     *
     * @param int   $intakeId  MariaDB auto-increment id for cross-reference
     * @param array $data      Normalised intake fields (snake_case)
     */
    public function appendIntake(int $intakeId, array $data): string
    {
        if ($this->spreadsheetId === '' || $this->saKeyPath === '') {
            return 'skipped'; // dev / test — Google Sheets not configured
        }

        try {
            $token = $this->getAccessToken();
        } catch (\Throwable $e) {
            error_log('[GoogleSheetsService] token error: ' . $e->getMessage());
            return 'failed';
        }

        // Parse birth_date_jalali (YYYY/MM/DD) into separate day/month/year components
        $jalali    = $data['birth_date_jalali'] ?? ($data['birth_date'] ?? '');
        $jalaliParts = explode('/', $jalali);

        $row = [
            $data['first_name']       ?? '',  // A: FirstName
            $data['last_name']        ?? '',  // B: LastName
            $data['father_name']      ?? '',  // C: FatherName
            $jalaliParts[2]           ?? '',  // D: TavalodDay
            $jalaliParts[1]           ?? '',  // E: TavalodMonth
            $jalaliParts[0]           ?? '',  // F: TavalodYear
            $data['home_tel']         ?? '',  // G: HomeTel
            $data['mobile']           ?? '',  // H: Mobile
            '',                               // I: Mobile2 (not collected; reserved)
            '',                               // J: CodeAshnaei
            $data['insurance_number'] ?? '',  // K: CodeBimeh
            $data['national_id']      ?? '',  // L: CodeMeli
            '',                               // M: CodeJob
            $data['home_address']     ?? '',  // N: HomeAd
            $data['chief_complaint']  ?? '',  // O: Description
            (string)($data['is_transfer'] ?? '0'), // P: IsTransfer
            '',                               // Q: drugs (not in intake v1)
            '',                               // R: difficult
            '',                               // S: morefmob
            $data['email']            ?? '',  // T: Email
            $data['visit_reason']     ?? '',  // U: VisitReason
            $data['submission_uuid']  ?? '',  // V: submission_uuid
            (string)$intakeId,                // W: intake_db_id
        ];

        $range   = urlencode("{$this->tabName}!A:W");
        $url     = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheetId}/values/{$range}:append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS";
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
            error_log('[GoogleSheetsService] network error appending intake ' . $intakeId);
            return 'failed';
        }

        $json = json_decode($response, true);
        if (!isset($json['updates'])) {
            error_log('[GoogleSheetsService] unexpected response: ' . $response);
            return 'failed';
        }

        return 'ok';
    }

    // -------------------------------------------------------------------------
    // OAuth2 — Service Account JWT bearer flow (no external library)
    // -------------------------------------------------------------------------

    private function getAccessToken(): string
    {
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch('gsheets_token_app');
            if ($cached) {
                return $cached;
            }
        }

        $key = json_decode((string)file_get_contents($this->saKeyPath), true);
        if (!$key) {
            throw new \RuntimeException('Cannot read Google Service Account key: ' . $this->saKeyPath);
        }

        $now     = time();
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

        $jwt  = "$header.$payload." . $this->b64url($signature);
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
            apcu_store('gsheets_token_app', $json['access_token'], 3500);
        }

        return $json['access_token'];
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
