<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# Marketing Site Information Architecture
## drbastaninejad.com — Content Plan & Sitemap

**Status:** Draft — content placeholders marked `[CONTENT: …]`  
**Author:** Bob AI (Frontend track) — 2026-07-29  
**Authority:** UNIFIED_MASTER_PLAN.md, SPACE_COORDINATION_PROTOCOL.md  

---

## Purpose

A static marketing/informational site replacing the previous WordPress installation.
It carries **zero intake logic, OTP, or patient data**.  
Its only CRM connection is a primary CTA that links to `https://app.drbastaninejad.com/`.

---

## Sitemap

```
drbastaninejad.com/
├── index.html          — Home
├── about.html          — About Dr. Bastaninejad
├── services.html       — Services / Procedures overview
│   └── (stub detail pages — linked but content TBD)
├── gallery.html        — Before/After gallery (placeholders only)
├── contact.html        — Address, hours, map placeholder, inquiry form shell
└── booking.html        — Booking bridge (CTA only → app.drbastaninejad.com)
```

---

## Page Specifications

### 1. `index.html` — Home

| Section | Content | Owner | Notes |
|---|---|---|---|
| Hero | Name, specialty, tagline, primary CTA | Product owner | Tagline = `[CONTENT: product owner to supply]` |
| Trust / credentials | Board certifications, years of experience, patient count | Product owner | All figures = `[CONTENT: …]` |
| Services teaser | 4–6 procedure cards (icon + name + short description) linking to services.html | Product owner | Procedure names / descriptions = `[CONTENT: …]` |
| Before / after | 3-up placeholder grid linking to gallery.html | Design | SVG silhouettes only — never real patient images |
| Testimonials | 3 placeholder cards | Product owner | Text = `[CONTENT: …]`; no patient names or photos |
| Contact / location | Address, phone, hours | Product owner | `[CONTENT: …]` |
| Footer | Copyright, nav links, MAZ//ID sig | Bob AI | No patient data |

**CTA target:** `https://app.drbastaninejad.com/` — "تشکیل پرونده اولیه"

---

### 2. `about.html` — About

| Section | Content | Owner |
|---|---|---|
| Hero / photo placeholder | SVG silhouette; name, title | Product owner |
| Biography | Career narrative | Product owner — `[CONTENT: …]` |
| Education & training | Degrees, fellowships | Product owner — `[CONTENT: …]` |
| Board memberships / awards | Professional bodies | Product owner — `[CONTENT: …]` |
| Philosophy | Approach to care | Product owner — `[CONTENT: …]` |
| CTA | Link to booking.html | Bob AI |

---

### 3. `services.html` — Services

| Section | Content | Owner |
|---|---|---|
| Intro | Short intro paragraph | Product owner — `[CONTENT: …]` |
| Procedure cards | Name, icon, 1-line description, "بیشتر بدانید" link | Product owner — all names/descriptions = `[CONTENT: …]` |
| CTA banner | Link to booking.html | Bob AI |

**Stub procedure pages:** The HTML links to `services/{slug}.html` stubs. Content = `[CONTENT: …]`.  
Do not invent procedure names or clinical claims.

---

### 4. `gallery.html` — Before / After

| Section | Content | Owner |
|---|---|---|
| Intro disclaimer | "نمونه‌های تصویری پس از اخذ رضایت کتبی بیمار و به صورت ناشناس" | Bob AI (legal copy) — mark as `[LEGAL: legal review recommended]` |
| Comparison sliders | Lazy-loaded placeholder SVG pairs; slider component | Bob AI |
| Filter pills | Procedure category filters (JS) | Bob AI |
| CTA | Link to booking.html | Bob AI |

**Rule:** Never include real patient images. All image slots = SVG silhouettes with `aria-label` descriptions.

---

### 5. `contact.html` — Contact

| Section | Content | Owner |
|---|---|---|
| Address block | Address, phone, fax, email | Product owner — `[CONTENT: …]` |
| Hours | Weekly schedule | Product owner — `[CONTENT: …]` |
| Map placeholder | Static SVG map embed or Google Maps embed placeholder | Product owner |
| Inquiry form | Name, phone, message; submit button | Bob AI (shell) |

⚠️ **Backend requirement:** The inquiry form has no endpoint in `docs/API_CONTRACT.md`.  
The form is non-functional in this build — submit button is disabled with an explanatory note.  
See Backend Requirements section below.

---

### 6. `booking.html` — Booking Bridge

| Section | Content | Owner |
|---|---|---|
| Trust copy | Short reassurance paragraph | Product owner — `[CONTENT: …]` |
| Primary CTA | `<a href="https://app.drbastaninejad.com/">` — "شروع تشکیل پرونده" | Bob AI |
| Secondary info | What to expect from the intake process | Product owner — `[CONTENT: …]` |

**Rule:** No OTP field, no Code Meli field, no intake form fragment on this page — ever.

---

## SEO Notes

- Each page gets a unique `<title>` and `<meta name="description">`.
- `<link rel="canonical">` pointing to the canonical URL.
- Structured data: `Person` schema on `about.html`; `MedicalBusiness` on `index.html` — both marked `[CONTENT: …]` for product owner to populate.
- `sitemap.xml` — deferred; product owner or Infrastructure track generates after content is final.
- `robots.txt` — deferred; Infrastructure track.

## ⚠️ Legacy WordPress Redirect Flag (product owner / Infrastructure track action required)

The previous drbastaninejad.com ran on WordPress with indexed permalinks (e.g. `/services/rhinoplasty/`, `/?p=123`).  
Before this new static site goes live, a **redirect map from old WordPress URLs to new pages** must be defined and implemented at the server/CDN level.  
Bob AI does not configure server-side redirects. This is a required pre-launch Infrastructure track action.

**Suggested action:** Product owner to supply a list of high-traffic WordPress URLs from Google Search Console. Infrastructure track to implement 301 redirects in the server config before DNS is pointed to the new site.

---

## Backend Requirements for Blackbox AI / Product Owner

### Contact form inquiry endpoint

The `contact.html` inquiry form (name, phone, message) has no backend endpoint in `docs/API_CONTRACT.md`.  
The form is rendered as a non-functional shell until Blackbox AI publishes an endpoint.

**Required endpoint (suggested):**
```
POST /api/v1/inquiries
Body: { name: string, phone: string, message: string }
Response: { success: true, data: { message: "پیام شما دریافت شد" } }
```
This is not an intake endpoint and must never accept Code Meli, national ID, or patient clinical data.

---

*M•Z · MAZ//ID · © 2026 Dr. Shahin Bastaninejad*
