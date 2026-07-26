# dashboard.drbastaninejad.com — Admin/CRM Dashboard

Part of the single-admin, multi-subdomain Medical CRM platform for Dr. Shahin Bastaninejad.
This subdomain hosts the staff-facing CRM shell (Overview, Patients, Calendar, EMR, Media,
AI Copilot, Billing, Tasks, Analytics, Staff/RBAC, Settings) and the patient-facing portal
lives under the same brand system, sharing the MySQL database and PHP MVC backend defined
in `app.drbastaninejad.com`.

## Structure
```
dashboard.drbastaninejad.com/
├── public/                 # DocumentRoot — never expose app/ or config/ to the web
│   ├── index.html          # SPA shell: sidebar nav + topbar + dynamic view root
│   ├── manifest.json        # PWA manifest (installable, RTL, brand theme colour)
│   ├── sw.js                # Service worker: offline shell caching
│   └── assets/
│       ├── css/theme.css    # Locked brand tokens (Evergreen/Graphite/Porcelain) — see Medical CRM design brief
│       └── js/app.js        # Client-side router + API fetch layer
├── app/
│   ├── Core/Controller.php  # Base controller (shared response envelope)
│   └── Controllers/DashboardController.php  # /api/v1/dashboard/overview aggregation
└── config/routes.dashboard.php  # Route registrations appended to the shared router
```

## Design system
Brand tokens (Evergreen `#2F7D32`, Graphite `#25272C`, Porcelain `#F7F8F6`, Soft Sage `#E4F0E4`,
Brass `#B6905E`) are locked in `public/assets/css/theme.css` per `Medical CRM.md` §2. RTL, Vazirmatn
font, 44×44px touch targets, WCAG 2.2 AA contrast, and skeleton loading states are baked in.

## Why this is more capable than WordPress, while staying simpler to use
- One login, one sidebar, every module (patients, calendar, EMR, billing, tasks, analytics,
  RBAC, AI copilot) in a single consistent UI — no plugin sprawl, no per-plugin settings pages.
- Native PWA + installable APK/iOS shell (same codebase, no separate WordPress mobile plugin).
- Real relational schema (MySQL) with foreign keys across patients/appointments/billing/tasks,
  instead of WordPress custom-post-type workarounds.
- RBAC is enforced server-side per route (`RbacMiddleware`), not via a third-party plugin.
- AI Copilot panel is a first-class module wired directly into EMR notes — not bolted on.

## Backend contract
`GET /api/v1/dashboard/overview` (auth required, permission `dashboard.view`) returns:
```json
{
  "ok": true,
  "data": {
    "metrics": [{ "label": "...", "value": "...", "href": "#calendar" }],
    "attention": [{ "patient": "...", "item": "...", "badge": "warning", "status": "..." }],
    "today": [{ "patient": "...", "time": "09:30", "reason": "...", "status": "confirmed", "badge": "success" }]
  }
}
```
The frontend (`app.js`) fails soft to skeleton/empty states if this endpoint is unreachable,
so the shell is always renderable during backend rollout.

## Next modules to scaffold (same pattern: theme.css + app.js view + Controller + route)
Patients index/detail, Calendar (day/week/month/agenda), EMR dynamic editor, Media viewer,
Billing/invoices, Task board, Analytics, Staff/RBAC, Settings/template builder — all specified
in `Medical CRM.md` §4–5 and ready to implement against this same shell and brand tokens.


## Patients module (implemented)

Files added:
- `app/Models/Patient.php` — search (paginated, clinic-scoped) + `timeline()` (UNION across
  intakes, appointments, EMR notes, invoices — ordered newest-first per design brief §5.4).
- `app/Core/Model.php`, `app/Core/Database.php` — base PDO model/singleton shared by all modules.
- `app/Controllers/PatientController.php` — `index`, `show`, `store`, `update`, all clinic-scoped
  and RBAC-gated (`patients.view` / `patients.manage`).
- `app/Services/PatientService.php` — staff-initiated patient creation (dedupes by mobile),
  distinct from the public intake auto-account flow.
- `config/routes.patients.php` — route registrations for `/api/v1/patients[/:id]`.
- `public/assets/js/patients.js` — list view (search debounce, pagination, clickable rows) +
  detail view (sticky header, timeline with type badges: پذیرش/نوبت/یادداشت بالینی/فاکتور).
- `public/index.html` — added `#view-patients` and `#view-patient-detail` sections.
- `public/assets/js/app.js` — `showSection()` now toggles between Overview/Patients views.

### API contract
```
GET  /api/v1/patients?q=&page=&per_page=   -> { rows: [...], total, page, per_page }
GET  /api/v1/patients/{id}                 -> { patient: {...}, timeline: [...] }
POST /api/v1/patients                      -> { id }   (validates mobile + Code Meli)
PUT  /api/v1/patients/{id}                 -> { id }
```

Timeline entry types: `intake`, `appointment`, `emr_note`, `invoice` — each mapped client-side
to a Persian label and status badge colour, matching the brand token system.


## Calendar / Scheduling module (implemented)

Files added:
- `app/Models/Appointment.php` — `inRange()` (clinic + optional provider filter, joined with
  patient name for display) and `hasConflict()` (interval-overlap check using duration_minutes,
  excludes cancelled appointments) — implements conflict detection from Medical CRM.md §5.6.
- `app/Controllers/AppointmentController.php` — `index` (range query for calendar rendering),
  `store` (quick-create with server-side conflict check, HTTP 409 on overlap), `reschedule`
  (drag-and-drop target, re-checks conflict excluding itself), `updateStatus` (scheduled →
  confirmed/cancelled/completed).
- `app/Services/AppointmentService.php` — creation wrapper; deliberately does NOT trigger
  reminder emails/SMS yet (reminder timing is still an open question per ROADMAP.md §11).
- `config/routes.appointments.php` — `/api/v1/appointments`, `/api/v1/appointments/{id}/reschedule`,
  `/api/v1/appointments/{id}/status`, all RBAC-gated (`appointments.view` / `appointments.manage`).
- `public/assets/js/calendar.js` — Day / Week / Month / Agenda views, previous/next/today
  navigation, native HTML5 drag-and-drop reschedule (day/week grid), colour-coded status badges,
  working-hours grid (08:00–20:00). Month view shows up to 3 events per cell with a "+N more"
  overflow indicator.
- `public/index.html` — added `#view-calendar` section with view-switch buttons.

### API contract
```
GET   /api/v1/appointments?from=&to=&provider_id=        -> { events: [...] }
POST  /api/v1/appointments                                -> { id }  (409 on time conflict)
PATCH /api/v1/appointments/{id}/reschedule                -> { id }  (409 on time conflict)
PATCH /api/v1/appointments/{id}/status                    -> { id, status }
```

### Notes for the next agent
- Jalali (Shamsi) date display is NOT yet wired into calendar.js — it currently renders Gregorian
  with Persian weekday/month names only. Swap in `JalaliService`-equivalent client-side formatting
  before shipping to production, per the non-negotiable Iranian market constraint in Medical CRM.md §3.
- Self-booking (patient-initiated slot picking) is explicitly deferred per ROADMAP.md — this
  module is staff-only quick-create/drag-reschedule, matching that decision.
- Reminder-sent indicator on each appointment card is not yet rendered — `reminder_sent_at`
  column exists in the schema and should be surfaced once the SMS/email reminder timing decision
  (ROADMAP.md open question #1) is confirmed.


## Dynamic EMR Editor (implemented) — connects Patients + Calendar

Files added:
- `app/Models/EmrRecord.php` — `forPatient()` (timeline-ready, joined with author name),
  `templatesForSpecialty()` (schema-driven form templates per Medical CRM.md §5.7).
- `app/Controllers/EmrController.php` — `index` (patient's notes), `templates`, `store`
  (fixed fields: chief_complaint/diagnosis/plan + specialty_fields JSON blob), `draftNote`
  (AI Copilot — returns text only, `requires_review: true` always set, never auto-saved).
- `app/Services/AiRouterService.php` — placeholder wrapper for the OpenRouter integration
  (see OpenRouter Router Service Spec doc); returns a clearly-labelled placeholder string
  so the "AI draft — review required" UI card is never mistaken for real clinical content.
- `config/routes.emr.php` — `/api/v1/patients/{id}/emr`, `/api/v1/emr/templates`,
  `/api/v1/ai/emr-draft`, all RBAC-gated (`emr.view` / `emr.edit`).
- `public/assets/js/emr.js` — slide-in drawer with chief complaint / diagnosis / plan fields,
  an AI Copilot card with explicit Accept/Discard actions (never auto-saves), launched from:
  - Patient detail header ("+ یادداشت بالینی" button)
  - Calendar event (double-click an appointment to open a note pre-linked to that appointment)

This is the module tying Patients and Calendar together, per your request to finish the
connected module before moving to other work.

## Jalali (Shamsi) calendar fix (implemented)

- `public/assets/js/jalali.js` — dependency-free Gregorian↔Jalali conversion (public-domain
  algorithm), Persian digit conversion, and formatting helpers (`formatFull`, `formatShort`,
  `formatNumeric`). Backend continues to store all timestamps as UTC/Gregorian — Jalali is a
  display-only layer, per the non-negotiable Iranian market constraint in Medical CRM.md §3/§6.7.
- `public/assets/js/calendar.js` — updated to use Jalali for: range label (day/week/month/agenda
  headers), week-view day numbers, month-view day-of-month cells, agenda date headers, and
  event time display (now in Persian digits).
- **Still open**: Patients module (`patients.js`) still renders `last_visit` and timeline
  timestamps in Gregorian via `toLocaleDateString('fa-IR')` — this needs the same `Jalali.*`
  helpers applied next (tracked in `PROJECT_CHECKLIST.md` §2).

See `PROJECT_CHECKLIST.md` at the repo root for the full remaining roadmap and action items
split between AI-buildable work and decisions that require your input.
