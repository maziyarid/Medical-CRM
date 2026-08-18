<?php
/** Boot the canonical WordPress installation while preserving this language host. */
declare(strict_types=1);

$wordpress = dirname(__DIR__) . '/public_html/wp-blog-header.php';
if (!is_file($wordpress)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit('WordPress bootstrap not found. Point this subdomain document root to public_html, or adjust the path in index.php.');
}
define('WP_USE_THEMES', true);
require $wordpress;
