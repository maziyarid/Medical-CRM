<!-- MAZ//ID · Dr. Shahin Bastaninejad Medical Platform -->
# Repository Audit — Frontend Track

**Date:** 2026-07-27
**Track:** Frontend
**Agent:** MAZ//ID Frontend Implementation Agent
**Phase:** 4 (Dashboard UI) per `UNIFIED_MASTER_PLAN.md` §3
**Scope:** `maziyarid/Medical-CRM` and `maziyarid/drbst`; `maziyarid/M-Z` referenced for signature rules only.
**Reading confirmed:** `UNIFIED_MASTER_PLAN.md`, `SPACE_COORDINATION_PROTOCOL.md`, `PROGRESS_LOG.md`, `PROJECT_CHECKLIST.md`, `docs/ARCHITECTURE.md`, `docs/SCHEMA.md`, `docs/BACKEND_PLAN.md`, `app.drbastaninejad.com/Frontend/FRONTEND_IMPLEMENTATION_GUIDE.md`, `app.drbastaninejad.com/Frontend/FRONTEND_MISSING_WORK_CHECKLIST.md`, `dashboard.drbastaninejad.com/docs/API_CONTRACT.md`, `drbst/Frontend/FRONTEND_DESIGNER_AGENT_PROMPT.md`, `M-Z/BRAND_IDENTITY.md`.

---

## 0. Classification legend

| Status | Meaning |
|---|---|
| ✅ **done** | Page exists, is styled per brand tokens, is wired to a real backend endpoint, and has skeleton / empty / error / permission states. |
| 🟡 **partial** | Page exists and is styled, but is not fully wired: some mock data, missing defensive states, or the API contract landed after the page was built. |
| 🟠 **mock-only** | Page renders but every data source is a client-side mock (`MAZCRM.api.*`, hard-coded HTML, etc.). No real endpoint call anywhere. |
| ❌ **missing** | Page is referenced by nav / brief but no file exists. |
| 🗄️ **legacy / archived** | Kept in the repo for reference. Do not extend, do not delete without explicit user confirmation. |

**Source of truth for API endpoints:** `dashboard.drbastaninejad.com/docs/API_CONTRACT.md`. New endpoint names must be requested from the Backend track and landed in `UNIFIED_MASTER_PLAN.md` before either side codes against them (per `SPACE_COORDINATION_PROTOCOL.md` §6).

---

## 1. `maziyarid/Medical-CRM/app.drbastaninejad.com/Frontend/` — Public intake · Patient portal · Staff CRM (per-page HTML)

This tree owns: public intake wizard, auth (staff + patient), patient portal (6 pages), and staff CRM (9 pages). Total 16 pages + 1 landing page + shared assets.

### 1.1 Landing / router

| File | Status | Notes |
|---|---|---|
| `index.html` | 🟠 mock-only | Static router card. No API calls. Fine as landing; no wiring needed. |

### 1.2 Auth (`pages/auth/`)

| File | Status | Wired endpoint | Missing |
|---|---|---|---|
| `auth/login.html` | 🟠 mock-only | `MAZCRM.api.sendOtp` + `verifyOtp` (mock, accepts `12345`) | Real `POST /api/v1/auth/otp/send` + `/verify`; token persistence; locked/rate-limited (429)/expired-OTP (410) states; session-expired banner; separate staff cookie/token scope from patient. |
| `auth/patient-login.html` | 🟠 mock-only | Same mocks | Same as above; patient login must use a **different** token scope than staff (per `SECURITY.md` §3 and `SPACE_COORDINATION_PROTOCOL.md` §5). |

### 1.3 Public intake (`pages/intake/`)

| File | Status | Wired endpoint | Missing |
|---|---|---|---|
| `intake/intake.html` | 🟠 mock-only | `MAZCRM.api.submitIntake` (mock) | Real `POST /api/v1/intakes`; **server-issued** `submission_uuid` sent with the payload (client generates UUID v4, persists to `sessionStorage`, reuses on retry — protocol is server-authoritative per the DB `UNIQUE KEY` on `intakes.submission_uuid`); confirmation UI must distinguish **DB commit success** from **Google Sheets sync status** (per `API_CONTRACT.md` "Idempotency & Dual-Write Notes"); handle `200 idempotent` vs. `201 created`; handle 422 field-level errors mapped to Persian messages; handle 500 with clear retry copy. |

### 1.4 Patient portal (`pages/patient/`)

| File | Status | Wired endpoint | Missing |
|---|---|---|---|
| `patient/overview.html` | 🟠 mock-only | none | Next appointment card, quick actions, clinic contact fallback, empty state. Endpoint: `TODO(API-CONTRACT)` — request `GET /api/v1/patient/overview` from Backend track. |
| `patient/profile.html` | 🟠 mock-only | none | Real read/edit boundary against `patients` table; avatar upload MIME validation + progress; save/conflict feedback. Endpoint: `TODO(API-CONTRACT)`. |
| `patient/records.html` | 🟠 mock-only | none | Read-only timeline from `intakes` + `emr_records`; staff-correction guidance ("to change this, contact reception"); no leakage of `notes`/staff-only fields. Endpoint: `GET /api/v1/patients/{uuid}` timeline (already exists, per SPA's `patients.js`). |
| `patient/appointments.html` | 🟠 mock-only | none | Upcoming + history from `appointments`; Jalali display via existing `jalali.js`; status pills + reminder-sent indicator; cancellation contact fallback. Endpoint: reuse `GET /api/v1/appointments?patient_id=...` (already used by SPA). |
| `patient/documents.html` | 🟠 mock-only | none | Signed-link download UX (short TTL); loading / expired-link / error states. Endpoint: `TODO(API-CONTRACT)` — request `GET /api/v1/patient/documents` and `GET /api/v1/media/{uuid}/url` verbatim. |
| `patient/notifications.html` | 🟠 mock-only | none | Real preference toggles; pending / saved / error states. Endpoint: `TODO(API-CONTRACT)` — request `GET/PATCH /api/v1/patient/notification-preferences`. |

### 1.5 Staff CRM (`pages/staff/`)

| File | Status | Wired endpoint | Missing |
|---|---|---|---|
| `staff/dashboard.html` | 🟠 mock-only | `MAZCRM.api.getDashboardKpis` + `listAppointments` (mock) | Real `GET /api/v1/dashboard/overview` (already implemented by Backend per `dashboard.drbastaninejad.com/README.md` §Backend contract); skeleton on KPI cards; empty state when no appointments today; click-to-filter KPI → patients/calendar. |
| `staff/patients.html` | 🟠 mock-only | `MAZCRM.api.listPatients` (mock) | Real `GET /api/v1/patients?q=&page=&per_page=` (already implemented); debounced search; pagination; Jalali `last_visit` display; table → mobile-card fallback under 640 px. |
| `staff/patient-detail.html` | 🟠 mock-only | none | Real `GET /api/v1/patients/{uuid}` returning `{ patient, timeline }`; sticky patient header on scroll; staff-only visibility cues (yellow left border on internal notes); safe rendering for ≥ 50 timeline entries (virtualisation or "show older" pagination). |
| `staff/calendar.html` | 🟠 mock-only | none | Real `GET /api/v1/appointments?from=&to=&provider_id=`; drag-reschedule → `PATCH /api/v1/appointments/{id}/reschedule`; status change → `PATCH /api/v1/appointments/{id}/status`; 409 conflict handling with clear warning UI; provider/room filters; day/week/month/agenda views. |
| `staff/emr.html` | 🟠 mock-only | none | Real `GET /api/v1/emr/templates`, `POST /api/v1/emr/records`, `PATCH /api/v1/emr/records/{uuid}`, `POST /api/v1/ai/draft`; JSON-schema-driven field renderer; specialty blocks (tooth chart, body diagram, media grid); AI Copilot always displays `requires_review: true`; Accept / Edit / Discard actions only, never auto-save. |
| `staff/billing.html` | 🟠 mock-only | none | Invoice list/detail; payment pending/success/failure; insurance "pending verification" state; Zarinpal/IDPay redirect. Endpoints: `TODO(API-CONTRACT)` — request full billing surface. |
| `staff/tasks.html` | 🟠 mock-only | none | Kanban with accessible drag alternative (keyboard-based move); filters (assignee / due / priority). Endpoints: `TODO(API-CONTRACT)` — request `GET/POST/PATCH /api/v1/tasks`. |
| `staff/analytics.html` | 🟠 mock-only | none | Referral-source conversion; date-range picker; empty / no-data states; readable Persian charts. Endpoints: `TODO(API-CONTRACT)` — request `GET /api/v1/analytics/*`. |
| `staff/settings.html` | 🟠 mock-only | none | Clinic settings, working hours, EMR template builder, user/RBAC admin UI. Endpoints: `TODO(API-CONTRACT)` — request `GET/PATCH /api/v1/settings/clinic`, `GET /api/v1/users`, `GET /api/v1/roles`. |

### 1.6 Shared assets (`assets/`)

| File | Status | Notes |
|---|---|---|
| `assets/css/tokens.css` | ✅ done | Locked brand tokens (Evergreen / Graphite / Porcelain / Soft Sage / Brass). Includes `--maz-*` accents but **flagged**: these must NOT be used on medical-product UI (per `FRONTEND_IMPLEMENTATION_GUIDE.md` and the current agent brief). Keeping the tokens for cross-brand marketing surfaces only. |
| `assets/css/base.css` | 🟡 partial | Shell + component primitives good. **Missing**: shared skeleton / empty / error / forbidden / offline / retry state classes as reusable primitives (will be added in this session as `states.css`). |
| `assets/js/app.js` | 🟡 partial | `MAZCRM.*` utilities are solid (Persian digits, mod-11, mobile validator, Jalali formatter, signature pad). The `MAZCRM.api.*` block is a mock — will be superseded by a new `assets/js/api.js` adapter that consumes `dashboard.drbastaninejad.com/docs/API_CONTRACT.md` verbatim. `MAZCRM.api.*` stays as a documented fallback for contract gaps, tagged `TODO(API-CONTRACT)`. |
| `assets/js/chrome.js` | ✅ done | Shell chrome injection works. No API calls, no wiring needed here. |
| `assets/img/logo.svg`, `favicon.svg` | ✅ done | Static assets. |

### 1.7 Missing files (referenced but not present)

| File | Why | Priority |
|---|---|---|
| `assets/js/api.js` | Real adapter to `/api/v1/*` per `API_CONTRACT.md`. Currently every page uses the mock in `app.js`. | High — blocks all wiring work. |
| `assets/js/states.js` + `assets/css/states.css` | Cross-cutting skeleton / empty / error / forbidden / offline / retry primitives. Required by `FRONTEND_MISSING_WORK_CHECKLIST.md`. | High — checklist says "before new features". |
| `assets/js/session.js` | Token storage / retrieval + auth header injection + 401/403 handling / session-expired banner. Separate scope for staff vs patient tokens. | High — required for any authed endpoint. |
| `assets/js/jalali.js` | Client-side Gregorian ↔ Jalali. **Note:** an equivalent already exists at `dashboard.drbastaninejad.com/public/assets/js/jalali.js`. To avoid duplication, this frontend will **copy** that file verbatim rather than re-implement — same algorithm, same tests. |
| `patient/session-expired.html` | Recovery page for expired patient sessions. | Medium. |
| `staff/session-expired.html` | Same for staff. | Medium. |
| `403.html`, `404.html`, `offline.html` | Standard error surfaces + service-worker fallback. | Medium. |

---

## 2. `maziyarid/Medical-CRM/dashboard.drbastaninejad.com/` — Backend + parallel SPA shell

**Ownership per `SPACE_COORDINATION_PROTOCOL.md` §5:** the Backend track owns this tree. Frontend track does not modify `app/`, `config/`, `database/`, `docs/API_CONTRACT.md`.

**Audience split (confirmed with product owner 2026-07-27):**
> "Do not archive either shell. The SPA's API contracts at `dashboard.drbastaninejad.com/docs/API_CONTRACT.md` are the shared wiring layer — `app.drbastaninejad.com/Frontend/` pages must use those same endpoint contracts, not invent new ones."

Both frontends are retained. The SPA at `dashboard.drbastaninejad.com/public/` is the Backend track's live-testing shell; the per-page `app.drbastaninejad.com/Frontend/` is the canonical customer-facing UI (public intake, patient portal, staff CRM). Both consume the **same** endpoints. `FRONTEND_MISSING_WORK_CHECKLIST.md` will be updated with this clarification in the same PR that adds this audit.

| Area | Status | Notes |
|---|---|---|
| `app/Controllers/*.php` | ✅ done (backend track) | Do not touch. Interfaces documented in `docs/API_CONTRACT.md` + `README.md`. |
| `app/Models/*.php`, `app/Services/*.php`, `app/Core/*.php` | ✅ done (backend track) | Do not touch. |
| `config/routes.*.php` | ✅ done (backend track) | Do not touch. |
| `database/migrations/*.sql` | ✅ done (backend track) | Do not touch. |
| `docs/API_CONTRACT.md` | ✅ authoritative | Frontend track reads verbatim. Any new endpoint request goes through `UNIFIED_MASTER_PLAN.md` amendment first. |
| `public/index.html` | ✅ done (backend track) | Working SPA shell. |
| `public/assets/js/{app,calendar,emr,patients,jalali}.js` | ✅ done (backend track) | Working SPA view modules with one real API call in `app.js`. Frontend track will **borrow the `jalali.js` verbatim** for `Frontend/assets/js/` to avoid two implementations of the same algorithm. |
| `public/assets/css/theme.css` | ✅ done (backend track) | Consistent with `Frontend/assets/css/tokens.css`. |
| `public/manifest.json`, `sw.js` | 🟡 partial | Manifest OK. Service worker is a stub — must not cache patient PII, auth responses, or signed media URLs (per `SECURITY.md` §4). Full SW implementation is deferred; the offline page must clearly state that clinical/patient data cannot be safely accessed offline. |

---

## 3. `maziyarid/drbst/Frontend/` — Marketing website (Next.js 16 + React 19)

**Canonical marketing tree.** Confirmed with product owner 2026-07-27: "Work exclusively inside `drbst/Frontend/`."

| File | Status | Notes |
|---|---|---|
| `app/layout.tsx` | 🟡 partial | Site shell. Needs verification against brand rules: no M-Z blue/neon; Vazirmatn primary; RTL. |
| `app/page.tsx` (Home) | 🟡 partial | Content rendered from `site-data.ts` (real Persian clinic content, real phone numbers). |
| `app/about/page.tsx` | 🟡 partial | Data from `site-data.ts`. |
| `app/services/page.tsx` | 🟡 partial | Data from `site-data.ts`. |
| `app/blog/page.tsx` + `app/components/blog-explorer.tsx` | 🟡 partial | Blog explorer client component. Backing content source unknown — `TODO(CONTENT-SOURCE)`: request from product owner whether posts are inline (`site-data.ts`), MDX, or CMS. |
| `app/contact/page.tsx` + `app/components/contact-form.tsx` | 🟠 mock-only | Contact form present. Submit target unknown — `TODO(API-CONTRACT)`: request an inbound-lead endpoint or a mail-to fallback decision. |
| `app/appointment/page.tsx` + `app/components/appointment-form.tsx` | 🟠 mock-only | 3-step appointment form that calls `setComplete(true)` on submit with no network call. **Per agent brief §5**: this CTA should link to the live intake application (`app.drbastaninejad.com/intake`) once the correct hand-off flow is confirmed by Backend. Recommended fix in a follow-up commit: replace the form with a clear CTA + explainer that hands off to `app.drbastaninejad.com/pages/intake/intake.html`. Do NOT re-implement the intake form here — that would duplicate the OTP / national-ID / signature logic. |
| `app/components/site-chrome.tsx` | 🟡 partial | Site navigation + footer. Needs `M•Z` byline in footer, no brand-mark misuse. |
| `app/components/ui.tsx`, `icons.tsx`, `before-after.tsx` | 🟡 partial | Reusable primitives. Icons use Persian-friendly geometric style. |
| `app/globals.css` | 🟡 partial | Tailwind + custom CSS. Needs a review pass to confirm brand tokens match `Frontend/assets/css/tokens.css` exactly (Evergreen `#2F7D32` etc.). |
| `app/site-data.ts` | ✅ done | Real clinic content — phones, hours, address, service catalogue. Solid single source of truth. |
| `content (4).zip` | ⚠️ artifact | Zipped content archive committed to the repo. Should be extracted and removed (large binary in git). Non-urgent. |
| `package.json`, `tsconfig.json`, `next.config.ts`, `vite.config.ts`, `worker/index.ts`, `build/sites-vite-plugin.ts`, `scripts/*` | ✅ done (build config) | Cloudflare Workers + Next.js hybrid build. Backend/DevOps track owns; frontend does not modify. |
| `README.md`, `FRONTEND_DESIGNER_AGENT_PROMPT.md` | ✅ done | Read-only docs. |
| `public/*.svg` | ✅ done | Static. |

---

## 4. `maziyarid/drbst/Front-end Design/` — 🗄️ LEGACY

Earlier React + Vite prototype for the marketing site. Superseded by `drbst/Frontend/` (Next.js 16).

**Decision (confirmed with product owner 2026-07-27):** "Work exclusively inside `drbst/Frontend/`. Mark `drbst/Front-end Design/` as LEGACY — archived in `REPOSITORY_AUDIT.md`. **No deletions yet — archive first, confirm with the user, then delete.**"

**This commit will add** `drbst/Front-end Design/README.LEGACY.md` as an in-tree marker declaring the folder archived, preventing agents (or accidental imports) from extending it. Every file below is frozen from now:

`design_guidelines.json`, `public/index.html`, `src/App.js`, `src/backend/server.py` (a stray Python file — flagged), `src/components/{Footer,Logo,Navbar,PageHeader,ThemeToggle}.jsx`, `src/index.css`, `src/lib/{content,icons,motion,theme}.{js,jsx}`, `src/pages/{About,Appointment,Blog,Contact,Home,Services}.jsx`, `tailwind.config.js`.

**Deletion approval requested** from product owner in a follow-up. No files are removed in this audit commit.

---

## 5. `maziyarid/M-Z/` — Personal brand identity

**Signature rules only.** Never use the M-Z personal palette (Z Electric `#0EA5FF`, Z Deep `#0B6FAF`, Neon Lime `#A8FF4D`) on medical-product UI. This has been reaffirmed at the top of `Frontend/assets/css/tokens.css` (`--maz-*` variables exist but are marked as dev/marketing surfaces only).

| File | Status |
|---|---|
| `BRAND_IDENTITY.md` | 📖 read-only reference. Author signature rules: `MΛZ` mark, `MAZ//ID` code header, `M•Z` byline, `Maziyar` wordmark. |
| `prototype/` | 📖 unrelated to medical product. Read-only reference. |

---

## 6. Gaps that block downstream work

Ordered by unblocking priority.

1. **API adapter (`Frontend/assets/js/api.js`)** — every wiring task depends on it. Ships in this session's next commit.
2. **Cross-cutting states (`Frontend/assets/{css/states.css, js/states.js}`)** — every data-driven page needs skeleton / empty / error / forbidden / offline / retry. Per checklist, these come before new features.
3. **Session layer (`Frontend/assets/js/session.js`)** — required for anything past `/auth/otp/verify`. Separate scopes for staff vs. patient bearer tokens.
4. **`submission_uuid` client policy on intake** — client generates UUID v4, persists to `sessionStorage` under a per-form key, reuses on retry. Server owns idempotency (DB `UNIQUE KEY`); client is UX only.
5. **Sheets-sync surfacing on intake success** — per `API_CONTRACT.md`, DB commit is authoritative; Sheets sync is best-effort. UI must not conflate them.
6. **Patient portal endpoints marked `TODO(API-CONTRACT)`** — requested from Backend track: `GET /api/v1/patient/overview`, `GET /api/v1/patient/documents`, `GET/PATCH /api/v1/patient/notification-preferences`, `GET /api/v1/media/{uuid}/url`. Each of these must be added to `UNIFIED_MASTER_PLAN.md` before either side implements against them (per `SPACE_COORDINATION_PROTOCOL.md` §6).
7. **drbst `appointment-form.tsx` handoff decision** — confirm with product owner whether the marketing appointment CTA should (a) hand off to the live intake app on `app.drbastaninejad.com`, or (b) capture leads locally and forward via a marketing API. Recommendation: (a), because the intake app already has OTP + national-ID + signature + real database write; duplicating any of that on the marketing site is a compliance risk.
8. **`drbst/Frontend/content (4).zip`** — extract or remove. A committed binary in the git history hurts long-term.
9. **Missing pages listed in §1.7** — session-expired, 403, 404, offline. Ship after §1–§3.

---

## 7. What this audit does **not** cover

- Backend PHP code — outside frontend track's ownership. Reviewed only to confirm API contract shape.
- Native mobile app wrappers — deferred per `UNIFIED_MASTER_PLAN.md` §3 Phase 8.
- SEO / structured data on `drbst/Frontend/` — needs a separate content-and-SEO pass.
- Email templates — a separate track per checklist.
- Database schema — read-only reference to `docs/SCHEMA.md` and `dashboard.drbastaninejad.com/database/migrations/*.sql`.

---

_M•Z — MAZ//ID_
