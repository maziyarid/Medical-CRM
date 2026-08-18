# Change manifest — 4.5.2

## True LTR / RTL UI repair

- `assets/dist6/assets/main-rtvy6FFi.js`: React root now reads locale `dir`/`lang` before first paint instead of hard-coding RTL/Persian.
- `assets/css/localization-layout.css`: rebuilt as a true direction parity layer. Removed the broken `.text-left → right` inversion; mirrored every physical left/right utility currently used by the compiled main bundle while leaving centered/dual-inset elements intact.
- Header order now relies on the correct locale direction: logo is the first left element in LTR and the first right element in RTL, followed by navigation/actions in reading order.
- Search controls, badges, floating widgets, scroll-to-top, galleries, native menus, notices, footer and icon spacing are mirrored.
- LTR typography uses a system Latin/Cyrillic stack.
- `assets/dynamic-content.js`: gallery previous/next arrows and “view all” arrow are direction-aware.

## Translation leakage repair

- `assets/js/drb-i18n-runtime.js`: normalized phrase matching; first-render direction enforcement; attribute translation; LTR numeral/punctuation cleanup.
- `inc/react-app.php`: runtime loads early in the head and no longer depends on the React app finishing first.
- `inc/i18n.php`: normalized live-text translation plus request-time HTML/presentation localization; no database writes.
- `languages/react/*.json` and `languages/content/pack_*.json`: expanded to **1,025 identical keys per non-Persian locale**, including gallery accessibility labels, current policy blocks and booking/SMS status messages.

## Booking / SMS repair

- `inc/forms.php`: consumes dashboard `sms_status` and returns an accurate localized booking message for SMS sent, SMS failed or SMS status unavailable.
- Existing server-to-server dashboard bridge, stable procedure key, country/dial/email fields, +98 default and one-phone/duplicate handling are retained.
- Provider secrets remain dashboard-only; no direct client-side TSMS implementation was introduced.
- Added `SMS-BOOKING-RUNBOOK.md` with the supplied bridge/TLS/document-root/provider configuration checks.

## Safety

- Current Persian WordPress content is never overwritten by this localization pass.
- Live WordPress content remains source-of-truth and is translated only in the response/presentation layer.
- No SQL/WXR/XML import payload is bundled.
- Historical gallery/demo fallback stays removed.
- Font binaries are excluded from the distributable; deployment is a merge over the existing live theme so its current font/media directories remain in place.
