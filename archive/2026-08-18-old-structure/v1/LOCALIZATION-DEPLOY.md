# Localization 4.5.2 deployment

## 1. Back up first

Back up the current WordPress database and live theme directory.

## 2. Merge — do not delete/replace the whole theme directory

Merge this release into:

`wp-content/themes/drbastaninejad-theme/`

The package intentionally excludes font binaries. Preserve the live `assets/dist6/fonts/` directory and current media/uploads.

Do **not** import old WordPress XML/WXR or SQL/database files. They may contain obsolete Persian content. This release does not need them.

## 3. Purge caches

Purge WordPress/plugin cache, server/LiteSpeed cache, object cache and CDN cache, then test in a fresh private browser session. The first-render LTR fix depends on clients receiving the new React main bundle and localization CSS/runtime together.

## 4. Visual acceptance — test UI before content details

For English first, then German/Russian/Turkish/French/Spanish:

- logo is the first element from the **left**;
- navigation/actions flow left-to-right;
- card/grid order starts left;
- headings/form labels/text begin left;
- absolute icons/badges/search controls are on their mirrored sides;
- floating contact widget and scroll-to-top occupy opposite physical sides compared with Persian;
- gallery arrows behave as LTR previous/next;
- footer columns/content start from the left;
- mobile header/menu/order is mirrored, not merely text-aligned.

For Persian and Arabic confirm the equivalent RTL geometry remains unchanged.

## 5. Translation acceptance

On every non-Persian core page check header, footer, menus, widgets, services, FAQs, policies, gallery state, forms, error/success messages, placeholders, button values, alt/title/ARIA labels and 404/native-template chrome.

EN/TR/RU/FR/DE/ES must not display Persian-script UI copy. Arabic naturally uses Arabic script.

Per the earlier project instruction, old blog article **body copy** is not mass-translated by this theme release; the blog shell/chrome is localized.

## 6. Phone acceptance

On non-Persian pages, Iranian clinic numbers must visibly include `+98` and `tel:` links must be E.164 (`tel:+98...`).

## 7. Booking / SMS acceptance

Follow `SMS-BOOKING-RUNBOOK.md`.

The important UI check is that a successful booking no longer blindly says SMS succeeded. It displays the actual `smsStatus` returned by the dashboard.

Do not disable SSL verification and do not put TSMS credentials in JavaScript/WordPress page content.

## 8. Persian safety check

After merge, compare current Persian home/services/FAQ/policies before and after. No content import/migration should replace Persian wording, slugs, media, SEO fields or settings.
