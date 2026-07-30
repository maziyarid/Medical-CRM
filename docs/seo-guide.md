# SEO Guide — drbastaninejad.com
<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

## Overview

This site uses a **data-driven SEO approach** (Path A).  
All per-page meta values are stored in one file: `drbastaninejad.com/data/seo.json`.  
You can update titles, descriptions, OG images, and canonical URLs **without touching any HTML file**.

---

## 1. How to edit a page's meta

Open `drbastaninejad.com/data/seo.json` and find the entry for the page you want to update.

**Example — update the homepage title:**

```json
{
  "/": {
    "title": "دکتر شاهین باستانی نژاد | جراح و متخصص بینی در تهران",
    "description": "دکتر شاهین باستانی نژاد، جراح و متخصص گوش، گلو و بینی...",
    "og_image": "/assets/images/دکتر-شاهین-باستانی--scaled.jpg",
    "canonical": "https://drbastaninejad.com/",
    "schema_type": "MedicalBusiness"
  }
}
```

**Fields:**

| Field | Purpose | Max length |
|---|---|---|
| `title` | `<title>` tag and `og:title` | 60 chars |
| `description` | `<meta name="description">` and `og:description` | 155 chars |
| `og_image` | Open Graph image (social shares) | — (use `/assets/images/…` relative path) |
| `canonical` | Canonical URL — prevents duplicate content | — (full https:// URL) |
| `schema_type` | JSON-LD `@type` value | Use values from Section 3 |
| `keywords` | Meta keywords (limited ranking value, used for reference) | — |

---

## 2. How to add a new blog post

1. Copy one of the existing blog HTML files (e.g. `blog/rhinoplasty-primary.html`) to a new slug in `drbastaninejad.com/blog/`.
2. Add a new entry to `data/seo.json`:

```json
{
  "/blog/my-new-post": {
    "title": "عنوان مقاله | دکتر شاهین باستانی نژاد",
    "description": "توضیح کوتاه مقاله — ۱۵۵ کاراکتر یا کمتر.",
    "og_image": "/assets/images/my-article-image.jpg",
    "canonical": "https://drbastaninejad.com/blog/my-new-post.html",
    "schema_type": "Article"
  }
}
```

3. Link to it from `blog.html` and add a breadcrumb in the article file.
4. Run the 9 confirmed articles from Section 4 of the product brief first.

---

## 3. JSON-LD schema types used

| `schema_type` | Used on |
|---|---|
| `MedicalBusiness` | Homepage, booking |
| `Person` | About page |
| `MedicalClinic` | Contact page |
| `MedicalProcedure` | Service detail pages |
| `ImageGallery` | Gallery page |
| `Blog` | Blog index |
| `Article` | Blog post pages |

---

## 4. Hreflang

All pages declare `<link rel="alternate" hreflang="fa-IR" href="[canonical]"/>`.  
If an English version is ever added, add `hreflang="en"` as a second `<link>` pointing to the EN version and add `hreflang="x-default"` on the EN page.

---

## 5. Favicon set

The complete favicon set is derived from `cropped-logo-t-min.webp` (from the WordPress export).  
Sizes confirmed: 32×32, 150×150, 180×180, 192×192, 270×270, 300×300.

**Product owner action:** Copy these files from the WordPress uploads folder into `drbastaninejad.com/assets/images/favicon/`:
- `favicon-32x32.png`
- `apple-touch-icon.png` (180×180)
- `android-chrome-192x192.png`
- `android-chrome-512x512.png` (use 270×270 as proxy until 512 is available)

Update `<head>` in each HTML page once the files are in place:

```html
<link rel="icon" type="image/png" sizes="32x32"   href="/assets/images/favicon/favicon-32x32.png"/>
<link rel="apple-touch-icon" sizes="180x180"       href="/assets/images/favicon/apple-touch-icon.png"/>
<link rel="icon" type="image/png" sizes="192x192"  href="/assets/images/favicon/android-chrome-192x192.png"/>
```

---

## 6. Persian URL slugs — infrastructure note

Several canonical URLs use Persian-script slugs (e.g. `/درباره-دکتر-شاهین-باستانی-نژاد/`).

**Risk:** AlmaLinux/Nginx must be configured to handle percent-encoded UTF-8 URLs.  
**Recommendation:** Test `curl -I 'https://drbastaninejad.com/%D8%AF%D8%B1%D8%A8%D8%A7%D8%B1%D9%87-...'` before relying on Persian folder names in production. If the server 404s, use ASCII slugs (`/about/`, `/services/`) until Nginx UTF-8 URL handling is confirmed.

The HTML files in this package use ASCII filenames (`about.html`, `services.html`, etc.) intentionally to avoid this risk in the pre-launch period.

---

## 7. Path B — database-backed SEO admin (not yet approved)

When the product owner approves SEO admin scope (UNIFIED_MASTER_PLAN.md Section 11 amendment required), Blackbox AI will implement:

- DB table: `seo_meta (route VARCHAR PK, meta_title, meta_description, og_image, canonical_url, schema_type, updated_at)`
- Endpoint: `GET /api/v1/seo/{route}` — public, read-only, no auth
- Admin UI screen under staff Settings → SEO Meta

**Do not build Path B without explicit product-owner approval.**

---

*MΛZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad*
