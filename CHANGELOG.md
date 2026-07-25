# Changelog — MΛZ Medical CRM

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and semantic-ish
versioning (MVP tags include a `-mvp` suffix).

---

## [1.0.0-mvp] — 2026-07-25

### Added — MVP frontend deliverable
- Complete MΛZ Medical CRM design system, locked to Evergreen palette and Vazirmatn typography (`assets/css/tokens.css`, `assets/css/base.css`).
- Full RTL Persian layout across every page (`dir="rtl" lang="fa"`).
- Shared shell chrome (`assets/js/chrome.js`) that injects sidebar, top bar, mobile bottom nav, and MAZ//ID brand footer into every staff / patient page — no duplication.
- **Staff CRM** (9 pages) — dashboard, patients index, patient master view with timeline & AI Copilot panel, calendar (day/week grid), dynamic EMR editor with rhinoplasty template, billing, task kanban, analytics, settings.
- **Patient portal** (6 pages) — overview, profile (adapted from the client-provided mockup), medical record (read-only timeline), appointments, documents, notification preferences.
- **Auth** — staff login (mobile + OTP with password fallback) and patient login (OTP only) with WebOTP hint and 45s resend countdown.
- **Public intake wizard** — 3-step form (identity + OTP → personal info → medical history + signature) with:
  - Live Persian-digit normalization,
  - `mod-11` national-ID validation,
  - Iranian mobile format `09\d{9}` check,
  - Pointer Events-based signature pad (touch-safe),
  - Success screen with tracking ID (`INT-####`).
- Mocked JS API layer (`assets/js/app.js` → `MAZCRM.api.*`) with a demo dataset so every page renders without a backend — ready to be swapped for `fetch('/api/v1/...')`.
- All code files carry the `MAZ//ID` header block; every rendered page shows the `M•Z` signature + copyright to _Dr. Shahin Bastaninejad_ + credit to _Maziyar_ in the footer.

### Documentation
- `README.md` root index (this file's sibling).
- `docs/PROJECT_PLAN.md` — vision, scope, users, deliverables, milestones.
- `docs/ARCHITECTURE.md` — layered architecture + brand system.
- `docs/BACKEND_PLAN.md` — PHP 8.x MVC skeleton + API contract for the frontend mocks.
- `docs/SCHEMA.md` — full MySQL 8.x DDL (22 tables) — normalized + EMR hybrid EAV/JSON.
- `docs/AI_STRATEGY.md` — Dr. Copilot OpenRouter design + model tier mapping.
- `docs/ROADMAP.md` — week-by-week MVP plan → v1.5 → v2.0.
- `LICENSE` — proprietary.
- `SECURITY.md`, `CONTRIBUTING.md`.

### Known gaps (tracked in ROADMAP)
- No backend code yet — frontend talks to mocks only.
- No real SMS provider connected (Kavenegar / Ghasedak / FarazSMS wiring is in AI/Backend plans).
- No native mobile apps (v2 target — the MVP is a responsive PWA-capable web).
- No SQL Server mirror yet (v1.5 target).

---

_MAZ//ID · © 2026 Dr. Shahin Bastaninejad_
