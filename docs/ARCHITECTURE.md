<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# Architecture — MΛZ Medical CRM

**Version:** 1.0 · **Author:** MAZ//ID · **Date:** 25 July 2026

---

## 1. Guiding principles

> **"Sell the skeleton, grow the muscle."**

- **Modular first**: one lean core (auth, patients, appointments, intake). Everything else (EMR templates, billing, AI, analytics) is a *pluggable module*.
- **AI is an adapter, not a dependency**: every LLM call goes through a single `RouterService` interface. Swap models without touching business code.
- **Persian-first, RTL-native**: the design system is built for Persian, and Latin is the fallback — not the other way round.
- **Cloud-first for storage, local-first for compute**: media on ArvanCloud/Liara S3 (sanction-proof); primary DB and PHP app on LiteSpeed/cPanel in Iran.
- **UTC in the DB, Jalali only at the presentation layer**. Never store Shamsi dates.

---

## 2. High-level system layers

```
┌───────────────────────────────────────────────────────────────┐
│  PRESENTATION LAYER                                             │
│  ┌───────────────┬───────────────┬──────────────┬────────────┐│
│  │ Staff CRM     │ Patient Portal│ Public Intake│  Public WP  ││
│  │ (this repo)   │ (this repo)   │ (this repo)  │  marketing  ││
│  └───────────────┴───────────────┴──────────────┴────────────┘│
└───────────────────────────┬───────────────────────────────────┘
                            │ REST + JSON over HTTPS · JWT
┌───────────────────────────┴───────────────────────────────────┐
│  APPLICATION LAYER (PHP 8.x MVC — Laravel or slim custom)     │
│  Controllers · Services · RBAC · Validation · Localization    │
├───────────────────────────────────────────────────────────────┤
│  MODULE LAYER (pluggable)                                      │
│  Intake · Scheduling · EMR · Billing · Media · AI Copilot     │
├───────────────────────────────────────────────────────────────┤
│  AI ORCHESTRATION LAYER                                        │
│  RouterService  →  OpenRouter API  →  [Model Pool] + RAG store│
├───────────────────────────────────────────────────────────────┤
│  DATA LAYER                                                    │
│  MySQL 8.x (primary)  │  ArvanCloud / Liara S3 (media)         │
│  Redis (cache/queue, planned v1.5)  │  SQL Server mirror (v2) │
└───────────────────────────┬───────────────────────────────────┘
                            │
┌───────────────────────────┴───────────────────────────────────┐
│  INTEGRATION LAYER (Iranian ecosystem)                         │
│  Kavenegar / Ghasedak / FarazSMS / TSMS · Zarinpal / IDPay    │
│  Async insurance verification queue                            │
└───────────────────────────────────────────────────────────────┘
```

### Why this shape works
- **Serving / Model / Data separation** is the recommended structure for AI-augmented systems — you can reason about caching, indexing, and fault tolerance independently.
- The **Router Service is the single entry point** for AI: it ingests the prompt, classifies intent, picks the cheapest capable model, and formats the result for human review.

---

## 3. Frontend architecture (this MVP)

### 3.1 Runtime

- Pure static HTML + CSS + vanilla JS. **No build step, no bundler, no npm.**
- Served by any static host (Nginx, Apache, Liara static, ArvanCloud CDN).
- Talks to the PHP backend over REST — the JSON contract is documented in [`BACKEND_PLAN.md`](BACKEND_PLAN.md#api-contract).

Why plain HTML/CSS/JS for MVP:
- Zero build tooling to break in production.
- Every clinic's IT team can debug it.
- The old-browser constraint for the public intake page is easier to satisfy without a bundler.

### 3.2 File layout

```
Frontend/
├── index.html                       ← landing / router
├── assets/
│   ├── css/
│   │   ├── tokens.css               ← Design tokens (SSOT — never bypass)
│   │   └── base.css                 ← Reset + shared components
│   ├── js/
│   │   ├── app.js                   ← MAZCRM.* utilities + mocked API
│   │   └── chrome.js                ← Sidebar / topbar / footer injector
│   ├── img/                         ← Brand SVGs
│   └── fonts/                       ← Local font fallbacks (Vazirmatn CDN by default)
└── pages/
    ├── auth/                        ← login.html, patient-login.html
    ├── intake/                      ← intake.html (public wizard)
    ├── staff/                       ← 9 pages
    └── patient/                     ← 6 pages
```

### 3.3 Design system — **locked** brand tokens

```css
/* Primary (clinical Evergreen system) */
--evergreen:      #2F7D32;
--evergreen-dark: #246b28;
--evergreen-soft: #E4F0E4;
/* Neutrals */
--graphite:       #25272C;
--muted:          #6A7078;
--border:         #DDE2DD;
--porcelain:      #F7F8F6;
--surface:        #FFFFFF;
/* Accents */
--brass:          #B6905E;
/* Semantic */
--error / --success / --warning / --info (+ soft variants)
/* MAZ//ID signature accents (dev/marketing surfaces only) */
--maz-primary:      #0EA5FF;
--maz-primary-dark: #0B6FAF;
--maz-accent:       #A8FF4D;
```

**Rules of engagement:**
- Never hardcode a hex value in a component file — extend `tokens.css` first.
- Never use pure black for text (use `--graphite`).
- Never use pure white text on light backgrounds.
- Card radius: `1rem`. Button/input radius: `0.65–0.75rem`.
- Cards get `--shadow`; never heavy black drop-shadows.

### 3.4 Typography

| Language | UI / body | Display / headings | Code |
|---|---|---|---|
| Persian (primary) | **Vazirmatn** 400/500/600/700 | Shabnam / Sahel 700 | IRANSans Mono / JetBrains Mono |
| Latin (fallback)  | **Inter** or system-ui | Merriweather / Poppins | JetBrains Mono |

Critical rule: Persian line-height is **1.6+** for body (never below 1.5). H1 stays at `clamp(1.4rem, 3vw, 1.75rem)` — the intake page must never trigger overflow at 360 px width.

### 3.5 Shared chrome injection (`chrome.js`)

Each page declares its shell via `<body data-shell="staff|patient" data-nav="dashboard">` and `chrome.js` injects the sidebar, mobile bottom nav, and the MAZ//ID brand footer. This keeps every page under 200 lines and removes the drift risk of copy-pasting sidebars across 15 files.

---

## 4. Backend architecture (planned — see [`BACKEND_PLAN.md`](BACKEND_PLAN.md))

```
app_private/
├── config/
├── src/
│   ├── Http/         (Controllers, Middleware)
│   ├── Services/     (business logic, RBAC, Auth, Router)
│   ├── Models/       (Eloquent / thin DB layer)
│   ├── Modules/      (pluggable: Intake, EMR, Billing, AI …)
│   └── Support/      (Persian digits, Jalali, National ID validator)
├── database/
│   ├── migrations/
│   └── seeds/
├── public/           (index.php + built assets from Frontend/)
├── storage/          (logs, cached views)
└── tests/            (PHPUnit)
```

### 4.1 Module boundaries
Each module (`Intake`, `Scheduling`, `EMR`, `Billing`, `Media`, `AI`) exposes:
- **Public API** — controller endpoints.
- **Domain service** — business logic (unit-testable, no HTTP knowledge).
- **Migrations** — DDL for its own tables.

Cross-module calls go through the domain service layer, never directly module → module DB queries.

---

## 5. Data architecture

Full DDL in [`SCHEMA.md`](SCHEMA.md). Highlights:

- 22 tables, `InnoDB`, `utf8mb4_unicode_ci`.
- All PKs `BIGINT UNSIGNED`, all public identifiers also have a `uuid CHAR(36)`.
- Every clinical table has `deleted_at` (soft delete) and `created_by` / `updated_by`.
- Global `audit_logs` for who-changed-what-when with before/after JSON diff.
- EMR uses a **hybrid EAV + JSON** design:
  - `emr_records.data_json` — full form snapshot (source of truth).
  - `emr_field_values` (EAV mirror) — indexed columns for searchable/reportable fields ("all diabetic patients").
- Multi-tenant ready: `clinic_id FK` on every core table (start with one row, add more later).

---

## 6. AI architecture (Dr. Copilot)

Full spec in [`AI_STRATEGY.md`](AI_STRATEGY.md). One-slide view:

```
User prompt
    │
    ▼
┌─────────────────┐
│  RouterService  │ ← rule-based routing first (context_type → tier)
└────────┬────────┘
         │  selects cheapest capable model
         ▼
┌─────────────────┐    ┌───────────────────┐
│    OpenRouter   │◄──►│  RAG Vector Store │  (clinic protocols, drug list,
└────────┬────────┘    └───────────────────┘   post-op instructions)
         │  fallback chain if primary model errors
         ▼
    Human "Accept" → write to `emr_records`
                     log to `ai_interactions`
```

Never auto-save. Never send raw PII to the model — pseudonymise with `patient_uuid`.

---

## 7. Iranian-market constraints (non-negotiable)

| Constraint | Implication |
|---|---|
| Full RTL | `dir="rtl"` on `<html>`; all chevrons and progress bars flip |
| Jalali calendar | Shamsi date pickers; store UTC, display Jalali |
| Persian digits | Inputs accept ۰۱۲۳; auto-normalise to EN before validation/DB |
| National ID (کد ملی) | 10 digits + mod-11 checksum, with clear error text |
| Mobile | `09\d{9}`, auto-format, normalise |
| SMS service line | Kavenegar / Ghasedak / FarazSMS / TSMS (multi-provider fallback) |
| Payment | Zarinpal / IDPay (Shaparak-compliant) |
| Storage | ArvanCloud / Liara S3 (in-country) |
| Old devices | No `?.` / `??` / `async` on the intake page; XMLHttpRequest fallback |

---

## 8. Deployment topology (MVP)

```
                  ┌──────────────────────┐
Cloudflare/       │  cPanel + LiteSpeed  │
Iranian CDN  ───► │  PHP 8.2/8.3         │
                  │  MySQL 8.x           │  ← app.drbastaninejad.com
                  │  Cron (queue worker) │
                  └────────┬─────────────┘
                           │
                           ▼
                  ┌──────────────────────┐
                  │  ArvanCloud S3       │  ← media, signed URLs only
                  └──────────────────────┘
                           │
                           ▼
                  ┌──────────────────────┐
                  │  OpenRouter          │  ← AI, tier-routed
                  └──────────────────────┘
```

Detailed steps in [`BACKEND_PLAN.md`](BACKEND_PLAN.md#deployment).

---

_M•Z · MAZ//ID · © 2026 Dr. Shahin Bastaninejad_
