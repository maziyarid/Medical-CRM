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
- Canonical UI changes must remain in `app.drbastaninejad.com/Frontend/`; the dashboard `public/index.html` smoke shell must not be promoted to product UI.

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
| SMS | Chain-of-responsibility (`SmsProviderChain`) | Kavenegar → Ghasedak → FarazSMS → TSMS; Log provider only outside production |
| Google Sheets | Live WorkingVersion webhook + `Code.gs` | WorkingVersion remains canonical Sheet writer; dashboard DB is clinical source of truth |

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
│       ├── AiRouterService.php     ← Server-only OpenRouter client; disabled by default, review-required
│       ├── AppointmentService.php
│       ├── GoogleSheetsService.php ← duplicate-write guard; WorkingVersion owns live Sheet writes
│       ├── OtpService.php          ← isRateLimited(), send(), verify(), issueToken()
│       ├── PatientService.php
│       ├── SmsProviderChain.php    ← Chain: Kavenegar→Ghasedak→FarazSMS→TSMS (LogSms only outside production)
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
│   ├── database/install/            ← clean installer for a new database
│   └── database/migrations/         ← additive upgrades for existing installs
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
| Patient auth | Mobile + OTP primary; reset OTP may be SMS or matching email |
| Staff auth | Mobile + OTP with explicit `audience=staff` |
| Token | SHA-256 hash of raw token stored in `auth_tokens`; normal APIs accept only `purpose=session` |
| Token storage (client) | Separate sessionStorage keys for patient and staff; no shared bearer key |
| Expiry | `auth_tokens.expires_at` checked in `AuthMiddleware` |
| Revocation | `auth_tokens.revoked_at` checked in `AuthMiddleware` |
| `$req->user` shape | `{ id, uuid, clinic_id, role, user_type: 'patient'|'staff' }` |

---

## 7. Database rules

- **New/empty DB:** use `database/install/drbastaninejad_dash_clean_install.sql`.
- **Existing DB:** take a backup, then use additive numbered migrations. Never reset/drop clinical tables in production.
- `clinic_id` is on every clinical table. Always scope queries to `clinic_id`.
- Soft delete (`deleted_at`) on all clinical tables. Never hard-delete patient rows.
- `submission_uuid` on `intakes` has a `UNIQUE` constraint — idempotency at DB level.
- All timestamps UTC. Jalali conversion is always at the presentation layer only.

---

## 8. Google Sheets and WorkingVersion bridge

- The live WorkingVersion remains the canonical Sheet writer.
- Exact additive placement is T `Email`, U `VisitReason`, V `درخواست شما از دکتر چیست؟`; JSON key `doctor_request`.
- WorkingVersion dual-writes MySQL through `X-Intake-Bridge-Secret` when `CRM_DUAL_WRITE_ENABLED=1`.
- MySQL is committed before the live Sheet completion signal; Sheet and SMS failures do not erase committed intake/patient data.
- `DASHBOARD_SHEETS_WRITE_ENABLED=0` is the default so the dashboard cannot duplicate the live Sheet row.

## 9. SMS provider chain

`SmsProviderChain` implements chain-of-responsibility. Order is configurable via `SMS_PROVIDERS` env var (comma-separated). Production default: `kavenegar,ghasedak,farazsms,tsms`. The `log` provider is available only outside production.

Each provider implements `SmsProvider` interface:
```php
interface SmsProvider {
    public function sendOtp(string $mobile, string $otp): void;
    public function sendReminder(string $mobile, string $message): void;
}
```

`LogSmsProvider` is available only outside production. Production delivery is never reported successful merely because a message was logged.

`OtpService` uses `SmsProviderChain` for both patient and staff OTP delivery. Recovery email is optional and sends only OTP codes, never passwords.

---

## 10. Environment variables required

Create `.env` from `.env.example`, replace every `CHANGE_ME` value, and `chmod 600 .env`. The checked-in template contains no production secret. Core production groups are:

- DB: `DB_HOST`, `DB_PORT`, `DB_NAME=drbastaninejad_dash`, `DB_USER=drbastaninejad_dash`, `DB_PASS`
- WorkingVersion bridge: `INTAKE_BRIDGE_SECRET`, intake success SMS toggle/template
- WordPress booking bridge: `WORDPRESS_BRIDGE_SECRET`, booking cooldown/SMS template
- Patient navigation: `PATIENT_LOGIN_URL`
- SMS: `SMS_PROVIDERS` and provider-specific credentials
- Recovery email: `EMAIL_OTP_ENABLED`, `MAIL_FROM`
- Reminder worker: `SMS_REMINDER_OFFSETS`, `EMAIL_REMINDER_ENABLED`
- AI clinical-text safety gates: `AI_ENABLED=0`, `AI_ALLOW_CLINICAL_TEXT=0`, plus server-only provider configuration when deliberately enabled
- Sheet duplicate-write guard: `DASHBOARD_SHEETS_WRITE_ENABLED=0` by default

See `.env.example` for exact names. Bridge/provider credentials are server-only.

---

## 11. cPanel / LiteSpeed deployment checklist

1. Back up the current live WorkingVersion files, dashboard code, WordPress theme, Apps Script deployment, and database.
2. Point `dashboard.drbastaninejad.com` document root to this project's `public/` directory. `public/index.html` remains a smoke shell only.
3. Copy `.env.example` to `.env`, replace every secret placeholder, and `chmod 600 .env`.
4. For the new database `drbastaninejad_dash`, run `database/install/drbastaninejad_dash_clean_install.sql` once. Do not run destructive reset SQL.
5. Create/assign the first staff user explicitly; no staff or patient PII is seeded by the installer.
6. Deploy dashboard backend + canonical Frontend. Confirm patient and staff OTP use separate audiences.
7. Configure the same `INTAKE_BRIDGE_SECRET` on dashboard and WorkingVersion runtime; deploy WorkingVersion code with `CRM_DUAL_WRITE_ENABLED=0` first, smoke-test, then enable it.
8. Deploy the WordPress theme and configure the same `WORDPRESS_BRIDGE_SECRET` server-side on WordPress and dashboard. Never expose it in JS.
9. Keep `PATIENT_LOGIN_URL=https://app.drbastaninejad.com/Frontend/pages/auth/patient-login.html` until the canonical patient UI is actually mapped to the dashboard host.
10. Deploy `Code.gs`/Apps Script only if the production script does not already contain the T/U/V additive header logic; preserve existing columns.
11. Run the CLI reminder worker from cron only after SMS provider credentials are verified.

## 12. API endpoints (summary)

`docs/API_CONTRACT.md` is the source of truth. Implemented route groups are:

- Public/audience auth: `/auth/otp/send`, `/auth/otp/verify`
- Patient recovery: `/auth/recovery/request`, `/auth/recovery/verify`, `/auth/recovery/password`
- WorkingVersion intake bridge + staff review: `/intakes`, `/intakes/{id}/status`
- WordPress server bridge: `/bookings`, `/bookings/stats`
- Patient portal: `/patient/overview`, `/patient/profile`, `/patient/appointments`, `/patient/documents`, `/patient/records`, `/patient/notification-preferences`
- Staff CRM: dashboard, patients, appointments, EMR, billing, tasks, analytics, clinic settings

Every route keeps the response envelope `{ok,status,data,errors,meta}`.

### Known contract boundaries

- Patient documents/media are currently list/read-only. The repository has no agreed upload/signing endpoint; the Frontend therefore does not fake an upload action.
- Staff Settings reads real MySQL users/roles and server integration configuration state, but no user/role mutation endpoint is contracted yet.
- EMR AI draft generation is off by default, requires explicit server-side clinical-text opt-in, and produces review-only text that is never auto-saved.
