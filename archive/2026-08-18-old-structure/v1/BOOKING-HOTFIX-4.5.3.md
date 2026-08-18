# Booking hotfix 4.5.3

This release changes only the booking bridge/diagnostics. Localization content and Persian WordPress content are not changed.

## What changed

- Dashboard booking timeout raised from 12s to a configurable 25s (`DRB_BOOKING_BRIDGE_TIMEOUT`, allowed 10–60).
- TLS verification remains mandatory (`sslverify=true`).
- Booking requests explicitly send the stable submission UUID header as well as the JSON field.
- Transport failures are classified as TLS, DNS, timeout, connection, or generic HTTP transport failure.
- A timeout is treated as ambiguous because the dashboard may have committed the booking before waiting on SMS; a short anti-duplicate lock is kept instead of immediately reopening submission.
- WordPress admin > نوبت و پیامک now shows the dashboard API base, bridge-secret presence, TLS verification state, timeout and the exact admin-only transport diagnostic.
- TSMS secrets are still never stored or displayed in WordPress.

## Immediate production check

Open WordPress admin > **نوبت و پیامک**.

The connection notice must be green. If red, use the diagnostic code:

- `BRIDGE-TLS`: certificate/vhost problem for `dashboard.drbastaninejad.com`; fix the certificate/SNI binding. Do not disable TLS verification.
- `BRIDGE-DNS`: WordPress server cannot resolve the dashboard hostname.
- `BRIDGE-CONNECT`: DNS works but the dashboard web server/upstream is unreachable.
- `BRIDGE-TIMEOUT`: dashboard/provider took too long; check dashboard logs and SMS provider latency. Do not immediately re-submit the same phone.
- `BRIDGE-HTTP-401/403`: bridge secret/header mismatch or authorization rejection.
- `BRIDGE-HTTP-404/405`: dashboard route/document-root/routing mismatch.
- `BRIDGE-HTTP-5xx`: dashboard application/provider failure.

## Required bridge configuration

WordPress `wp-config.php`:

```php
define( 'DRB_WORDPRESS_BRIDGE_SECRET', 'SAME_LONG_RANDOM_STRING' );
```

Dashboard `.env`:

```text
WORDPRESS_BRIDGE_SECRET=SAME_LONG_RANDOM_STRING
```

The values must match exactly.

## TSMS configuration

The supplied project history specifies that TSMS is owned by the dashboard `SmsProviderChain`, after the booking DB commit. The dashboard `.env` must contain the TSMS/SMS credentials, sender/line, provider URL and provider selection expected by that dashboard source. These values must not be copied into WordPress or JavaScript.

The uploaded files in this conversation do not include the live dashboard `.env` or `SmsProviderChain` source, so secret values and exact environment variable names cannot be verified from the theme package alone. Do not invent or rename them; compare the live `.env` against the dashboard source that reads them.

A provider failure must result in a saved booking with `sms_status=failed`; it must not roll back the booking.
