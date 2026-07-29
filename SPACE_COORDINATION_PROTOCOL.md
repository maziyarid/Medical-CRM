<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# SPACE COORDINATION PROTOCOL
## Multi-Agent, Multi-Track Development Rules

**Status:** Mandatory  
**Version:** 2.0  
**Date:** 2026-07-29

---

## 1. Purpose

This protocol prevents duplicate development, conflicting schema decisions, accidental
deployment, and unsupported architecture changes across all agents, chats, repositories,
and branches working on the Dr. Shahin Bastaninejad medical platform.

This protocol applies to frontend, backend, infrastructure, documentation, testing, and
security work.

---

## 2. Authority Order

When documents conflict, use this order:

1. Explicit current product-owner instruction
2. `UNIFIED_MASTER_PLAN.md`
3. `docs/DEPLOYMENT_GATE.md`
4. Latest factual entry in `PROGRESS_LOG.md`
5. This protocol
6. Historical documents, PR descriptions, agent comments, and earlier plans

Historical documents are reference material only. They do not override the master plan.

---

## 3. Architecture Boundary

The production application architecture is:

```text
Custom PHP 8.x MVC
MariaDB 10.11+ primary relational database
Nginx + Apache/PHP-FPM on AlmaLinux VPS
```

Agents must not introduce:

- Laravel or Symfony as the production backend framework
- Python, FastAPI, Node.js, or a separate production API
- MongoDB as a clinical/CRM source of truth
- A second active clinical database
- A separate patient table for the dashboard/portal
- A duplicate public intake/OTP/signature path

An agent that believes an exception is necessary must write an amendment proposal and stop.
No code implementing the exception may be written before product-owner approval.

## Persistent Role Ownership

### Bob AI — Frontend / Product UI
Owns:
- Public website redesign
- Dashboard UI
- Patient portal UI
- RTL layouts
- Design tokens and component styling
- Mobile UI adaptation
- PWA presentation layer
- APK/iOS UI wrapper behavior

### Blackbox AI — Backend / Database / Platform
Owns:
- PHP MVC backend
- Database schema and migrations
- Authentication, RBAC, sessions
- Intake idempotency and validation
- Scheduling, EMR, billing, analytics services
- API contracts and backend tests
- Deployment and environment documentation

### Shared rules
- Frontend never invents backend table or route names.
- Backend never changes design tokens or screen structure.
- Any cross-boundary naming change must be written into `UNIFIED_MASTER_PLAN.md` first.

---

## 4. Mandatory Start-of-Session Procedure

Before writing code, each agent must:

1. Read `UNIFIED_MASTER_PLAN.md` in full.
2. Read `SPACE_COORDINATION_PROTOCOL.md` in full.
3. Read the newest entries in `PROGRESS_LOG.md`.
4. Search for an active task overlapping the intended work.
5. Declare exactly one bounded package:
   - Phase
   - Track: frontend, backend, infrastructure, QA, documentation, or security
   - Files expected to change
   - Explicit statement that no active logged task overlaps

### Required opening format

```md
Scope: Phase [number], [track].
Package: [one exact deliverable].
Files: [exact paths or "documentation only"].
Overlap check: No active PROGRESS_LOG.md entry claims this package.
Deployment: No production or cPanel/VPS change is authorized by this task.
```

If a package is already claimed, choose another package. Do not create a competing
implementation without product-owner direction.

---

## 5. Ownership Boundaries

| Track | Owns | Must not change without coordination |
|---|---|---|
| Backend | PHP MVC, models, controllers, services, routes, migrations, validators, API contracts, OTP, DB state | Design tokens, frontend component structure, marketing copy |
| Frontend | UI components, CSS, RTL layout, design tokens, accessibility, static page structure | Table names, columns, API routes, backend business logic |
| Infrastructure | Nginx/Apache/PHP-FPM configuration, VPS checks, deployment docs, backups, TLS | Application feature logic, schema design |
| QA | Test plans, PHPUnit tests, build verification, acceptance evidence | Production secrets, deployment without owner approval |
| Documentation | Master-plan amendments, protocol, deployment gate, factual logs | Technical behavior not evidenced in code or owner confirmation |
| Security | Secret scanning, safe remediation plans, permission review, incident documentation | Secret rotation, force pushes, public visibility changes, production destructive actions |

### Cross-track rule

If frontend needs a backend field or endpoint, it describes the requirement. The backend
owner defines the literal table/column/route name. The name becomes authoritative only when
recorded in the master plan or backend contract.

---

## 6. Work Package Rules

A valid package must have one deliverable:

- Exact code diff plus tests
- Exact documentation replacement text
- Exact test report
- Exact non-destructive server inspection commands
- Exact deployment runbook for a human

Invalid package examples:

- “Fix all backend problems”
- “Finish dashboard”
- “Review everything”
- “Handle security”
- “Make it production-ready”

Agents may not mark a package complete if they only identified work, wrote a plan, or left
placeholders for a later agent.

---

## 7. `PROGRESS_LOG.md` Rules

`PROGRESS_LOG.md` is append-only. Never rewrite or delete prior entries.

After meaningful work, append:

```md
## [YYYY-MM-DD HH:MM UTC] — [Track] — [Agent name] — Phase [N]
### Done
- [Only actions actually completed and verified]

### Files touched
- [Exact paths]

### Blocked / open
- [Unproven facts, owner decisions, or human-only actions]

### Next
- [One action and its assigned owner]
```

Use these words precisely:

- **Confirmed:** supported by direct code, command output, or product-owner statement
- **Unproven:** not checked or no direct evidence
- **Blocked:** cannot proceed without a defined decision/action
- **Complete:** code/document/test evidence is delivered, not merely proposed

---

## 8. Intake and Data Integrity Rule

Only the backend may implement submission idempotency.

The canonical lifecycle is:

```text
pending → attempting → submitted
                     → failed_confirmed
                     → outcome_unknown
```

Requirements:

- A stable idempotency identity exists before an external write.
- The same OTP/token must map to the same submission identity.
- Concurrent requests must not cause duplicate Google Sheet or database writes.
- `submitted` is the only terminal-success state name.
- The solution must include runnable PHPUnit tests for:
  - successful submission
  - same-token double submission
  - confirmed no-write failure
  - ambiguous mid-flight failure

Frontend click-disable behavior is optional UX. It is never accepted as the sole protection.

---

## 9. Secrets and Private Data Rule

Never commit, paste into a PR, or include in agent prompts:

- API keys, passwords, PATs, SSH keys, webhook URLs, or database credentials
- `.env` files
- OTP values
- patient signatures
- patient identifying data
- logs containing request headers, URLs, credentials, or personal data
- database exports
- uploaded patient media

Runtime folders must be ignored by git. Agents may produce remediation instructions, but
only the product owner or a designated human may rotate credentials, rewrite git history,
force-push, change repository visibility, or run destructive production commands.

---

## 10. Deployment Rule

No agent may instruct the product owner to deploy code to cPanel/VPS, create a production
database, run production migrations, create a public DNS route, or enable real patient
traffic until `docs/DEPLOYMENT_GATE.md` is completed and product-owner approval is recorded.

Development order is:

```text
Local development
→ Local tests
→ Private staging
→ Staging tests + backup/restore test
→ Product-owner approval
→ Production deployment
→ Production smoke test
```

Production credentials are created or rotated immediately before production activation.
Temporary local credentials are permitted only when private, uncommitted, and unused for
real patient traffic.

---

## 11. Conflict Procedure

If an agent finds conflicting code, competing architecture, unclear ownership, or an
inconsistent log claim:

1. Stop the affected task.
2. Do not overwrite another implementation.
3. Append a `CONFLICT` entry to `PROGRESS_LOG.md`.
4. State the exact files and conflict.
5. Offer one bounded resolution option.
6. Wait for the product owner or designated lead to assign a single owner.

---

## 12. Final Agent Instruction

Every implementation request must include:

> Read `UNIFIED_MASTER_PLAN.md`, `SPACE_COORDINATION_PROTOCOL.md`, and the latest
> `PROGRESS_LOG.md`. Declare one package. Do not change locked architecture, secrets,
> deployment configuration, database schema, or another agent’s claimed work. Do not
> propose production deployment until the Deployment Gate is complete.
