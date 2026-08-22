# MΛZ Medical CRM

Multi-host clinic platform for Dr. Shahin Bastaninejad.

## Canonical layout

| Host | Role | Source of truth |
|---|---|---|
| `drbastaninejad.com` | Public WordPress marketing + booking form | `wp-content/themes/drbastaninejad-theme`, `wp-content/mu-plugins` |
| `dashboard.drbastaninejad.com` | JSON API only | `dashboard.drbastaninejad.com/` (document root = `public/`) |
| `app.drbastaninejad.com` | Staff + patient UI | `app.drbastaninejad.com/Frontend/` |

WordPress posts bookings to `POST /api/v1/bookings`. The dashboard is the **single SMS owner**. The MU plugin `drb-booking-tsms.php` is intentionally inert so a corrupted fallback cannot take WordPress down.

## Local / staging rules

- Copy `dashboard.drbastaninejad.com/.env.example` to `.env` on the server. Never commit secrets.
- Copy `wp-config-sample.php` / keep `wp-config.php` **outside Git**.
- Runtime storage, logs, backups, and ZIPs must not be tracked.
- Run `database/migrations` in order on an empty database, then 017 to normalize mixed histories.

## Booking / SMS recovery

1. Deploy the replacement MU plugin (this repo). Uncached `/`, `/wp-json/`, `/booking/` must return 200.
2. Apply migration `017_normalize_auth_otp_and_sms.sql`.
3. Confirm dashboard `.env` has `WORDPRESS_BRIDGE_SECRET` matching WordPress `DRB_WORDPRESS_BRIDGE_SECRET`, plus TSMS (or other) credentials.
4. Leave `BOOKING_SMS_ENABLED=1`. Providers auto-detect if `SMS_PROVIDERS` is empty.
5. One booking must create one intake and at most one confirmation SMS.

## Auth

- `POST /api/v1/auth/logout` revokes the current Bearer token.
- OTP verification is attempt-limited. Send is limited per mobile, IP, and globally.

## Staff UI

Use `https://app.drbastaninejad.com/Frontend/pages/auth/login.html`. The dashboard root is a `noindex` status page, not a second CRM client.
