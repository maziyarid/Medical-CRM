<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# CHATGPT_AGENT_ONBOARDING.md
## Universal AI Agent Sync Package
### MΛZ Medical CRM — Dr. Shahin Bastaninejad Platform

> **This file is the single document you paste into any new AI agent (ChatGPT, Claude, Gemini, Genspark, etc.) to bring it fully in sync with this project.** Read every section before writing a single line of code.

---

## PART 1 — System Prompt (paste as the first message or Custom Instructions)

```
You are a senior full-stack engineer working on the MΛZ Medical CRM platform
for Dr. Shahin Bastaninejad, a multi-specialty medical clinic in Iran.

Your codename on this project is: [AGENT_NAME] (replace with e.g. "GPT-Backend", "GPT-Frontend", "GPT-SEO").

Before writing any code, you MUST:
1. State which Phase (1–8) from UNIFIED_MASTER_PLAN.md you are working on.
2. State which Track you are on: Backend OR Frontend (one track per agent session).
3. Confirm you have read PROGRESS_LOG.md to avoid duplicating completed work.
4. Confirm the specific files you will touch — no cross-track file edits.

Core rules (non-negotiable):
- UNIFIED_MASTER_PLAN.md is the only authoritative architecture document. Never contradict it.
- PROGRESS_LOG.md is append-only. Always append your session entry when done. Never rewrite it.
- SPACE_COORDINATION_PROTOCOL.md defines track boundaries. Respect them.
- No second `patients` table. No second `appointments` table. One canonical schema.
- All PHP follows the existing App\Core\* namespace in app/ directory.
- All frontend is vanilla HTML/CSS/JS + Vazirmatn font, RTL (dir=rtl lang=fa), no React/Vue.
- Persian digit normalisation must be applied on every numeric input (mirrors ValidatorService.php).
- Bearer token key in localStorage: `mz_auth_token`. Intake UUID key in sessionStorage: `mz_intake_uuid`.
- API base: https://app.drbastaninejad.com/api/v1
- Brand signature on every file: MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad

When uncertain about a schema detail, re-read the authoritative files in this order:
  1. UNIFIED_MASTER_PLAN.md
  2. docs/API_CONTRACT.md (Phase A+B endpoints live here)
  3. PROGRESS_LOG.md (what has already been built)
  4. Ask the user — never guess a table/column/route name.
```

---

## PART 2 — Project Brief (read once, memorise)

### What this project is
A private-practice medical platform for **Dr. Shahin Bastaninejad**, an ENT/rhinoplasty specialist in Iran. It replaces a WordPress site and a Google Sheets intake system with:

| Surface | URL | Status |
|---|---|---|
| Marketing site | drbastaninejad.com | WordPress → rebuild (Phase 8) |
| Patient intake wizard | app.drbastaninejad.com/intake | ✅ Live (Google Sheets write) |
| Patient portal | app.drbastaninejad.com | 🔧 In progress (Phase 3–4) |
| Staff CRM dashboard | dashboard.drbastaninejad.com | 🔧 In progress (Phase 4–6) |
| Admin super-panel | admin.drbastaninejad.com | Phase 6 |

### Tech stack (locked — do not change)
| Layer | Choice | Reason |
|---|---|---|
| Frontend | Vanilla HTML/CSS/JS, Vazirmatn, RTL | No framework — works on cPanel LiteSpeed without a build step |
| Backend | PHP 8.2 custom MVC (`App\Core\*`) | Already deployed, no Composer dependency |
| Database | MariaDB/MySQL 8 utf8mb4 InnoDB | 22-table schema (see §2.3 below) |
| Auth | OTP (Kavenegar SMS) + bcrypt-hashed code + SHA-256 bearer token | Phase B complete |
| AI | OpenRouter (GPT-4o / Claude) via `AiRouterService.php` | Phase 7 |
| Calendar | Custom Persian (Jalali) calendar, no external lib | Pure PHP + JS |
| Payments | Zarinpal + IDPay | Phase 6 |
| PWA/APK | Service Worker (stub live) + Capacitor wrapper | Phase 8 |

### GitHub repositories
| Repo | Purpose |
|---|---|
| `github.com/maziyarid/Medical-CRM` | **Main repo** — backend PHP + frontend HTML/CSS/JS |
| `github.com/maziyarid/drbst` | Marketing site (drbastaninejad.com) |
| `github.com/maziyarid/M-Z` | Design system, brand assets, shared docs |

### Directory layout (Medical-CRM)
```
app/
  Core/          Controller.php, Database.php, Model.php
  Controllers/   AppointmentController, DashboardController, EmrController,
                 PatientController, IntakeController, OtpController  ← Phase A+B NEW
  Models/        Appointment, Patient, EmrRecord, IntakeModel          ← Phase A NEW
  Services/      AppointmentService, PatientService, AiRouterService,
                 ValidatorService, OtpService, GoogleSheetsService,
                 SmsService                                             ← Phase A+B NEW
config/
  routes.*.php   One file per domain (appointments, dashboard, emr,
                 patients, intake, auth)                               ← Phase A+B NEW
database/
  migrations/    001_intakes, 002_otp_codes, 003_auth_tokens           ← Phase A+B NEW
docs/
  API_CONTRACT.md                                                      ← Phase A+B NEW
app.drbastaninejad.com/
  Frontend/
    assets/
      css/       tokens.css, base.css
      js/        app.js (MAZCRM namespace), chrome.js
      img/
    shared/
      api.js     ← Phase 3 NEW — universal API adapter (import this)
    pages/
      auth/      patient-login.html  ← Phase 3 wired
      intake/    intake.html         ← Phase 3 PENDING wire to api.js
      patient/   overview.html, appointments.html, documents.html,
                 profile.html, notifications.html  ← Phase 4 PENDING
      staff/     (Phase 6)
```

---

## PART 3 — Current Project State (as of 2026-07-27)

### ✅ Phase A+B — Complete (Backend)
| Deliverable | File | Notes |
|---|---|---|
| Intake API | `app/Controllers/IntakeController.php` | POST /api/v1/intakes, UUID idempotency, dual-write MariaDB + Google Sheets |
| OTP Auth | `app/Controllers/OtpController.php` | Send + verify, bcrypt hash, bearer token |
| Validators | `app/Services/ValidatorService.php` | Mobile, Code Meli (mod-11), Jalali date |
| OTP service | `app/Services/OtpService.php` | Rate limit, bcrypt, SHA-256 token |
| SMS stub | `app/Services/SmsService.php` | Kavenegar — full chain Phase C |
| Google Sheets | `app/Services/GoogleSheetsService.php` | Sheets API v4 JWT, APCu cache |
| DB migrations | `database/migrations/001–003` | intakes, otp_codes, auth_tokens tables |
| API contract | `docs/API_CONTRACT.md` | All Phase A+B routes documented |

### ✅ Phase 3 — Partial (Frontend)
| Deliverable | File | Notes |
|---|---|---|
| API adapter | `app.drbastaninejad.com/Frontend/shared/api.js` | All endpoints wired, Persian error map, auth helpers, UI helpers |
| OTP login page | `Frontend/pages/auth/patient-login.html` | Full OTP flow wired to real backend, WebOTP, countdown, loading states |

### 🔧 Phase 3–4 — Pending (Frontend) — THIS IS YOUR TASK LIST
| # | File | Work needed |
|---|---|---|
| 1 | `Frontend/pages/intake/intake.html` | Replace `MAZCRM.api.submitIntake()` mock with `Intake.submit()` from `shared/api.js`; add UUID idempotency; loading state on submit button; `duplicate_submission` non-error state on success screen |
| 2 | `Frontend/pages/patient/overview.html` | Wire `Patient.getOverview()` + `Patient.getAppointments()`; replace hard-coded mock with skeleton → pending-backend → error → data states |
| 3 | `Frontend/pages/patient/appointments.html` | Wire `Patient.getAppointments()`; Jalali display via `MAZCRM.formatJalali()`; skeleton states |
| 4 | `Frontend/pages/patient/documents.html` | Wire `Patient.getDocuments()`; render list of signed-URL cards; pending-backend state until backend adds route |
| 5 | `Frontend/pages/patient/profile.html` | Wire `Patient.getProfile()`; show/edit form fields; pending-backend state |
| 6 | `Frontend/pages/patient/notifications.html` | Wire `Patient.getNotificationPreferences()` + `updateNotificationPreferences()`; pending-backend state |
| 7 | `Frontend/assets/css/base.css` (or new `utilities.css`) | Add `.skeleton`, `.skeleton-row`, `.skeleton-line`, `.state-pending`, `.state-error`, `.mz-toast` CSS (extract from patient-login.html inline styles) |

### 🔧 Phase C — Pending (Backend)
| # | File | Work needed |
|---|---|---|
| 1 | `app/Http/AuthMiddleware.php` | Bearer token validation — referenced in all protected routes but not yet committed |
| 2 | `app/Http/RbacMiddleware.php` | Role-based access — referenced in all staff routes but not yet committed |
| 3 | `app/Controllers/OtpController.php` | Add `POST /api/v1/auth/password` (staff password login) |
| 4 | `config/database.php` | DB config file referenced by `Database.php` — must be added (with env-var pattern) |
| 5 | `docs/API_CONTRACT.md` | Add 5 patient portal routes: GET /patient/overview, GET /patient/profile, GET /patient/appointments, GET /patient/documents, GET+PATCH /patient/notification-preferences |
| 6 | `app/Services/SmsService.php` | Full SMS chain: Kavenegar → Ghasedak → FarazSMS → TSMS fallback |
| 7 | `database/migrations/` | Run migrations 001–003 on the live MariaDB instance |

---

## PART 4 — API Contract Summary

### Live endpoints (Phase A+B)
```
POST /api/v1/intakes                 (public) — submit intake form
GET  /api/v1/intakes                 (staff)  — paginated intake queue
POST /api/v1/auth/otp/send          (public) — send OTP to mobile
POST /api/v1/auth/otp/verify        (public) — verify OTP, returns bearer token
```

### Pending endpoints (Phase C backend must add)
```
GET  /api/v1/patient/overview
GET  /api/v1/patient/profile
GET  /api/v1/patient/appointments
GET  /api/v1/patient/documents
GET  /api/v1/patient/notification-preferences
PATCH /api/v1/patient/notification-preferences
```

### Auth pattern
- All patient portal routes require `Authorization: Bearer {token}` header
- Token stored client-side in `localStorage` under key `mz_auth_token`
- Token is SHA-256 hash stored in `auth_tokens` table (raw token returned once on verify)

---

## PART 5 — Brand & Design Tokens

```css
/* Key tokens from tokens.css — do not invent new values */
--evergreen:         #2D7D5C;  /* primary brand colour */
--evergreen-dark:    #1F5940;
--evergreen-soft:    #EAF5EF;
--graphite:          #25272C;
--muted:             #6B7280;
--porcelain:         #F7F8FA;  /* page background */
--surface:           #FFFFFF;
--border:            #E5E7EB;
--error:             #DC2626;
--success:           #16A34A;
--info:              #2563EB;
--info-soft:         #EFF6FF;
--warning-soft:      #FFFBEB;
--radius-md:         8px;
--radius-lg:         12px;
--radius-full:       9999px;
--shadow:            0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
--font-fa:           'Vazirmatn', system-ui, sans-serif;
--font-en:           'Inter', 'Segoe UI', sans-serif;
--transition:        150ms ease;
--transition-slow:   300ms ease;
```

**All pages must be RTL** (`<html dir="rtl" lang="fa">`). Touch targets minimum 44px. No horizontal scroll at 320px viewport width.

---

## PART 6 — Shared JavaScript Namespace

`app.js` exposes `window.MAZCRM` with:
- `MAZCRM.toEnDigits(s)` — Persian/Arabic → Latin digits
- `MAZCRM.toFaDigits(s)` — Latin → Persian digits
- `MAZCRM.isValidMobile(m)` — Iranian mobile validation
- `MAZCRM.isValidNationalId(id)` — mod-11 Code Meli
- `MAZCRM.formatJalali(dateStr)` — Gregorian ISO → Jalali string
- `MAZCRM.toast(msg, type)` — toast notification
- `MAZCRM.initSignaturePad(canvas)` — signature pad
- `MAZCRM.api.*` — **MOCK** (MVP only) — replace with `shared/api.js` ES module imports

`shared/api.js` exposes ES module exports:
- `Auth.sendOtp(mobile)` / `Auth.verifyOtp(mobile, otp)` / `Auth.logout()`
- `Intake.submit(formData)`
- `Patient.getOverview()` / `.getProfile()` / `.getAppointments()` / `.getDocuments()` / `.getNotificationPreferences()` / `.updateNotificationPreferences(prefs)`
- `requireAuth()` — call at top of every patient portal page
- `renderSkeleton(el, n)` / `renderPendingBackend(el)` / `renderError(el, err)` / `showToast(msg, type)`
- `normalizePersianDigits(s)` / `normalizeMobile(s)` / `generateUUID()`

---

## PART 7 — PROGRESS_LOG.md Protocol

After completing ANY meaningful work, append this block to `PROGRESS_LOG.md` in the repo:

```
## [YYYY-MM-DD HH:MM UTC] — Track: [Frontend/Backend] — Agent/Chat: [YOUR_AGENT_NAME]
Phase: [number from UNIFIED_MASTER_PLAN.md]
Completed: [one-line summary]
Files touched:
  - [path] ([NEW/UPDATED])
Schema/API changes: [none OR describe]
Blocking questions raised: [none OR list]
```

**Never rewrite prior entries. This is a git-log, not a wiki.**

---

## PART 8 — Track Boundaries (who touches what)

| Track | May touch | Must NOT touch |
|---|---|---|
| **Frontend** | `app.drbastaninejad.com/Frontend/**`, `drbst/Frontend/**`, `public/*.html` | `app/Controllers/`, `app/Models/`, `app/Services/`, `config/routes.*.php`, `database/migrations/` |
| **Backend** | `app/**`, `config/**`, `database/**`, `docs/API_CONTRACT.md` | CSS/brand tokens, page copy, HTML structure, `shared/api.js` |
| **Both** | `PROGRESS_LOG.md` (append-only), `UNIFIED_MASTER_PLAN.md` (read-only) | — |

---

## PART 9 — Immediate Task (start here)

If you are a **Frontend agent**, start with Task 1 from Part 3:
> Wire `intake.html` — replace all `MAZCRM.api.*` mock calls with real imports from `shared/api.js`. Add UUID idempotency, loading state, and a graceful "already submitted" path on the success screen.

If you are a **Backend agent**, start with Phase C Task 1:
> Commit `app/Http/AuthMiddleware.php` — the bearer-token validator that all protected routes already reference but is not yet in the repo.

---

## PART 10 — Handshake Confirmation

When you start a new session with this project, your **first reply** must include this block:

```
✅ HANDSHAKE
Agent name  : [YOUR_AGENT_NAME]
Track       : [Frontend / Backend]
Phase       : [current phase number]
Read        : UNIFIED_MASTER_PLAN.md ✓ / PROGRESS_LOG.md ✓ / API_CONTRACT.md ✓
Last entry in PROGRESS_LOG: [copy the date+agent line of the most recent entry]
I will touch: [list files]
I will NOT touch: [list at least one cross-track file to confirm boundary awareness]
```

Do not proceed until you have output this block.

---

*MZ — MAZ//ID · https://maziyarid.com · © 2026 Maziyar / Dr. Shahin Bastaninejad*
