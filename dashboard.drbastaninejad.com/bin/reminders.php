<?php
declare(strict_types=1);

// CLI-only appointment reminder worker. Intended for cron every 1–5 minutes.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

define('BASE_PATH', dirname(__DIR__));

// Same minimal .env semantics as public/index.php, without booting HTTP routing.
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
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
    $sent = (new App\Services\ReminderService())->sendDue();
    fwrite(STDOUT, "reminders_sent={$sent}\n");
    exit(0);
} catch (Throwable $e) {
    error_log('[reminders.php] fatal: ' . $e->getMessage());
    fwrite(STDERR, "reminder worker failed\n");
    exit(1);
}
