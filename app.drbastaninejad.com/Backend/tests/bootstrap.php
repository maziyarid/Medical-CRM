<?php
declare(strict_types=1);

/**
 * PHPUnit bootstrap for app.drbastaninejad.com Backend tests.
 *
 * 1. Registers PSR-4 autoloader for App\ (maps to app/) and Tests\ (maps to tests/)
 * 2. Loads .env.testing if it exists (overrides .env values for the test run)
 * 3. Bootstraps the Database singleton with test credentials
 */

// ── Autoloader ────────────────────────────────────────────────────────────────
// Use Composer autoloader if available (preferred); fall back to manual PSR-4.
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    spl_autoload_register(function (string $class): void {
        $map = [
            'App\\'   => __DIR__ . '/../app/',
            'Tests\\' => __DIR__ . '/',
        ];
        foreach ($map as $prefix => $base) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }
            $relative = substr($class, strlen($prefix));
            $file     = $base . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    });
}

// ── .env.testing loader ───────────────────────────────────────────────────────
$envTesting = __DIR__ . '/../.env.testing';
if (file_exists($envTesting)) {
    foreach (file($envTesting) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value]  = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}
