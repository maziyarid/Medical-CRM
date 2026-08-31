# Security rotation checklist

The repository is private now, but credentials previously committed or bundled in backups must still be treated as exposed. Private visibility does not invalidate old copies, clones, CI logs, caches, or downloaded archives.

## Rotate before production deployment

- WordPress database username/password and all WordPress authentication salts formerly present in `wp-config.php`.
- Dashboard and `app_private` database credentials from every `.env`, `.env.bak`, or `.env.bak2` copy.
- `WORDPRESS_BRIDGE_SECRET` and `INTAKE_BRIDGE_SECRET`; keep the matching values only in the two server-side environments that need them.
- TSMS username, password, sender/from number credentials, and any fallback Kavenegar, Ghasedak, or FarazSMS keys.
- Google Apps Script `SHARED_SECRET`; update both Script Properties and `BOOKING_SHEET_SHARED_SECRET` together.
- Any Google service-account JSON, OAuth client secret, refresh token, or API key ever stored in the repository or an archive.
- SMTP/mail provider credentials and `MAIL_FROM` account password or API token.
- WordPress administrator passwords, application passwords, active sessions, and recovery codes.
- Dashboard staff bootstrap/admin credentials and active auth/password-reset tokens.
- OpenRouter, You.com/YDC, Cloudflare, GitHub, WHM/cPanel, backup-storage, and monitoring tokens if they appeared in any tracked file or archive.

## Files that must be supplied manually, never committed

- `/wp-config.php`
- `/dashboard.drbastaninejad.com/.env`
- `/app_private/.env`
- Apps Script Script Properties: `SPREADSHEET_ID`, `SHEET_NAME`, and `SHARED_SECRET`
- Any service-account/OAuth JSON and TLS private keys

Use the committed `.env.example` files as key-name templates. Apply least privilege, use distinct secrets per integration, set `.env` permissions to `0600`, and keep the dashboard project root outside the public document root.

## Repository cleanup

- Remove tracked `.env*`, `wp-config.php`, database dumps, archives, logs, runtime storage, and deployment bundles from the current tree.
- Purge exposed secrets from Git history with a history-rewrite tool, then invalidate old clones and cached build artifacts.
- Enable GitHub secret scanning/push protection and review Actions logs/artifacts after the rewrite.
- Verify the new values in staging, revoke the old values, then deploy production.
