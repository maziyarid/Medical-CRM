# Booking / SMS deployment runbook

This reflects the booking/SMS instructions from the supplied project history and the 4.6.0 theme contract.

## Architecture

- WordPress is the marketing/form frontend, not the clinical vault.
- Appointment form → WordPress REST endpoint → server-to-server dashboard `/api/v1/bookings`.
- WordPress sends `X-WordPress-Bridge-Secret`.
- Dashboard commits the booking, then its `SmsProviderChain` / TSMS integration sends the confirmation SMS.
- SMS provider failure must not erase/roll back an already committed booking.
- SMS/provider credentials stay in the dashboard `.env`, never JavaScript and never page content.

## Required secrets

In WordPress `wp-config.php`:

```php
define( 'DRB_WORDPRESS_BRIDGE_SECRET', 'SAME_LONG_RANDOM_STRING' );
```

In the dashboard `.env`:

```text
WORDPRESS_BRIDGE_SECRET=SAME_LONG_RANDOM_STRING
```

The values must match. Keep the existing TSMS/SMS provider keys, sender and provider URL only in the dashboard `.env` using the variable names expected by the dashboard source.

## Dashboard host requirements

The project history requires the dashboard virtual host/document root to point to its `public/` directory, not the repository root. The dashboard TLS certificate must be valid for exactly `dashboard.drbastaninejad.com`.

Do not disable TLS verification as a workaround.

From the server, the historical smoke check was:

```bash
curl -I https://dashboard.drbastaninejad.com/api/v1/auth/otp/send
```

A method-not-allowed response for a GET is evidence that the API route is being reached; a directory listing/404 means the document root/routing is wrong. A certificate hostname error must be fixed in the certificate/vhost, not in theme code.

## SMS provider diagnostics

If an API POST reaches the controller but reports a confirmation-code/SMS error, check the dashboard `.env` provider configuration only:

- TSMS / SMS credentials;
- sender identity/line;
- provider URL;
- provider enablement/selection used by `SmsProviderChain`.

Do not copy those values into WordPress JavaScript.

## Theme-side booking test

For a non-Persian locale:

1. Country is required.
2. Dial code is required and starts at `+98` by default.
3. Email is required.
4. Submit a valid 18–45 test case.
5. For revision rhinoplasty, previous surgery must be at least 24 complete months ago.
6. Confirm the browser receives a booking success response with `bookingId` and `smsStatus`.
7. Confirm the displayed message agrees with `smsStatus` rather than always claiming SMS success.
8. Confirm the SMS arrives.
9. Immediately repeat with the same normalized phone and confirm the duplicate/cooldown path directs the user to patient login.

For Persian, repeat one test and confirm the international-only country/dial/email enhancement does not alter the existing Persian form UI.

## Interpretation

- **Booking saved + `smsStatus=sent/delivered` + SMS received:** complete.
- **Booking saved + `smsStatus=failed`:** theme/booking write succeeded; fix dashboard TSMS/SMS configuration.
- **WordPress gets 503 before a booking ID:** check dashboard TLS, document root, bridge secret and dashboard availability.
- **403 form-session error:** verify the public form nonce/bootstrap and cache is serving the current theme files.
- **409/429 with patient-login URL:** duplicate/cooldown behavior is working as designed.
