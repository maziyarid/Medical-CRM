# Repository Consolidation Plan

**Document Version**: 1.0  
**Created**: 2026-07-29  
**Author**: Maziyar ID (Agent)  
**Status**: DRAFT - Awaiting Review

---

## Executive Summary

This document outlines the consolidation plan for the MΛZ Medical CRM repository, resolving architectural conflicts and ensuring alignment with the locked architecture decision in UNIFIED_MASTER_PLAN.md Section 1.

**Core Principle**: The backend architecture is LOCKED to Custom PHP 8.x MVC with a SINGLE MySQL 8.x database (utf8mb4/InnoDB). No second live DB, no MongoDB, no Python/FastAPI, no Laravel/Symfony.

---

## Critical Findings

### P1 - Architecture Violation (RESOLVED by Archival)
- File: drbastaninejad.com/Front-end Design/src/backend/server.py
- Issue: Python/FastAPI + MongoDB backend directly violates UNIFIED_MASTER_PLAN.md Section 1
- Resolution: Archived to _archive/server.py on 2026-07-29
- Security Issues Resolved by Archival:
  - Unauthenticated PII exposure in GET /api/appointments
  - Unauthenticated PII exposure in GET /api/contact
  - CORS wildcard (allow_origins=*)
  - MongoDB dependency conflict

### P1 - DO NOT MERGE PR #5 Until Resolved
- PR #5 contains the archived server.py and must NOT be merged until:
  1. All references to server.py routes are removed or redirected
  2. All CTA links are verified to point to https://app.drbastaninejad.com/
  3. No MongoDB dependencies remain

---

## Current Repository Structure

### Production (DO NOT TOUCH)
| Directory | Purpose | Status |
|-----------|---------|--------|
| intake-app/public/ | Live intake form (v15) | FROZEN |
| app_private/src/ | Backend services | READ ONLY |
| app.drbastaninejad.com/ | Live intake app | PRODUCTION |
| dashboard.drbastaninejad.com/ | Admin dashboard | PRODUCTION |

### Development Branches
| Branch | Purpose | Status |
|--------|---------|--------|
| tehpars-patch-1 (PR #5) | Frontend design + server.py | NEEDS REVIEW |
| Addendum (PR #4) | Backend intake app | PENDING REVIEW |

### Frontend Implementations
| Directory | Stack | Status | CTA Target |
|-----------|-------|--------|------------|
| drbastaninejad.com/Front-end Design/ | React 18 + Vite | LEGACY (Archived 2026-07-27) | Needs verification |
| drbastaninejad.com/Frontend/ | Next.js 16 + React 19 + TS | CURRENT | Needs verification |

---

## Locked Architecture (Non-Negotiable)

Per UNIFIED_MASTER_PLAN.md Section 1:

### Backend
- Framework: Custom lightweight PHP 8.x MVC only
- Pattern: HP MVC Project Skeleton.md
- Prohibited: Laravel, Symfony, Python/FastAPI, Node.js, or any other framework

### Database
- Single: MySQL 8.x (utf8mb4/InnoDB)
- Prohibited: MongoDB, SQLite, PostgreSQL, or any second live database
- Connection: Single connection, no parallel persistence outside ADDENDUM_V15_PARALLEL_PERSISTENCE.md

### Deployment
- Environment: cPanel/LiteSpeed native-PHP
- Domain: app.drbastaninejad.com (intake), dashboard.drbastaninejad.com (admin)

---

## Consolidation Actions

### Phase 1: Cleanup (COMPLETE)
- [x] Archive server.py to _archive/ with dated note
- [x] Delete server.py from original location
- [x] Document security issues as resolved by archival

### Phase 2: CTA Link Standardization (IN PROGRESS)
**Requirement**: All booking, appointment, and contact CTAs must point to https://app.drbastaninejad.com/

#### Files to Audit and Fix
1. drbastaninejad.com/Front-end Design/ (Legacy React)
   - [ ] App.js - Internal routes only (OK)
   - [ ] pages/Appointment.jsx - Verify external links
   - [ ] pages/Contact.jsx - Verify external links
   - [ ] pages/Home.jsx - Verify external links
   - [ ] components/Navbar.jsx - Verify external links
   - [ ] components/Footer.jsx - Verify external links
   - [ ] lib/content.js - Verify all URLs

2. drbastaninejad.com/Frontend/ (Next.js)
   - [ ] app/page.tsx - Verify external links
   - [ ] app/appointment/ - Verify external links
   - [ ] app/contact/ - Verify external links
   - [ ] app/site-data.ts - Verify all URLs
   - [ ] components/ - Verify all CTA buttons/links

### Phase 3: Documentation (PENDING)
- [ ] This document (REPO_CONSOLIDATION_PLAN.md)
- [ ] Update PROGRESS_LOG.md
- [ ] Flag CONFLICT entry if server.py routes are called live

---

## Verification Checklist

### Before Merging PR #5
- [ ] No Python/FastAPI code in production paths
- [ ] No MongoDB dependencies
- [ ] All CTA links point to https://app.drbastaninejad.com/
- [ ] No GET /api/appointments or GET /api/contact endpoints
- [ ] CORS origins restricted (not wildcard)

### Before Merging PR #4
- [ ] Greptile review complete
- [ ] Coderabbit review complete
- [ ] No conflicts with UNIFIED_MASTER_PLAN.md

---

## Decision Matrix

| Scenario | Action |
|----------|--------|
| Python/FastAPI code found | Archive to _archive/, do NOT merge |
| MongoDB dependencies found | Remove or archive, do NOT merge |
| CTA links to wrong target | Fix to point to https://app.drbastaninejad.com/ |
| Architecture questions | Refer to UNIFIED_MASTER_PLAN.md Section 1 |

---

## References

1. UNIFIED_MASTER_PLAN.md - Ultimate architecture authority
2. ADDENDUM_V15_PARALLEL_PERSISTENCE.md - Parallel persistence rules
3. FRONTEND_DESIGN_SYSTEM.md - Frontend standards
4. SPACE_COORDINATION_PROTOCOL.md - Coordination rules

---

Approval Required: This plan requires explicit sign-off from product owner before execution of Phase 2 and Phase 3 actions.

---

Generated by Maziyar ID Agent on 2026-07-29