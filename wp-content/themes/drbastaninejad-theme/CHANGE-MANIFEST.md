# Change manifest — 4.6.0

## Arabic runtime and first-frame repair

- `assets/js/drb-i18n-runtime.js`: replaced delayed document-wide rescans with a `#root`-scoped, mutation-local, idempotent observer. Arabic target strings are no longer classified as Persian by script range.
- `style.css` + `inc/lang-chrome.php`: non-Persian React output is held until the first localized pass, with a four-second safety release.
- `inc/i18n.php`: missing translations now fall back to English and log in debug mode; server-side live-text translation uses the same shared-script safeguards.
- `languages/ar.php`, `languages/react/ar.json`, `languages/content/pack_ar.json`: synchronized the Arabic UI glossary, physician spelling, booking/SMS wording and international-phone guidance.

## Navigation, routes and accessibility

- `inc/lang-chrome.php` + `assets/js/lang-switcher.js`: server-localized chrome is excluded from retranslation; listbox misuse was removed; active links use `aria-current`; menu IDs are unique; Escape restores focus.
- `assets/inc/lang-chrome.php`: obsolete duplicate implementation replaced by a compatibility shim to the canonical module.
- `inc/localized-content.php`: link rewriting derives from the full localized route map and is explicitly registered on `the_content`.
- `assets/js/booking-international.js`: the phone input accepts a national number beside the separate country code, and its observer is scoped to the React root.

## Regression coverage

- `tests/i18n-regression.mjs`: locale parity, Arabic glossary/leakage, runtime architecture, accessibility, phone UX, fallback and release-version assertions.
- DOM instrumentation confirmed zero text rewrites across navigation animation toggles and exactly one localization response to a simulated React Persian rewrite.

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
