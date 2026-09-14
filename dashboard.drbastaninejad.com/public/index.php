<?php
declare(strict_types=1);

/**
 * public/index.php — MΛZ Medical CRM front-controller
 *
 * This is the single PHP entry point for all API requests routed here by
 * .htaccess / LiteSpeed rewrite rules.
 *
 * Responsibility:
 *   1. Define BASE_PATH (the project root one level above public/).
 *   2. Load environment variables from .env (simple key=value parser — no
 *      Composer dependency; compatible with cPanel PHP without Composer).
 *   3. Register the PSR-4 autoloader for the App\ namespace.
 *   4. Set global PHP settings (timezone, error reporting, JSON header).
 *   5. Build the Router, register all route files, dispatch the request.
 *   6. Serialise the controller response to JSON and send it.
 *
 * All errors are caught at the top level and returned as a standard JSON
 * error envelope so the client always gets a parseable response.
 *
 * Deployment note:
 *   Place the project root (the directory containing app/, config/, etc.)
 *   OUTSIDE the web-root.  public/ is the web-root.  .env must live in the
 *   project root and must be chmod 600.
 *
 * cPanel note:
 *   Set the document root for dashboard.drbastaninejad.com to point at
 *   this public/ directory, not the project root.
 */

// ---------------------------------------------------------------------------
// 1. Paths
// ---------------------------------------------------------------------------
define('BASE_PATH', dirname(__DIR__));  // one level up from public/

// ---------------------------------------------------------------------------
// 2. Environment loader — prefer deployment-external runtime configuration.
//    Supports: KEY=value, KEY="value", KEY='value', # comments, blank lines
//    Variables are written to $_ENV and putenv() so they are accessible from
//    both getenv() and $_ENV throughout the application.
// ---------------------------------------------------------------------------
(static function (): void {
    $envFile = is_file('/home/drbastaninejad/.dashboard.env')
        ? '/home/drbastaninejad/.dashboard.env'
        : BASE_PATH . '/.env';
    if (!is_file($envFile)) {
        return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }

        $key   = trim(substr($line, 0, $eqPos));
        $value = trim(substr($line, $eqPos + 1));

        if (strlen($value) >= 2 &&
            (($value[0] === '"' && str_ends_with($value, '"')) ||
             ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
})();

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 4));
    $file     = BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $relative . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

date_default_timezone_set('UTC');

$appEnv = $_ENV['APP_ENV'] ?? 'production';
if ($appEnv !== 'production') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cross-Origin-Resource-Policy: same-site');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=15552000; includeSubDomains');
}

$allowedOrigins = [
    'https://app.drbastaninejad.com',
    'https://dashboard.drbastaninejad.com',
    'https://drbastaninejad.com',
    'https://www.drbastaninejad.com',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
} elseif ($appEnv !== 'production') {
    header('Access-Control-Allow-Origin: *');
    header('Vary: Origin');
} else {
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Intake-Bridge-Secret, X-WordPress-Bridge-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $router = new App\Core\Router();

    foreach ([
        'routes.auth',
        'routes.dashboard',
        'routes.intake',
        'routes.bookings',
        'routes.patients',
        'routes.appointments',
        'routes.emr',
        'routes.billing',
        'routes.tasks',
        'routes.analytics',
        'routes.settings',
        'routes.staff',
    ] as $routeFile) {
        $path = BASE_PATH . '/config/' . $routeFile . '.php';
        if (is_file($path)) {
            require $path;
        }
    }

    $request  = App\Core\Request::fromGlobals();
    $response = $router->dispatch($request);

} catch (\Throwable $e) {
    error_log('[index.php] Uncaught exception: ' . $e->getMessage()
        . ' in ' . $e->getFile() . ':' . $e->getLine());

    $message = match ($e->getMessage()) {
        'Request body too large' => 'حجم درخواست بیش از حد مجاز است.',
        'Malformed JSON' => 'بدنه درخواست JSON نامعتبر است.',
        default => 'خطای داخلی سرور',
    };
    $status = match ($e->getMessage()) {
        'Request body too large' => 413,
        'Malformed JSON' => 400,
        default => 500,
    };
    $response = [
        'ok'     => false,
        'status' => $status,
        'data'   => null,
        'errors' => [['field' => null, 'message' => $message]],
        'meta'   => null,
    ];
}

$httpStatus = (int)($response['status'] ?? 200);
$redirect = isset($response['_redirect']) ? trim((string)$response['_redirect']) : '';
if ($redirect !== '' && preg_match('#^https://(?:www\.)?drbastaninejad\.com(?:/|$)#i', $redirect)) {
    header('Location: ' . $redirect, true, 303);
    exit;
}
unset($response['_redirect']);
http_response_code($httpStatus);

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);