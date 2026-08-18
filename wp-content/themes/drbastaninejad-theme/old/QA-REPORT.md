# QA Report — 4.5.2 UI Mirror + Booking/SMS Pass

Date: 2026-08-14

## Release blockers addressed

- The compiled React root no longer hard-codes `dir="rtl" lang="fa-IR"`. It reads the active locale before first render.
- LTR is a true global mirror for EN/TR/RU/FR/DE/ES: logo/header order begins from the left, flex/grid auto-placement follows LTR, physical left/right Tailwind utilities are mirrored, and RTL-only text alignment is corrected.
- Arabic and Persian remain RTL.
- The previous incorrect LTR rule that changed `.text-left` into right alignment was removed.
- Search icons/padding, absolute badges, floating Chaty, scroll-to-top, gallery navigation, native dropdowns, notices, footer alignment and physical margins are mirrored where the Persian bundle uses physical left/right utilities.
- LTR pages use a Latin/Cyrillic-safe system font stack so Russian and Western-language glyph metrics do not distort controls.

## Translation coverage

- Seven non-Persian React/runtime packs have exactly **1,025 identical source keys** each.
- EN/TR/RU/FR/DE/ES packs contain **zero Persian-script translation values**.
- Current dynamic gallery/policy/accessibility strings were added, including labels that previously leaked Persian in `aria-label`, gallery controls and policy cards.
- Runtime matching now tolerates current-live Unicode/spacing differences (ZWNJ, Arabic/Persian yeh/kaf, diacritics, dashes and whitespace).
- Current WordPress presentation fields are localized **in memory only** before being passed to React. IDs, slugs, URLs, API endpoints and stable payload values are never translated.
- Non-Persian LTR output also normalizes Persian/Arabic numerals and Persian punctuation where appropriate.
- Static simulation over compiled **main, services and legal UI** leaves zero Persian-letter UI strings after phrase replacement. Blog article bodies remain outside mass translation per the earlier project requirement; their shared UI/chrome is localized.

## Phone / direction coverage

- Persian: RTL.
- Arabic: RTL.
- English, Turkish, Russian, French, German, Spanish: LTR.
- Non-Persian Iranian clinic phone display and `tel:` links normalize to `+98` / E.164.
- International booking defaults the dialing-code field to `+98`, while allowing the patient to change country/dial code when applicable.

## Booking / SMS coverage

The WordPress theme follows the supplied booking contract:

1. Browser submits to the WordPress public booking REST endpoint with nonce.
2. WordPress validates eligibility, country/dial/email (non-FA), stable procedure key and duplicate/cooldown rules.
3. WordPress posts server-to-server to dashboard `/api/v1/bookings` with `X-WordPress-Bridge-Secret`.
4. Payload includes `sms_template`, language and stable clinical fields.
5. Dashboard remains responsible for the SMS provider (`SmsProviderChain` / TSMS) after booking DB commit.
6. WordPress now returns and displays the real dashboard `sms_status`:
   - sent/delivered/success → booking saved + confirmation SMS sent;
   - failed/error/rejected/undelivered → booking saved + SMS failure explicitly reported;
   - missing/unknown → booking saved + SMS status unavailable explicitly reported.
7. An SMS failure does not tell the browser to retry a committed booking and therefore does not intentionally create a duplicate appointment.

No TSMS API key, username, password or sender secret is placed in JavaScript or WordPress content.

## Persian-content safety

- No SQL/WXR/XML content import is bundled.
- No release path is allowed to mass-import or overwrite current Persian pages/posts/FAQ/services/settings.
- Live WordPress records remain the data source; localization is request-time presentation only.
- Historical gallery demo data remains removed.

## Automated/static regression suite

- **98 / 98 release-specific checks passed.**
- **38 PHP files** passed `php -l`.
- **16 JavaScript files** passed `node --check`.
- **16 JSON files** parsed successfully.
- No font binaries are included in the release tree/package.

The 98-check suite covers phrase-key parity, Persian-script leakage, React first-render direction, physical LTR utility inventory, translation-runtime ordering, live-data localization safety, dynamic gallery/policy strings, +98 normalization, booking/SMS payload/status behavior, duplicate login handling, content-mutation safeguards and release-file safety.

## Required live smoke test

A real SMS cannot be safely generated from this offline build environment because that would require a real patient phone and the live dashboard/provider configuration. On deployment, submit one authorized test booking and verify:

- WordPress response contains a booking ID and `smsStatus`;
- dashboard DB contains one booking only;
- SMS arrives on the test phone;
- dashboard counters record `sms_sent` (not `sms_failed`);
- a second immediate submission for the same normalized phone is blocked and points to patient login.

If booking is saved but SMS is not delivered, follow `SMS-BOOKING-RUNBOOK.md`; that is a dashboard/provider/TLS configuration issue, not a reason to disable SSL verification or store provider secrets in the theme.
