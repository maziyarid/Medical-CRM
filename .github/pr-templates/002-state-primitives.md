<!-- MAZ//ID  	 2026 Maziyar / Dr. Shahin Bastaninejad -->

# PR Template: Task-002 - Create Cross-Cutting State Primitives

**Task ID:** Task-002  
**Priority:** P0  
**Estimate:** S (1-2 days)  
**Owner:** Bob AI (Frontend)  
**Phase:** 0 (Foundations)  
**Status:**  Ready  
**Branch:** `vibe/state-primitives-bea8b8`  
**Merge Safety:** merge-ready

---

## Title
```
feat(frontend): add skeleton/empty/error/forbidden/offline/retry states
```

## Description

This PR adds reusable CSS classes and JavaScript utilities for handling common UI states across all pages. This is a **P0 blocking task** - every data-driven page needs these primitives.

### What this PR does

- Creates `app.drbastaninejad.com/Frontend/assets/css/states.css` - CSS classes for common states
- Creates `app.drbastaninejad.com/Frontend/assets/js/states.js` - JavaScript utilities for state management
- Provides consistent styling and behavior for:
  - **Skeleton** - Loading/pending states
  - **Empty** - No data available
  - **Error** - Something went wrong
  - **Forbidden** - 403 access denied
  - **Offline** - Network disconnected
  - **Retry** - Action to retry failed operations

### Why this is needed

Per `REPOSITORY_AUDIT.md`  1.6 and `FRONTEND_MISSING_WORK_CHECKLIST.md`:
- "Cross-cutting states come before new features"
- Currently missing shared state primitives
- Every data-driven page needs skeleton/empty/error states
- Required for consistent UX across the application

### Ownership

- **Owner:** Bob AI (Frontend) - per `SPACE_COORDINATION_PROTOCOL.md`  5
- **No backend changes:** Pure frontend addition
- **No design token changes:** Uses existing tokens from `tokens.css`

---

## Files Changed

| File | Change | Status |
|---|---|---|
| `app.drbastaninejad.com/Frontend/assets/css/states.css` | NEW | Required |
| `app.drbastaninejad.com/Frontend/assets/js/states.js` | NEW | Required |

---

## Acceptance Criteria

### CSS Classes (states.css)

- [ ] `.skeleton` - Loading state styling
- [ ] `.skeleton-text` - Text skeleton (pulsing placeholder)
- [ ] `.skeleton-avatar` - Avatar/circular skeleton
- [ ] `.skeleton-card` - Card skeleton
- [ ] `.empty` - Empty state container
- [ ] `.empty-icon` - Empty state icon
- [ ] `.empty-title` - Empty state title
- [ ] `.empty-description` - Empty state description
- [ ] `.empty-action` - Empty state CTA button
- [ ] `.error` - Error state container
- [ ] `.error-icon` - Error icon
- [ ] `.error-title` - Error title
- [ ] `.error-message` - Error message
- [ ] `.error-action` - Error retry button
- [ ] `.forbidden` - 403 forbidden state
- [ ] `.offline` - Offline state
- [ ] `.retry` - Retry action button

### JavaScript Utilities (states.js)

- [ ] `showSkeleton(container)` - Show skeleton state
- [ ] `hideSkeleton(container)` - Hide skeleton state
- [ ] `showEmpty(container, options)` - Show empty state with custom message
- [ ] `showError(container, error, options)` - Show error state
- [ ] `showForbidden(container)` - Show 403 state
- [ ] `showOffline(container)` - Show offline state
- [ ] `showRetry(container, action)` - Show retry state with action handler

### Design Requirements

- [ ] All colors use CSS variables from `tokens.css` (no hardcoded hex)
- [ ] All text uses Vazirmatn font (Persian-first)
- [ ] RTL-compatible (test with Persian text)
- [ ] Consistent spacing and sizing
- [ ] Accessible (proper contrast, keyboard navigation)
- [ ] Responsive (works on mobile)

---

## Test Instructions

### Setup
```bash
cd app.drbastaninejad.com/Frontend
python3 -m http.server 8080
# Open http://localhost:8080
```

### Test Cases

1. **Skeleton State**
   - Create a test container
   - Call `showSkeleton(container)`
   - Verify skeleton animation displays
   - Call `hideSkeleton(container)`
   - Verify skeleton is removed

2. **Empty State**
   - Create a test container
   - Call `showEmpty(container, { title: 'No data', message: 'There is no data to display' })`
   - Verify empty state displays with title and message
   - Verify empty action button works if provided

3. **Error State**
   - Create a test container
   - Call `showError(container, new Error('Test error'))`
   - Verify error state displays
   - Call `showError(container, new Error('Test error'), { retry: true })`
   - Verify retry button displays

4. **Forbidden State**
   - Create a test container
   - Call `showForbidden(container)`
   - Verify 403 state displays with appropriate message

5. **Offline State**
   - Create a test container
   - Call `showOffline(container)`
   - Verify offline state displays

6. **RTL Test**
   - Set page direction to RTL
   - Verify all states display correctly
   - Verify Persian text renders properly

7. **Responsive Test**
   - Resize browser to mobile width (< 640px)
   - Verify all states adapt to mobile layout

---

## Dependencies

**None** - This is a foundational task with no dependencies.

---

## Blocks

This PR unblocks:
- All data-driven pages (patient portal, staff CRM)
- Task-009 through Task-014 (Patient portal wiring)
- Task-019 through Task-023 (Staff CRM wiring)

---

## Merge Safety

**Status:** merge-ready

- No backend changes
- No schema changes
- No route changes
- No design token changes (uses existing tokens)
- Pure frontend addition
- Does not conflict with any active work per PROGRESS_LOG.md

---

## Related Documents

- `UNIFIED_MASTER_PLAN.md` - Development sequence
- `SPACE_COORDINATION_PROTOCOL.md` - Ownership boundaries
- `REPOSITORY_AUDIT.md` - Audit findings
- `TASK_PRIORITY_LIST.md` - Full task list
- `docs/pages.md` - Page inventory
- `app.drbastaninejad.com/Frontend/assets/css/tokens.css` - Design tokens (authoritative)

---

## Notes

- These primitives should be designed to be composable
- Keep the CSS minimal and reusable
- All state messages should support Persian text
- Consider adding animations for state transitions
- Follow the existing code style in the repository

_MZ  MAZ//ID  	 2026 Dr. Shahin Bastaninejad. All rights reserved._
