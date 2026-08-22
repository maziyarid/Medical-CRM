<?php

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\OtpController;
use App\Controllers\RecoveryController;
use App\Middleware\AuthMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/v1/health', [HealthController::class, 'show']);

$router->post('/api/v1/auth/otp/send', [OtpController::class, 'send']);
$router->post('/api/v1/auth/otp/verify', [OtpController::class, 'verify']);
$router->post('/api/v1/auth/recovery/request', [RecoveryController::class, 'request']);
$router->post('/api/v1/auth/recovery/verify', [RecoveryController::class, 'verify']);
$router->post('/api/v1/auth/recovery/password', [RecoveryController::class, 'setPassword']);
$router->get('/api/v1/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);
$router->post('/api/v1/auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
$router->post('/api/v1/auth/logout-all', [AuthController::class, 'logoutAll'], [AuthMiddleware::class]);
