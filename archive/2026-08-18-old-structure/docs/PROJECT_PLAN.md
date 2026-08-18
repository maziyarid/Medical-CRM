<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# Project Plan — MΛZ Medical CRM

**Version:** 1.0
**Date:** 25 July 2026
**Author:** MAZ//ID (Maziyar)
**Product Owner:** Dr. Shahin Bastaninejad
**Status:** MVP frontend delivered — backend + AI orchestration in flight

---

## 1. Vision

Build the **canonical Persian-first, RTL-native, multi-specialty medical CRM** for Iran — one that a receptionist can operate at 90-seconds-per-intake speed, a doctor trusts with real clinical records, and a patient can use on a mid-range Android phone with zero horizontal scroll.

The MVP validates one core loop end-to-end (Intake → Patient record → Appointment → EMR note → Payment) for **one clinic (Dr. Bastaninejad)**, then generalises to a multi-tenant SaaS.

---

## 2. Success criteria for the MVP

| Metric | Target |
|---|---|
| Time to complete public intake (mobile, mid-range Android) | ≤ **90 s** |
| Time for receptionist to book a patient (13" laptop) | ≤ **60 s** |
| Time to deploy first paying clinic after code freeze | ≤ **2 weeks** |
| Frontend Lighthouse Accessibility score | ≥ **95** |
| WCAG contrast compliance | 100 % of interactive text |
| Persian text bugs at launch | **0** overflow / cut-off in critical fields |

The MVP is considered **sellable** the moment one clinic can:
- Book a patient
- Keep their record
- Send an SMS reminder
- Take a payment
— all in Persian, with Shamsi (Jalali) dates and Iranian mobile validation.

---

## 3. Users & roles

### 3.1 Staff (clinic-facing)
| Role | Access |
|---|---|
| **Super Admin** | Everything, including RBAC and template builder |
| **Doctor** | Patients, EMR (write), calendar, media, billing (read) |
| **Receptionist** | Patients, calendar, billing (write), tasks — no clinical writes |
| **Nurse** | Patients (read), tasks, media upload |

### 3.2 Patient (self-service portal)
Read-only medical summary, upcoming appointments, document downloads, profile edit (limited), notification preferences, password.

### 3.3 The MAZ//ID developer persona
Maziyar (product engineer) — signs code, ships releases, owns brand tokens and CI.

---

## 4. Scope — in / out for MVP

### ✅ In (must ship)

1. **Auth + RBAC** (staff: super_admin / doctor / receptionist / nurse; patients get portal identity via OTP).
2. **Public intake wizard** — 3 steps, OTP, national-ID validation, signature.
3. **Patient Master View** — one timeline per patient (intake / appointment / EMR / media / invoice).
4. **Scheduling** — day + week calendar; SMS reminders (queued).
5. **1–2 EMR templates** — start with rhinoplasty pre-op & post-op (the owner's own specialty).
6. **Basic billing** — invoice + Zarinpal payment link.
7. **Media upload** — X-rays / clinical photos to ArvanCloud S3 with signed URLs.
8. **AI Copilot v1** — human-in-the-loop drafting for EMR notes and patient summaries.

### 🚫 Deliberately out (post-MVP)

- Full multi-specialty template library
- SQL Server async mirror (v1.5)
- Advanced marketing/ROI dashboards
- Native mobile apps (v2)
- Real-time insurance API — MVP marks it "Pending Verification"
- Autonomous AI agents (never in MVP — see AI_STRATEGY §6)

---

## 5. Deliverables

| # | Deliverable | Location | Status |
|---|---|---|---|
| D1 | MVP frontend (this repo) — 16 pages, RTL, brand-locked | `app.drbastaninejad.com/Frontend/` | ✅ v1.0.0-mvp |
| D2 | Backend PHP MVC skeleton | (branch: `feat/backend-skeleton`) | 🟡 planned |
| D3 | MySQL schema DDL + migrations | `docs/SCHEMA.md` | ✅ documented |
| D4 | OpenRouter router service (PHP) | (branch: `feat/ai-router`) | 🟡 designed |
| D5 | Deployment recipe (cPanel + LiteSpeed) | `docs/BACKEND_PLAN.md#deployment` | ✅ documented |
| D6 | Onboarding runbook for first clinic | `docs/ROADMAP.md#week-8` | 🟡 stub |

---

## 6. Team & responsibilities

| Role | Owner |
|---|---|
| Product owner / clinical direction | Dr. Shahin Bastaninejad |
| Engineering, frontend, brand system, CI | Maziyar (MAZ//ID) |
| Clinical content review (EMR templates) | Dr. Bastaninejad + designated staff |
| Compliance / data-protection review | External counsel (TBD) |

---

## 7. Milestones (see [`ROADMAP.md`](ROADMAP.md) for detail)

| Week | Milestone |
|---|---|
| 1 | Repo, brand tokens, DB schema, RBAC scaffold |
| 2 | Google Sheets → MySQL migration |
| 3 | Intake wizard + patient master view wired to real backend |
| 4 | Calendar + appointment CRUD |
| 5 | Kavenegar OTP + Zarinpal payment link |
| 6 | Rhinoplasty EMR templates + ArvanCloud media |
| 7 | Dr. Copilot v1 (OpenRouter router + RAG) |
| 8 | Security audit + first-clinic onboarding |

---

## 8. Risk register

| Risk | Impact | Mitigation |
|---|---|---|
| SMS provider blocking | Cannot deliver OTP | Multi-provider adapter (Kavenegar → Ghasedak → FarazSMS → TSMS fallback chain) |
| AI cost overrun | Margin erosion | Tier-based routing (small models for 70 % of traffic); log every call to `ai_interactions`; alerts when daily cost > threshold |
| Persian text overflow in critical fields | Data trust | Automated visual regression on ≥ 10 sample names/addresses in CI |
| National-ID validation false-negative | Blocks legit patient | Full mod-11 algorithm implemented + tested; escape hatch marked as "verify at reception" |
| Data residency questions | Legal | Iran-only storage (ArvanCloud + local MySQL). No cross-border replication without written consent. |
| Autonomous AI hallucination in clinical note | Patient safety | **Never** auto-save AI output; explicit human "Accept" required before EMR write |

---

## 9. Governance

- **Weekly review** with the product owner — every Thursday 20:00 Tehran.
- **Change control** — any change to `docs/SCHEMA.md` or the AI tier map in `docs/AI_STRATEGY.md` needs an owner sign-off recorded in the PR.
- **Confidentiality** — repository is private; no screenshots with real patient names in issues.

---

_M•Z · MAZ//ID · © 2026 Dr. Shahin Bastaninejad_
