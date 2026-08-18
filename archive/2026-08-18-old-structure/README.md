<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

<div align="center">

# MΛZ Medical CRM

**Multi-Specialty Medical Platform for Dr. Shahin Bastaninejad**
Persian-first · RTL · AI-augmented · Iran-native

![status](https://img.shields.io/badge/status-MVP--v1.0.0-2F7D32?style=flat-square)
![stack](https://img.shields.io/badge/stack-PHP%208.x%20%2B%20MySQL%208.x-246b28?style=flat-square)
![RTL](https://img.shields.io/badge/RTL-Persian%20first-B6905E?style=flat-square)
![Made by MAZ//ID](https://img.shields.io/badge/Made%20by-MAZ%2F%2FID-0EA5FF?style=flat-square)

</div>

---

## What is this

A **multi-purpose medical CRM + patient portal** built for the practice of **Dr. Shahin Bastaninejad**, designed to work for any medical specialty (rhinoplasty, dentistry, dermatology, ophthalmology, orthopedics, cosmetology, general surgery) without rewriting the core.

Two public surfaces live under this repository:

| Domain | Audience | Folder |
|---|---|---|
| **`app.drbastaninejad.com`** | Staff CRM + patient portal (this MVP) | `app.drbastaninejad.com/Frontend/` |
| _dashboard.drbastaninejad.com_ (future) | Legacy patient dashboard target | — planned in ROADMAP |

The MVP is designed to be **sellable to the first clinic in 6–8 weeks** by keeping the core lean, wrapping every LLM call in an OpenRouter-powered router, and reserving the premium/expensive models for the tasks that actually need clinical accuracy.

---

## Repository layout

```
Medical-CRM/
├── README.md                       ← you are here
├── LICENSE
├── CHANGELOG.md
├── SECURITY.md
├── CONTRIBUTING.md
├── docs/                           ← all long-form documentation
│   ├── PROJECT_PLAN.md
│   ├── ARCHITECTURE.md
│   ├── BACKEND_PLAN.md
│   ├── AI_STRATEGY.md
│   ├── SCHEMA.md                   ← full MySQL DDL
│   └── ROADMAP.md
└── app.drbastaninejad.com/
    └── Frontend/                   ← MVP frontend (this deliverable)
        ├── index.html
        ├── assets/{css,js,img,fonts}
        └── pages/
            ├── auth/               ← login, patient-login
            ├── intake/             ← public 3-step wizard + OTP + signature
            ├── staff/              ← dashboard, patients, patient-detail,
            │                         calendar, emr, billing, tasks,
            │                         analytics, settings
            └── patient/            ← overview, profile, records,
                                      appointments, documents, notifications
```

## Quickstart — run the frontend locally

The MVP frontend is pure static HTML + CSS + JS. No build step, no npm install.

```bash
# From the repo root
cd "app.drbastaninejad.com/Frontend"
python3 -m http.server 8080
# → open http://localhost:8080
```

**Demo OTP: `12345`** everywhere. All API calls are mocked in `assets/js/app.js`
and must be wired to the PHP backend before production — see
[`docs/BACKEND_PLAN.md`](docs/BACKEND_PLAN.md).

## Design system — locked tokens

The visual system is fully documented in [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md#brand-system) and encoded in [`app.drbastaninejad.com/Frontend/assets/css/tokens.css`](app.drbastaninejad.com/Frontend/assets/css/tokens.css).

| Token | Hex | Use |
|---|---|---|
| `--evergreen` | `#2F7D32` | Primary CTA, active nav, focus rings |
| `--evergreen-dark` | `#246b28` | Hover / dark mode primary |
| `--evergreen-soft` | `#E4F0E4` | Soft sage — active nav bg, badges |
| `--graphite` | `#25272C` | Body text |
| `--muted` | `#6A7078` | Secondary text |
| `--porcelain` | `#F7F8F6` | Page bg (with 24px radial-dot pattern) |
| `--brass` | `#B6905E` | Sparingly, for premium/highlight accents |

Typography: **Vazirmatn** (Persian body/UI) + **Inter** (Latin fallback) + **JetBrains Mono** (code/dev surfaces). Persian line-height is +8–10 % over the Latin equivalent — never below 1.5.

## Signature & authorship

Every file in this repository carries a `MAZ//ID` code header and a Persian-language copyright footer in the UI:

- **Primary product brand** — MΛZ Medical CRM, clinical Evergreen system, in service of **Dr. Shahin Bastaninejad**.
- **Author / developer signature** — `MAZ//ID` in code headers, `M•Z` on editorial surfaces, footer credit to `Maziyar` linked to [maziyarid.com](https://maziyarid.com).
- **Copyright** — © 2026 Dr. Shahin Bastaninejad (product owner). Frontend & CRM engineering by Maziyar. See [`LICENSE`](LICENSE).

## Documentation index

| Doc | Purpose |
|---|---|
| [`docs/PROJECT_PLAN.md`](docs/PROJECT_PLAN.md) | The single-source project plan — vision, scope, users, deliverables, milestones. |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | High-level system layers, module inventory, brand system, RTL rules. |
| [`docs/BACKEND_PLAN.md`](docs/BACKEND_PLAN.md) | PHP 8.x MVC layout, API contract for the MVP frontend, deployment. |
| [`docs/SCHEMA.md`](docs/SCHEMA.md) | Full MySQL 8.x DDL (22 tables) — normalized + EMR EAV/JSON hybrid. |
| [`docs/AI_STRATEGY.md`](docs/AI_STRATEGY.md) | Dr. Copilot design — OpenRouter router, model tier mapping, cost controls. |
| [`docs/ROADMAP.md`](docs/ROADMAP.md) | Week-by-week MVP plan → v1.5 → v2.0. |
| [`SECURITY.md`](SECURITY.md) | Reporting vulnerabilities, secrets policy, PHI handling. |
| [`CONTRIBUTING.md`](CONTRIBUTING.md) | Branching, commit prefix (`MAZ:` / `MAZ:fix`), PR rules. |
| [`CHANGELOG.md`](CHANGELOG.md) | Release notes. |

---

<div align="center">

**M•Z**
Built by [Maziyar](https://maziyarid.com) — **MAZ//ID**
© 2026 Dr. Shahin Bastaninejad. All rights reserved.

</div>
