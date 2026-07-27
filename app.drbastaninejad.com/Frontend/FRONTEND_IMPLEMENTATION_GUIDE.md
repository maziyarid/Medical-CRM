<!-- MAZ//ID · Dr. Shahin Bastaninejad Medical Platform · Frontend coordination guide -->
# Frontend Implementation Guide

**Applies to:** `Medical-CRM/app.drbastaninejad.com/Frontend/`  
**Status:** authoritative frontend handoff and execution guide  
**Read before coding:** `../../docs/ARCHITECTURE.md`, `../../docs/SCHEMA.md`, `../../docs/BACKEND_PLAN.md`, repository `PROJECT_CHECKLIST.md`, and the cross-project `UNIFIED_MASTER_PLAN.md` / `SPACE_COORDINATION_PROTOCOL.md`.

## Product boundary

This folder owns the RTL/Persian interfaces for the public intake flow, staff CRM, and patient portal. It does **not** own database names, PHP controllers, API routes, authentication/session logic, SMS delivery, payment gateway logic, or AI service internals. The backend track publishes those contracts; frontend consumes them.

## Existing structure — preserve it

```
assets/css/{tokens.css,base.css}
assets/js/{app.js,chrome.js}
pages/auth/{login.html,patient-login.html}
pages/intake/intake.html
pages/patient/{overview,profile,records,appointments,documents,notifications}.html
pages/staff/{dashboard,patients,patient-detail,calendar,emr,billing,tasks,analytics,settings}.html
```

Do not create a second dashboard, second design system, or duplicate page tree. Improve and connect these files in place.

## Locked product rules

- `dir="rtl"` is mandatory. Directional arrows/chevrons/progress must mirror; number/date cells may use `dir="ltr"` where readability requires it.
- Product tokens are Evergreen `#2F7D32`, Graphite `#25272C`, Porcelain `#F7F8F6`, Soft Sage `#E4F0E4`, Brass `#B6905E`. Do not use M-Z personal blue/neon tokens for medical-product UI.
- Primary UI font: Vazirmatn/Vazir; headings: Shabnam or Sahel; Latin fallback: Inter/system UI.
- All code files begin with `MAZ//ID` comment header. Public-page footer credit is `M•Z`, small and unobtrusive.
- WCAG 2.2 AA: visible labels, keyboard path, 44px targets, visible focus, meaningful errors linked by `aria-describedby`, `aria-live="polite"` for status messages, and reduced-motion support.
- Jalali is display-only. API timestamps remain UTC; use the existing Jalali utility rather than another conversion library.
- Persian/Arabic numerals must be accepted and normalized before client validation or API transmission.
- Support older devices: avoid optional chaining, nullish coalescing, `async/await`, and unpolyfilled modern APIs in browser scripts. Use feature detection and Promise chains where needed.

## API integration rules

1. Never invent endpoint paths, request keys, database fields, or status enums.
2. Keep API access in a small adapter layer in `assets/js/app.js` (or feature modules), not embedded throughout HTML pages.
3. Until backend publishes a contract, use explicit mock fixtures and tag them `TODO(API-CONTRACT)`.
4. Every API-backed view needs loading, empty, error/offline, forbidden, and success states.
5. Intake duplicate prevention is server-authoritative: send a backend-specified `submission_uuid`; client button locking is only a UX measure.
6. Never expose signed media URLs, tokens, API keys, SMS credentials, or OpenRouter credentials in source code.

## Required shared UI components

Build/reuse these primitives before page-specific work:

- App shell: desktop staff sidebar; patient mobile bottom tabs; accessible mobile drawer
- Page header, breadcrumb/back control, section/card, status badge, empty state, skeleton, inline alert, toast
- Button variants, text/select/textarea, Persian mobile and national-ID fields, checkbox/radio/switch, OTP input, dialog/drawer
- Data table with keyboard-friendly filters and mobile card fallback
- Jalali date renderer/date-picker wrapper
- File/media tile with pending/failed/ready states
- Permission/no-access state and network retry state

## Definition of done

A frontend task is complete only when:

- RTL + 360px mobile + desktop layout are verified
- keyboard-only path works; no hover-only action is required
- loading/empty/error/permission state exists
- no mock API name is silently left in production code
- `MAZ//ID` header exists, no secrets exist, and lint/HTML checks pass
- the change is recorded in the shared `PROGRESS_LOG.md`
