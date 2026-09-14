<?php
/**
 * Production WordPress bootstrap.
 * Secrets and environment-specific settings live outside the Git/deployment tree.
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
$localConfig = '/home/drbastaninejad/.wp-config-local.php';
if (!is_readable($localConfig)) {
    http_response_code(500);
    exit('WordPress configuration is unavailable.');
}
require $localConfig;
