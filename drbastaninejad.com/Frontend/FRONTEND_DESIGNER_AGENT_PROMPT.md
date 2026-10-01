# Prompt for Frontend Designer / Implementation Agents

You are contributing to the **Dr. Shahin Bastaninejad Medical Platform**. Work in existing repository code only—do not create a replacement application.

## Your code locations

1. `Medical-CRM/app.drbastaninejad.com/Frontend/` — public intake, patient portal, and staff medical CRM.
2. `drbst/Frontend/` — public marketing website (Home, About, Services, Blog, Contact, Appointment).

Read before work:

- `Medical-CRM/app.drbastaninejad.com/Frontend/FRONTEND_IMPLEMENTATION_GUIDE.md`
- `Medical-CRM/app.drbastaninejad.com/Frontend/FRONTEND_MISSING_WORK_CHECKLIST.md`
- `Medical-CRM/docs/ARCHITECTURE.md`, `Medical-CRM/docs/SCHEMA.md`, `Medical-CRM/docs/BACKEND_PLAN.md`
- root `UNIFIED_MASTER_PLAN.md` and `SPACE_COORDINATION_PROTOCOL.md`
- `M-Z/BRAND_IDENTITY.md` only for MAZ//ID / M•Z signature use—not for medical UI colours.

## Current reality

The page structure already exists. Audit and improve it in place. Existing CRM pages are static/mock-first until the backend agent publishes real API contracts. Do not invent routes, payload names, schema fields, auth behavior, or database logic.

## Visual direction

Create a calm, premium, Iranian clinical system: clean white cards on Porcelain, Evergreen for primary actions and focus, Graphite typography, Soft Sage selected states, Brass only as a restrained accent. Use Persian RTL and Vazirmatn/Vazir with Shabnam/Sahel headings. Avoid generic SaaS gradients, neon, emoji icons, decorative blobs, excessive rounded cards, or personal M-Z blue/neon branding.

## Mandatory UX requirements

- Full `dir="rtl"`; mirror directional icons and keep numbers/date values readable with local LTR isolation where necessary.
- Jalali display; backend owns UTC storage and conversion contract.
- Persian/Arabic digit acceptance and normalization.
- WCAG 2.2 AA: 44px touch targets, visible labels, focus ring, keyboard navigation, inline errors, `aria-live` feedback, reduced-motion support.
- Mobile-first: test 360px, tablet, and desktop. Patient portal uses calm/simple mobile bottom navigation; staff CRM uses compact but usable desktop data density and mobile fallbacks.
- Every data view must include skeleton, empty, error/offline, and permission-denied states.
- Never expose secrets, API keys, patient PII in logs, or permanent media links.
- Compatibility matters: avoid optional chaining, nullish coalescing, `async/await`, and other unsupported syntax in browser code unless transpilation/polyfills are verified.

## Work order

1. Audit current files and record static/mock/API status.
2. Strengthen shared tokens, shell, components, responsive rules, and defensive states.
3. Wire existing patient portal pages after backend API contracts are documented.
4. Wire staff CRM in vertical slices: dashboard/patients → calendar → EMR/media → billing/tasks → settings/analytics.
5. In `drbst/Frontend`, consolidate the existing public website rather than creating another site; connect appointment CTAs to the live intake application when backend confirms the correct flow.
6. For form submission, show UX-level duplicate protection, but send and reuse the backend-provided idempotency UUID; SQL unique constraint is the real protection.

## Acceptance output

For each change provide:

- files changed and exact feature state
- API dependency or confirmed contract used
- screenshot/verification note for 360px and desktop
- accessibility/RTL notes
- any blocking question
- append an entry to shared `PROGRESS_LOG.md`

Every newly created code file begins with: `/* MAZ//ID · Dr. Shahin Bastaninejad Medical Platform */`.
