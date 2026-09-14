<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Super-admin-only writer for appointment integration configuration.
 * Secrets remain in the private deployment-external runtime env and are never returned by the API.
 */
final class AppointmentIntegrationSettingsService
{
    /** @param array<string,mixed> $input */
    public function update(array $input): array
    {
        $envFile = is_file('/home/drbastaninejad/.dashboard.env')
            ? '/home/drbastaninejad/.dashboard.env'
            : dirname(__DIR__, 2) . '/.env';
        if (!is_file($envFile) || !is_readable($envFile) || !is_writable($envFile)) {
            throw new RuntimeException('appointment environment file is not writable');
        }

        $updates = [];
        if (array_key_exists('deposit_rials', $input)) {
            $amount = (int)$input['deposit_rials'];
            if ($amount < 10000 || $amount > 2000000000) {
                throw new RuntimeException('invalid booking deposit');
            }
            $updates['BOOKING_APPOINTMENT_DEPOSIT_RIALS'] = (string)$amount;
        }
        if (array_key_exists('payment_gateways', $input)) {
            $raw = is_array($input['payment_gateways']) ? $input['payment_gateways'] : explode(',', (string)$input['payment_gateways']);
            $gateways = array_values(array_unique(array_intersect(['zarinpal','vandar'], array_map(static fn($v) => strtolower(trim((string)$v)), $raw))));
            if (!$gateways) {
                throw new RuntimeException('at least one payment gateway is required');
            }
            $updates['BOOKING_PAYMENT_GATEWAYS'] = implode(',', $gateways);
        }

        $this->secret($updates, $input, 'zarinpal_merchant_id', 'ZARINPAL_MERCHANT_ID', 10, 120);
        $this->secret($updates, $input, 'vandar_api_token', 'VANDAR_API_TOKEN', 10, 300);
        if (!array_key_exists('vandar_api_token', $input)) {
            $this->secret($updates, $input, 'vandar_api_key', 'VANDAR_API_TOKEN', 10, 300);
        }
        $this->secret($updates, $input, 'google_calendar_id', 'GOOGLE_CALENDAR_ID', 3, 300);
        $this->secret($updates, $input, 'google_client_id', 'GOOGLE_CALENDAR_CLIENT_ID', 10, 300);
        $this->secret($updates, $input, 'google_client_secret', 'GOOGLE_CALENDAR_CLIENT_SECRET', 8, 300);
        $this->secret($updates, $input, 'google_refresh_token', 'GOOGLE_CALENDAR_REFRESH_TOKEN', 10, 1000);
        $this->secret($updates, $input, 'sheet_shared_secret', 'BOOKING_SHEET_SHARED_SECRET', 16, 500);

        if (array_key_exists('sheet_webhook_url', $input) && trim((string)$input['sheet_webhook_url']) !== '') {
            $url = trim((string)$input['sheet_webhook_url']);
            $parts = parse_url($url);
            if (($parts['scheme'] ?? '') !== 'https' || strtolower((string)($parts['host'] ?? '')) !== 'script.google.com') {
                throw new RuntimeException('invalid booking sheet webhook URL');
            }
            $updates['BOOKING_SHEET_WEBHOOK_URL'] = $url;
            $updates['BOOKING_SHEET_WRITE_ENABLED'] = '1';
        }

        if (!$updates) {
            return ['updated' => false];
        }
        $updates['BOOKING_VISIT_SHEET_NAME'] = 'ScheduledVisits';

        $contents = (string)file_get_contents($envFile);
        $backup = dirname($envFile) . '/.env.appointment-backup-' . gmdate('Ymd-His');
        if (!@copy($envFile, $backup)) {
            throw new RuntimeException('failed to back up appointment environment');
        }
        @chmod($backup, 0600);

        foreach ($updates as $key => $value) {
            $line = $key . '=' . $this->envValue($value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $line, $contents, 1) ?? $contents;
            } else {
                $contents = rtrim($contents) . PHP_EOL . $line . PHP_EOL;
            }
        }
        $tmp = $envFile . '.tmp-' . bin2hex(random_bytes(4));
        if (file_put_contents($tmp, $contents, LOCK_EX) === false) {
            throw new RuntimeException('failed to write appointment environment');
        }
        @chmod($tmp, 0600);
        if (!@rename($tmp, $envFile)) {
            @unlink($tmp);
            throw new RuntimeException('failed to activate appointment environment');
        }
        @chmod($envFile, 0600);
        foreach ($updates as $key => $value) {
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
        return ['updated' => true, 'fields' => array_values(array_filter(array_keys($updates), static fn($k) => !str_contains($k, 'SECRET') && !str_contains($k, 'TOKEN') && !str_contains($k, 'KEY') && $k !== 'ZARINPAL_MERCHANT_ID'))];
    }

    /** @param array<string,string> $updates @param array<string,mixed> $input */
    private function secret(array &$updates, array $input, string $inputKey, string $envKey, int $min, int $max): void
    {
        if (!array_key_exists($inputKey, $input)) {
            return;
        }
        $value = trim((string)$input[$inputKey]);
        if ($value === '') {
            return;
        }
        if (strlen($value) < $min || strlen($value) > $max || preg_match('/[\r\n]/', $value)) {
            throw new RuntimeException('invalid integration credential');
        }
        $updates[$envKey] = $value;
    }

    private function envValue(string $value): string
    {
        if (preg_match('/^[A-Za-z0-9_.,:\/@+\-=]+$/', $value)) {
            return $value;
        }
        return '"' . addcslashes($value, "\\\"") . '"';
    }
}
