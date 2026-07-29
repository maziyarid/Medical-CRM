<!-- MAZ//ID  	 2026 Maziyar / Dr. Shahin Bastaninejad -->

# PR Template: Task-003 - Create Session Management Layer

**Task ID:** Task-003  
**Priority:** P0  
**Estimate:** S (1-2 days)  
**Owner:** Bob AI (Frontend)  
**Phase:** 0 (Foundations)  
**Status:**  Ready  
**Branch:** `vibe/session-layer-bea8b8`  
**Merge Safety:** merge-ready (depends on Task-001)

---

## Title
```
feat(frontend): add session management with separate staff/patient scopes
```

## Description

This PR adds a comprehensive session management layer for handling authentication tokens, session state, and auth-related UI concerns. This is a **P0 blocking task** - all authenticated pages depend on this layer.

### What this PR does

- Creates `app.drbastaninejad.com/Frontend/assets/js/session.js` - Session management utilities
- Provides secure token storage with separate scopes for staff and patient
- Handles auth header injection for API calls
- Manages 401/403 response handling
- Displays session-expired banners
- Provides logout functionality that clears all tokens
- Implements token refresh mechanism

### Why this is needed

Per `REPOSITORY_AUDIT.md`  1.2 and `SPACE_COORDINATION_PROTOCOL.md`  8:
- Staff and patient must use **different token scopes**
- Session management is required for anything past `/auth/otp/verify`
- Frontend must handle token expiration and refresh
- Session-expired state must be clearly communicated to users

### Ownership

- **Owner:** Bob AI (Frontend) - per `SPACE_COORDINATION_PROTOCOL.md`  5
- **No backend changes:** Pure frontend addition
- **No design token changes:** Uses existing tokens
- **Depends on:** Task-001 (API adapter) for integration

---

## Files Changed

| File | Change | Status |
|---|---|---|
| `app.drbastaninejad.com/Frontend/assets/js/session.js` | NEW | Required |
| `app.drbastaninejad.com/Frontend/assets/js/api.js` | MODIFY | Integrate session with API adapter |

---

## Acceptance Criteria

### Token Storage

- [ ] Staff tokens stored separately from patient tokens
- [ ] Tokens stored securely (HttpOnly if using cookies, or secure localStorage)
- [ ] Tokens not accessible via JavaScript if using HttpOnly cookies
- [ ] Token storage configurable (cookie vs localStorage)

### Token Scopes

- [ ] `STAFF` scope for staff authentication
- [ ] `PATIENT` scope for patient authentication
- [ ] Separate storage keys for each scope
- [ ] Cannot mix scopes (staff token never used for patient requests)

### Auth Header Injection

- [ ] `getAuthHeader(scope)` returns `Authorization: Bearer <token>` for valid token
- [ ] `getAuthHeader(scope)` returns `null` if no token for scope
- [ ] Automatically injects auth header into API adapter requests

### Session State

- [ ] `isAuthenticated(scope)` checks if valid token exists for scope
- [ ] `getCurrentUser(scope)` returns decoded token payload (if JWT)
- [ ] `getToken(scope)` returns raw token for scope

### 401/403 Handling

- [ ] Intercepts 401 Unauthorized responses
- [ ] Intercepts 403 Forbidden responses
- [ ] Clears token for scope on 401/403
- [ ] Displays session-expired banner
- [ ] Redirects to login page (configurable)

### Logout

- [ ] `logout(scope)` clears token for specific scope
- [ ] `logoutAll()` clears all tokens
- [ ] Clears any session-related state
- [ ] Redirects to login page or home page

### Token Refresh

- [ ] `refreshToken(scope)` attempts to refresh token
- [ ] Handles refresh token endpoint
- [ ] Updates stored token on success
- [ ] Clears token on failure
- [ ] Configurable refresh threshold

### Session-Expired Banner

- [ ] Displays when session expires
- [ ] Shows appropriate message for scope (staff vs patient)
- [ ] Provides "Re-login" action
- [ ] Auto-dismisses after action or timeout
- [ ] RTL-compatible
- [ ] Uses design tokens from `tokens.css`

---

## Test Instructions

### Setup
```bash
cd app.drbastaninejad.com/Frontend
python3 -m http.server 8080
# Open http://localhost:8080
```

### Test Cases

1. **Token Storage**
   - Store a staff token
   - Verify token is stored
   - Store a patient token
   - Verify both tokens are stored separately
   - Clear staff token
   - Verify patient token remains

2. **Auth Header Injection**
   - Set staff token
   - Get auth header for staff scope
   - Verify header is `Authorization: Bearer <token>`
   - Get auth header for patient scope
   - Verify header is `null` (no patient token set)

3. **Authentication State**
   - Verify `isAuthenticated('staff')` returns false initially
   - Set staff token
   - Verify `isAuthenticated('staff')` returns true
   - Clear staff token
   - Verify `isAuthenticated('staff')` returns false

4. **401/403 Handling**
   - Mock a 401 response from API
   - Verify token is cleared for scope
   - Verify session-expired banner displays
   - Verify redirect behavior (if configured)

5. **Logout**
   - Set staff and patient tokens
   - Call `logout('staff')`
   - Verify staff token cleared
   - Verify patient token remains
   - Call `logoutAll()`
   - Verify all tokens cleared

6. **Token Refresh**
   - Set token with expiration
   - Mock refresh endpoint
   - Call `refreshToken('staff')`
   - Verify new token is stored
   - Verify old token is replaced

7. **Session-Expired Banner**
   - Trigger session expiration
   - Verify banner displays
   - Verify banner message is appropriate
   - Click "Re-login" action
   - Verify redirect to login page
   - Verify banner dismisses

8. **RTL Compatibility**
   - Set page direction to RTL
   - Trigger session expiration
   - Verify banner displays correctly in RTL

---

## Dependencies

- **Task-001**: API adapter must exist for integration

---

## Blocks

This PR unblocks:
- Task-005: Wire Authentication Pages
- Task-006: Create Error Pages
- Task-007: Wire Public Intake Page
- All authenticated pages

---

## Merge Safety

**Status:** needs-review (depends on Task-001)

- No backend changes
- No schema changes
- No route changes
- No design token changes
- Pure frontend addition
- Wait for Task-001 to merge first

---

## Related Documents

- `UNIFIED_MASTER_PLAN.md` - Development sequence
- `SPACE_COORDINATION_PROTOCOL.md` - Ownership boundaries (Section 8: Intake and Data Integrity Rule)
- `REPOSITORY_AUDIT.md` - Audit findings
- `SECURITY.md` - Security requirements
- `TASK_PRIORITY_LIST.md` - Full task list
- `docs/pages.md` - Page inventory

---

## Security Notes

- **Never store tokens in plain localStorage** without additional security considerations
- **Prefer HttpOnly cookies** for production if possible
- **Separate scopes are mandatory** - staff tokens must never be used for patient requests
- **Clear tokens on logout** - ensure complete cleanup
- **Handle token expiration** gracefully
- **Never expose tokens** in URLs, logs, or error messages

_MZ  MAZ//ID  	 2026 Dr. Shahin Bastaninejad. All rights reserved._
