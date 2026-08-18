# QA Report — 4.6.0 Multilingual/Arabic Stability Pass

Date: 2026-08-15  
Scope: repository `v1` only  
Source commit: `ec84aa10c22c77dd453298265a274302969d4957`

## Release blockers fixed

- The localization bridge no longer rescans the whole document or performs delayed whole-page translation passes.
- The active observer is scoped to `#root`, processes only changed/added nodes and uses `WeakMap` output caches.
- Arabic output is no longer treated as untranslated Persian merely because both languages use Arabic script.
- Non-Persian React output is hidden until its first localized DOM pass, preventing a visible Persian first frame.
- Missing non-Persian PHP translations now fall back to English, then the key, with development logging. Persian is no longer the global fallback.
- Server-localized language chrome is marked `data-drb-no-translate`.
- The language selector now uses disclosure navigation semantics, unique `aria-controls`, `aria-current="page"`, Escape-to-close and focus restoration.
- The language-switcher and international-booking bootstrap observers watch only long enough to find `#root`, then observe only that root and disconnect after installation.
- Arabic navigation, contact, booking, confidentiality, message-count, physician-name and phone wording is synchronized across PHP, content and React packs.
- The international phone field now accepts a national number beside the separate dial code, includes an Arabic hint and avoids a duplicate `+98` instruction.
- Localized body-content links are rewritten from `drb_localized_page_map()` and the filter is explicitly registered.
- The obsolete `assets/inc/lang-chrome.php` implementation is now a compatibility shim to the canonical module.
- Release metadata is synchronized at `4.6.0`.

## Automated results

| Check | Result |
| --- | --- |
| React phrase-key parity | 1,025 keys in each of 7 non-Persian locales; passed |
| Content-pack structure parity | 1,161 scalar paths in each of 7 non-Persian locales; passed |
| PHP locale-key parity | 84 keys in each of 7 non-Persian locales; passed |
| Arabic populated-value/source-copy/glossary checks | Passed |
| Arabic Persian-specific-glyph lint | Passed, excluding `source_slug_fa` metadata |
| JavaScript syntax | 16/16 files passed `node --check` |
| JSON syntax | 16/16 files parsed with `jq` |
| PHP syntax/AST parse | 38/38 files parsed with `php-parser` |
| Runtime DOM localization | Passed |
| International booking DOM/serialization | Passed |

Runtime DOM instrumentation observed:

- initial localized navigation: 2 bounded text mutations;
- 20 navigation class/ARIA animation toggles: 0 text rewrites;
- one simulated React reset to Persian: 1 React mutation + 1 localization mutation, then stable Arabic;
- server-rendered no-translate content stayed unchanged;
- the first-frame pending class was removed only after localized content existed;
- the cloned language menu received a unique ID and restored focus on Escape.

International booking instrumentation observed:

- displayed national-number placeholder: `912 123 4567`;
- dial selector: `+98`;
- `09121234567` serialized once as `+989121234567`;
- an explicitly entered `+989121234567` remained unchanged;
- locale and nonce fields remained present.

## Arabic terminology standard

The release uses these canonical high-visibility forms:

| Concept | Arabic |
| --- | --- |
| Home | `الرئيسية` |
| About | `عن الطبيب` |
| Rhinoplasty | `جراحة الأنف` |
| Gallery | `المعرض` |
| Articles | `المقالات` |
| FAQ | `الأسئلة الشائعة` |
| Contact | `تواصل معنا` |
| Book appointment | `احجز موعدًا` |
| Contact clinic | `اتصل بالعيادة` |
| Confidential and secure | `سري وآمن` |
| Physician | `د. شاهين باستاني نجاد` |

## Architecture decision

The editable React source is not present in `v1`; only compiled assets are available. The release therefore uses a tactical compatibility bridge that is targeted, idempotent and first-frame guarded. The long-term replacement remains source-level React i18n where components render translation keys directly and no DOM translation observer is required.

## Required deployment smoke tests

This offline audit cannot exercise the live WordPress database, CDN/cache, dashboard bridge, provider credentials or real SMS delivery. After deployment:

1. Purge WordPress/server/CDN caches.
2. Open Arabic desktop and mobile navigation in a fresh private session; confirm no Persian flash or typing/flicker.
3. Exercise Enter, Space, Escape, Tab and Shift+Tab on the language selector.
4. Submit one authorized international test booking and confirm exactly one dashboard record.
5. Verify the response `smsStatus` and the matching sent/failed/unknown UI copy.
6. Confirm the expected SMS arrives only when the backend reports it as sent.

Do not disable TLS verification and do not place provider credentials in the theme.
