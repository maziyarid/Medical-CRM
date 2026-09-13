<?php

use App\Controllers\AppointmentBookingController;
use App\Controllers\BookingController;
use App\Controllers\WordPressAppointmentBridgeController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

// Existing WordPress intake bridge.
$router->post('/api/v1/bookings', [BookingController::class, 'store']);
$router->post('/api/v1/bookings/otp/send', [BookingController::class, 'sendOtp']);
$router->post('/api/v1/bookings/otp/verify', [BookingController::class, 'verifyOtp']);
$router->get('/api/v1/bookings/stats', [BookingController::class, 'stats']);

// Trusted WordPress bridge for public slot checkout and WordPress-admin setup.
// These methods authenticate X-WordPress-Bridge-Secret internally; the shared
// secret never reaches the patient's browser.
$router->get('/api/v1/bookings/v2/status', [WordPressAppointmentBridgeController::class, 'status']);
$router->post('/api/v1/bookings/v2/setup', [WordPressAppointmentBridgeController::class, 'setup']);
$router->get('/api/v1/bookings/v2/open-days', [WordPressAppointmentBridgeController::class, 'openDays']);
$router->post('/api/v1/bookings/v2/open-days', [WordPressAppointmentBridgeController::class, 'saveOpenDay']);
$router->post('/api/v1/bookings/v2/checkout', [WordPressAppointmentBridgeController::class, 'checkout']);

// Appointment-system v2: public capacity view, authenticated patient hold/payment.
$router->get('/api/v1/appointment-bookings/availability', [AppointmentBookingController::class, 'availability']);
$router->post('/api/v1/appointment-bookings/holds', [AppointmentBookingController::class, 'hold'], [
    AuthMiddleware::class,
]);
$router->post('/api/v1/appointment-bookings/{id}/payment', [AppointmentBookingController::class, 'startPayment'], [
    AuthMiddleware::class,
]);
$router->get('/api/v1/appointment-bookings/payment/callback/{gateway}', [AppointmentBookingController::class, 'paymentCallback']);

// Reception/admin workflow. Free bookings create the confirmed appointment immediately;
// paid bookings create a held slot plus a gateway URL and retain receptionist attribution.
$router->post('/api/v1/admin/appointment-bookings', [AppointmentBookingController::class, 'adminCreate'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.manage'),
]);
$router->post('/api/v1/admin/appointment-bookings/{id}/confirm', [AppointmentBookingController::class, 'confirm'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.manage'),
]);
$router->post('/api/v1/admin/appointment-open-days', [AppointmentBookingController::class, 'saveOpenDay'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.availability.manage'),
]);
