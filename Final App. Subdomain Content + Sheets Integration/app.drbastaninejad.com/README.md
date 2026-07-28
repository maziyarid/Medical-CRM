# Appointment Form — Fixed Files (v11)

This package contains the five corrected deployment files plus a complete
copy/paste handoff prompt.

## Deployment files

- `public/index.html`
- `public/app.js`
- `public/config.js`
- `app_private/src/OtpService.php`
- `app_private/src/IntakeService.php`

Keep every other file in the existing installation unchanged. Replace only the
matching paths above, preserving the existing public/private directory layout.

## Included fixes

1. `morefmob` (شماره معرف) is optional in both the browser and backend.
2. The SMS code is validated only during OTP activation; final submission
   checks the verified server-side session and does not validate the code again.
3. A verified OTP session lasts exactly 30 minutes, with a visible frontend
   countdown and expiry handling.
4. The red medical restriction notice appears above the form so it is read
   before users proceed.
5. Frontend asset revision is `v=11`.

## Handoff document

`Appointment_Form_Complete_Handoff_Prompt.md` is designed to be pasted into a
new chat. It describes the full project, current behavior, fields, mappings,
methods, API flow, security/storage behavior, latest changes, known current
constraints, tests, deployment layout, and authoritative source snapshots.

No `.env` values, SMS credentials, shared secrets, OTP storage records,
signatures, logs, or patient submissions are included in this package.
