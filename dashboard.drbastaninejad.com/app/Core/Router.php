<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Router — lightweight HTTP router for the MΛZ Medical CRM PHP MVC
 *
 * Responsibilities:
 *   1. Accept route registrations from config/routes.*.php files
 *      (get, post, put, patch, delete methods).
 *   2. Match the incoming Request to a registered route, extracting any
 *      named path placeholders ({id}, {uuid}, etc.).
 *   3. Run the middleware chain for the matched route; short-circuit with
 *      the middleware error response if any middleware returns non-null.
 *   4. Instantiate the controller and call the handler method, passing the
 *      fully-enriched Request object.
 *   5. Return the response array from the controller to the caller
 *      (public/index.php serialises it to JSON).
 *   6. Return a 404 array if no route matches, or 405 if the path matches
 *      but not the HTTP verb.
 *
 * Middleware contract:
 *   - A middleware is registered as either a class-string (instantiated with
 *     new $class()) or a callable that returns a middleware instance.
 *   - Middleware must have a handle(Request): ?array method.
 *   - Returning null means "pass through"; returning an array short-circuits
 *     the chain and that array becomes the HTTP response.
 *
 * Route placeholders:
 *   - Syntax: /api/v1/patients/{id}
 *   - Extracted values are placed in $request->params['id'].
 *   - Placeholders match one or more non-slash characters ([^/]+).
 */
final class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, names:list<string>, handler:array, middleware:list<mixed>}> */
    private array $routes = [];

    // -------------------------------------------------------------------------
    // Route registration
    // -------------------------------------------------------------------------

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, array $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    // -------------------------------------------------------------------------
    // Dispatch
    // -------------------------------------------------------------------------

    /**
     * Match the request and return a response array.
     * The caller (index.php) is responsible for encoding and sending the array.
     *
     * @return array{ok:bool, status:int, data:mixed, errors:mixed, meta:mixed}
     */
    public function dispatch(Request $req): array
    {
        $method  = $req->method;
        $path    = $req->path;

        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $pathMatched = true;

            if ($route['method'] !== $method) {
                // Path matches but verb does not — keep looking in case
                // another route covers the same path with the right verb
                continue;
            }

            // Extract named placeholder values
            $params = [];
            foreach ($route['names'] as $name) {
                $params[$name] = $matches[$name] ?? '';
            }
            $req->params = $params;

            // Run middleware chain
            foreach ($route['middleware'] as $mw) {
                $instance = is_callable($mw) ? $mw() : new $mw();
                $result   = $instance->handle($req);
                if ($result !== null) {
                    // Middleware rejected the request
                    return $result;
                }
            }

            // Call controller
            [$class, $action] = $route['handler'];
            $controller = new $class();
            return $controller->$action($req);
        }

        if ($pathMatched) {
            // Path found but wrong verb
            return [
                'ok'     => false,
                'status' => 405,
                'data'   => null,
                'errors' => [['field' => null, 'message' => 'Method Not Allowed']],
                'meta'   => null,
            ];
        }

        return [
            'ok'     => false,
            'status' => 404,
            'data'   => null,
            'errors' => [['field' => null, 'message' => 'مسیر یافت نشد']],
            'meta'   => null,
        ];
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        // Normalise path: leading slash, no trailing slash (except root)
        $normalised = '/' . trim($path, '/');
        if ($normalised === '') {
            $normalised = '/';
        }

        // Convert {name} placeholders to named regex capture groups.
        // We must split the path BEFORE calling preg_quote so that the curly
        // braces in {name} are not escaped before we can match them.
        $names  = [];
        $parts  = preg_split('/(\{[a-zA-Z_][a-zA-Z0-9_]*\})/', $normalised, -1, PREG_SPLIT_DELIM_CAPTURE);
        $regex  = '';
        foreach ($parts ?? [] as $part) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $part, $m)) {
                $names[] = $m[1];
                $regex  .= '(?P<' . $m[1] . '>[^/]+)';
            } else {
                $regex .= preg_quote($part, '#');
            }
        }

        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $normalised,
            'regex'      => '#^' . $regex . '$#u',
            'names'      => $names,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }
}
