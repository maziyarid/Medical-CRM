<?php

use App\Controllers\OtpController;
use App\Controllers\RecoveryController;

/** @var App\Core\Router $router */

$router->post('/api/v1/auth/otp/send', [OtpController::class, 'send']);
$router->post('/api/v1/auth/otp/verify', [OtpController::class, 'verify']);
$router->post('/api/v1/auth/recovery/request', [RecoveryController::class, 'request']);
$router->post('/api/v1/auth/recovery/verify', [RecoveryController::class, 'verify']);
$router->post('/api/v1/auth/recovery/password', [RecoveryController::class, 'setPassword']);
