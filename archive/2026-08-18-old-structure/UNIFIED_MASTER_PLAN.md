<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# UNIFIED MASTER PLAN
## Dr. Shahin Bastaninejad Medical Platform

**Status:** Authoritative architecture and implementation sequence  
**Version:** 2.0  
**Date:** 2026-07-29  
**Owner:** MAZ//ID  
**Applies to:** `maziyarid/Medical-CRM`, `maziyarid/drbst`, `maziyarid/M-Z`

---

## 1. Purpose

This document is the single source of truth for technical architecture, data ownership,
implementation sequence, and deployment decisions for the Dr. Shahin Bastaninejad
medical platform.

It replaces conflicting or historical statements in:

- `ROADMAP.md`
- `PROJECTSTATUS.md`
- Earlier dashboard plans
- Earlier Laravel/FastAPI/MongoDB suggestions
- Agent comments that conflict with this document

Historical design documents remain useful as references only. They do not override this
file.

---

## 2. Locked Architecture

The following decisions are final unless the product owner approves a written amendment
to this document.

| Area | Locked decision |
|---|---|
| Application backend | Custom PHP 8.x MVC |
| PHP framework | No Laravel, Symfony, FastAPI, Node.js, or Python production API |
| Primary relational database | One MariaDB 10.11+ database using `utf8mb4` and InnoDB |
| Future database compatibility | Write portable SQL compatible with MySQL 8 and MariaDB 10.11 |
| Current temporary store | Google Sheet SmartFormat contract during intake transition only |
| Public intake URL | `https://app.drbastaninejad.com/` |
| Dashboard URL | `https://dashboard.drbastaninejad.com/` when deployed |
| Public marketing site | `https://drbastaninejad.com/` |
| Server operating system | AlmaLinux VPS |
| Web-server topology | Nginx with Apache/PHP-FPM and cPanel services; verify vhost routing before deployment |
| File storage | Private server storage during development; future S3-compatible Iranian object storage |
| Time storage | UTC in database; Asia/Tehran and Jalali conversion in presentation layer |
| RTL language | Persian-first RTL, with English support where needed |
| Mobile applications | Deferred until the web API, authentication, and scheduling foundation are stable |

### Explicitly forbidden in production

- A FastAPI/Python/Node.js backend API
- MongoDB as a clinical, CRM, or appointment system of record
- More than one active clinical relational database
- A separate patient table for the portal
- A separate OTP/national-ID/signature intake flow on marketing pages
- Secrets, webhook URLs, signatures, logs, or patient data committed to git
- Deploying unreviewed application code directly to a public hostname

---

## 3. Current Verified State

### Confirmed

| Component | Current state |
|---|---|
| Public website | Existing informational website; separate from the future CRM backend |
| Public intake | Three-step intake flow with OTP, Code Meli validation, signature, email, and visit reason |
| Intake persistence | Current path writes to a frozen Google Sheet contract |
| PHP runtime | PHP is available through the current server stack |
| Database server | MariaDB 10.11 is installed and running |
| Database application schema | Not yet created or deployed |
| PHP MVC backend | Incomplete; requires completion, tests, and integration |
| `app_private` | Incomplete; contains or supports the intake path but is not the finished CRM backend |
| Dashboard | Code/scaffold may exist in the repository; deployment/DNS must be treated as unproven until verified |
| Patient portal | Not complete |
| Calendar, EMR, billing, media | Not complete |
| AI/RAG/OpenRouter | Specification only; not production-ready |
| SQL Server mirror | Deferred; not part of the initial deployment |

### Unproven until independently checked

- Whether any dashboard subdomain is publicly deployed
- Whether any legacy Python/MongoDB service is installed or listening
- Whether historical MongoDB records exist
- Whether historical repository paths contain sensitive files or credentials
- Whether any legacy frontend is reachable by a production URL

Agents must label these items `unproven` until direct evidence exists.

---

## 4. Data Ownership

### During development

- Use synthetic test data only.
- Do not store real patient data, signatures, national IDs, OTPs, or credentials in git.
- Keep `.env` files outside version control.
- Keep signature and upload directories outside the public document root.

### During the intake transition

The existing Google Sheet is temporary. The frozen column order must not change without
an approved migration:

```text
FirstName
LastName
FatherName
TavalodDay
TavalodMonth
TavalodYear
HomeTel
Mobile
Mobile2
CodeAshnaei
CodeBimeh
CodeMeli
CodeJob
HomeAd
Description
IsTransfer
drugs
difficult
morefmob
```

`Email` and `VisitReason` are application fields and must map to nullable columns in the
future `intakes` table.

### After database cutover

- MariaDB becomes the primary system of record.
- Google Sheets becomes read-only historical/export support, then is retired according to
  a documented migration decision.
- SQL Server remains deferred and must not be introduced during initial CRM development.

---

## 5. Required Backend Structure

The PHP MVC application must use a single coherent structure:

```text
app/
  Controllers/
  Core/
  Middleware/
  Models/
  Services/
  Validators/
  Views/
config/
database/
  migrations/
  seeds/
docs/
public/
routes/
storage/
  app/
  logs/
  private/
tests/
  Unit/
  Integration/
```

### Storage rules

- `storage/private/` is never web-accessible.
- Signatures, uploads, generated documents, pointer files, and logs remain outside
  `public/`.
- Runtime storage directories must be writable only by the PHP process user.
- Runtime storage content must be ignored by git.

### Required backend capabilities before public CRM deployment

1. Database connection and migration runner
2. Role-based access control: `superadmin`, `doctor`, `receptionist`, `nurse`, `patient`
3. Secure session/authentication service
4. OTP verification abstraction
5. Server-side Code Meli and mobile-number validation
6. CSRF protection, rate limits, validation, audit logs, and error redaction
7. Intake idempotency protection implemented once in the backend
8. Secure media/signature storage
9. PHPUnit unit and integration tests
10. Database backup and restore procedure

---

## 6. Intake Submission Rule

The intake backend owns duplicate-submission prevention.

The canonical state vocabulary is:

```text
pending
attempting
submitted
failed_confirmed
outcome_unknown
```

Rules:

1. The backend creates one stable idempotency identity before any external side effect.
2. The same OTP/token must always resolve to the same submission identity.
3. A second simultaneous request must never cause another Sheet or database write.
4. A failed known-no-write outcome is `failed_confirmed`.
5. A timeout, crash, or ambiguous external-write result is `outcome_unknown`.
6. The terminal-success name is `submitted`, never `succeeded`.
7. Frontend button disabling is a user-experience enhancement only; it is not the
   integrity mechanism.

No agent may implement another idempotency approach without a written amendment.

---

## 7. Development Sequence

### Phase 0 — Repository and environment reconciliation

- Confirm repository ownership and active branches.
- Confirm the actual PHP MVC structure.
- Confirm `app_private` gaps and move reusable code into the defined application structure.
- Verify actual server stack with read-only commands.
- Keep development local/private; do not deploy unfinished work.

### Phase 1 — Database foundation

- Finalize the approved MariaDB schema and migrations.
- Add `intakes.email` and `intakes.visit_reason` as nullable fields.
- Add `patient_auth_tokens` and `email_log` according to the approved schema.
- Create test seed data only.
- Build backup/restore documentation.
- Run migrations locally and in private staging before production.

### Phase 2 — Intake backend cutover

- Complete PHP MVC intake services/controllers/validators.
- Implement server-side OTP, Code Meli, mobile normalization, signature handling, and
  idempotency.
- Add tests for success, invalid input, duplicate submission, external failure, and
  ambiguous outcome.
- Use controlled dual-write only after the database path passes tests:
  Google Sheet plus MariaDB for a documented transition period.
- Do not replace the stable intake path until dual-write reconciliation passes.

### Phase 3 — Authentication and patient portal

- Extend the existing `patients` table; do not create a parallel portal-patient table.
- Implement account setup, password reset, secure sessions, and role checks.
- Reuse the verified intake mobile identity where appropriate.
- Build patient overview, profile, appointments, documents, and notification preferences.

### Phase 4 — Staff dashboard

- Deploy `dashboard.drbastaninejad.com` only after authentication and database foundation.
- Build intake queue, patient timeline, appointment list, and role-appropriate dashboard.
- Use the locked MAZ//ID design tokens and RTL accessibility requirements.

### Phase 5 — Scheduling and communications

- Build provider/room scheduling, conflicts, reminders, email templates, and message log.
- Select a production SMS provider and service line before enabling real OTP/reminders.

### Phase 6 — CRM expansion

- Add EMR templates, secure media, billing, tasks, analytics, and settings.
- Keep specialty fields modular using the approved schema design.

### Phase 7 — AI assistant

- Implement OpenRouter/RAG only after access controls, audit trails, and clinical data
  boundaries are complete.
- AI output is always draft-only and requires human review before clinical record use.

### Phase 8 — Deferred expansion

- PWA hardening, Android APK, iOS app, SQL Server mirror, multi-clinic SaaS capabilities,
  and other integrations are deferred until the web platform is stable.

---

## 8. Deployment Policy

The team develops and tests first. The product owner deploys only after the mandatory
deployment gate in `docs/DEPLOYMENT_GATE.md` is complete.

### Development

- Local Windows development is permitted.
- Use test credentials and synthetic data.
- Never commit `.env`, credentials, OTP records, logs, signatures, uploads, or database dumps.
- Do not make unfinished development endpoints public.

### Private staging

- Create a separate private/staging hostname or access-restricted environment.
- Use a separate staging database and staging secrets.
- Run migrations, backups, restore tests, and acceptance tests there first.
- Do not use real patient data unless approved safeguards and legal requirements are met.

### Production

Before enabling real patient intake:

1. Confirm source review and tests.
2. Confirm backups and rollback plan.
3. Generate and configure production-only secrets.
4. Rotate temporary Google Apps Script/webhook credentials.
5. Verify TLS, Nginx/Apache routing, PHP-FPM, and least-privilege database access.
6. Confirm private storage is not web-accessible.
7. Obtain product-owner approval recorded in the deployment log.

---

## 9. Open Product Decisions

The following require product-owner decisions before the relevant phase begins:

- SMS provider and registered service line
- Reminder timing and channels
- Self-booking versus staff-only appointment creation
- Email/SMTP provider
- Object-storage provider for clinical media
- Duration and reconciliation criteria for Google Sheet/MariaDB dual-write
- Payment-provider scope and billing requirements
- AI knowledge-source approval and retention policy

These are not blockers for Phase 0 repository reconciliation or local PHP MVC completion.

---

## 10. Agent Rules

Every agent must:

1. Read this document, `SPACE_COORDINATION_PROTOCOL.md`, and the latest `PROGRESS_LOG.md`.
2. Declare one bounded task before editing.
3. Avoid work owned by another active entry.
4. Use existing backend-controlled table, column, and API names.
5. Use frontend-controlled design tokens and component conventions.
6. Submit complete diffs/tests/documentation, not only a plan.
7. Append a factual entry to `PROGRESS_LOG.md` after meaningful work.
8. Never claim deployment, test, security, or credential rotation occurred without evidence.

---

## 11. Amendment Process

A deviation from this plan requires:

1. A short written amendment proposal.
2. The affected files, data, and deployment consequences.
3. Product-owner approval.
4. An append-only entry in `PROGRESS_LOG.md`.

No agent may silently introduce Laravel, Python, MongoDB, a second patient table, a second
database, or a new production API stack.
