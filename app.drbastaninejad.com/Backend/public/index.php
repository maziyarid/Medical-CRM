<?php
/**
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * DEPLOYMENT GATE — DO NOT PUSH THIS FILE TO main UNTIL DEPLOYMENT_GATE.md
 * IS FULLY SATISFIED AND PRODUCT OWNER GRANTS EXPLICIT APPROVAL.
 * See: docs/DEPLOYMENT_GATE.md
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 * public/index.php — Front controller for app.drbastaninejad.com
 *
 * Responsibilities:
 *   1. Load .env (simple KEY=VALUE parser — no external library required)
 *   2. Register PSR-4 autoloader (app/ → App\)
 *   3. Send CORS and security headers
 *   4. Instantiate Router, register routes, dispatch
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// ── 1. .env loader ────────────────────────────────────────────────────────────
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        // Strip inline comments
        if (str_contains($value, ' #')) {
            $value = trim(explode(' #', $value, 2)[0]);
        }
        if (!isset($_ENV[$key]) && !isset($_SERVER[$key])) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// ── 2. PSR-4 autoloader ───────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file     = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// ── 3. Security and CORS headers ─────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');

// CORS — restrict to the same origin family in production
$origin      = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'https://app.drbastaninejad.com',
    'https://dashboard.drbastaninejad.com',
    'https://drbastaninejad.com',
];
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── 4. Route and dispatch ─────────────────────────────────────────────────────
$router = new \App\Core\Router();
require BASE_PATH . '/config/routes.php';

$method = strtoupper($_SERVER['REQUEST_METHOD']);
$path   = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$router->dispatch($method, rawurldecode($path));
