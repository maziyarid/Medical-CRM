<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# DEPLOYMENT GATE
## Mandatory Checklist Before VPS/cPanel Deployment or Real Patient Traffic

**Status:** Mandatory  
**Owner:** Product owner  
**Rule:** No public deployment, database migration, or real patient intake is enabled until
all applicable boxes are checked and the product owner records approval.

---

## 1. Scope

This gate applies before any of the following:

- Deploying PHP MVC code to VPS/cPanel
- Creating or modifying a production database
- Running production migrations
- Pointing a public domain/subdomain to new code
- Enabling Google Sheet, SMS, email, payment, or storage integrations
- Accepting real patient intake, signatures, national IDs, or OTP requests

This gate does not prevent local development using synthetic data.

---

## 2. Server Verification

Complete read-only verification first.

- [ ] VPS operating system confirmed
- [ ] Nginx/Apache/PHP-FPM topology confirmed
- [ ] Active PHP version confirmed
- [ ] MariaDB version confirmed
- [ ] Production document root confirmed for each hostname
- [ ] DNS and TLS ownership confirmed for `drbastaninejad.com`
- [ ] DNS and TLS ownership confirmed for `app.drbastaninejad.com`
- [ ] DNS and TLS ownership confirmed for `dashboard.drbastaninejad.com`
- [ ] Firewall and SSH access reviewed
- [ ] No unexpected Python/FastAPI/Uvicorn/MongoDB process is publicly listening
- [ ] Server backup/snapshot created and restoration method documented

### Read-only verification commands

Run these as root on the AlmaLinux VPS and keep output private:

```bash
hostnamectl
cat /etc/os-release
systemctl --type=service --state=running | \
  grep -Ei 'nginx|httpd|apache|php.*fpm|mariadb|mysql|mongodb|mongod|uvicorn|gunicorn|fastapi|supervisord'

ps aux | grep -Ei '[u]vicorn|[g]unicorn|[f]astapi|[m]ongod|[p]ython.*server\.py'

ss -ltnp | grep -E ':(80|443|8000|8080|27017|3306)\b' || true

nginx -T 2>/dev/null
apachectl -S
php -v
mysql --version
```

Do not run destructive commands based solely on this checklist. If an unexpected service
appears, record the service/process/port and obtain a reviewed remediation instruction.

---

## 3. Source and Dependency Review

- [ ] One approved PHP MVC repository/branch is identified
- [ ] Exact deployment commit SHA is recorded
- [ ] PHP syntax checks pass
- [ ] PHPUnit unit tests pass
  <!-- Unit tests authored (2026-07-31):
       tests/Unit/ValidatorServiceTest.php       — mobile, national ID, Jalali, validateIntake()
       tests/Unit/JalaliConverterTest.php        — Jalali↔Gregorian conversion
       tests/Unit/OtpServiceTest.php             — send, verify, rate-limit, replay prevention
       tests/Unit/IntakeModelTest.php            — insert, findByUuid, updateSyncStatus,
                                                   idempotency, failed_confirmed, outcome_unknown
       tests/Unit/PatientPortalControllerTest.php — PATCH /patient/profile validation (15 cases)
       ⚠ Tests require .env.testing + test DB (migration 001 applied). Run: ./vendor/bin/phpunit ⟫⟫ needs sign-off once DB is provisioned. -->
- [ ] PHP integration tests pass
  <!-- Integration test authored (2026-07-31):
       tests/Integration/IntakeControllerIntegrationTest.php — POST /intakes happy path,
         idempotent retry, outcome_unknown reconciliation, failed_confirmed,
         double-submit race condition (7 test cases, @group intake_integration)
       ⚠ Requires .env.testing with DB_DATABASE=maz_test and migration 001. -->
- [ ] Frontend build/development validation passes where applicable
- [ ] Code review completed for authentication, intake, storage, and database changes
- [ ] No unreviewed or abandoned alternate backend is reachable
  <!-- dashboard.drbastaninejad.com/app/Services/OtpService.php — RETIRED (tombstone, 2026-07-31)
       dashboard.drbastaninejad.com/app/Services/GoogleSheetsService.php — RETIRED (tombstone, 2026-07-31)
       Canonical services: app.drbastaninejad.com/Backend/app/Services/ -->
- [ ] No Laravel/Python/MongoDB production component has been introduced without a signed amendment
- [ ] Rollback commit/release procedure is documented

---

## 4. Secrets and Data Safety

- [ ] `.env` is not committed
- [ ] `.env.example` contains placeholders only
- [ ] Git history and active branches were reviewed for secrets/private data
- [ ] No signatures, uploads, logs, OTPs, patient data, database dumps, or credentials are committed
- [ ] Runtime storage is outside the public web root
- [ ] Runtime storage folders are ignored by git
- [ ] Logs redact credentials, authorization headers, webhook URLs, and personal data
- [ ] Production database password is newly generated and stored in a password manager
- [ ] Production Google Apps Script/webhook endpoint is newly generated or rotated
- [ ] Production email, SMS, payment, and object-storage credentials are newly generated or rotated
- [ ] Only required users/services can access each secret
- [ ] Temporary development credentials are disabled after production activation

**Rule:** Never paste a production secret, webhook URL, token, database password, or `.env`
content into GitHub, chat, PRs, tickets, screenshots, or logs.

---

## 5. Database Readiness

- [ ] MariaDB database name is approved
- [ ] Dedicated least-privilege database user is created
- [ ] Database uses `utf8mb4` and InnoDB
- [ ] One approved migration set exists
- [ ] Migrations were run successfully in local development
- [ ] Migrations were run successfully in private staging
- [ ] Test seed data is separate from production data
- [ ] Database backup completed before production migration
- [ ] Database restore was tested in staging
- [ ] UTC storage rule is verified
- [ ] `intakes.email` and `intakes.visit_reason` are present if the intake flow collects them
- [ ] No parallel `patients` table exists for dashboard/portal authentication
- [ ] Google Sheet/MariaDB dual-write duration and reconciliation criteria are approved

---

## 6. Application Security

- [ ] HTTPS enforced on all public hosts
- [ ] Secure cookie settings verified
- [ ] CSRF protection enabled for browser form actions
- [ ] Authentication and RBAC tested for all five roles
- [ ] Password hashing and reset-token expiration tested
- [ ] OTP rate limiting, expiry, and replay prevention tested
- [ ] Code Meli and Iranian mobile validation tested server-side
- [ ] Intake idempotency tests pass
- [ ] A duplicate token cannot create a second Google Sheet/database record
- [ ] Error and ambiguous external-write states are recoverable and logged without sensitive data
- [ ] Signature/upload validation is tested
- [ ] Signature/upload files cannot be requested directly by public URL
- [ ] Audit logging exists for sensitive staff actions
- [ ] Production debug mode is disabled

---

## 7. Staging Acceptance

- [ ] A non-public or access-controlled staging environment exists
- [ ] Staging uses separate database and secrets
- [ ] All migrations complete successfully in staging
- [ ] Intake happy-path test completed with synthetic data
- [ ] OTP failure/retry test completed with synthetic data
- [ ] Duplicate-submit test completed
- [ ] Role/permission checks completed
- [ ] Mobile RTL layout tested
- [ ] Dashboard login tested
- [ ] Backup and restore test completed
- [ ] Rollback procedure tested or rehearsed
- [ ] Product owner signs off on staging evidence

---

## 8. Production Activation

- [ ] Product owner approves production deployment
- [ ] Production secrets generated/rotated immediately before activation
- [ ] Deployment is performed from the approved commit SHA
- [ ] Migration result is recorded
- [ ] Cache/config reload is completed if needed
- [ ] Public health check passes
- [ ] One synthetic smoke test passes
- [ ] Logs are checked for errors without exposing data
- [ ] Backup schedule is active
- [ ] Deployment record is appended to `PROGRESS_LOG.md`

### Required deployment record

```md
## [YYYY-MM-DD HH:MM UTC] — Infrastructure — Human — Production deployment
### Done
- Commit deployed: [SHA]
- Hosts changed: [hostnames]
- Migration status: [success / none]
- Backup reference: [private location reference only]
- Smoke-test result: [success / failure]
- Production secrets: generated/rotated and stored outside git

### Files touched
- [deployment manifest/config paths, never secret values]

### Blocked / open
- [none or exact issue]

### Next
- [one assigned post-deployment verification action]
```

---

## 9. Explicit Stop Conditions

Stop deployment and do not enable public traffic if any of these is true:

- A secret, webhook URL, patient signature, patient record, log, or database dump is present in git
- A backup/restore test has not been performed
- A production migration has not first passed in staging
- The actual server routing/document root is unknown
- The database schema is not approved
- Intake idempotency tests are missing or failing
- Production credentials have not been generated/rotated
- An agent proposes deployment without this completed gate
