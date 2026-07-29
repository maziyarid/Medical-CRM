<!-- MAZ//ID  	 2026 Maziyar / Dr. Shahin Bastaninejad -->

# PR Template: Task-001 - Create API Adapter for Frontend

**Task ID:** Task-001  
**Priority:** P0  
**Estimate:** S (1-2 days)  
**Owner:** Bob AI (Frontend)  
**Phase:** 0 (Foundations)  
**Status:**  Ready  
**Branch:** `vibe/api-adapter-bea8b8`  
**Merge Safety:** merge-ready

---

## Title
```
feat(frontend): add API adapter consuming API_CONTRACT.md
```

## Description

This PR adds the foundational API adapter that all frontend pages will use to communicate with the backend REST API. This is a **P0 blocking task** - all frontend wiring depends on this adapter.

### What this PR does

- Creates `app.drbastaninejad.com/Frontend/assets/js/api.js` - A new API client adapter
- Consumes the authoritative API contract from `dashboard.drbastaninejad.com/docs/API_CONTRACT.md`
- Provides a single, consistent interface for all API calls across the frontend
- Handles HTTP status codes: 200, 201, 401, 403, 404, 422, 429, 500
- Injects authentication headers (Bearer tokens) with separate scopes for staff vs patient
- Handles token refresh and expired session scenarios

### Why this is needed

Per `REPOSITORY_AUDIT.md`  1.6 and `FRONTEND_MISSING_WORK_CHECKLIST.md`:
- Every wiring task depends on a real API adapter
- Currently all pages use mocks in `app.js`
- The `MAZCRM.api.*` block is a mock that must be superseded
- This adapter will be the single source of truth for API communication

### Ownership

- **Owner:** Bob AI (Frontend) - per `SPACE_COORDINATION_PROTOCOL.md`  5
- **Backend Contract:** Blackbox AI owns `API_CONTRACT.md` - this PR consumes it verbatim
- **No schema changes:** This PR does not modify any backend tables, routes, or contracts

---

## Files Changed

| File | Change | Status |
|---|---|---|
| `app.drbastaninejad.com/Frontend/assets/js/api.js` | NEW | Required |
| `app.drbastaninejad.com/Frontend/assets/js/app.js` | MODIFY | Optional - may add adapter integration notes |

---

## Acceptance Criteria

- [ ] Adapter file created at `assets/js/api.js`
- [ ] All API_CONTRACT.md endpoints can be called through the adapter
- [ ] Handles 200 OK responses
- [ ] Handles 201 Created responses
- [ ] Handles 401 Unauthorized (token refresh/expired)
- [ ] Handles 403 Forbidden
- [ ] Handles 404 Not Found
- [ ] Handles 422 Unprocessable Entity (validation errors)
- [ ] Handles 429 Too Many Requests (rate limiting)
- [ ] Handles 500 Internal Server Error
- [ ] Injects `Authorization: Bearer <token>` header for authed requests
- [ ] Supports separate token scopes for staff vs patient
- [ ] Token storage uses secure mechanisms
- [ ] All existing mock calls in `app.js` can be replaced with adapter calls
- [ ] No hardcoded endpoint URLs (uses base URL configuration)
- [ ] No hardcoded hex color values (uses tokens.css)
- [ ] RTL-compatible

---

## Test Instructions

### Setup
```bash
cd app.drbastaninejad.com/Frontend
python3 -m http.server 8080
# Open http://localhost:8080
```

### Test Cases

1. **Adapter Initialization**
   - Open browser console
   - Verify `MAZCRM.api` or new adapter namespace is available
   - Verify base URL is configurable

2. **Mock Endpoint Test**
   - Call a mock endpoint through the adapter
   - Verify response is properly formatted
   - Verify errors are properly caught and formatted

3. **Error Handling**
   - Test with 404 response
   - Test with 500 response
   - Test with network error
   - Verify error states are properly returned

4. **Authentication Header Injection**
   - Set a test token
   - Verify `Authorization: Bearer <token>` is included in request headers
   - Verify token can be cleared

5. **Token Scope Separation**
   - Set staff token
   - Set patient token
   - Verify they are stored separately
   - Verify correct token is used for each scope

---

## Dependencies

**None** - This is a foundational task with no dependencies.

---

## Blocks

This PR unblocks:
- Task-003: Session layer
- Task-005: Wire Authentication Pages
- Task-006: Create Error Pages
- Task-007: Wire Public Intake Page
- All other frontend wiring tasks

---

## Merge Safety

**Status:** merge-ready

- No backend changes
- No schema changes
- No route changes
- No design token changes
- Pure frontend addition
- Does not conflict with any active work per PROGRESS_LOG.md

---

## Related Documents

- `UNIFIED_MASTER_PLAN.md` - Development sequence
- `SPACE_COORDINATION_PROTOCOL.md` - Ownership boundaries
- `REPOSITORY_AUDIT.md` - Audit findings
- `docs/API_CONTRACT.md` - API contract (authoritative)
- `TASK_PRIORITY_LIST.md` - Full task list
- `docs/pages.md` - Page inventory

---

## Notes

- This adapter should be designed to be extended as new endpoints are added
- Keep the interface minimal and consistent
- All error messages should be in Persian where appropriate
- Follow the existing code style in the repository

_MZ  MAZ//ID  	 2026 Dr. Shahin Bastaninejad. All rights reserved._
