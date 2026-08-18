<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# Roadmap — MΛZ Medical CRM

**Version:** 1.0 · **Author:** MAZ//ID · **Date:** 25 July 2026

> **Sales-first insight:** the MVP becomes sellable the moment a clinic can
> **book a patient, keep their record, send an SMS reminder, and take a payment** — all in Persian with Shamsi dates. Everything else is upsell.

---

## Phase 0 — Foundations *(this delivery)*

| Item | Status |
|---|---|
| Repository scaffolding + license + docs skeleton | ✅ |
| Brand tokens (`tokens.css`) locked | ✅ |
| MVP frontend — 16 pages (auth, intake, staff, patient) | ✅ |
| Full architecture + backend + AI docs | ✅ |
| MySQL DDL (22 tables) documented | ✅ |
| MAZ//ID authorship + copyright everywhere | ✅ |

---

## Phase 1 — MVP core (Weeks 1–8)

### Week 1 · Foundations
- Laravel 11 skeleton in `app_private/` (see [`BACKEND_PLAN.md`](BACKEND_PLAN.md)).
- Migrations for the 22 tables in `SCHEMA.md`.
- Auth (Sanctum) + RBAC (`roles`, `permissions`, `role_user`, `permission_role`).
- Health check endpoint (`/api/health.php`) that surfaces DB, storage, SMS, and OpenRouter status.
- Deliverable: `feat/backend-skeleton` branch, ready to serve `/api/health`.

### Week 2 · Data migration
- `_import_smartformat` staging table.
- Artisan command `intakes:import-sheet` — idempotent, digit-normalising, Jalali → Gregorian.
- Every legacy row preserved as `intakes.raw_payload`.
- Deliverable: production import of the historical Google Sheet.

### Week 3 · Intake + Patient view (live)
- Public intake API (`POST /api/v1/intakes`) — wires the frontend wizard already built.
- Patient Master View wired to `GET /api/v1/patients/{uuid}` returning the timeline (intakes + appointments + emr + media + invoices).
- Search / filter / pagination on `GET /api/v1/patients`.
- Deliverable: staff can view a patient and their timeline; public intake writes to MySQL and appears in the CRM.

### Week 4 · Scheduling
- `appointments` CRUD.
- Calendar UI (already built) wired to real data.
- Conflict detection (same provider, overlapping `starts_at`/`ends_at`).
- Room + provider filters.
- Deliverable: full scheduling flow — book, reschedule, cancel.

### Week 5 · Iranian integrations
- Kavenegar OTP (production line) + fallback chain (Ghasedak, FarazSMS, TSMS).
- Zarinpal payment driver + callback verification.
- SMS reminder cron (`reminders:send --hours-before=24`).
- Deliverable: OTP works with real numbers; a paid invoice writes back to `payments`.

### Week 6 · EMR + Media
- Two rhinoplasty templates (pre-op + post-op) seeded via `emr_templates`.
- `emr_records` write path — updates `data_json` **and** mirrors searchable fields into `emr_field_values`.
- ArvanCloud S3 media uploads with 10-minute signed URLs; media grid + lightbox.
- Deliverable: a full clinical note with photos attached, retrievable by patient.

### Week 7 · Dr. Copilot v1
- `RouterService` + `OpenRouterClient` under `app/Modules/AI/`.
- RAG store (SQLite + `sqlite-vss`) seeded with clinic docs.
- Two endpoints wired: `POST /api/v1/ai/draft` (patient summary + EMR draft).
- `ai_interactions` logging + daily cost cap enforcement.
- Deliverable: the EMR page's "Dr. Copilot" panel produces a real draft, doctor accepts/edits/discards; every call is logged.

### Week 8 · Polish + first-clinic onboarding
- Bug bash from a dry-run with real staff.
- Security audit (checklist in `SECURITY.md`).
- Backup + monitoring baseline (daily encrypted mysqldump to a separate ArvanCloud bucket).
- **Ship v1.0.0.**

---

## Phase 2 — v1.5 (Months 3–4)

Focus: turn the one-clinic MVP into a **multi-clinic-ready product**.

- Full multi-specialty EMR template library (dentistry, dermatology, orthopedics).
- Semantic AI routing on ambiguous contexts (add a small classifier in `RouterService`).
- Insurance async verification queue (background worker + status pill in patient master view).
- SQL Server mirror job (nightly export → SQL Server for legacy hospital reporting systems).
- SMS marketing campaigns with `marketing_sms_opt_in` respected.
- Native iOS/Android web-shell (Capacitor) for staff, if demand justifies.

---

## Phase 3 — v2.0 (Months 5+)

Focus: **multi-tenant SaaS**.

- Clinic onboarding wizard — creates `clinics`, seeds specialties, provisions ArvanCloud bucket, first admin user.
- Multi-tenant middleware (`VerifyClinicScope`) enforced across every query.
- Native mobile apps (React Native or Flutter) for patient + staff.
- Marketing ROI dashboard (lead → mrraja → surgery funnel; per-channel CAC).
- Fine-tuned small model for intake parsing (once we have ≥ 10 k labelled examples).
- Public API + webhook system for third-party integrations.

---

## Cross-cutting workstreams (parallel to phases)

| Workstream | Frequency |
|---|---|
| **Accessibility regression** — visual + keyboard test on 10 sample pages | Every PR that touches CSS |
| **Persian text QA** — run the "worst-case name" fixture set (long names, mixed EN/FA digits, edge diacritics) | Weekly |
| **AI eval** — small held-out set of intake blurbs and EMR notes graded on tier candidates | Weekly, starting v1.5 |
| **Cost review** — `ai_interactions.cost_usd` per clinic per day | Weekly |
| **Security review** — dependency updates, secret scan, CSP tightening | Every 2 weeks |

---

## Explicit non-goals

- We are not building a general hospital HIS.
- We are not integrating with SEPAS / centralised national EMR at MVP — plan for it in v2.
- We are not building an autonomous AI agent that reschedules patients on its own. Ever, without an explicit product decision.
- We are not building native mobile apps at MVP — the responsive web hits 95 %+ of use cases and buys us months.

---

_M•Z · MAZ//ID · © 2026 Dr. Shahin Bastaninejad_
