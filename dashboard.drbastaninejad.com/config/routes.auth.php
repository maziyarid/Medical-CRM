<?php
// Authentication routes — public (no auth required)

use App\Controllers\OtpController;

/** @var App\Core\Router $router */

$router->post('/api/v1/auth/otp/send',   [OtpController::class, 'send']);
$router->post('/api/v1/auth/otp/verify', [OtpController::class, 'verify']);
