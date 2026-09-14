<?php
declare(strict_types=1);

// CLI-only Calendar/booking reconciliation worker. Run every 1–5 minutes.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

define('BASE_PATH', dirname(__DIR__));

$envFile = is_file('/home/drbastaninejad/.dashboard.env')
    ? '/home/drbastaninejad/.dashboard.env'
    : BASE_PATH . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 4));
    $file = BASE_PATH . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

date_default_timezone_set('UTC');

try {
    $clinicId = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
    (new App\Services\AppointmentSchemaBootstrapService())->ensure();
    $released = (new App\Services\AppointmentBookingService())->releaseExpiredHolds($clinicId);

    $calendar = ['status' => 'skipped', 'push' => null, 'pull' => null];
    $bridgeConfigured = trim((string)($_ENV['GOOGLE_BRIDGE_URL'] ?? '')) !== ''
        && trim((string)($_ENV['GOOGLE_BRIDGE_TOKEN'] ?? '')) !== '';
    $oauthConfigured = trim((string)($_ENV['GOOGLE_CALENDAR_CLIENT_ID'] ?? '')) !== ''
        && trim((string)($_ENV['GOOGLE_CALENDAR_CLIENT_SECRET'] ?? '')) !== ''
        && trim((string)($_ENV['GOOGLE_CALENDAR_REFRESH_TOKEN'] ?? '')) !== '';
    $calendarConfigured = trim((string)($_ENV['GOOGLE_CALENDAR_ID'] ?? '')) !== ''
        && ($bridgeConfigured || $oauthConfigured);
    if ($calendarConfigured) {
        try {
            $sync = new App\Services\CalendarSyncService();
            $calendar['push'] = $sync->pushOutbox((int)($_ENV['CALENDAR_SYNC_BATCH_SIZE'] ?? 25));
            $calendar['pull'] = $sync->pullChanges($clinicId);
            $calendar['status'] = 'ok';
        } catch (Throwable $e) {
            $calendar['status'] = 'error';
            $calendar['error'] = mb_substr($e->getMessage(), 0, 300);
            error_log('[calendar-sync.php] Calendar integration failed: ' . $e->getMessage());
        }
    }

    $sheet = ['status' => 'skipped', 'summary' => null];
    try {
        $sheet['summary'] = (new App\Services\ScheduledVisitSheetService())->syncPending(
            (int)($_ENV['BOOKING_SHEET_SYNC_BATCH_SIZE'] ?? 25)
        );
        $sheet['status'] = (($_ENV['BOOKING_SHEET_WRITE_ENABLED'] ?? '0') === '1') ? 'ok' : 'skipped';
    } catch (Throwable $e) {
        $sheet['status'] = 'error';
        $sheet['error'] = mb_substr($e->getMessage(), 0, 300);
        error_log('[calendar-sync.php] ScheduledVisits sync failed: ' . $e->getMessage());
    }

    fwrite(STDOUT, json_encode([
        'expired_holds_released' => $released,
        'calendar' => $calendar,
        'scheduled_visits_sheet' => $sheet,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(0);
} catch (Throwable $e) {
    error_log('[calendar-sync.php] fatal: ' . $e->getMessage());
    fwrite(STDERR, "calendar sync worker failed\n");
    exit(1);
}