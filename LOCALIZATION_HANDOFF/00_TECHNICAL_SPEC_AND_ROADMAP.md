# Dr. Bastaninejad — Localization Handoff
**For an external AI / localization engineer**  
**Source language:** Persian (fa)  
**Targets:** en, ar, tr, ru, fr, de, es  
**Date:** 2026-08-13  
**Constraint:** No WPML / Polylang / TranslatePress. Single WordPress install + subdomain hosts.

---

## 1. Architecture (do not redesign)

| Host | Role | Localize? |
|------|------|-----------|
| `drbastaninejad.com` | Marketing WP (fa default) | Yes — primary |
| `en.` `ar.` `tr.` `ru.` `fr.` `de.` `es.` `.drbastaninejad.com` | Same WP, language via **host detection** | Yes |
| `app.drbastaninejad.com` | Live intake + patient/staff UI | **Out of scope for marketing i18n** (Persian clinical intake stays) |
| `dashboard.drbastaninejad.com` | Clinical API only | API errors may stay FA; not marketing pages |

**Detection:** theme `inc/i18n.php` → `drb_detect_lang()` from HTTP host.  
**Direction:** fa + ar = `dir=rtl`; en/tr/ru/fr/de/es = `dir=ltr`.  
**Association:** post meta `drb_lang_code` + `drb_lang_group_id` (same logical page across languages).  
**Chrome strings:** PHP packs `languages/{lang}.php` + helper `__t('key')`.  
**SPA:** React bundle under `assets/dist6/` — **most visible marketing UI is here**, currently compiled Persian. PHP packs alone do **not** translate the React menu/hero/services.

---

## 2. What “language switcher works but no translation” means

| Layer | Mechanism | Status |
|-------|-----------|--------|
| URL / subdomain | Switcher links to `en.drbastaninejad.com/...` | Works if DNS + docroot OK |
| `html[lang]` / `dir` | i18n + lang-chrome | Works |
| Header/footer PHP bits | `__t()` + `languages/*.php` | Partial (~34 keys) |
| React SPA (nav, hero, services cards, booking UI chrome) | Inside minified `main-*.js` | **Still FA** until string packs or rebuild |
| WP page body (About, Contact, …) | Separate WP pages per language + `drb_lang_group_id` | **Must create** translated pages |
| Rank Math title/description | Per-page SEO fields | Per language page; non-FA metas need natural “in Iran” |

---

## 3. Page inventory (TRANSLATE THESE — pages only)

From `demo-content/wxr-content.json` (FA source in `01_pages_fa_SOURCE.json`):

| FA slug | FA title | Priority | Notes |
|---------|----------|----------|-------|
| `صفحه-اصلی` | صفحه اصلی | P0 | Home — often also React-driven |
| `درباره-دکتر-شاهین-باستانی-نژاد` | درباره دکتر | P0 | About |
| `تماس-با-ما` | تماس با ما | P0 | Contact |
| `دریافت-نوبت` | دریافت نوبت | P0 | Booking landing |
| `سوالات-متداول` | سؤالات متداول | P0 | FAQ index (see also `03_faqs`) |
| `گالری` | گالری | P1 | Gallery shell |
| `آتل-بینی` | نکات مهم پس از برداشتن آتل بینی | P1 | Aftercare / post-splint |
| `سیاست-حفظ-حریم-خصوصی` | سیاست حفظ حریم خصوصی | P1 | Privacy (draft in export; still translate) |
| `مقالات` | مقالات | P2 | Blog index label only |
| `دکتر-شاهین-باستانی-نژاد` | دکتر شاهین باستانی نژاد | P2 | Possibly legacy/home alias |

**Do NOT mass-translate blog posts** in v1 (`01_posts_INDEX_only.json` is index-only).

---

## 4. Service pages (8) — P0

Source: `02_services_fa_SOURCE.json`

| slug | title_fa |
|------|----------|
| `rhinoplasty-primary` | جراحی بینی اولیه |
| `rhinoplasty-revision` | جراحی بینی ترمیمی |
| `rhinoplasty-bony` | جراحی بینی استخوانی |
| `rhinoplasty-natural` | بینی طبیعی |
| `hump-removal` | رفع قوز بینی |
| `septoplasty` | سپتوپلاستی |
| `turbinoplasty` | توربینوپلاستی |
| `sinus-endoscopy` | آندوسکوپی سینوس (FESS) |

Each has: `title`, `subtitle`, `description` (and any extra fields in JSON).

---

## 5. FAQs (22) — P0

Source: `03_faqs_fa_SOURCE.json` — translate question + answer pairs. Keep medical meaning accurate; no guarantees/testimonials invention.

---

## 6. Chrome UI keys (~34) — P0

Source: `04_chrome_ui_keys_fa.json`  
Extend existing `languages/en.php` (and ar/tr/ru/fr/de/es) — same keys, natural translations.  
Also: `06_forms_booking_strings_fa.json` (server-side form errors / booking messages).

---

## 7. Case gallery labels — P1

Source: `05_case_galleries_fa_SOURCE.json` — labels/procedure names only. **No patient-identifying filenames in public copy.**

---

## 8. SEO rules (“In Iran”)

For **every non-FA** page meta title/description:

- **Required:** natural phrase meaning the clinic is **in Iran** (en: “in Iran”, fr: “en Iran”, de: “im Iran”, …).
- **FA:** do **not** force «در ایران» / «در تهران» into metas if not already natural.
- Output: `seo_title`, `seo_description`, `slug` (Latin for en/tr/de/…; Arabic script OK for ar).
- hreflang handled by theme (`drb_output_hreflang`); x-default = fa.

---

## 9. Booking form (non-FA UX copy only)

UI strings for:

- country + dial code labels  
- email required  
- eligibility age 18–45, revision ≥24 months  
- success / cooldown / CTA to patient login  

**Do not change API field names** (`mobile`, `email`, `procedure`, …).  
Submissions still go to dashboard bridge; one clinical DB.

Patient login URL (copy only):  
`https://app.drbastaninejad.com/Frontend/pages/auth/patient-login.html`

---

## 10. Deliverable format for the translating AI

Produce **one JSON file per language**, UTF-8, `ensure_ascii=false`:

```json
{
  "lang": "en",
  "dir": "ltr",
  "chrome": { "nav_home": "Home", "...": "..." },
  "forms": { "<fa source string>": "<translation>", "...": "..." },
  "pages": [
    {
      "source_slug_fa": "درباره-دکتر-شاهین-باستانی-نژاد",
      "slug": "about",
      "title": "...",
      "content_html": "...",
      "seo_title": "... Rhinoplasty Surgeon in Iran",
      "seo_description": "..."
    }
  ],
  "services": [
    {
      "slug": "rhinoplasty-primary",
      "title": "...",
      "subtitle": "...",
      "description": "..."
    }
  ],
  "faqs": [
    { "question": "...", "answer": "..." }
  ],
  "gallery_labels": [ { "key": "...", "label": "..." } ]
}
```

**Style rules:**

- Natural medical marketing copy, not word-for-word.  
- Keep doctor name: **Dr. Shahin Bastaninejad** (transliteration stable).  
- Brand green is visual only (`#2F7D32`) — not a string.  
- No fake reviews, no outcome guarantees.  
- Preserve HTML structure when `content_html` is provided; translate visible text only.  
- Western digits in en/de/fr/…; Arabic-Indic optional only for ar/fa.  
- Brand product names: Instagram, YouTube, Telegram, Aparat, Neshan, Balad — keep as brand names where customary.

---

## 11. Implementation roadmap (after translations exist)

### Phase A — Chrome only (fast)
1. Fill `languages/{en,ar,tr,ru,fr,de,es}.php` from `chrome` section.  
2. Confirm `functions.php` loads `i18n.php` + `lang-chrome.php`.  
3. Subdomains share same WP docroot; DNS live.

### Phase B — WP pages + services + FAQs (content)
1. For each FA page, create sibling pages in WP (or import).  
2. Set `drb_lang_code` + shared `drb_lang_group_id`.  
3. Latin slugs for LTR languages.  
4. Rank Math title/description per language.  
5. Import translated services into whatever structure the React app reads (`dist6-services` / WP options / CPT — match production).  
6. FAQ CPT or page blocks as used live.

### Phase C — React SPA strings (largest UX gap)
Options (pick one):

1. **Rebuild** frontend with i18n framework + JSON catalogs from this handoff.  
2. **Runtime pack:** load `dr8_pack_{lang}.json` (Persian key → translation) and replace text nodes / known keys in the SPA (fragile but no rebuild).  
3. Hybrid: critical nav/CTA in PHP header; long copy in WP pages.

Without Phase C, users will still see Persian in the main React chrome even on `en.`.

### Phase D — QA
- Switcher stays on same logical page (`lang_group_id`).  
- RTL/LTR mirror (nav, logo side, CTA side).  
- Booking non-FA: email required + country/dial.  
- No clinical data in WP.  
- hreflang + canonical per host.

---

## 12. Files in this package

| File | Purpose |
|------|---------|
| `00_TECHNICAL_SPEC_AND_ROADMAP.md` | This document |
| `01_pages_fa_SOURCE.json` | All marketing pages FA (html + plain) |
| `01_posts_INDEX_only.json` | Blog index — do not mass-translate |
| `02_services_fa_SOURCE.json` | 8 services |
| `03_faqs_fa_SOURCE.json` | 22 FAQs |
| `04_chrome_ui_keys_fa.json` | PHP `__t` keys |
| `05_case_galleries_fa_SOURCE.json` | Gallery labels |
| `06_forms_booking_strings_fa.json` | Booking/forms FA strings |
| `07_PROMPT_FOR_TRANSLATING_AI.md` | Copy-paste prompt |

---

## 13. Out of scope

- Live intake WorkingVersion on `app.`  
- Dashboard API / SMS / SSL  
- Inventing medical claims  
- Translation plugins  
