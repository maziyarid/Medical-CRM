<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# Backend Requirement Note
## Marketing Site Dynamic Content — drbastaninejad.com

**Date:** 2026-08-01  
**Author:** Bob AI (Frontend track)  
**Status:** ⛔ BLOCKED — Product-owner decision required before any work  
**Authority:** UNIFIED_MASTER_PLAN.md §3 (Locked Architecture) + §7 (Development Sequence)  

---

## 1. Product-Owner Request

> "All pages, services, etc. must be dynamic and have a backend."
> — Product owner, 2026-08-01

---

## 2. What "dynamic" means in scope

The following marketing pages currently contain static HTML content that would
benefit from CMS-style backend management:

| Page | Current state | What "dynamic" means |
|---|---|---|
| `services.html` + `services/*.html` | Static HTML content | Specialty/procedure descriptions, prices, FAQ editable without file commits |
| `gallery.html` | Placeholder — images not yet wired | Before/after image gallery pulled from server; consent-gated; new images uploaded by staff |
| `blog.html` + `blog/*.html` | Static HTML articles | Blog posts stored as records, editable by admin, new posts without code deploy |
| `about.html` | Static HTML bio + team | Doctor profile, team cards editable without file commits |
| `contact.html` | Static HTML + POST /api/v1/inquiries | Inquiry form already has a backend endpoint; map/phone static |
| `index.html` | Static HTML hero/trust copy | Trust numbers, testimonials, hero text editable via admin |

---

## 3. What backend work is required

Making these pages dynamic requires the following backend components, **none of which
currently exist in the PHP MVC application**:

### 3.1 Database tables (new schema — requires migration)

```sql
-- Content management
CREATE TABLE cms_pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  title_fa VARCHAR(255) NOT NULL,
  body_html MEDIUMTEXT,
  meta_description VARCHAR(320),
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  name_fa VARCHAR(255) NOT NULL,
  description_html MEDIUMTEXT,
  faq_json JSON,
  active TINYINT(1) DEFAULT 1,
  sort_order SMALLINT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE blog_posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(200) NOT NULL UNIQUE,
  title_fa VARCHAR(255) NOT NULL,
  excerpt_fa TEXT,
  body_html MEDIUMTEXT,
  published_at DATETIME,
  active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE gallery_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  before_path VARCHAR(500),
  after_path VARCHAR(500),
  caption_fa VARCHAR(500),
  consent_on_file TINYINT(1) DEFAULT 0,  -- ⚠️ must be 1 before row is served
  active TINYINT(1) DEFAULT 0,
  sort_order SMALLINT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**All migration files must follow the existing `migrations/NNN_*.sql` numbering scheme.**

### 3.2 PHP controllers required

| Controller | Endpoints | Notes |
|---|---|---|
| `MarketingController` | `GET /api/v1/public/pages/{slug}` | Public, unauthenticated, cached |
| `MarketingController` | `GET /api/v1/public/services` | Returns active services list |
| `MarketingController` | `GET /api/v1/public/services/{slug}` | Single service detail |
| `MarketingController` | `GET /api/v1/public/blog` | Published posts, paginated |
| `MarketingController` | `GET /api/v1/public/blog/{slug}` | Single post |
| `MarketingController` | `GET /api/v1/public/gallery` | Only `consent_on_file=1 AND active=1` rows |
| `ContentAdminController` | CRUD for all of the above | Requires `superadmin` or `doctor` role |

**These are new controllers on the `app.drbastaninejad.com` backend** (or a shared backend if
the architecture is consolidated). They must not be invented by a frontend agent.

### 3.3 Admin UI required

A staff-facing admin panel page is needed to manage:
- Service descriptions / FAQ
- Blog post editor (rich-text or Markdown)
- Gallery item upload + consent toggle
- Page meta / hero copy

This admin UI must live under `app.drbastaninejad.com/Frontend/pages/staff/` and must use
the locked design system (RTL, Irancell, Evergreen tokens).

### 3.4 Frontend changes required on marketing site

Once the above endpoints exist, every marketing page needs to be converted from static
HTML to a JS-loaded page that:
1. Fetches data from the public API on `DOMContentLoaded`
2. Renders content using `escHtml()` on all server strings
3. Shows a skeleton/loading state while fetching
4. Falls back gracefully if the API is unavailable (cached HTML or meaningful error)

**This is significant frontend rework** across 11+ HTML files.

---

## 4. Risks and constraints

| Risk | Detail |
|---|---|
| **Gallery consent** | Before/after patient images require documented patient consent. No image may be served from a dynamic endpoint unless `consent_on_file = 1`. This is a clinical and legal requirement — not a feature flag. |
| **SEO regression** | Converting static pages to JS-rendered content degrades search crawlability. SSR (server-side rendering) or pre-rendered HTML templates must be used to maintain SEO. This requires PHP view templates, not a client-side SPA. |
| **Deployment gate** | The PHP MVC backend has not yet run migrations 001–007 on the live database. No new migrations may be added to production until the deployment gate is signed off. |
| **Content security** | Any CMS-style admin must enforce RBAC (`superadmin` / `doctor` only). Rich-text HTML stored in the database must be sanitized server-side before serving. |

---

## 5. Decision required from product owner

Before any code is written, the product owner must answer:

1. **PHP view templates vs. client-side fetch?**  
   Option A: PHP renders pages server-side (SSR — better SEO, simpler, follows current architecture).  
   Option B: Pages fetch content via JS fetch on load (SPA-like — worse SEO without extra work).  
   **Recommendation: Option A — PHP templates.** Matches the locked PHP MVC architecture.

2. **Subdomain for public API?**  
   Should `MarketingController` live on `app.drbastaninejad.com` (same as patient portal) or
   a new `api.drbastaninejad.com` subdomain?  
   **Recommendation: `app.drbastaninejad.com/api/v1/public/*`** — no new subdomain needed.

3. **Priority and sequence?**  
   Marketing dynamic content is Phase 6 work (CRM expansion). Currently Phase 3 (patient portal)
   is in progress. Should this be elevated in priority?

4. **Gallery consent workflow?**  
   How does the clinic record patient consent? Paper form scanned to file? Digital signature
   via the existing intake flow? This must be defined before gallery upload is built.

---

## 6. What frontend can do NOW (without backend)

The following frontend work is unblocked and does NOT require a new backend:

| Page | Work that can be done now |
|---|---|
| `gallery.html` | Placeholder grid with "تصاویر به‌زودی اضافه می‌شوند" — product owner supplies images |
| All pages | Irancell font — ✅ already done (2026-08-01) |
| `contact.html` | POST /api/v1/inquiries already wired — no additional backend needed |
| All service pages | Content polishing once product owner supplies clinical text |

---

## 7. API contract additions needed

Once product owner approves the architecture, the following endpoints must be added
to `docs/API_CONTRACT.md` **before any frontend code is written against them**:

```
GET /api/v1/public/services          → list
GET /api/v1/public/services/{slug}   → detail
GET /api/v1/public/blog              → paginated list
GET /api/v1/public/blog/{slug}       → detail
GET /api/v1/public/gallery           → consent-gated list
GET /api/v1/public/pages/{slug}      → CMS page
```

---

**No frontend code for dynamic marketing pages may be written until this note is
approved and the contract endpoints are documented in `docs/API_CONTRACT.md`.**
