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
