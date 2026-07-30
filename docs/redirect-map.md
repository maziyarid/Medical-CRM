# Redirect Map — drbastaninejad.com
<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

> **This document is for Infrastructure / product owner reference only.**  
> Bob AI (Frontend track) does not touch server config. The Nginx rewrite rules below are produced for the Infrastructure track to implement on the AlmaLinux VPS.

---

## Background

The site migrated from:
- Old domain: `https://drbastaninejad.ir` (WordPress era)  
- Old permalink pattern: `/YYYY/MM/DD/slug/` and `/page-slug/`  
- New domain: `https://drbastaninejad.com` (clean slugs, flat hierarchy)

The 2026-07-09 WordPress export shows GUIDs using `.ir` while `attachment_url` fields use `.com`. Both old patterns must redirect to the new clean URLs.

---

## 1. Domain-level redirect (highest priority)

Redirect all `drbastaninejad.ir` traffic to `drbastaninejad.com` (301 permanent):

```nginx
server {
    listen 80 443 ssl;
    server_name drbastaninejad.ir www.drbastaninejad.ir;
    return 301 https://drbastaninejad.com$request_uri;
}
```

---

## 2. www to non-www

```nginx
server {
    listen 80 443 ssl;
    server_name www.drbastaninejad.com;
    return 301 https://drbastaninejad.com$request_uri;
}
```

---

## 3. WordPress permalink rewrites

WordPress used `/YYYY/MM/DD/slug/` date-based permalinks. Map all confirmed WordPress post slugs to their new clean paths.

### Nginx rewrite rules

```nginx
# ── WordPress date-based blog posts → new flat slugs ──────────────────────────

# جراحی بینی گوشتی
rewrite ^/\d{4}/\d{2}/\d{2}/(جراحی-بینی-گوشتی|gousht[^/]*)/?$
    https://drbastaninejad.com/blog/rhinoplasty-fleshy.html permanent;

# اقدامات قبل از جراحی بینی
rewrite ^/\d{4}/\d{2}/\d{2}/(اقدامات-قبل[^/]*)/?$
    https://drbastaninejad.com/blog/pre-op-steps.html permanent;

# مراقبت‌های بعد از جراحی بینی
rewrite ^/\d{4}/\d{2}/\d{2}/(مراقبت[^/]*)/?$
    https://drbastaninejad.com/blog/post-op-care.html permanent;

# تاثیر خندیدن بعد از جراحی بینی
rewrite ^/\d{4}/\d{2}/\d{2}/(تاثیر-خندیدن[^/]*)/?$
    https://drbastaninejad.com/blog/laughing-after-rhinoplasty.html permanent;

# آمبولی چیست
rewrite ^/\d{4}/\d{2}/\d{2}/(آمبولی[^/]*)/?$
    https://drbastaninejad.com/blog/what-is-embolism.html permanent;

# ترس از بیهوشی کامل
rewrite ^/\d{4}/\d{2}/\d{2}/(ترس-از-بیهوشی[^/]*)/?$
    https://drbastaninejad.com/blog/fear-of-anesthesia.html permanent;

# جراحی بینی یا راینوپلاستی
rewrite ^/\d{4}/\d{2}/\d{2}/(راینوپلاستی[^/]*)/?$
    https://drbastaninejad.com/blog/rhinoplasty.html permanent;

# جراحی بینی ترمیمی
rewrite ^/\d{4}/\d{2}/\d{2}/(ترمیمی[^/]*)/?$
    https://drbastaninejad.com/blog/rhinoplasty-revision.html permanent;

# تفاوت غضروف گوش و دنده
rewrite ^/\d{4}/\d{2}/\d{2}/(غضروف[^/]*)/?$
    https://drbastaninejad.com/blog/cartilage-ear-vs-rib.html permanent;

# ── WordPress static page slugs → new clean paths ─────────────────────────────

rewrite ^/(خدمات|services)/?$
    https://drbastaninejad.com/services.html permanent;

rewrite ^/(درباره|about|درباره-ما|about-us)/?$
    https://drbastaninejad.com/about.html permanent;

rewrite ^/(تماس|contact|تماس-با-ما)/?$
    https://drbastaninejad.com/contact.html permanent;

rewrite ^/(گالری|gallery|نمونه-کارها)/?$
    https://drbastaninejad.com/gallery.html permanent;

rewrite ^/(نوبت|booking|نوبت-گیری|نوبت-آنلاین)/?$
    https://drbastaninejad.com/booking.html permanent;

# ── WordPress attachment/media URLs (both .ir and .com) ───────────────────────
# These /wp-content/uploads/ paths may be referenced in Google-indexed images.
# Option A: serve from new /assets/images/ path; Option B: ignore (images break in old index)
# Product owner decision needed.

# Placeholder — update once the product owner decides asset migration strategy:
# rewrite ^/wp-content/uploads/(.*)$  /assets/images/$1 permanent;

# ── WordPress admin / feed / xmlrpc — block all ───────────────────────────────
rewrite ^/wp-admin/?     https://drbastaninejad.com/ permanent;
rewrite ^/wp-login\.php  https://drbastaninejad.com/ permanent;
rewrite ^/xmlrpc\.php    https://drbastaninejad.com/ permanent;
rewrite ^/feed/?         https://drbastaninejad.com/ permanent;
```

---

## 4. Confirmed WordPress post slugs (for manual verification)

The following slugs were confirmed present in the 2026-07-09 export (alt-text and image filenames cross-reference):

| Original WP slug | Confirmed by | New URL |
|---|---|---|
| جراحی-بینی-گوشتی | image: `جراحی-بینی-گوشتی-مقاله-min.jpg` | `/blog/rhinoplasty-fleshy.html` |
| اقدامات-قبل-جراحی-بینی | image: `اقدامات-قبل-جراحی-بینی-min.jpg`; excerpt confirmed in export | `/blog/pre-op-steps.html` |
| مراقبت-های-بعد-جراحی-بینی | image: `نکات-طلایی-مقاله-min.jpg` | `/blog/post-op-care.html` |
| تاثیر-خندیدن-بعد-جراحی | image: `تاثیر-خندیدن-مقاله-min.jpg` | `/blog/laughing-after-rhinoplasty.html` |
| آمبولی-چیست | image: `مقاله-امبولی-چیست-؟-min.jpg` | `/blog/what-is-embolism.html` |
| ترس-از-بیهوشی-کامل | image: `مقاله--ترس-از-بیهوشی-کامل-min.jpg` | `/blog/fear-of-anesthesia.html` |
| راینوپلاستی | image: `مقاله-راینوپلاستی-min.jpg` | `/blog/rhinoplasty.html` |
| جراحی-بینی-ترمیمی | image: `مقاله-جراحی-ترمیمی-بینیی-چیست-min.jpg` | `/blog/rhinoplasty-revision.html` |
| غضروف-گوش-دنده | image: `غضروف-min.jpg` / `غضروف-جراحی-بینی.jpg` | `/blog/cartilage-ear-vs-rib.html` |

---

## 5. Open items requiring product-owner decision

- [ ] Confirm exact WordPress date-based permalink format used (was `/YYYY/MM/DD/slug/` or `/?p=ID`?)
- [ ] Decide on `/wp-content/uploads/` asset migration: serve new paths or accept broken legacy image URLs
- [ ] Confirm whether Instagram/social sharing links used `drbastaninejad.ir` or `.com`
- [ ] Infrastructure: test Nginx UTF-8 URL handling before enabling Persian-slug redirects
- [ ] Validate redirect map against Google Search Console data once site goes live

---

*MΛZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad*
