<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Request — HTTP request value object
 *
 * Populated once by the Router before it calls any controller or middleware.
 * Carries the parsed body, query string, headers, route params, and —
 * after AuthMiddleware runs — the authenticated user context.
 *
 * All property values are read from PHP superglobals + php://input once and
 * frozen for the lifetime of the request.  Controllers and middleware must
 * never read superglobals directly.
 *
 * Properties:
 *   string   $method      Normalised HTTP verb (GET, POST, PATCH, PUT, DELETE)
 *   string   $path        URL path without query string, with leading slash
 *   array    $body        Decoded JSON body (POST / PATCH / PUT) or []
 *   array    $query       Decoded query-string params
 *   array    $headers     All HTTP headers, keys lowercased
 *   array    $params      Route placeholder values (e.g. {id} => '42')
 *   ?array   $user        Set by AuthMiddleware on authenticated requests
 */
final class Request
{
    public string  $method;
    public string  $path;
    public array   $body;
    public array   $query;
    public array   $headers;
    public array   $params  = [];
    public ?array  $user    = null;

    /**
     * Build a Request from current PHP superglobals.
     * Called exactly once per request in public/index.php.
     */
    public static function fromGlobals(): self
    {
        $req = new self();

        // Verb
        $req->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Path — strip query string, normalise trailing slash
        $uri       = $_SERVER['REQUEST_URI'] ?? '/';
        $path      = parse_url($uri, PHP_URL_PATH) ?? '/';
        $req->path = '/' . trim($path, '/');
        if ($req->path === '') {
            $req->path = '/';
        }

        // Query string
        parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
        $req->query = $qs;

        // Headers — collect all HTTP_* server vars + CONTENT_TYPE / CONTENT_LENGTH
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        // These two are not prefixed with HTTP_ by PHP
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }
        $req->headers = $headers;

        // Body — parse JSON for write verbs; fall back to [] for GET / HEAD
        $req->body = [];
        if (in_array($req->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $raw = file_get_contents('php://input');
            if ($raw !== false && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $req->body = $decoded;
                }
            }
        }

        return $req;
    }
}
