<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# PROGRESS_LOG — MΛZ Medical CRM

This file is append-only. Every agent appends completed tasks below with date, scope, and affected files.

---

## 2026-07-27 — Phase A + Phase B (Intake Write Path & OTP Auth)

**Agent:** Perplexity Space Agent  
**Instruction source:** User prompt 2026-07-27 / Space instructions (UNIFIED_MASTER_PLAN + SPACE_COORDINATION_PROTOCOL)  
**Audit action:** Fully read all existing controllers, models, services, routes, Core classes, config dirs, and docs/BACKEND_PLAN.md before writing a single line of new code.

### Audit findings (READ-ONLY, no changes)

| File | Status | Notes |
|---|---|---|
| `app/Core/Controller.php` | ✅ Exists | Base class with `success()`, `error()` helpers |
| `app/Core/Database.php` | ✅ Exists | PDO singleton, utf8mb4, UTC timezone |
| `app/Core/Model.php` | ✅ Exists | `find()`, `create()`, `update()`, `softDelete()` |
| `app/Controllers/AppointmentController.php` | ✅ Exists | Full CRUD + conflict detection |
| `app/Controllers/DashboardController.php` | ✅ Exists | KPI aggregations |
| `app/Controllers/EmrController.php` | ✅ Exists | EMR read/write |
| `app/Controllers/PatientController.php` | ✅ Exists | Patient search + timeline |
| `app/Models/Appointment.php` | ✅ Exists | `inRange()`, `hasConflict()`, `reschedule()` |
| `app/Models/Patient.php` | ✅ Exists | `search()`, `timeline()` — uses `patients` table |
| `app/Models/EmrRecord.php` | ✅ Exists | EMR record model |
| `app/Services/AppointmentService.php` | ✅ Exists | Wraps create with reminder hook stub |
| `app/Services/PatientService.php` | ✅ Exists | Staff-initiated patient upsert |
| `app/Services/AiRouterService.php` | ✅ Exists | OpenRouter client stub |
| `config/routes.appointments.php` | ✅ Exists | 4 routes, AuthMiddleware + RbacMiddleware |
| `config/routes.dashboard.php` | ✅ Exists | Dashboard KPI route |
| `config/routes.emr.php` | ✅ Exists | EMR routes |
| `config/routes.patients.php` | ✅ Exists | Patient routes |
| `public/index.html` | ✅ Exists | 10 KB dashboard shell |
| `public/manifest.json` | ✅ Exists | PWA manifest |
| `public/sw.js` | ✅ Exists | Service worker stub |
| `IntakeController` | ❌ Missing | **Deliverable A** |
| `OtpController` | ❌ Missing | **Deliverable B** |
| `ValidatorService` | ❌ Missing | **Deliverable A** |
| `OtpService` | ❌ Missing | **Deliverable B** |
| `GoogleSheetsService` | ❌ Missing | **Deliverable A** |
| `SmsService` | ❌ Missing | **Deliverable B** |
| DB migrations | ❌ Missing | **Deliverable A+B** |
| `PROGRESS_LOG.md` | ❌ Missing | **This file** |

### Deliverables committed in this session

| File | Type | Description |
|---|---|---|
| `app/Controllers/IntakeController.php` | NEW | `POST /api/v1/intakes` with `submission_uuid` idempotency, Jalali/Code Meli/mobile validation, transactional dual-write (MariaDB + Google Sheets), upsert-safe patient creation |
| `app/Controllers/OtpController.php` | NEW | `POST /api/v1/auth/otp/send` (rate-limited) + `POST /api/v1/auth/otp/verify` (bcrypt OTP, 5-min expiry, issues bearer token) |
| `app/Models/IntakeModel.php` | NEW | Maps to existing `intakes` table; `findByUuid()` for idempotency, `createIntake()`, `list()` paginated queue |
| `app/Services/ValidatorService.php` | NEW | `normalizePersianDigits()`, `normalizeMobile()`, `isValidMobile()`, `isValidNationalId()` (mod-11), `isValidJalaliDate()`, `jalaliToGregorian()` (pure PHP, no external lib) |
| `app/Services/OtpService.php` | NEW | `isRateLimited()`, `send()` (hashed storage), `verify()` (bcrypt check), `issueToken()` (SHA-256 token in `auth_tokens`) |
| `app/Services/GoogleSheetsService.php` | NEW | Sheets API v4 dual-write via Service Account JWT (no Composer dependency); APCu token cache; non-fatal on failure |
| `app/Services/SmsService.php` | NEW | Kavenegar REST stub; logs OTP in non-production; full provider chain deferred to Phase C |
| `config/routes.intake.php` | NEW | `POST /api/v1/intakes` (public) + `GET /api/v1/intakes` (staff + RBAC) |
| `config/routes.auth.php` | NEW | OTP send + verify routes |
| `database/migrations/001_create_intakes_table.sql` | NEW | Idempotent DDL; `submission_uuid` UNIQUE enforced at DB level |
| `database/migrations/002_create_otp_codes_table.sql` | NEW | OTP storage with bcrypt hash + expiry |
| `database/migrations/003_create_auth_tokens_table.sql` | NEW | Bearer token table with SHA-256 hash, user_type discriminator |
| `docs/API_CONTRACT.md` | NEW | Full documented API contract for Phase A+B endpoints (consumed by frontend agents) |
| `PROGRESS_LOG.md` | NEW | This file |

### Design decisions & rationale

1. **No second `patients` table** — `IntakeController.upsertPatient()` writes to the existing `patients` table. Match is on `mobile + clinic_id`.
2. **No second `appointments` table** — intake does not create an appointment row. That conversion happens in the staff UI (existing `AppointmentController`).
3. **No new PHP MVC skeleton** — all new files follow the existing `App\Core\Controller` / `App\Core\Model` / `App\Services\*` namespace pattern.
4. **Idempotency at DB level** — `UNIQUE KEY uk_submission_uuid` on `intakes.submission_uuid` prevents race-condition duplicates even if two requests arrive simultaneously.
5. **Dual-write is non-fatal** — Google Sheets failure is caught and logged, never rolls back the DB transaction. MariaDB is the source of truth.
6. **Pure PHP Jalali conversion** — no external package dependency (`morilog/jalali` is a Phase C Laravel upgrade). The algorithm is standard Jalali ↔ Julian Day Number math.
7. **OTP hashed with bcrypt** — raw OTP never persisted. In development (`APP_ENV != production`) it is also written to `error_log` for easier testing.
8. **Bearer token SHA-256 hashed** — raw token returned once to client, never stored in plain text.

### Remaining Phase C items (not in this commit)
- Full SMS provider chain (Ghasedak → FarazSMS → TSMS fallback)
- `AuthMiddleware.php` + `RbacMiddleware.php` (referenced in existing routes but not yet in repo)
- Staff password login endpoint (`POST /api/v1/auth/password`)
- `config/database.php` config file (referenced by `Database.php`)
- Running migrations on the actual MariaDB instance
- `manifest.json` icon assets + service worker full implementation

---

_Append new entries below this line._


## [2026-07-27 11:38] — Track: Frontend — Agent/Chat: Perplexity (Frontend/Dashboard coordination chat)
Phase: 0 (Reconciliation) → prep for Phase 4/Frontend audit
Completed: Verified public read access to drbst, Medical-CRM, M-Z repos via GitHub API; committed frontend coordination docs directly to repos.
Files touched:
- Medical-CRM/app.drbastaninejad.com/Frontend/FRONTEND_IMPLEMENTATION_GUIDE.md (new)
- Medical-CRM/app.drbastaninejad.com/Frontend/FRONTEND_MISSING_WORK_CHECKLIST.md (new)
- drbst/Frontend/FRONTEND_DESIGNER_AGENT_PROMPT.md (new)
Schema/API changes: none
Blocking questions raised: none — frontend agents should begin repository audit (Phase A) per UNIFIED_MASTER_PLAN.md before wiring any API.


## [2026-07-27 13:10] — Track: Frontend — Agent/Chat: MAZ//ID Frontend Implementation Agent
Phase: 4 (Dashboard UI) — audit before wiring
Completed: Repository audit for both frontends (`app.drbastaninejad.com/Frontend/` per-page HTML and `dashboard.drbastaninejad.com/public/` SPA) and `drbst/Frontend/` marketing site; classified every page done/partial/mock-only/missing; marked `drbst/Front-end Design/` as LEGACY (archived, no deletion pending user approval); added audience-split clarification to `FRONTEND_MISSING_WORK_CHECKLIST.md` naming `dashboard.drbastaninejad.com/docs/API_CONTRACT.md` as the shared endpoint contract source of truth.
Files touched:
- Medical-CRM/REPOSITORY_AUDIT.md (new)
- Medical-CRM/app.drbastaninejad.com/Frontend/FRONTEND_MISSING_WORK_CHECKLIST.md (updated: audience-split section)
- drbst/Front-end Design/README.LEGACY.md (new)
Schema/API changes: none (frontend track — no schema/API authority).
Blocking questions raised (recorded in REPOSITORY_AUDIT.md §6, requested from Backend track through UNIFIED_MASTER_PLAN.md amendment):
- GET /api/v1/patient/overview
- GET /api/v1/patient/documents
- GET/PATCH /api/v1/patient/notification-preferences
- GET /api/v1/media/{uuid}/url (signed short-TTL read URL)
- Full billing surface (list, detail, Zarinpal/IDPay redirect callback status enum)
- Full tasks surface (Kanban CRUD + status/priority enums)
- Full analytics surface (referral-source conversion, date-range)
- Full settings surface (clinic, working hours, users, roles, EMR template builder)
- drbst appointment CTA handoff: link to app.drbastaninejad.com/intake vs. new marketing lead endpoint (recommendation in audit: link to intake — avoid duplicating OTP/national-ID/signature logic).
