# Booking / SMS deployment runbook

WordPress is the marketing/form frontend. The dashboard is the **only** SMS owner.

## Architecture

- Appointment form → WordPress OTP proxies → dashboard TSMS OTP → one-time booking grant
- Verified form → WordPress REST `/drb/v1/appointment` → dashboard `POST /api/v1/bookings`
- WordPress sends `X-WordPress-Bridge-Secret`
- Dashboard commits the intake, then writes the dedicated Booking Sheet and sends acknowledgement notifications
- The MU plugin `drb-booking-tsms.php` is **disabled** (inert). Do not re-enable a rest_post_dispatch SMS fallback
- Provider failure must not roll back a committed booking
- Credentials stay in dashboard `.env`, never JavaScript

## WordPress origin recovery

If uncached pages return 500 with a parse error in `drb-booking-tsms.php`:

```bash
cd /home/drbastaninejad/public_html/wp-content/mu-plugins
php -l drb-booking-tsms.php
```

Replace the file atomically with the repo copy (the inert 2.0 stub), then reset OPcache/PHP-FPM. Confirm cache-bypassed `/`, `/wp-json/`, `/booking/` return 200 **before** purging CDN cache.

## Required secrets

WordPress `wp-config.php` (not in Git):

```php
define( 'DRB_WORDPRESS_BRIDGE_SECRET', 'SAME_LONG_RANDOM_STRING' );
```

Dashboard `.env`:

```text
WORDPRESS_BRIDGE_SECRET=SAME_LONG_RANDOM_STRING
BOOKING_SMS_ENABLED=1
BOOKING_EMAIL_ENABLED=1
MAIL_FROM=appointments@example.com
BOOKING_SHEET_WRITE_ENABLED=1
BOOKING_SHEET_WEBHOOK_URL=https://script.google.com/macros/s/.../exec
BOOKING_SHEET_SHARED_SECRET=ANOTHER_LONG_RANDOM_STRING
TSMS_USERNAME=...
TSMS_PASSWORD=...
TSMS_FROM=...
```

If `SMS_PROVIDERS` is empty, the chain auto-detects TSMS / Kavenegar / Ghasedak / Faraz from credentials. TSMS credentials are sent in a POST body, never a query string.

## Acceptance

- One booking → one intake → at most one SMS
- Persian booking requires a consumed, mobile-bound one-time verification grant
- Booking Sheet row uses the exact 19-column Windows import header contract
- The browser, SMS and optional email all state that the requested time is not guaranteed and the clinic will call to confirm it
- `sms_status=sent` only after the provider returns a validated message id
- Failed SMS leaves the booking in place with `sms_status=failed`
- Repeat submit within cooldown is idempotent
