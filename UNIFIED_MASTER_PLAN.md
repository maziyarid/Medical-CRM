# UNIFIED MASTER PLAN — Dr. Shahin Bastaninejad Medical Platform

**Status:** Single source of truth, reconciling all prior documents
**Prepared:** 2026-07-26
**Supersedes:** `ROADMAP.md`, `PROJECT_STATUS.md` (this thread) + all planning docs from the parallel chat
**Author note:** This document exists because the project was being planned in two separate conversations with different context. From this point forward, only this file should be treated as authoritative. All other roadmap/status .md files in the repo are now historical/reference only.

---

## 0. Why this document exists

Two parallel conversations produced overlapping but not-identical plans:

| Source | What it produced |
|---|---|
| **This thread** | Live intake-form fixes (Code Meli checksum, old-browser compatibility, new fields), a lightweight dashboard/email roadmap, a static HTML profile mockup |
| **Other thread ("Medical-CRM" chat)** | Full multi-specialty CRM vision, MVP strategy with AI Copilot, a 22-table normalized MySQL schema (EAV+JSON hybrid), a working PHP MVC skeleton, OpenRouter routing spec, RAG ingestion spec |

The other thread's output is architecturally deeper and was designed to scale to a multi-specialty, multi-clinic SaaS product with AI built in from day one. This thread's output was a tactical fix-list for the *already-live* intake form. **They are compatible, not conflicting** — but they used different table names and different account/patient models, which would have caused real integration bugs if both were built independently. This document resolves every discrepancy.

---

## 1. Resolved architecture decisions (locked — do not re-litigate)

| Decision | Final answer | Reason |
|---|---|---|
| Database | **Single MySQL 8.x** (utf8mb4/InnoDB), no SQLite, no second live DB | Confirmed independently in both threads |
| Schema authority | **The 22-table schema from `Detailed MySQL Schema (Normalized + EMR EAV, JSON).md` wins** | It's multi-tenant-ready, versioned, audit-logged, and already maps all 19 frozen Sheet columns — this thread's simpler 4-table draft (`patients`, `appointments`, `account_setup_tokens`, `email_log`) is now **retired** |
| Patient account table | **`patients` table already exists in the 22-table schema** — do NOT create a second "patients" table for the dashboard. The dashboard patient portal login is a *new* `password_hash` + auth capability added onto the *existing* `patients` row, not a separate entity | This thread mistakenly proposed a second, parallel `patients` table — that would have caused duplicate patient records with the same mobile number |
| Password/session tables | Add `patient_auth_tokens` (rename of this thread's `account_setup_tokens`) referencing `patients.id` from the master schema | Naming consistency with the master schema's `_tokens`/`otp_codes` pattern |
| Email log | Add `email_log` table (from this thread) to the master schema — it did **not** exist there yet | New addition, no conflict |
| Appointments | **Use the master schema's `appointments` table** (has `provider_id`, `room_id`, full status enum) — NOT this thread's simplified read-only version | Master schema is already staff-CRM-compatible; building a separate simplified table would fragment scheduling data |
| Backend framework | **Custom lightweight PHP 8.x MVC** per `HP MVC Project Skeleton.md` — not Laravel/Symfony | Matches existing cPanel/LiteSpeed native-PHP deployment already live at `app.drbastaninejad.com` |
| RBAC | 5 roles: `super_admin`, `doctor`, `receptionist`, `nurse`, `patient` — already seeded in master schema | Already resolved in other thread |
| AI Copilot | OpenRouter router + RAG, human-in-the-loop only, no autonomous agents at MVP stage | Already resolved in other thread; this thread never touched AI |
| Frozen Sheet contract | **Unchanged** — 19 columns, exact names/order, still the Step 1 source of truth until MySQL migration runs | Confirmed in both threads |
| New intake fields (`Email`, `VisitReason`) | Added as extra columns **beyond** the frozen 19 — map to `intakes.email` (new) and `intakes.visit_reason` (new) in the master schema | This thread added these live; master schema needs two new nullable columns to receive them |
| IsTransfer default | `0` unless explicitly changed by staff | Confirmed live fix in this thread, no conflict |
| National ID validation | Official mod-11 checksum algorithm, implemented client-side (this thread) and must also be enforced server-side in `IntakeController::store()` per the master schema's `ValidatorService::isValidCodeMeli()` | Already designed in both threads — consistent |

---

## 2. What is ACTUALLY live in production right now

| Component | Status | Detail |
|---|---|---|
| `app.drbastaninejad.com` intake form | ✅ Live | 3-step wizard, OTP, signature, Code Meli checksum, old-browser-safe JS, علت مراجعه + email field added |
| Backend | ✅ Live | PHP 8.2/8.3, writes to Google Sheet "SmartFormat" (19 frozen columns), `/admin/` staff view |
| MySQL database | ❌ NOT yet created | Neither thread has run any migration yet — Google Sheets is still the only live data store |
| PHP MVC skeleton | 🟡 Designed, not deployed | Code exists in `HP MVC Project Skeleton.md` but has not been installed on the server |
| Dashboard subdomain | ❌ Not started | `dashboard.drbastaninejad.com` has no DNS, no code, no deployment |
| AI Copilot / OpenRouter | 🟡 Spec only | No API keys configured, no code deployed |

**This is the most important finding: nothing beyond the intake form is live.** Everything else — the 22-table schema, the MVC skeleton, the dashboard, the AI layer — exists only as design documents. This means there is no legacy data conflict to worry about; we can implement the master schema cleanly from zero.

---

## 3. Unified build sequence (replaces both prior roadmaps' Section 10s)

### Phase 0 — Reconciliation (this document)
Merge all planning into one schema and one roadmap. No code changes yet.

### Phase 1 — Database foundation
1. Deploy the 22-table master schema to MySQL (from `Detailed MySQL Schema...md`), plus two additions:
   - `intakes.email VARCHAR(120) NULL`
   - `intakes.visit_reason VARCHAR(120) NULL`
   - New table `patient_auth_tokens` (password reset / account setup, see §1)
   - New table `email_log` (see §1)
2. Run the one-time `_import_smartformat` migration job (already coded in `HP MVC Project Skeleton.md` §9) to backfill any existing Sheet rows into MySQL — this can run in parallel with Sheets without disrupting the live form.
3. Seed `roles`, `specialties` (already scripted in the schema doc §7).

### Phase 2 — Backend cutover
4. Deploy the PHP MVC skeleton (`app/Controllers`, `app/Models`, `app/Services`) alongside the existing `app_private/` code — do NOT replace the live intake endpoint until MySQL writes are verified in parallel (dual-write: keep writing to Sheet AND MySQL for a transition window).
5. Update `IntakeController::store()` to persist `Email` and `VisitReason` into the new `intakes` columns (currently these are sent by the frontend but have nowhere to land in the DB layer).
6. Verify Code Meli + OTP + Jalali-to-Gregorian conversion server-side (code already drafted).

### Phase 3 — Patient portal auth (dashboard.drbastaninejad.com)
7. Add `password_hash`, `email_verified_at` to the existing `patients` table (not a new table).
8. Build `patient_auth_tokens` flow: intake submission → auto-create/link `patients` row → send Account Setup email with signed token → patient sets password.
9. Reuse the intake's OTP-verified mobile as the patient login identity (no second OTP at signup) — confirmed decision from both threads.

### Phase 4 — Dashboard UI
10. Stand up `dashboard.drbastaninejad.com`, wire it to the same MySQL DB and PHP backend.
11. Build patient-facing sections: Overview, Profile (the HTML mockup already built in this thread is the visual reference), Medical record (read-only from `emr_records`/`intakes`), Appointments (read-only against the master `appointments` table), Documents (`media` table), Notification preferences.

### Phase 5 — Email system
12. Build the Vazirmatn RTL email templates (Appointment Confirmation, Account Setup) — content plan already exists in this thread's `ROADMAP.md` §7, now targets the unified `email_log`/`patients` tables.
13. Wire `Mailer.php` to log every send into `email_log`.

### Phase 6 — Staff CRM expansion (per master schema + design brief)
14. Global Dashboard, Patient Master Timeline, Scheduling Calendar (day/week/month, drag-drop), 1–2 EMR templates for ENT/rhinoplasty, Billing (Zarinpal), Task Board — per `Medical CRM.md` UI spec and the MVP 8-week plan.

### Phase 7 — AI Copilot v1
15. OpenRouter router service, RAG on clinic docs, human-in-the-loop only, logged to `ai_interactions`.

### Phase 8 — Everything else (unchanged, still deferred)
16. Real SMS provider contract, SQL Server async mirror, backups/monitoring/staging, multi-tenant SaaS, native mobile apps.

---

## 4. Open decisions still blocking implementation

These were raised in both threads and remain unanswered:

1. Reminder timing — hours/days before appointment, which channel(s)?
2. Self-booking vs. staff-only appointment creation for v1?
3. SMTP provider — cPanel built-in vs. dedicated transactional service?
4. Confirm: which SMS provider (Kavenegar / Ghasedak / FarazSMS / TSMS) is actually being registered as the service line?
5. Profile photo / media storage — confirm ArvanCloud or Liara account is provisioned (master schema assumes this in the `media` table's `storage_disk` field).
6. Confirm dual-write transition window length before fully cutting over from Google Sheets to MySQL.

---

## 5. File map (what to trust going forward)

| File | Status |
|---|---|
| **This document** | ✅ Authoritative |
| `Detailed MySQL Schema (Normalized + EMR EAV, JSON).md` | ✅ Authoritative for DB schema (with 3 additions noted in §1/§3) |
| `HP MVC Project Skeleton.md` | ✅ Authoritative for backend code structure |
| `Medical CRM.md` (UI/UX brief) | ✅ Authoritative for design system + screen specs |
| `MVP Architecture, Roadmap & AI Strategy.md` | ✅ Authoritative for AI/OpenRouter strategy + week-by-week sequencing reference |
| `OpenRouter Router Service Spec.md`, `RAG Ingestion Spec.md` | ✅ Authoritative for AI implementation details |
| `ROADMAP.md` (this thread) | ⚠️ Superseded — patient/appointment/email_log table design replaced by master schema |
| `PROJECT_STATUS.md` (this thread) | ⚠️ Superseded by §2 of this document |
| `dashboard_profile_mockup.html` (this thread) | ✅ Still valid as visual reference for Phase 4 |
| `Maziyar ID.md`, `Server Details.md` | ✅ Reference, unchanged |

---


---

## 6. Phase C implementation addendum (2026-07-27) — locked decisions

These decisions were made during the Phase C implementation session. They must not be relitigated without a new explicit §6 amendment.

### 6.1 Field-name normalisation contract (IntakeController)

The public intake form (`intake.html`) sends a camelCase JSON payload:
`firstName`, `lastName`, `fatherName`, `nationalId`, `birthDate`, `homeTel`, `homeAd`, `visitReason`, `isTransfer`.

The PHP backend has always expected snake_case: `first_name`, `last_name`, etc.
This mismatch was safe so far because the frontend calls the **mock** API, not the real backend.

**Locked decision:** The normalisation happens in `IntakeController::normalisePayload()` — a single, documented mapping in one file. The frontend payload contract is **not changed** (old-browser-safe JS cannot be broken). The mapping is:

| Frontend key | DB / backend key |
|---|---|
| `firstName`  | `first_name` |
| `lastName`   | `last_name` |
| `fatherName` | `father_name` |
| `nationalId` | `national_id` |
| `birthDate`  | `birth_date_jalali` |
| `homeTel`    | `home_tel` (stored in `raw_payload` only; not a top-level DB column) |
| `homeAd`     | `home_address` |
| `visitReason`| `visit_reason` |
| `isTransfer` | `is_transfer` |
| `email`      | `email` (no rename needed) |
| `description`| `description` (no rename needed — but field is `chief_complaint` on the DB row; see below) |

Note: the frontend sends `description` as free-text notes; the backend maps this to `intakes.chief_complaint` for the MVP (per frozen Sheet column mapping in §2). `visitReason` maps to the new `intakes.visit_reason` column.

### 6.2 Google Sheets column contract

The frozen 19-column Sheet contract (columns A–K in `GoogleSheetsService`) must not be changed.
Two new fields (`email`, `visit_reason`) are appended as columns **L** and **M** only.
Any future additions must use columns N onward. The Sheet tab name and spreadsheet ID are not touched.

### 6.3 Sheets sync outcome tracking

A new `sheets_sync_status` column is added to `intakes` (migration 006):
- `'pending'` — row committed to DB; Sheets write not yet attempted (should not persist after a normal request cycle, but covers a process-kill scenario).
- `'ok'` — Sheets append confirmed (API returned `updates` key).
- `'failed'` — Sheets throw caught; row is in DB but not in Sheet.
- `'skipped'` — Sheets not configured (env vars missing); expected in dev/staging.

**Idempotent re-try rule:** the idempotent 200 path re-attempts the Sheets write if and only if `sheets_sync_status != 'ok'`. It does not alter the DB row on a re-try failure (non-fatal, same as the initial write).

### 6.4 Schema divergence between docs/SCHEMA.md and migration 001

`docs/SCHEMA.md §3.4` (the 22-table long-term target) and migration 001 (the operational intake table) intentionally differ. Migration 001 is the source of truth for the running system. `docs/SCHEMA.md` is the migration target for Phase 1 database foundation. No code should be written against `docs/SCHEMA.md` until the full migration is run; all PHP code targets migration 001's shape.

---

*M•Z — MAZ//ID*
*Unified 2026-07-26*
