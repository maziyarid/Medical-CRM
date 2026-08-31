<?php

use App\Controllers\BookingController;

/** @var App\Core\Router $router */
$router->post('/api/v1/bookings', [BookingController::class, 'store']);
$router->post('/api/v1/bookings/otp/send', [BookingController::class, 'sendOtp']);
$router->post('/api/v1/bookings/otp/verify', [BookingController::class, 'verifyOtp']);
$router->get('/api/v1/bookings/stats', [BookingController::class, 'stats']);
