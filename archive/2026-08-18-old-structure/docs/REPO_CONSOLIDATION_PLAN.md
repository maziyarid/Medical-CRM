<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# REPO CONSOLIDATION PLAN

**Status:** PENDING PRODUCT-OWNER SIGN-OFF — do not archive or delete trees until this document is reviewed and approved.  
**Prepared:** 2026-07-29  
**Repos:** Grok (xAI) reconciliation pass + Task 1–3 execution  
**Authority:** `UNIFIED_MASTER_PLAN.md` (architecture) · `SPACE_COORDINATION_PROTOCOL.md` (track boundaries)  
**Repos:** This file may be updated; it does **not** grant permission to delete production or legacy paths without explicit sign-off.

## Persistent Role Ownership

### Bob AI — Frontend / Product UI
Owns:
- Public website redesign
- Dashboard UI
- Patient portal UI
- RTL layouts
- Design tokens and component styling
- Mobile UI adaptation
- PWA presentation layer
- APK/iOS UI wrapper behavior

### Blackbox AI — Backend / Database / Platform
Owns:
- PHP MVC backend
- Database schema and migrations
- Authentication, RBAC, sessions
- Intake idempotency and validation
- Scheduling, EMR, billing, analytics services
- API contracts and backend tests
- Deployment and environment documentation

### Shared rules
- Frontend never invents backend table or route names.
- Backend never changes design tokens or screen structure.
- Any cross-boundary naming change must be written into `UNIFIED_MASTER_PLAN.md` first.

---

## 0. Architecture lock (do not re-litigate)

| Layer | Canonical choice | Forbidden |
|---|---|---|
| Backend | Custom **PHP 8.x MVC** (`Medical-CRM` / `app.drbastaninejad.com` + `dashboard.drbastaninejad.com/app`) | Python FastAPI as production API |
| Database | **Single MySQL 8.x** utf8mb4/InnoDB | MongoDB as live store; second parallel clinical DB |
| Public intake | **https://app.drbastaninejad.com/** | Duplicate OTP / national-ID / signature on marketing site |
| Marketing frontend | **`maziyarid/drbst` → `Frontend/` (Next.js)** | Treating `Front-end Design/` (Vite prototype) as production |
| Schema / route names | Backend track only | Frontend inventing table/column/route names |

---

## 1. Repository map

| Repo | Role |
|---|---|
| `maziyarid/Medical-CRM` | PHP MVC, intake + staff SPA trees, schema docs, API contract |
| `maziyarid/drbst` | Marketing site: canonical Next.js (`Frontend/`) + LEGACY Vite (`Front-end Design/`) |
| `maziyarid/M-Z` | Brand identity reference only — not medical product UI |

---

## 2. Dashboard status — reconciliation vs UNIFIED_MASTER_PLAN §2

**UNIFIED_MASTER_PLAN.md §2 (2026-07-26) stated:**

> `dashboard.drbastaninejad.com` has **no DNS, no code, no deployment**.

**Repo evidence as of 2026-07-29 (`Medical-CRM` main):**

| Claim | Evidence |
|---|---|
| “no code” | **FALSE in-repo.** Tree `dashboard.drbastaninejad.com/` exists with `app/Controllers/*`, `app/Models/*`, `app/Services/*`, `config/routes.*.php`, `public/index.html`, PWA stubs. |
| “no DNS / no deployment” | **Not proven from git alone.** No deploy config in-repo proves live DNS for `dashboard.drbastaninejad.com`. Treat production dashboard as **not confirmed live** until DNS/hosting is verified outside git. |
| Plan document | **Stale on “no code”.** §2 should be amended after sign-off to: *code scaffold present in Medical-CRM; production DNS/deployment unconfirmed.* |

**Implication:** Agents must not assume a blank dashboard subdomain. They also must not assume public production without operational verification.

---

## 3. Task 1 outcome — `server.py` (drbst)

**Path:** `drbst/Front-end Design/src/backend/server.py`  
**Commit:** `73445c5` on `drbst` main (2026-07-29)

| Change | Detail |
|---|---|
| GET `/api/appointments` | **Removed** (unauthenticated PII list) |
| GET `/api/contact` | **Removed** (unauthenticated PII list) |
| POST `/api/appointments` | **Removed** — no live caller in Next.js `Frontend/` (see §4) |
| POST `/api/contact` | **Removed** — no live caller in Next.js `Frontend/` |
| Mongo / Motor client | **Removed** from active process path |
| CORS | Wildcard `*` replaced with explicit allowlist: `drbastaninejad.com`, `www.drbastaninejad.com`, `app.drbastaninejad.com` |
| Token compare | N/A — file had no bearer/token comparison |
| Archive/delete | **Not done** — file retained in-tree until this plan is signed off |

**Consequence:** The marketing site has **no working contact/appointment submission path through `server.py`**. Live clinical intake is only via **https://app.drbastaninejad.com/**.

### Mongo data (mandatory before any future archive/delete)

If any MongoDB instance was ever pointed at this prototype:

1. Export `appointments` and `contacts` collections (or confirm empty).
2. Store export offline under clinic data-governance policy (not in public git).
3. Only then consider deleting the legacy FastAPI file after sign-off.

---

## 4. Q1 evidence — Next.js callers of `/api/appointments` or `/api/contact`

**Scope searched:** entire `maziyarid/drbst` → `Frontend/` (Next.js) tree: `app/**`, `components/**`, `worker/**`, `scripts/**`, tests.

**Search targets:** `fetch(`, `axios`, `ky`, `useSWR`, path strings `/api/appointments`, `/api/contact`, any env-built API base calling those routes.

| Result | Detail |
|---|---|
| **Matches** | **None.** GitHub code search returned 0 hits for those patterns under `path:Frontend`. |
| `Frontend/app/components/appointment-form.tsx` | Client-only; `setComplete(true)` on submit; explicit “نمایشی” notice; **no HTTP**. |
| `Frontend/app/components/contact-form.tsx` | Client-only; `setSent(true)` on submit; explicit non-connected copy; **no HTTP**. |

Therefore POST endpoints on `server.py` had **no live frontend consumer** in the canonical Next.js tree.

---

## 5. Task 2 outcome — CTAs (drbst `Frontend/` only)

**Commit:** `d08c40e` on `drbst` main (2026-07-29)

| File | Change |
|---|---|
| `Frontend/app/page.tsx` | Booking CTAs → `https://app.drbastaninejad.com/` (labels unchanged) |
| `Frontend/app/components/site-chrome.tsx` | Header / mobile / footer / dock booking CTAs → live intake |
| `Frontend/app/components/ui.tsx` | Service “رزرو مشاوره” + CallToAction buttons → live intake |
| `Frontend/app/appointment/page.tsx` | Replaced demo form page with `redirect("https://app.drbastaninejad.com/")` |
| `Frontend/app/contact/page.tsx` | Replaced demo form page with same redirect |

**Not done:** merge of Medical-CRM PR #5; deletion of `Front-end Design/`.

---

## 6. Canonical frontend choice

| Tree | Verdict |
|---|---|
| `drbst/Frontend/` (Next.js) | **Canonical marketing site** — sole surface for SEO public pages going forward |
| `drbst/Front-end Design/` (Vite) | **LEGACY prototype** — keep until sign-off; do not extend; do not delete yet |
| `Medical-CRM/app.drbastaninejad.com/Frontend/` | **Canonical product UI** (intake, patient portal, staff CRM HTML) |
| `Medical-CRM/dashboard.drbastaninejad.com/public/` | Backend/dev SPA shell — same API contracts; not the marketing site |

---

## 7. Pending sign-off checklist (product owner)

- [ ] Approve this consolidation plan
- [ ] Confirm Mongo export completed or N/A (never deployed)
- [ ] Approve eventual archive/delete of `Front-end Design/src/backend/server.py`
- [ ] Approve eventual archive/delete of entire `Front-end Design/` after content parity check vs `Frontend/`
- [ ] Amend `UNIFIED_MASTER_PLAN.md` §2 dashboard row (code scaffold exists; production DNS unconfirmed)
- [ ] Confirm DNS/TLS for `dashboard.drbastaninejad.com` if/when staff CRM goes public
- [ ] Do **not** merge Medical-CRM PR #5 until Greptile/security items and CTA policy are satisfied on that branch independently

---

## 8. Agent rules during consolidation

1. No schema/route/table naming from Frontend track.
2. No second patients/appointments tables.
3. No reintroduction of Mongo or FastAPI as production.
4. All real intake/OTP flows go through PHP + documented `docs/API_CONTRACT.md`.
5. Append `PROGRESS_LOG.md`; never rewrite history entries.

---

*M•Z — MAZ//ID · https://maziyarid.com · © 2026 Maziyar / Dr. Shahin Bastaninejad*
