<!-- MAZ//ID  	 2026 Maziyar / Dr. Shahin Bastaninejad -->

# PR Template: Task-004 - Copy Jalali Utility from Backend

**Task ID:** Task-004  
**Priority:** P0  
**Estimate:** S (1-2 days)  
**Owner:** Bob AI (Frontend)  
**Phase:** 0 (Foundations)  
**Status:**  Ready  
**Branch:** `vibe/jalali-utility-bea8b8`  
**Merge Safety:** merge-ready

---

## Title
```
feat(frontend): copy jalali.js from backend to avoid duplication
```

## Description

This PR copies the Jalali date utility from the backend to the frontend to avoid maintaining two implementations of the same algorithm. This is a **P0 blocking task** - any page requiring Jalali date display depends on this.

### What this PR does

- Copies `dashboard.drbastaninejad.com/public/assets/js/jalali.js` to `app.drbastaninejad.com/Frontend/assets/js/jalali.js`
- Ensures the file is an **exact copy** with no functional changes
- Maintains consistency between frontend and backend date handling

### Why this is needed

Per `REPOSITORY_AUDIT.md`  1.6:
- "To avoid duplication, this frontend will **copy** that file verbatim rather than re-implement"
- The backend already has a working jalali.js at `dashboard.drbastaninejad.com/public/assets/js/jalali.js`
- Both frontends should use the same algorithm with the same tests

### Ownership

- **Owner:** Bob AI (Frontend) - per `SPACE_COORDINATION_PROTOCOL.md`  5
- **No backend changes:** Pure copy operation
- **No functional changes:** File must be exact copy

---

## Files Changed

| File | Change | Status |
|---|---|---|
| `app.drbastaninejad.com/Frontend/assets/js/jalali.js` | NEW | Required (copy from backend) |

---

## Acceptance Criteria

- [ ] File created at `app.drbastaninejad.com/Frontend/assets/js/jalali.js`
- [ ] File content is **exact copy** of `dashboard.drbastaninejad.com/public/assets/js/jalali.js`
- [ ] No functional changes to the algorithm
- [ ] No changes to variable names, function names, or logic
- [ ] File header/comments preserved
- [ ] All date conversion functions work identically:
  - [ ] Gregorian to Jalali conversion
  - [ ] Jalali to Gregorian conversion
  - [ ] Date formatting
  - [ ] Date parsing
- [ ] Handles Persian digits correctly
- [ ] Handles edge cases (leap years, month boundaries)
- [ ] No hardcoded hex color values
- [ ] No hardcoded endpoint URLs

---

## Test Instructions

### Setup
```bash
cd app.drbastaninejad.com/Frontend
python3 -m http.server 8080
# Open http://localhost:8080
```

### Test Cases

1. **File Comparison**
   - Compare `app.drbastaninejad.com/Frontend/assets/js/jalali.js` with `dashboard.drbastaninejad.com/public/assets/js/jalali.js`
   - Verify files are identical (use `diff` command)
   ```bash
   diff dashboard.drbastaninejad.com/public/assets/js/jalali.js app.drbastaninejad.com/Frontend/assets/js/jalali.js
   ```
   - Should show no differences

2. **Gregorian to Jalali Conversion**
   - Test known date: 2026-07-29 (Gregorian)
   - Expected: 1405-05-07 (Jalali)
   - Call conversion function
   - Verify result matches expected

3. **Jalali to Gregorian Conversion**
   - Test known date: 1405-05-07 (Jalali)
   - Expected: 2026-07-29 (Gregorian)
   - Call conversion function
   - Verify result matches expected

4. **Persian Digit Handling**
   - Test with Persian digits: 	 (1405)
   - Verify conversion works correctly
   - Verify output uses Persian digits where appropriate

5. **Edge Cases**
   - Test year boundaries (e.g., last day of year)
   - Test month boundaries (e.g., last day of month)
   - Test leap years
   - Verify all conversions are accurate

6. **Date Formatting**
   - Test various format strings
   - Verify output is correctly formatted
   - Test with Persian format strings

7. **Integration Test**
   - Import jalali.js in a test page
   - Use conversion functions
   - Verify they work as expected

---

## Dependencies

**None** - This is a standalone copy operation.

---

## Blocks

This PR unblocks:
- Task-012: Wire Patient Appointments Page (requires Jalali display)
- Any page requiring Jalali date display

---

## Merge Safety

**Status:** merge-ready

- No backend changes
- No schema changes
- No route changes
- No design token changes
- Pure file copy
- Does not conflict with any active work per PROGRESS_LOG.md

---

## Related Documents

- `UNIFIED_MASTER_PLAN.md` - Development sequence
- `SPACE_COORDINATION_PROTOCOL.md` - Ownership boundaries
- `REPOSITORY_AUDIT.md` - Audit findings (Section 1.6)
- `TASK_PRIORITY_LIST.md` - Full task list
- `docs/pages.md` - Page inventory
- `dashboard.drbastaninejad.com/public/assets/js/jalali.js` - Source file (authoritative)

---

## Notes

- This is a **copy-only** operation - do not modify the file content
- If the backend jalali.js is updated, this frontend copy should be updated to match
- Consider adding a build step or symlink in the future to avoid manual copying
- For now, manual copy is acceptable per audit findings

_MZ  MAZ//ID  	 2026 Dr. Shahin Bastaninejad. All rights reserved._
