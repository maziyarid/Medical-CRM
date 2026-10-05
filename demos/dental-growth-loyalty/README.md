# Smart Teb Dental Growth + Loyalty Demo

This demo adds two independently sellable products to the Smart Teb showcase while keeping an integration path between them:

1. **Smart Teb Dental CRM & PMS** — acquisition, front-office, clinical workflow, treatment sales, retention, payments and analytics.
2. **Smart Teb Club (باشگاه مشتریان)** — loyalty, rewards, memberships, referrals, gamification and customer lifecycle analytics.

They share an event contract, but neither product requires the other to operate. This preserves the Smart Teb rule that each demo can stand alone commercially and prevents the loyalty product from becoming a hard dependency of the dental CRM/PMS.

## Demo principles

- Persian-first RTL UI with responsive web/PWA layouts.
- Synthetic demo data only; no real patient records.
- No competitor names or copied branding in the public interface.
- Human-in-the-loop for any clinical AI output.
- Separate role surfaces for owner, manager, reception, treatment coordinator, clinician, finance, marketing and patient/member.
- Multi-location capable from the data model upward.
- API-first boundaries so the same domain services can later power web, mobile and Windows clients.

## Product A — Dental CRM & PMS modules

### Growth and lead management
- Multi-channel lead intake: website forms, landing pages, calls, social, campaigns and referrals.
- Lead source, owner, temperature, service interest, estimated case value and next follow-up date.
- Visual pipelines for implant, orthodontics, cosmetic, general dentistry and configurable custom services.
- Automatic lead-to-patient conversion after booking.
- Lost-reason tracking, ageing, SLA timers, duplicate detection and lead scoring.
- Campaign attribution and source-to-revenue analytics.
- Call and conversation timeline.

### Patient 360
- Identity, contact, consent, communication preferences and household/guarantor relationships.
- Appointments, treatment plans, transactions, forms, documents, images and communications on one timeline.
- Tags, cohorts, risk/retention flags and custom fields.
- Portal actions, document exchange and patient requests.

### Scheduling and front office
- Multi-provider, multi-location, chair/room-aware scheduling.
- Online booking rules by service, provider, branch and duration.
- Waitlist and last-minute fill workflows.
- Check-in, kiosk, digital forms and queue/arrival state.
- Confirmation, cancellation, rescheduling and no-show workflows.
- Resource conflicts and capacity indicators.

### Treatment sales and case acceptance
- Treatment plan builder with phases, alternatives, estimates, discounts and payment options.
- E-signature and consent capture.
- Statuses: draft, presented, viewed, pending, accepted, partially accepted, scheduled, declined and expired.
- Unscheduled-treatment worklists and automated follow-up.
- Case-acceptance analytics by provider, location, treatment and coordinator.
- Deposit/payment-plan options and financing placeholders.

### Patient engagement
- Unified inbox for SMS, email, WhatsApp-compatible channel adapters and call events.
- Reminders, recalls, post-op instructions, reactivation and missed-call follow-up.
- Templates, variables, quiet hours, consent checks and frequency caps.
- Campaigns with audience segments, exclusions, A/B variants and outcome tracking.
- Review/survey requests after configurable appointment outcomes.

### Clinical workflow
- Odontogram/tooth chart, periodontal chart and procedure history.
- Clinical notes, templates, SOAP format and treatment documentation.
- Imaging/document references.
- Lab case and implant tracking.
- Prescriptions/integration boundary.
- Ambient note/voice-charting integration boundary.
- Radiology-assist integration boundary with explicit clinician review before save or publication.

### Finance and revenue cycle
- Patient balances, invoices, receipts and payment links.
- Card-on-file/token boundary, text-to-pay and instalment plans.
- Insurance eligibility and claim lifecycle adapters.
- Accounts receivable ageing and worklists.
- Reconciliation, refunds, adjustments and audit history.
- Provider production/collection metrics.

### Operations and analytics
- Owner dashboard: production, collections, booked value, case acceptance, reactivation, no-show, recall, acquisition and retention.
- Multi-location benchmarking and branch drill-down.
- Staff task queues, SLAs and workload.
- Audit log, role-based access control and consent history.
- Export/API/webhook boundaries for BI and integrations.

## Product B — Smart Teb Club (باشگاه مشتریان)

### Loyalty engine
- Points earn/burn rules with pending, active, redeemed and expired balances.
- Multiple wallets/currencies where needed.
- Tier/VIP progression with rolling or fixed qualification windows.
- Tier benefits, protected perks and downgrade/grace rules.
- Coupons, vouchers, gift rewards and benefit entitlements.
- Reward catalogue with stock/limit controls.
- Expiry policies and liability reporting.

### Membership plans
- Paid or complimentary memberships.
- Recurring plan billing boundary.
- Family/household memberships.
- Included benefits, annual allowances and member-only pricing.
- Renewal, pause, cancellation and win-back flows.

### Referrals and advocacy
- Unique referral codes/links.
- Referrer and referred-member rewards.
- Attribution, eligibility and anti-abuse checks.
- Post-visit referral prompts.
- Ambassador/VIP referral tiers.

### Gamification
- Missions, streaks, badges, achievements and progress bars.
- Spin-to-win / prize-wheel demo mechanic.
- Scratch-card / lottery demo mechanic.
- Birthday, anniversary and milestone bonuses.
- Check-in, profile-completion, survey and review actions.
- Team/family challenges where suitable.

### Campaigns and journeys
- Visual campaign builder with trigger → conditions → reward/action.
- Event-based and scheduled campaigns.
- Segments based on tier, points, visit history, treatment completion, last activity and branch.
- Frequency caps, exclusions, consent and quiet hours.
- Campaign performance, reward cost and incremental-retention metrics.

### Member app / portal
- Wallet and points balance.
- Tier progress and benefits.
- Missions/streaks.
- Reward store and redemption history.
- Referral sharing.
- Saved payment methods boundary.
- Membership details and renewal.
- Notifications and preferences.
- Branch/store/service discovery hooks.

### Loyalty analytics
- Active members, enrolment, engagement and retention.
- Points issued, redeemed, expired and outstanding liability.
- Redemption success rate.
- Tier distribution and migration.
- Reward/category performance.
- Campaign uplift and referral conversion.
- Member lifetime value and cohort retention.
- Breakage and cost-of-reward.

## Shared architecture

```
Channels
├─ Smart Teb web admin
├─ PWA/mobile member app
├─ Public booking/forms widgets
└─ Optional Windows/mobile clients

API / BFF
├─ auth + tenant context
├─ RBAC + consent enforcement
├─ rate limits
└─ response shaping

Domain services
├─ CRM / Leads
├─ Patient
├─ Scheduling
├─ Treatment Plans
├─ Communications
├─ Clinical
├─ Finance / Insurance
├─ Loyalty / Membership
├─ Campaigns
└─ Analytics

Event bus / outbox
├─ lead.created
├─ appointment.booked
├─ appointment.completed
├─ treatment.presented
├─ treatment.accepted
├─ payment.completed
├─ review.received
├─ referral.converted
└─ member.reward.redeemed

Adapters
├─ SMS / email / messaging
├─ payments
├─ insurance
├─ telephony
├─ imaging / radiology assist
├─ analytics / BI
└─ webhooks
```

The loyalty service consumes business events through the event/outbox contract. Dental CRM deployments can omit the loyalty service entirely; loyalty-only deployments can receive events from ecommerce, POS or another CRM.

## Core data entities

- tenants, locations, users, roles, permissions
- leads, lead_sources, pipeline_stages, lead_activities
- patients, households, consents, communication_preferences
- providers, chairs, rooms, appointment_types, appointments, waitlist_entries
- treatment_plans, treatment_plan_items, treatment_options, signatures
- conversations, messages, campaigns, campaign_deliveries
- clinical_notes, chart_entries, imaging_refs, lab_cases
- invoices, payment_intents, payments, claims, eligibility_checks
- loyalty_members, points_ledger, wallets, tiers, tier_history
- rewards, redemptions, referral_codes, referrals
- missions, mission_progress, badges, memberships
- audit_events, webhook_deliveries, outbox_events

## Demo milestone sequence

### M1 — interactive showcase
- Responsive CRM/PMS dashboard.
- Lead pipeline, patient/treatment summary, schedule and automation views.
- Loyalty admin dashboard.
- Mobile member app preview.
- Navigation and mode switching with synthetic data.

### M2 — richer product simulation
- Editable lead stages and treatment-plan state.
- Simulated booking/rescheduling and waitlist fill.
- Reward creation/redemption.
- Mission completion, tier progress and referral flow.
- Persist demo state in local storage with reset button.

### M3 — reusable demo backend
- JSON fixtures + API mock server.
- Stable domain contracts matching the future production service boundaries.
- Role/persona switching.
- Event stream linking CRM actions to loyalty consequences.

### M4 — Smart Teb showcase integration
- Add a dedicated card under `/demos/`.
- Preview route should be isolated from the WordPress marketing theme.
- Preserve the existing `/demo/` lead page.
- CTA: «مشاهده دموی تعاملی» + «درخواست نسخه اختصاصی».
- No competitor names, no claims of production deployment, no real patient data.

## Current implementation

The first interactive static preview lives in this directory and is intentionally dependency-light so it can be embedded in the Smart Teb showcase, served from a subpath, or packaged as a standalone sales demo.
