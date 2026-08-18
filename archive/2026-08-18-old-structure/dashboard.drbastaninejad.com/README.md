# dashboard.drbastaninejad.com — PHP MVC Backend

<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

> **For Blackbox:** This README is your authoritative onboarding document.
> Read it fully before touching any file. All architecture decisions are locked.
> Do not introduce Composer, Laravel, Symfony, or any external framework.
> Do not change the namespace convention, response envelope shape, or database layer.

---

## 0. Canonical frontend decision (2026-07-31 — product owner instruction)

**There are two dashboard frontend shells in this repository. The canonical one is:**

> **`app.drbastaninejad.com/Frontend/`** — per-page HTML (15+ pages, full state management, escHtml, WCAG 2.1 AA, proper error/empty/loading states, staff CRM + patient portal)

The SPA shell at `dashboard.drbastaninejad.com/public/index.html` (single-page, JS-module-based) is the **Backend track's live-testing and development shell only**. It is NOT the customer-facing UI. Do not extend it for new features; do not point users to it.

| Shell | Location | Role |
|---|---|---|
| **Canonical (use this)** | `app.drbastaninejad.com/Frontend/pages/staff/` | 9 staff pages — customer-facing CRM UI |
| Canonical (use this) | `app.drbastaninejad.com/Frontend/pages/patient/` | 6 patient portal pages |
| Development/testing shell only | `dashboard.drbastaninejad.com/public/index.html` | Backend track API smoke-test; do not extend |

**Rules:**
- All new backend endpoints are documented in `docs/API_CONTRACT.md` (this directory) before either frontend consumes them.
- The per-page `app.drbastaninejad.com/Frontend/` pages consume the `docs/API_CONTRACT.md` contracts verbatim.
- The SPA shell at `public/index.html` may also consume these contracts for smoke-testing — it does NOT define them.
- Frontend track owns `app.drbastaninejad.com/Frontend/` — do not modify HTML, CSS, or JS in that tree from the Backend track.

---

## 1. Architecture overview

| Layer | Technology | Notes |
|---|---|---|
| Language | PHP 8.1+ | `declare(strict_types=1)` on every file |
| Database | MySQL 8 / MariaDB 10.6+ | InnoDB, utf8mb4, UTC timestamps only |
| HTTP dispatch | Custom `App\Core\Router` + `App\Core\Request` | No external routing library |
| Autoloading | PSR-4 via `spl_autoload_register` in `public/index.php` | No Composer |
| Auth | Bearer token (SHA-256 hash stored in `auth_tokens` table) | Enforced in `AuthMiddleware` |
| RBAC | Permission table join via `RbacMiddleware` | `super_admin` bypasses all checks |
| Timezone | All DB writes in UTC; Jalali only at presentation layer | Never store Jalali dates |
| SMS | Chain-of-responsibility (`SmsProviderChain`) | Kavenegar → Ghasedak → FarazSMS → TSMS → LogSmsProvider |
| Google Sheets | Service-account JWT, APCu token cache | Non-fatal; MariaDB is source of truth |

---

## 2. Directory map

```
dashboard.drbastaninejad.com/
│
├── public/                         ← DocumentRoot (point Apache/LiteSpeed here)
│   ├── index.php                   ← ONLY entry point; all requests route through this
│   ├── .htaccess                   ← Rewrites everything to index.php; blocks dotfiles
│   ├── index.html                  ← Staff SPA shell (sidebar + topbar)
│   ├── manifest.json               ← PWA manifest
│   ├── sw.js                       ← Service worker stub
│   └── assets/
│       ├── css/theme.css           ← Brand tokens (locked — do not modify colours)
│       └── js/
│           ├── app.js              ← Client-side router + API fetch layer
│           ├── calendar.js         ← Calendar views (day/week/month/agenda)
│           ├── emr.js              ← Dynamic EMR editor drawer
│           ├── jalali.js           ← Gregorian↔Jalali conversion (display only)
│           └── patients.js         ← Patient list + detail timeline
│
├── app/
│   ├── Core/
│   │   ├── Controller.php          ← Base: success(), error(), validationError()
│   │   ├── Database.php            ← PDO singleton (reads config/database.php)
│   │   ├── Model.php               ← Base: find(), create(), update(), softDelete()
│   │   ├── Request.php             ← Immutable HTTP value object (fromGlobals())
│   │   └── Router.php              ← Route registration + dispatch + middleware chain
│   │
│   ├── Controllers/
│   │   ├── AppointmentController.php
│   │   ├── DashboardController.php
│   │   ├── EmrController.php
│   │   ├── IntakeController.php    ← POST /api/v1/intakes (public form → DB + Sheets)
│   │   ├── OtpController.php       ← POST /api/v1/auth/otp/send|verify
│   │   ├── PatientController.php   ← Staff patient CRUD
│   │   └── PatientPortalController.php  ← 7 patient-facing endpoints (auth required)
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php      ← Bearer token validation; populates $req->user
│   │   └── RbacMiddleware.php      ← Permission check against roles/permissions tables
│   │
│   ├── Models/
│   │   ├── Appointment.php
│   │   ├── EmrRecord.php
│   │   ├── IntakeModel.php
│   │   └── Patient.php
│   │
│   └── Services/
│       ├── AiRouterService.php     ← OpenRouter stub (review-required, never auto-saves)
│       ├── AppointmentService.php
│       ├── GoogleSheetsService.php ← Sheets API v4 JWT dual-write; returns 'ok'|'failed'|'skipped'
│       ├── OtpService.php          ← isRateLimited(), send(), verify(), issueToken()
│       ├── PatientService.php
│       ├── SmsProviderChain.php    ← Chain: Kavenegar→Ghasedak→FarazSMS→TSMS→LogSms
│       ├── SmsService.php          ← Single-provider legacy stub (kept for OtpService compatibility)
│       └── ValidatorService.php    ← normalizeMobile(), isValidNationalId() mod-11, isValidJalaliDate()
│
├── config/
│   ├── database.php                ← DB connection params from ENV (required by Database.php)
│   ├── routes.appointments.php
│   ├── routes.auth.php
│   ├── routes.dashboard.php
│   ├── routes.emr.php
│   ├── routes.intake.php
│   └── routes.patients.php
│
├── database/
│   └── migrations/                 ← Run these in order on first deploy
│       ├── 001_create_intakes_table.sql
│       ├── 002_create_otp_codes_table.sql
│       ├── 003_create_auth_tokens_table.sql
│       ├── 004_add_email_visit_reason_to_intakes.sql
│       ├── 005_add_password_hash_to_patients.sql
│       ├── 006_add_sheets_sync_status_to_intakes.sql
│       └── 007_add_birth_date_jalali_to_intakes.sql
│
└── docs/
    └── API_CONTRACT.md             ← Single source of truth for every HTTP endpoint
```

---

## 3. Request lifecycle

```
Browser / app.drbastaninejad.com
        │
        ▼
public/.htaccess  →  rewrites to index.php
        │
        ▼
public/index.php
  ├── loads .env
  ├── registers PSR-4 autoloader (App\ → app/)
  ├── sets UTC timezone, CORS headers
  ├── handles OPTIONS pre-flight
  ├── builds App\Core\Router
  ├── requires all config/routes.*.php
  ├── calls Router::dispatch(Request::fromGlobals())
  │       │
  │       ├── regex-matches path + verb
  │       ├── runs middleware chain (AuthMiddleware → RbacMiddleware)
  │       └── calls Controller::method(Request)
  │                 └── returns array{ok, status, data, errors, meta}
  └── json_encode() + http_response_code()
```

---

## 4. Response envelope (never change this shape)

Every controller method returns a plain PHP array — `index.php` serialises it.

```php
// Success
return $this->success($data);
// → {"ok":true,"status":200,"data":{...},"errors":null,"meta":null}

// Error
return $this->error(422, 'INVALID_MOBILE', 'شماره موبایل معتبر نیست');
// → {"ok":false,"status":422,"data":null,"errors":[{"field":"mobile","message":"...","code":"..."}],"meta":null}

// Validation (multiple fields)
return $this->validationError($errors);
// → {"ok":false,"status":422,...}
```

---

## 5. Adding a new module — exact steps

1. **Migration** (if new columns/tables needed): add `NNN_description.sql` to `database/migrations/`. Use `ADD COLUMN IF NOT EXISTS` for idempotency.
2. **Model** in `app/Models/YourModel.php` — extend `App\Core\Model` or write raw PDO via `App\Core\Database::conn()`.
3. **Controller** in `app/Controllers/YourController.php` — extend `App\Core\Controller`. Every public method signature: `public function methodName(Request $req): array`.
4. **Route file** in `config/routes.your_feature.php`:
   ```php
   <?php
   // $router is injected by index.php via require
   use App\Controllers\YourController;
   use App\Middleware\AuthMiddleware;
   use App\Middleware\RbacMiddleware;

   $router->get('/api/v1/your-resource', [YourController::class, 'index'],
       [AuthMiddleware::class, new RbacMiddleware('your.permission')]);
   ```
5. **Register** the route file in `public/index.php` by adding `'routes.your_feature'` to the foreach array.
6. **Document** the new endpoint in `docs/API_CONTRACT.md` before writing any frontend code.

---

## 6. Authentication model

| Concern | Implementation |
|---|---|
| Patient auth | OTP → SMS → bcrypt-hashed OTP stored in `otp_codes` table |
| Staff auth | Password hash (`bcrypt`) in `users.password_hash` — login endpoint pending |
| Token | SHA-256 hash of raw token stored in `auth_tokens`; raw token returned once |
| Token storage (client) | `localStorage` key `mz_auth_token` |
| Expiry | `auth_tokens.expires_at` checked in `AuthMiddleware` |
| Revocation | `auth_tokens.revoked_at` checked in `AuthMiddleware` |
| `$req->user` shape | `{ id, uuid, clinic_id, role, user_type: 'patient'|'staff' }` |

---

## 7. Database rules

- **All PHP code targets the migration files** (`001–007_*.sql`), not `docs/SCHEMA.md`.
  `docs/SCHEMA.md` is the future normalised target — do not write code against it.
- `clinic_id` is on every clinical table. Always scope queries to `clinic_id`.
- Soft delete (`deleted_at`) on all clinical tables. Never hard-delete patient rows.
- `submission_uuid` on `intakes` has a `UNIQUE` constraint — idempotency at DB level.
- All timestamps UTC. Jalali conversion is always at the presentation layer only.

---

## 8. Google Sheets dual-write

- `GoogleSheetsService::appendIntake()` returns `'ok' | 'failed' | 'skipped'` — never throws.
- The DB transaction commits **before** the Sheets call. Sheets failure never rolls back the DB.
- `intakes.sheets_sync_status` tracks outcome. The idempotent `GET /intakes` re-submit path retries Sheets if status is not `'ok'` or `'skipped'`.
- Sheet columns A–K are frozen (match the legacy Google Sheet). Columns L–M are `email` and `visit_reason` (added by migration 004).

---

## 9. SMS provider chain

`SmsProviderChain` implements chain-of-responsibility. Order is configurable via `SMS_PROVIDERS` env var (comma-separated). Default: `kavenegar,ghasedak,farazsms,tsms,log`.

Each provider implements `SmsProvider` interface:
```php
interface SmsProvider {
    public function sendOtp(string $mobile, string $otp): void;
    public function sendReminder(string $mobile, string $message): void;
}
```

`LogSmsProvider` is always last — writes OTP to `error_log` in non-production; never throws.

> **TODO for next agent:** Wire `OtpService::send()` to use `SmsProviderChain` instead of the legacy `SmsService` stub.

---

## 10. Environment variables required

Create `.env` from `.env.example` (now in this directory). `chmod 600 .env`. Never commit `.env`.

Sample/placeholder values (replace before deploying — see `docs/DEPLOYMENT_GATE.md §4`):

```env
APP_ENV=production
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=mazcrm_db
DB_USER=mazcrm_user
DB_PASS=REPLACE_WITH_STRONG_PASSWORD
DEFAULT_CLINIC_ID=1

# Google Sheets dual-write (leave empty to skip)
GOOGLE_SHEET_ID=
GOOGLE_SHEET_TAB=Intakes
GOOGLE_SA_KEY_PATH=/home/USER/sa-key.json

# SMS providers (comma-separated, tried in order)
SMS_PROVIDERS=kavenegar,ghasedak,farazsms,tsms,log
KAVENEGAR_API_KEY=
GHASEDAK_API_KEY=
FARAZSMS_USERNAME=
FARAZSMS_PASSWORD=
TSMS_USERNAME=
TSMS_PASSWORD=
TSMS_FROM=
```

---

## 11. cPanel / LiteSpeed deployment checklist

1. Set the document root for `dashboard.drbastaninejad.com` to `public/`.
2. Place the entire project root **outside** `public_html` (e.g. in `~/apps/maz-crm/`).
3. Create `.env` in the project root. `chmod 600 .env`.
4. Enable `mod_rewrite` / LiteSpeed rewrite (already configured in `public/.htaccess`).
5. Run migrations 001–007 in order against the MariaDB instance.
6. Confirm PHP 8.1+ with `APCu` extension enabled (used by `GoogleSheetsService` token cache).

---

## 12. API endpoints (summary)

Full contract with request/response shapes is in `docs/API_CONTRACT.md`.

| Method | Path | Auth | Permission |
|---|---|---|---|
| POST | `/api/v1/auth/otp/send` | Public | — |
| POST | `/api/v1/auth/otp/verify` | Public | — |
| POST | `/api/v1/intakes` | Public | — |
| GET | `/api/v1/intakes` | Bearer | `intakes.view` |
| GET | `/api/v1/patients` | Bearer | `patients.view` |
| GET | `/api/v1/patients/{id}` | Bearer | `patients.view` |
| POST | `/api/v1/patients` | Bearer | `patients.manage` |
| PUT | `/api/v1/patients/{id}` | Bearer | `patients.manage` |
| GET | `/api/v1/patient/overview` | Bearer (patient) | `patient.self` |
| GET | `/api/v1/patient/profile` | Bearer (patient) | `patient.self` |
| PATCH | `/api/v1/patient/profile` | Bearer (patient) | `patient.self` |
| GET | `/api/v1/patient/appointments` | Bearer (patient) | `patient.self` |
| GET | `/api/v1/patient/documents` | Bearer (patient) | `patient.self` |
| GET | `/api/v1/patient/notification-preferences` | Bearer (patient) | `patient.self` |
| PATCH | `/api/v1/patient/notification-preferences` | Bearer (patient) | `patient.self` |
| GET | `/api/v1/appointments` | Bearer | `appointments.view` |
| POST | `/api/v1/appointments` | Bearer | `appointments.manage` |
| PATCH | `/api/v1/appointments/{id}/reschedule` | Bearer | `appointments.manage` |
| PATCH | `/api/v1/appointments/{id}/status` | Bearer | `appointments.manage` |
| GET | `/api/v1/dashboard/overview` | Bearer | `dashboard.view` |
| GET | `/api/v1/patients/{id}/emr` | Bearer | `emr.view` |
| POST | `/api/v1/patients/{id}/emr` | Bearer | `emr.edit` |
| GET | `/api/v1/emr/templates` | Bearer | `emr.view` |
| POST | `/api/v1/ai/emr-draft` | Bearer | `emr.edit` |

---

## 13. What Blackbox should build next

These items are specified and ready for implementation — no design decisions needed:

1. **Staff password login** — `POST /api/v1/auth/password` → verify `users.password_hash` (bcrypt), issue bearer token same as OTP flow. Route file `config/routes.auth.php` already bootstrapped.
2. **Wire `OtpService` → `SmsProviderChain`** — replace `new SmsService()` in `OtpService::send()` with `(new SmsProviderChain())->sendOtp(...)`.
3. **Billing module** — `invoices` table (see `docs/SCHEMA.md` §8 for target shape), `BillingController`, `routes.billing.php`. Zarinpal / IDPay payment gateway callbacks.
4. **Tasks / Kanban** — `tasks` table, `TaskController`, `routes.tasks.php`. Statuses: `todo`, `in_progress`, `done`. Priority: `low`, `medium`, `high`, `urgent`.
5. **Analytics** — `AnalyticsController::referralConversion()` (date-range, group by `intakes.referral_code`), `AnalyticsController::appointmentStats()`.
6. **Settings** — clinic working hours, user management (RBAC), EMR template builder (stores JSON template definitions).
7. **Jalali fix in `patients.js`** — replace `toLocaleDateString('fa-IR')` with `Jalali.formatFull()` from `jalali.js`.

---

## 14. What must NOT be changed

- The response envelope shape (`ok / status / data / errors / meta`).
- The `App\` PSR-4 namespace root.
- The DB column conventions (`created_at`, `updated_at`, `deleted_at`, `clinic_id`).
- `docs/API_CONTRACT.md` as the single source of truth for endpoints.
- Google Sheet columns A–K.
- Brand tokens in `public/assets/css/theme.css`.
