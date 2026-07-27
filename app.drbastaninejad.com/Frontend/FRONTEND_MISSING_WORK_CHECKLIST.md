<!-- MAZ//ID · Dr. Shahin Bastaninejad Medical Platform · frontend gap checklist -->
# Frontend Missing Work Checklist

**Scope:** `Medical-CRM/app.drbastaninejad.com/Frontend/`
**Rule:** pages already exist in the repository. Treat this as a wiring, quality, and missing-capability checklist—not permission to rebuild duplicate screens.

## Audience split (added 2026-07-27, confirmed with product owner)

Two frontends live inside the `Medical-CRM` repository. Both are retained; both consume the **same** backend contracts.

| Tree | Owns | Audience |
|---|---|---|
| **`app.drbastaninejad.com/Frontend/`** (this folder) | Public intake wizard, auth (staff + patient), patient portal (6 pages), staff CRM (9 pages). Per-page HTML, RTL, mobile-first. | Real end users — patients and clinic staff on their production URLs. |
| **`dashboard.drbastaninejad.com/public/`** | Backend track's SPA shell for live-testing the API. | Backend developers; not a customer-facing surface. |

**Endpoint contract source of truth:** [`dashboard.drbastaninejad.com/docs/API_CONTRACT.md`](../../dashboard.drbastaninejad.com/docs/API_CONTRACT.md). This folder consumes those endpoints **verbatim** — same paths, same payload keys, same status codes. Never invent parallel endpoints for the two frontends.

**New endpoint names go through `UNIFIED_MASTER_PLAN.md` first** (per `SPACE_COORDINATION_PROTOCOL.md` §6). If a wiring task in this checklist needs an endpoint that does not exist in `API_CONTRACT.md`, tag it `TODO(API-CONTRACT)` and request it from the Backend track by describing the need — never coin the path yourself.

**Shared client-side code** (`jalali.js` conversion algorithm, response envelope shape) that already exists inside the SPA is copied verbatim into `Frontend/assets/js/` rather than reimplemented, to keep the two frontends bit-for-bit consistent.

## Cross-cutting

- [ ] Audit each current page for static/demo content versus real API calls; record outcome in `REPOSITORY_AUDIT.md`
- [ ] Create one API adapter strategy after backend publishes contracts
- [ ] Add skeleton, empty, error/offline, forbidden, and retry states to every data-driven page
- [ ] Verify complete RTL and bidirectional number/date behavior
- [ ] Verify 360px, 768px, and 1024px+; no horizontal scrolling
- [ ] Keyboard, focus, contrast, screen-reader, and reduced-motion QA
- [ ] Add client submit lock plus repeat-safe reuse of backend idempotency UUID on intake retries
- [ ] Replace demo OTP/API results only after server endpoints are ready
- [ ] Add a non-secret environment/config pattern for API base URL

## Auth and intake

- [ ] Staff login: real request/error/locked/rate-limit states
- [ ] Patient login: account-setup, login, password-reset, and session-expired screens
- [ ] OTP: paste/autofocus, resend countdown, invalid/expired state, accessible status announcement
- [ ] Intake: backend submission adapter, persisted `submission_uuid`, retry-safe result state
- [ ] Intake: successful confirmation must clearly distinguish successful SQL acceptance from secondary Sheet-sync status

## Patient portal

- [ ] Overview: next appointment, quick actions, clinic contact fallback, empty state
- [ ] Profile: real read/edit boundaries, avatar upload validation/progress, save/conflict feedback
- [ ] Records: read-only timeline/summary, staff-correction guidance, no clinical data leakage
- [ ] Appointments: upcoming/history, Jalali display, status/reminder state, cancellation/contact fallback
- [ ] Documents: secure signed-link UX, loading/download/expired-link/error states
- [ ] Notifications: real preference toggles, pending/saved/error feedback
- [ ] Mobile bottom tabs and authenticated-route redirect behavior

## Staff CRM

- [ ] Dashboard: live metric cards, schedule, attention queue, click-to-filter, empty states
- [ ] Patients list: pagination/search/filter/debounce, Jalali last-visit dates, table-to-mobile-card fallback
- [ ] Patient detail: sticky header, unified timeline, staff-only visibility cues, safe long-history rendering
- [ ] Calendar: live provider/room/status filters, day/week/month/agenda, drag-reschedule feedback, provider/room conflict warning
- [ ] EMR: JSON-template field renderer, specialty blocks (tooth chart, body diagram, media grid), draft/save/sign state
- [ ] EMR AI: clearly labelled “AI draft — review required”; Accept/Edit/Discard only; never auto-save
- [ ] Billing: invoice list/detail, payment pending/success/failure, insurance pending-verification state
- [ ] Tasks: Kanban, filters, accessible drag alternative, assignee/due/priority states
- [ ] Analytics: referral conversion, date range, empty/no-data state, readable Persian charts
- [ ] Settings: clinic settings, working hours, templates, user/RBAC admin UI
- [ ] Global search/command palette and notification centre

## Media and PWA

- [ ] Media viewer: signed URLs only, thumbnail/load failure states, zoom/rotate/download, before/after comparison
- [ ] Confirm manifest/service-worker caching does not cache patient PII, auth responses, or signed media URLs
- [ ] Offline page must say when clinical/patient data cannot be safely accessed

## Handoff gates

- [ ] Backend contract is published before a real endpoint is wired
- [ ] QA evidence attached for desktop/mobile/RTL/accessibility
- [ ] `PROGRESS_LOG.md` appended; unresolved dependencies flagged
