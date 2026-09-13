<?php

use App\Controllers\AppointmentBookingController;
use App\Controllers\BookingController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

// Existing WordPress intake bridge.
$router->post('/api/v1/bookings', [BookingController::class, 'store']);
$router->post('/api/v1/bookings/otp/send', [BookingController::class, 'sendOtp']);
$router->post('/api/v1/bookings/otp/verify', [BookingController::class, 'verifyOtp']);
$router->get('/api/v1/bookings/stats', [BookingController::class, 'stats']);

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
$router->post('/api/v1/admin/appointment-bookings/{id}/reconcile-slot', [AppointmentBookingController::class, 'reconcilePaidSlot'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.payments.reconcile'),
]);
$router->post('/api/v1/admin/appointment-bookings/{id}/confirm', [AppointmentBookingController::class, 'confirm'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.manage'),
]);
$router->post('/api/v1/admin/appointment-open-days', [AppointmentBookingController::class, 'saveOpenDay'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.availability.manage'),
]);

// Role-specific dashboard read/actions. Reception can work the booking/follow-up
// queue; payment reconciliation and integration configuration remain admin-only.
$router->get('/api/v1/admin/appointment-bookings', [AppointmentBookingController::class, 'adminBookings'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.manage'),
]);
$router->get('/api/v1/admin/appointment-open-days', [AppointmentBookingController::class, 'adminOpenDays'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.manage'),
]);
$router->get('/api/v1/admin/appointment-payments', [AppointmentBookingController::class, 'adminPayments'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.payments.reconcile'),
]);
$router->get('/api/v1/admin/appointment-integrations', [AppointmentBookingController::class, 'integrationStatus'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.availability.manage'),
]);
$router->patch('/api/v1/admin/appointment-integrations', [AppointmentBookingController::class, 'updateIntegrationSettings'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.integrations.manage'),
]);
$router->post('/api/v1/admin/appointment-bookings/{id}/followup-complete', [AppointmentBookingController::class, 'completeFollowup'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.manage'),
]);
$router->post('/api/v1/admin/appointment-payments/{id}/reconcile', [AppointmentBookingController::class, 'reconcilePayment'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.payments.reconcile'),
]);
$router->post('/api/v1/admin/appointment-bookings/{id}/sheet-sync', [AppointmentBookingController::class, 'syncScheduledVisit'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('booking.payments.reconcile'),
]);
