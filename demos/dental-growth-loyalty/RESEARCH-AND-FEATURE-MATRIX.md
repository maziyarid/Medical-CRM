# Smart Teb Dental CRM/PMS + باشگاه مشتریان — Research & Feature Matrix

**Research snapshot:** 2026-10-05  
**Purpose:** product architecture and demo scope, not a claim of parity with any named vendor.

## Research method

The feature inventory below was checked against current first-party product pages/documentation from dental PMS/engagement platforms and loyalty-platform vendors. Vendor performance claims are not treated as independent evidence; the research is used only to identify product capabilities and workflow patterns.

### Dental / practice-management references

- Adit — https://adit.com/en and https://adit.com/dental
- Adit treatment-plan documentation — https://help.adit.com/portal/en/kb/articles/getting-started-set-up-treatment-plans-dental
- NexHealth — https://www.nexhealth.com/
- NexHealth Synchronizer APIs — https://synchronizer.nexhealth.com/products
- Dentrix Ascend — https://www.dentrixascend.com/about-us/why-dentrix-ascend/
- CareStack — https://carestack.com/en-GB/solutions
- Curve Care+ — https://www.curvedental.com/curve-care
- Pearl Practice Intelligence — https://hellopearl.com/products/practice-intelligence

### Loyalty references

- Open Loyalty — https://www.openloyalty.io/applications/saas-enrichment
- Antavo — https://antavo.com/product/loyalty-platform/
- LoyaltyLion — https://loyaltylion.com/

## What the market baseline now implies

The dental product cannot stop at a contact list plus appointments. Modern platforms combine growth, patient experience, operational scheduling, treatment acceptance, clinical workflow, collections/insurance, automation and analytics. Current products also expose API/integration layers rather than treating the PMS as a closed UI.

The loyalty product should likewise be more than a points table. Current loyalty engines combine points ledgers, tiers, rewards, referrals, wallets, challenges, badges, gamification, automated journeys and analytics, with API/webhook integration.

Clinical AI must be implemented as **assistive** tooling: draft notes, voice charting, radiology-assist, prioritisation and workflow automation. The demo must visibly retain provider review/approval before a clinical output becomes part of the record.

---

# Prioritised build matrix

Legend:
- **P0** = required for the sales demo / first production slice
- **P1** = next competitive layer
- **P2** = advanced / integration-heavy

| Domain | Capability | Priority | Demo state |
|---|---|---:|---|
| CRM | Multi-channel lead capture | P0 | UI architecture included |
| CRM | Lead source/campaign attribution | P0 | UI architecture included |
| CRM | Visual treatment-sales pipelines | P0 | Interactive preview included |
| CRM | Lead value, temperature, owner, next action | P0 | Planned in pipeline model |
| CRM | SLA / ageing / overdue follow-up | P0 | Planned |
| CRM | Lost reasons + reactivation | P1 | Planned |
| CRM | Duplicate detection / merge | P1 | Planned |
| CRM | Lead scoring | P1 | Planned |
| Patient | Patient 360 timeline | P0 | Module preview included |
| Patient | Household / guarantor relationships | P1 | Planned |
| Patient | Consent + communication preferences | P0 | Data model included |
| Patient | Tags / segments / custom fields | P0 | Planned |
| Scheduling | Multi-provider calendar | P0 | Module preview included |
| Scheduling | Multi-location calendar | P0 | Architecture included |
| Scheduling | Chair / room / resource allocation | P0 | Module preview included |
| Scheduling | Real-time online booking | P0 | Architecture included |
| Scheduling | Waitlist / last-minute fill | P0 | Module preview included |
| Scheduling | Check-in / kiosk / digital intake | P1 | Planned |
| Scheduling | No-show / cancellation workflows | P0 | Planned |
| Treatment | Treatment-plan builder | P0 | Module preview included |
| Treatment | Options / phases / estimates / discounts | P0 | Planned |
| Treatment | Digital signature / consent | P0 | Planned |
| Treatment | Payment-plan / deposit options | P0 | Planned |
| Treatment | Unscheduled-treatment worklist | P0 | Planned |
| Treatment | Case-acceptance analytics | P0 | KPI included |
| Engagement | Unified inbox | P0 | Module preview included |
| Engagement | SMS / email adapters | P0 | Architecture included |
| Engagement | WhatsApp-compatible adapter | P1 | Architecture included |
| Engagement | Reminders / confirmation / reschedule | P0 | Planned |
| Engagement | Recall / recare automation | P0 | Module preview included |
| Engagement | Reactivation journeys | P0 | Module preview included |
| Engagement | Post-op instructions | P1 | Planned |
| Engagement | Reviews / surveys | P1 | Planned |
| Engagement | Campaign segmentation / A-B tests | P1 | Planned |
| Clinical | Odontogram / treatment charting | P0 | Architecture included |
| Clinical | Perio charting | P1 | Architecture included |
| Clinical | Clinical notes / SOAP templates | P0 | Architecture included |
| Clinical | Imaging references | P0 | Data model included |
| Clinical | Lab-case management | P1 | Planned |
| Clinical | Implant tracking | P1 | Planned |
| Clinical AI | Ambient note draft | P1 | Integration boundary |
| Clinical AI | Voice charting / perio | P1 | Integration boundary |
| Clinical AI | Radiology-assist | P2 | Integration boundary + clinician review |
| Finance | Invoices / patient balances | P0 | Module preview included |
| Finance | Payment links / text-to-pay | P0 | Module preview included |
| Finance | Instalment plans | P1 | Planned |
| Finance | Insurance eligibility | P1 | Adapter boundary |
| Finance | Claims lifecycle | P1 | Adapter boundary |
| Finance | A/R ageing / worklists | P1 | Planned |
| Finance | Payment reconciliation | P1 | Planned |
| Analytics | Production / collections | P0 | Dashboard included |
| Analytics | Funnel / source-to-revenue | P0 | Planned |
| Analytics | Capacity / no-show / utilisation | P0 | KPI included |
| Analytics | Multi-location benchmarking | P1 | Architecture included |
| Analytics | Retention / cohort analytics | P1 | Planned |
| Loyalty | Points ledger (earn/burn/expiry) | P0 | Architecture + mobile preview |
| Loyalty | Wallet / balance | P0 | Mobile preview included |
| Loyalty | Tier / VIP progression | P0 | Dashboard + mobile preview |
| Loyalty | Reward catalogue | P0 | Mobile preview included |
| Loyalty | Redemptions | P0 | Dashboard architecture |
| Loyalty | Referrals | P0 | Mobile preview included |
| Loyalty | Paid membership plans | P1 | Architecture included |
| Loyalty | Family memberships | P1 | Architecture included |
| Loyalty | Missions / challenges | P0 | Mobile preview included |
| Loyalty | Streaks / badges | P1 | Architecture included |
| Loyalty | Prize wheel | P1 | Planned |
| Loyalty | Scratch card / lottery | P2 | Planned |
| Loyalty | Birthday / anniversary rewards | P1 | Planned |
| Loyalty | Visual campaign builder | P1 | Planned |
| Loyalty | Event-triggered journeys | P0 | Event architecture included |
| Loyalty | Points liability / breakage | P1 | Analytics architecture |
| Loyalty | Referral attribution | P1 | Analytics architecture |
| Loyalty | Tier migration analytics | P1 | Dashboard architecture |
| Loyalty | LTV / cohort retention | P1 | Analytics architecture |
| Platform | Multi-tenant / multi-location | P0 | Architecture included |
| Platform | RBAC / audit log | P0 | Architecture included |
| Platform | Event outbox | P0 | Architecture included |
| Platform | REST API / webhooks | P0 | Architecture included |
| Platform | Consent / quiet hours / frequency caps | P0 | Architecture included |
| Platform | Persian-first RTL + responsive PWA | P0 | Implemented in preview |
| Platform | Synthetic demo-data mode | P0 | Implemented |
| Platform | Demo reset | P0 | Implemented |

---

# Recommended bounded contexts

## 1. CRM / Leads
Owns acquisition identity before conversion to patient, pipeline state, source/UTM attribution, assigned owner, opportunity value, SLA and activities.

## 2. Patient
Owns patient identity, contact/household relationships, consents, communication preferences, tags and patient-facing profile metadata.

## 3. Scheduling
Owns provider/location/chair/room availability, appointment types, bookings, cancellation, waitlist and check-in state.

## 4. Treatment
Owns treatment proposals, phases, alternatives, estimates, signatures, acceptance state and unscheduled-treatment opportunities.

## 5. Clinical
Owns charting, notes, procedure history, imaging references, lab cases and clinical review state. This service must not depend on loyalty.

## 6. Communication
Owns inbox conversations, message templates, channel adapters, delivery outcomes and consent enforcement.

## 7. Finance / Insurance
Owns invoices, payment intents, payments, refunds, patient balances, claims, eligibility and reconciliation.

## 8. Loyalty / Membership
Owns member enrolment, points ledger, wallets, tiers, rewards, redemptions, referrals, missions and memberships. It consumes business events but does not own clinical records.

## 9. Campaigns / Journeys
Owns segments, triggers, conditions, actions, frequency policy and attribution. It can call communication and loyalty through explicit contracts.

## 10. Analytics
Consumes domain events into reporting models; it must not become the transactional source of truth.

---

# Event contract examples

- `lead.created`
- `lead.stage_changed`
- `appointment.booked`
- `appointment.confirmed`
- `appointment.completed`
- `appointment.no_show`
- `treatment.presented`
- `treatment.accepted`
- `treatment.scheduled`
- `payment.completed`
- `payment.refunded`
- `review.received`
- `referral.converted`
- `member.enrolled`
- `member.points_earned`
- `member.tier_changed`
- `reward.redeemed`

The loyalty product can subscribe to events such as `appointment.completed` or `referral.converted`. A clinic buying CRM/PMS without the loyalty module simply has no loyalty subscriber. A business buying Smart Teb Club without the dental CRM can feed the same event contract from POS, ecommerce, another CRM or an import/API adapter.

---

# Demo UX direction derived from the supplied references

The uploaded UI references consistently use:
- light surfaces and generous whitespace;
- strong card hierarchy;
- compact, readable KPI cards;
- green/teal loyalty states with gold reward accents;
- a wallet/points-first mobile home;
- tier progress, missions, rewards and referrals close to the member home screen;
- management dashboards that favour charts plus drill-down tables;
- dense campaign administration on desktop, but simplified member actions on mobile.

The Smart Teb preview deliberately follows those interaction principles without copying third-party brand assets or layouts verbatim.

# Safety / product boundaries

- All demo records are synthetic.
- AI-generated clinical documentation remains a draft until a clinician explicitly reviews/saves it.
- Radiology-assist output is not an autonomous diagnosis.
- Loyalty incentives should not be designed to pressure patients into clinically unnecessary treatment.
- Communication automation must honour consent, opt-out, frequency caps and quiet hours.
- Audit trails are required for treatment-plan signatures, payments, clinical-note approval and points adjustments.
