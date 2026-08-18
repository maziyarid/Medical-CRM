# Dr. Bastaninejad WordPress theme — 4.6.0 localization/UI release

This is a merge release for the existing live theme. It repairs true LTR/RTL geometry, removes Arabic navigation translation churn and first-frame Persian flash, eliminates residual Persian UI leakage, and aligns booking/SMS feedback with the dashboard contract.

Key properties:

- FA + AR: RTL.
- EN + TR + RU + FR + DE + ES: true LTR mirror.
- Targeted, idempotent `#root` localization; no repeated whole-page rescans.
- Missing non-Persian PHP strings fall back to English, never Persian.
- Arabic terminology is synchronized across PHP/content/React stores.
- 1,025 shared runtime phrase keys in each non-Persian locale.
- Current WordPress content is read live and localized in memory; no Persian-content overwrite.
- Non-Persian Iranian clinic phones use `+98`.
- Booking remains server-to-server to the clinical dashboard.
- SMS status is reported accurately after booking instead of always claiming success.
- No TSMS/provider secrets are shipped in the theme.
- No SQL/WXR/XML old-content import payload is included.
- No font binaries are included; preserve the existing live theme font directory when merging.

Read `LOCALIZATION-DEPLOY.md`, `SMS-BOOKING-RUNBOOK.md`, and `QA-REPORT.md` before deployment.

For a local data/architecture regression check with Node.js, run:

`node tests/i18n-regression.mjs`
