<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# Backend Plan — MΛZ Medical CRM

**Version:** 1.0 · **Author:** MAZ//ID · **Date:** 25 July 2026
**Target stack:** PHP 8.2/8.3 · MySQL 8.x · LiteSpeed on cPanel/AlmaLinux

---

## 1. Framework decision

| Option | Verdict |
|---|---|
| **Laravel 11** | ✅ **Recommended for MVP** — Eloquent, queues, tinker, migrations, first-class RBAC packages, Jalali packages available (`morilog/jalali`). |
| Symfony 7 | Overkill for a one-clinic MVP; keep as a v2 option if the platform grows into multi-tenant SaaS. |
| Custom slim PHP | Fastest to deploy on cPanel with zero opinions, but re-invents queues/migrations/auth. Only choose if hosting refuses Composer. |

**Chosen:** Laravel 11 with a lean feature set (no Livewire, no Inertia — the frontend is already static). Use Laravel purely as an API + queue runner.

---

## 2. Project layout

```
app_private/                        (OUTSIDE the web root — see §7)
├── app/
│   ├── Console/                    Artisan commands (queue:work, migrate)
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── AuthController.php
│   │   │   ├── PatientController.php
│   │   │   ├── IntakeController.php
│   │   │   ├── AppointmentController.php
│   │   │   ├── EmrController.php
│   │   │   ├── BillingController.php
│   │   │   ├── MediaController.php
│   │   │   └── AICopilotController.php
│   │   ├── Middleware/
│   │   │   ├── ForceJsonResponse.php
│   │   │   ├── VerifyClinicScope.php
│   │   │   └── CheckPermission.php
│   │   └── Requests/               FormRequest validators
│   ├── Models/                     Patient, Appointment, EmrRecord, ...
│   ├── Modules/
│   │   ├── AI/
│   │   │   ├── RouterService.php   ← the AI single entry point
│   │   │   ├── OpenRouterClient.php
│   │   │   ├── PromptRegistry.php
│   │   │   └── RagStore.php
│   │   ├── Intake/
│   │   ├── Scheduling/
│   │   ├── EMR/
│   │   ├── Billing/                (Zarinpal, IDPay drivers)
│   │   ├── Media/                  (ArvanCloud driver)
│   │   └── SMS/                    (Kavenegar, Ghasedak, FarazSMS, TSMS)
│   ├── Services/
│   │   ├── PersianDigits.php       fa/ar → en normaliser
│   │   ├── JalaliDate.php          UTC ↔ Jalali (uses morilog/jalali)
│   │   ├── NationalIdValidator.php mod-11
│   │   └── AuditLogger.php
│   └── Providers/
├── bootstrap/
├── config/
│   ├── ai.php                      OpenRouter keys, tier map
│   ├── sms.php                     Provider chain
│   └── clinic.php                  Working hours, brand
├── database/
│   ├── migrations/                 (22 files matching SCHEMA.md)
│   └── seeders/
├── public_html/                    ← this is the web root (via cPanel setting)
│   ├── index.php                   Laravel entry
│   └── assets → symlink to Frontend/assets     (or copy at deploy)
├── routes/
│   ├── api.php
│   └── web.php                     (mostly empty — this is API-first)
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env                            chmod 600, outside web root
├── composer.json
└── artisan
```

Frontend HTML files under `app.drbastaninejad.com/Frontend/pages/` are copied to `public_html/` at deploy time (or served by a static route). The `assets/` folder is served directly.

---

## 3. API contract (v1) — what the MVP frontend expects

All endpoints are prefixed with `/api/v1`. All responses are JSON. Auth is Bearer JWT (Sanctum) except where noted.

| Method | Path | Purpose | Frontend consumer |
|---|---|---|---|
| POST | `/auth/otp/send`      | send OTP to a mobile number | `login.html`, `patient-login.html`, `intake.html` |
| POST | `/auth/otp/verify`    | verify OTP → returns JWT     | same as above |
| POST | `/auth/password`      | staff password login          | `login.html` |
| POST | `/auth/logout`        | revoke token                  | shell logout button |
| GET  | `/me`                 | current session summary       | shell (top-right chip) |
| GET  | `/dashboard/kpis`     | today’s KPIs                  | `staff/dashboard.html` |
| GET  | `/patients`           | paginated + filter by q/status | `staff/patients.html` |
| GET  | `/patients/{uuid}`    | full patient object + timeline | `staff/patient-detail.html`, `patient/*.html` |
| POST | `/patients`           | create patient                | intake success path |
| PATCH| `/patients/{uuid}`    | update patient                | `patient/profile.html` |
| GET  | `/appointments`       | list, filter by date/provider | `staff/calendar.html`, `patient/appointments.html` |
| POST | `/appointments`       | create                        | staff calendar |
| PATCH| `/appointments/{uuid}`| reschedule / status change    | staff calendar |
| POST | `/intakes`            | public intake submission      | `intake.html` (public) |
| GET  | `/intakes`            | staff review queue            | `staff/dashboard.html`, `patients.html` filter |
| GET  | `/emr/templates`      | list active templates         | `staff/emr.html` |
| POST | `/emr/records`        | create clinical note (writes JSON + EAV mirror) | `staff/emr.html` |
| PATCH| `/emr/records/{uuid}` | update note                   | `staff/emr.html` |
| POST | `/emr/records/{uuid}/sign` | freeze + audit-log        | `staff/emr.html` |
| GET  | `/media`              | list, filter by patient/type  | `staff/patient-detail.html` |
| POST | `/media/presign`      | get short-lived S3 upload URL | media grid |
| POST | `/media`              | register uploaded object      | media grid |
| GET  | `/media/{uuid}/url`   | fresh signed read URL         | `patient/documents.html` |
| GET  | `/invoices`           | list / filter                 | `staff/billing.html`, `patient/documents.html` |
| POST | `/invoices`           | create draft                  | `staff/billing.html` |
| POST | `/invoices/{uuid}/issue` | move to issued              | `staff/billing.html` |
| POST | `/invoices/{uuid}/pay/zarinpal` | request payment URL   | `staff/billing.html`, patient portal |
| POST | `/payments/zarinpal/callback` | gateway callback        | (server-to-server) |
| GET  | `/tasks`              | kanban board                  | `staff/tasks.html` |
| POST | `/tasks`              | create                        | `staff/tasks.html` |
| PATCH| `/tasks/{id}`         | move column / assign          | `staff/tasks.html` |
| POST | `/ai/draft`           | request Copilot draft         | patient-detail / EMR side panel |
| POST | `/ai/accept`          | write approved AI output      | Accept button |
| GET  | `/settings/clinic`    | clinic info                   | `staff/settings.html` |
| PATCH| `/settings/clinic`    | update                        | `staff/settings.html` |

### Response envelope

Consistent envelope for every response — the frontend mock in `assets/js/app.js` already conforms to this shape (arrays for lists, `{ok:true, ...}` for actions).

```json
{
  "data": ...,
  "meta": { "page": 1, "per_page": 20, "total": 42 },
  "errors": null
}
```

Errors:
```json
{ "data": null, "errors": [{ "field": "mobile", "message": "شماره نامعتبر" }] }
```

---

## 4. Auth & RBAC

- **Package:** Laravel Sanctum (bearer tokens).
- **Password hashing:** Argon2id (or bcrypt cost 12 as fallback if the host disables Argon2).
- **OTP:** 5 digits, 5-minute expiry, 3 sends / 10 minutes per mobile (rate-limited via `RateLimiter::for('otp', ...)`).
- **RBAC:** hand-rolled thin layer against `roles`, `permissions`, `role_user`, `permission_role` (see `SCHEMA.md` §3.2). Permissions are namespaced (`patients.view`, `emr.edit`, `billing.manage`).
- **Middleware:** `CheckPermission:emr.edit` on every mutating clinical endpoint.
- **Session cookies (patient portal):** `HttpOnly`, `Secure`, `SameSite=Lax`; **separate cookie name** from staff to prevent scope confusion.

---

## 5. Iranian integrations — first pass

### 5.1 SMS
- Multi-provider adapter under `app/Modules/SMS/`.
- Chain default: Kavenegar → Ghasedak → FarazSMS → TSMS.
- Every provider implements `SmsProvider` interface (`sendOtp`, `sendReminder`).
- Log every send in `otp_codes` (for OTPs) and a lightweight `sms_log` table (planned).

### 5.2 Payment (Zarinpal + IDPay)
- Drivers under `app/Modules/Billing/Gateways/`.
- Store transaction states in `payments` (see SCHEMA.md).
- Callback verification via signed HMAC when the gateway supports it, else server-side re-fetch.

### 5.3 Storage (ArvanCloud / Liara S3)
- Laravel Flysystem S3 driver, endpoint override to Arvan.
- Never expose `object_key` in HTML — always fetch a fresh signed URL from `/media/{uuid}/url` (10-minute TTL).

---

## 6. Data migration (Google Sheet → MySQL)

See SCHEMA.md §6 for the staging DDL. The one-shot migrator lives at:
`app/Console/Commands/ImportSmartFormat.php`

Idempotent — matches on `mobile` then `national_id`. Every legacy row is preserved as `intakes.raw_payload` so nothing is lost if the mapping needs a re-run.

---

## 7. Deployment (cPanel + LiteSpeed)

Steps:

1. In cPanel, create a subdomain `app.drbastaninejad.com` pointing at `~/public_html/app`.
2. Upload the project. `app_private/` sits **beside** `public_html`, never inside it. Set the cPanel document root to `~/public_html/app_private/public_html`.
3. `composer install --no-dev --optimize-autoloader` via SSH or cPanel Terminal.
4. `php artisan key:generate`, `php artisan migrate --seed`, `php artisan storage:link`.
5. `.env` at `~/public_html/app_private/.env`, chmod `600`.
6. Add a cron for the queue worker:
   ```
   * * * * * cd /home/USER/public_html/app_private && php artisan schedule:run >> /dev/null 2>&1
   * * * * * cd /home/USER/public_html/app_private && php artisan queue:work --stop-when-empty --max-time=55 >> storage/logs/queue.log 2>&1
   ```
7. Copy `Frontend/` HTML/CSS/JS into `public_html/app/` (or, better, symlink `public_html/app/assets → app_private/Frontend/assets`).
8. Set LiteSpeed cache exclusions for `/api/*`.

---

## 8. Testing (PHPUnit)

- **Unit** tests for `PersianDigits`, `NationalIdValidator`, `JalaliDate`, and the SMS provider chain.
- **Feature** tests for every `/api/v1/*` endpoint (auth happy path + one denied case + one validation-error case).
- Coverage target for MVP: **> 60 %** on services; **> 40 %** overall.

Snippet — `tests/Unit/NationalIdValidatorTest.php`:
```php
public function test_valid_national_id(): void
{
    $this->assertTrue(NationalIdValidator::isValid('0079643178'));
    $this->assertFalse(NationalIdValidator::isValid('1234567890'));
    $this->assertFalse(NationalIdValidator::isValid('0000000000'));
}
```

---

## 9. Backups & disaster recovery (v1.5 target)

- Nightly `mysqldump | gpg --encrypt` → separate ArvanCloud bucket.
- Weekly restore drill into staging.
- Signed-URL audit logs retained 12 months.

---

_M•Z · MAZ//ID · © 2026 Dr. Shahin Bastaninejad_
