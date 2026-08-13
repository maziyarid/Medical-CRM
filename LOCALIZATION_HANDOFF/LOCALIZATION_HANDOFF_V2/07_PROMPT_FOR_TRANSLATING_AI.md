# Prompt — paste to translating AI

You are localizing the marketing site of **Dr. Shahin Bastaninejad**, ENT / rhinoplasty surgeon in **Iran** (Tehran).

## Inputs
I will provide JSON files extracted from production:
- `01_pages_fa_SOURCE.json` — WordPress pages (Persian)
- `02_services_fa_SOURCE.json` — 8 service entries
- `03_faqs_fa_SOURCE.json` — FAQs
- `04_chrome_ui_keys_fa.json` — UI chrome keys
- `05_case_galleries_fa_SOURCE.json` — gallery labels
- `06_forms_booking_strings_fa.json` — form/booking messages

## Target languages (produce one file each)
`en`, `ar`, `tr`, `ru`, `fr`, `de`, `es`

## Rules
1. Natural, professional medical-marketing tone. Not literal calque from Persian.
2. Doctor name stays **Dr. Shahin Bastaninejad** (stable transliteration).
3. For **en/ar/tr/ru/fr/de/es** SEO titles and descriptions: include a natural **“in Iran”** phrase in that language. For content body, mention Iran/Tehran only where geographically natural.
4. Do **not** translate the blog post list; pages + services + FAQs + chrome + forms only.
5. Do **not** invent testimonials, success rates, or guarantees.
6. Preserve HTML tags/structure in `content_html`; translate visible text only.
7. Keep JSON keys stable. Return valid UTF-8 JSON (`ensure_ascii=false` equivalent).
8. Suggested Latin slugs for LTR languages (e.g. about, contact, booking, faq, gallery, privacy, home, after-splint-care).
9. Brand names: Instagram, YouTube, Telegram, Aparat, Neshan, Balad — keep recognizable.
10. RTL for `ar` (and note fa is source). LTR for en/tr/ru/fr/de/es.

## Output schema (per language file `pack_{lang}.json`)
```json
{
  "lang": "en",
  "dir": "ltr",
  "chrome": {},
  "forms": {},
  "pages": [],
  "services": [],
  "faqs": [],
  "gallery_labels": []
}
```

Map every chrome key from `04_chrome_ui_keys_fa.json`.  
Map every forms string from `06_forms_booking_strings_fa.json` as `{ "FA": "translation" }`.  
Map every page in `01_pages_fa_SOURCE.json` with `source_slug_fa`, new `slug`, `title`, `content_html`, `seo_title`, `seo_description`.  
Map all 8 services and all FAQs.

Work language-by-language if needed; finish `en` first as the reference pack.

## Integrity check (do this first)

Before translating, verify file sizes:

| File | Minimum size |
|------|----------------|
| `01_pages_fa_SOURCE.json` | **> 50 KB** (10 pages with HTML) |
| `02_services_fa_SOURCE.json` | > 2 KB |
| `03_faqs_fa_SOURCE.json` | > 5 KB |
| `04_chrome_ui_keys_fa.json` | > 1 KB |

If `01_pages_fa_SOURCE.json` is ~2 bytes or `{}`, you have the **wrong/empty file**. Stop and reload from the handoff ZIP. There must be exactly **10** objects in the pages array.

Canonical filenames use **UPPERCASE** `SOURCE` (e.g. `01_pages_fa_SOURCE.json`). Ignore any lowercase `01_pages_fa_source.json` stub.
