<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# MΛZ Medical CRM — MVP Frontend

Static, RTL-first, Persian-native frontend for the MΛZ Medical CRM. This is
what the staff and patients open. It talks to the PHP backend over REST — see
[`../../docs/BACKEND_PLAN.md`](../../docs/BACKEND_PLAN.md#api-contract) for the
JSON contract.

---

## Run locally

Zero build, zero npm.

```bash
python3 -m http.server 8080
# → open http://localhost:8080
```

**Demo OTP everywhere: `12345`.**

## Page inventory

### Public
- `index.html` — landing/router
- `pages/intake/intake.html` — 3-step public intake wizard (OTP + national-ID + signature)
- `pages/auth/login.html` — staff login
- `pages/auth/patient-login.html` — patient login

### Staff CRM (shell = `staff`)
- `pages/staff/dashboard.html` — KPIs, today's schedule, needs attention, Copilot card
- `pages/staff/patients.html` — searchable patient index
- `pages/staff/patient-detail.html` — patient master view + timeline + Copilot side panel
- `pages/staff/calendar.html` — day/week grid
- `pages/staff/emr.html` — dynamic EMR editor (rhinoplasty template shown)
- `pages/staff/billing.html` — invoices + Zarinpal/IDPay
- `pages/staff/tasks.html` — kanban board
- `pages/staff/analytics.html` — KPIs + source breakdown
- `pages/staff/settings.html` — clinic info + integrations + RBAC + templates

### Patient portal (shell = `patient`)
- `pages/patient/overview.html`
- `pages/patient/profile.html` (adapted from the client-provided mockup)
- `pages/patient/records.html`
- `pages/patient/appointments.html`
- `pages/patient/documents.html`
- `pages/patient/notifications.html`

## File map

```
Frontend/
├── index.html
├── assets/
│   ├── css/
│   │   ├── tokens.css         ← Design tokens — SSOT for colors / spacing
│   │   └── base.css           ← Reset + shared components (button, card, form, table, pill…)
│   ├── js/
│   │   ├── app.js             ← MAZCRM utilities + MOCKED API
│   │   └── chrome.js          ← Sidebar / topbar / footer injected on data-shell="staff|patient"
│   └── img/
│       ├── logo.svg
│       └── favicon.svg
└── pages/
    ├── auth/
    ├── intake/
    ├── staff/
    └── patient/
```

## The shell injection pattern

Every staff / patient page declares:

```html
<body data-shell="staff" data-nav="patients">
  <div class="app-shell">
    <main class="main">
      ...page content...
    </main>
  </div>
  <script src="../../assets/js/app.js"></script>
  <script src="../../assets/js/chrome.js"></script>
</body>
```

`chrome.js` reads `data-shell` and `data-nav` and injects:
- the sidebar (with the correct nav highlighted),
- the MAZ//ID brand footer,
- a mobile bottom nav.

To add a new staff page, copy any staff HTML file, change the `<title>`, the
`data-nav` value, and drop the page content inside `<main class="main">…</main>`.

## Wiring the mocks to the real backend

`assets/js/app.js` exposes `MAZCRM.api.*`:

```js
MAZCRM.api.listPatients()            // → array
MAZCRM.api.listAppointments()        // → array
MAZCRM.api.getDashboardKpis()        // → { today, pending, revenue, tasks }
MAZCRM.api.sendOtp(mobile)           // → { ok:true, demo_code:'12345' }
MAZCRM.api.verifyOtp(mobile, code)   // → { ok:true, token:'jwt…' }
MAZCRM.api.submitIntake(payload)     // → { ok:true, intake_id:1234 }
```

To go to production, replace each function body with a `fetch('/api/v1/...')`
call that returns the same shape. Endpoints are listed in `BACKEND_PLAN.md`.

## Iranian conventions already handled

- `MAZCRM.toEnDigits('۰۹۱۲۱۲۳۴۵۶۷')` → normalises Persian / Arabic digits.
- `MAZCRM.isValidMobile()` and `MAZCRM.isValidNationalId()` — the same
  algorithms the backend uses (mod-11 for national ID, `09\d{9}` for mobile).
- `MAZCRM.formatJalali('2026-07-25')` — approximate Gregorian→Jalali for
  presentation. Production should use the server-computed Jalali string
  (`patients.birth_date_jalali`) — the client formatter is a display fallback.

## Signature / copyright

Every file carries the `MAZ//ID` header. The Persian footer that appears on
every rendered page:

> ساخته شده توسط **Maziyar** — © 2026 دکتر شاهین باستانی‌نژاد. تمامی حقوق محفوظ است.

Do not remove the signature or attribution — see [`../../LICENSE`](../../LICENSE).

---

_M•Z_
