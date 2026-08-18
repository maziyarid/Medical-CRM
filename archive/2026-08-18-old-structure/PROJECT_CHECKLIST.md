# Project Checklist — Dr. Shahin Bastaninejad Medical Platform

Last updated: 2026-07-26. Single source of truth for what's built, what's next, and who owns each item.
No timeline estimates are given per your instruction — everything is AI-developed and sequenced by dependency, not calendar time.

## Legend
- [x] Done and pushed to `Medical-CRM` repo
- [ ] Not started / in progress
- 🧑 = requires your decision or external action (cannot be resolved by AI alone)
- 🤖 = pure AI/engineering work, no input needed from you

---

## 1. Public intake form (app.drbastaninejad.com)
- [x] 3-step wizard, OTP verification, signature capture 🤖
- [x] SEO meta, schema.org markup, llms.txt 🤖
- [ ] Real SMS provider contract (Kavenegar/Ghasedak/FarazSMS/TSMS) — 🧑 you need to select and sign with a provider
- [ ] Staff notification templates (new intake alert) 🤖
- [ ] SQL Server async mirror for speech-to-text call logs — 🤖 build once Windows STT pipeline exists
- [ ] Backups, monitoring, staging, log rotation 🤖

## 2. Dashboard / CRM (dashboard.drbastaninejad.com) — this thread's work
- [x] Brand-locked theme system (Evergreen/Graphite tokens, RTL, PWA shell) 🤖
- [x] Dashboard Overview (metrics, needs-attention, today's schedule) 🤖
- [x] Patients module (list, search, detail timeline) 🤖
- [x] Calendar/Scheduling module (day/week/month/agenda, drag-reschedule, conflict detection) 🤖
- [x] Dynamic EMR Editor — fixed fields + AI Copilot draft (accept/discard only) 🤖
- [x] Jalali (Shamsi) calendar conversion utility wired into Calendar module 🤖
- [ ] Jalali wiring extended to Patients module (last_visit, timeline timestamps) 🤖
- [ ] Specialty-specific EMR blocks (tooth chart, body diagram, media grid) 🤖
- [ ] Media/Document Viewer (signed URLs, lightbox, before/after slider) 🤖
- [ ] Billing/Invoicing (Zarinpal/IDPay integration) — 🧑 you need to choose payment gateway + get merchant credentials
- [ ] Task/Workflow Board (Kanban) 🤖
- [ ] Analytics & Marketing Dashboard (referral-source conversion tracking) 🤖
- [ ] Staff/User Management (RBAC UI — roles currently enforced server-side only, no admin UI yet) 🤖
- [ ] Settings & Template Builder (EMR JSON-schema editor) 🤖
- [ ] AI Copilot — real OpenRouter wiring (currently a placeholder string in `AiRouterService.php`) 🤖
- [ ] Global search / command palette (Ctrl+K currently a stub) 🤖
- [ ] Notification centre (bell icon currently non-functional) 🤖

## 3. Patient portal (dashboard.drbastaninejad.com — patient-facing side)
- [ ] Auto-account creation on intake submit (patients/accountsetuptokens tables) 🤖
- [ ] Account-setup email (signed link, set password) 🤖
- [ ] Patient login + session (separate cookie scope from staff) 🤖
- [ ] Profile section (avatar upload with MIME validation + re-encode) 🤖
- [ ] Medical record (read-only view of intake) 🤖
- [ ] Appointments (read-only calendar view for v1) 🤖
- [ ] Documents (signed consent PDF, downloads) 🤖
- [ ] Notification preferences (marketing opt-in/out toggles) 🤖

## 4. Email & SMS engagement
- [ ] SMTP provider decision (cPanel mail vs. dedicated transactional service) — 🧑 your decision
- [ ] Appointment Confirmation email template (HTML) 🤖
- [ ] Account Setup email template (HTML) 🤖
- [ ] Mailer service (queue + send via SMTP) 🤖
- [ ] Unsubscribe/compliance handling (marketing_email_optin flag) 🤖
- [ ] Reminder timing rule — 🧑 your decision (how many hours/days before, which channels)
- [ ] Reminder job (cron-based, email/SMS) 🤖 — blocked on the above decision
- [ ] Real SMS provider integration for reminders/marketing — 🧑 blocked on provider contract (see §1)

## 5. Native apps & PWA
- [x] Web manifest + service worker (installable PWA) 🤖
- [ ] Push notifications (appointment reminders) 🤖
- [ ] Native APK wrapper (WebView + push, Android) 🤖
- [ ] Native iOS wrapper (WebView + push, requires Apple Developer account) — 🧑 you need an active Apple Developer account/certificates
- [ ] App store listings (Google Play + App Store metadata, screenshots) — 🧑 requires your branding assets/approval

## 6. AI training / backend intelligence
- [ ] Ingest Google Search Console query data into RAG pipeline 🤖
- [ ] Ingest Google Analytics data into RAG pipeline 🤖
- [ ] Ingest website chat transcripts into RAG pipeline 🤖
- [ ] Windows speech-to-text pipeline for on-call conversations — 🧑 you need to confirm Windows STT tool/service and provide access to call recordings
- [ ] SQL Server storage + fetch bridge for STT transcripts (per your original instruction) 🤖 — blocked on above
- [ ] RAG ingestion spec implementation (sanctions-safe, Persian-first) — see existing spec doc 🤖
- [ ] AI Copilot real backend wiring (OpenRouter) replacing current placeholder 🤖

## 7. Infrastructure & security decisions still open (🧑 = your call)
- [ ] Self-booking vs. staff-only appointment creation — 🧑 your decision (currently staff-only, matches your prior instruction)
- [ ] Profile photo storage: local `app_private/storage` vs. external object storage (ArvanCloud/Liara) — 🧑
- [ ] Confirm single-MySQL architecture (no SQLite, no second DB) — already recommended, awaiting your sign-off 🧑
- [ ] SQL Server mirror architecture finalization — 🧑 confirm scope (STT transcripts only, or broader mirror)
- [ ] Multi-clinic/multi-tenant readiness — clinic_id scoping already built into every query, but no clinic-switcher UI yet 🤖

## 8. SEO (main public website redesign)
- [ ] Full WordPress → custom platform migration for drbastaninejad.com main site 🤖
- [ ] Schema.org markup across all pages (Physician, MedicalWebPage, BreadcrumbList) 🤖
- [ ] Sitemap + robots.txt + llms.txt at root 🤖
- [ ] Core Web Vitals optimization pass 🤖
- [ ] Content migration from existing WordPress site — 🧑 you need to confirm which existing pages/content to keep vs. rewrite

---

## Your action items right now (🧑 items, consolidated)
1. Choose and contract a real SMS provider (Kavenegar/Ghasedak/FarazSMS/TSMS) for OTP + reminders.
2. Decide reminder timing rule (hours/days before appointment, which channel by default).
3. Decide SMTP provider (cPanel built-in vs. dedicated transactional email service).
4. Choose a payment gateway for Billing (Zarinpal or IDPay) and obtain merchant credentials.
5. Decide profile photo storage (local server vs. ArvanCloud/Liara object storage).
6. Confirm self-booking policy (patients pick their own slots eventually, or always staff-created).
7. Provide/confirm Windows speech-to-text tool for on-call transcription, and access to call recordings.
8. Get/confirm Apple Developer account for iOS app store submission.
9. Confirm which content on the current WordPress site should carry over vs. be rewritten for SEO.
10. Sign off on single-MySQL, no-SQLite architecture decision (already recommended in ROADMAP.md).

## My action items right now (🤖, next in sequence)
1. Extend Jalali date formatting to the Patients module (last_visit, timeline timestamps).
2. Build Media/Document Viewer module (connects to EMR and Patient timeline).
3. Build Billing/Invoicing module UI (backend can be scaffolded now; gateway wiring waits on your decision).
4. Build Task/Workflow Board (Kanban).
5. Build Staff/User Management (RBAC admin UI).
6. Build Analytics & Marketing Dashboard.
7. Wire real OpenRouter AI backend to replace the current placeholder draft function.
8. Build patient portal auto-account-creation flow and login.
