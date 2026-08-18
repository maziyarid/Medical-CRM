<?php
declare(strict_types=1);

namespace App\Core;

/**
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * DEPLOYMENT GATE — DO NOT PUSH THIS FILE TO main UNTIL DEPLOYMENT_GATE.md
 * IS FULLY SATISFIED AND PRODUCT OWNER GRANTS EXPLICIT APPROVAL.
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 * Router — lightweight HTTP router for app.drbastaninejad.com
 *
 * Supports GET, POST, PATCH, DELETE with named URL placeholders ({id}, {uuid}).
 * Optional middleware array per route — each middleware must implement handle().
 *
 * Example:
 *   $router->post('/api/v1/intakes', [IntakeController::class, 'store']);
 *   $router->get('/api/v1/patients/{uuid}', [PatientController::class, 'show'],
 *       [AuthMiddleware::class]);
 */
final class Router
{
    /** @var array<string, array{pattern: string, handler: array{0: string, 1: string}, middleware: string[]}> */
    private array $routes = [];

    // -------------------------------------------------------------------------
    // Route registration
    // -------------------------------------------------------------------------

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->register('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->register('POST', $path, $handler, $middleware);
    }

    public function patch(string $path, array $handler, array $middleware = []): void
    {
        $this->register('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, array $handler, array $middleware = []): void
    {
        $this->register('DELETE', $path, $handler, $middleware);
    }

    // -------------------------------------------------------------------------
    // Dispatch
    // -------------------------------------------------------------------------

    public function dispatch(string $method, string $uri): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $params = $this->match($route['pattern'], $uri);
            if ($params === null) {
                continue;
            }

            // Run middleware chain
            foreach ($route['middleware'] as $middlewareClass) {
                if (is_callable($middlewareClass)) {
                    $middlewareClass()->handle();
                } else {
                    (new $middlewareClass())->handle();
                }
            }

            // Invoke handler
            [$controllerClass, $action] = $route['handler'];
            $controller = new $controllerClass();
            $controller->$action(...array_values($params));
            return;
        }

        // 404
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => ['code' => 'NOT_FOUND', 'message' => 'مسیر مورد نظر یافت نشد'],
        ], JSON_UNESCAPED_UNICODE);
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function register(string $method, string $path, array $handler, array $middleware): void
    {
        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $path,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * Match a URI against a pattern with {placeholder} segments.
     * Returns assoc array of captured values, or null on no match.
     *
     * @return array<string, string>|null
     */
    private function match(string $pattern, string $uri): ?array
    {
        $regex  = preg_replace('/\{([a-z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex  = '#^' . $regex . '$#u';
        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }
        // Filter out integer-keyed captures, keep named only
        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }
}
