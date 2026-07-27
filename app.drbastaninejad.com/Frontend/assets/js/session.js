/* MAZ//ID · Dr. Shahin Bastaninejad Medical Platform */
/*
 * ============================================================================
 *  Bearer-token session storage — separate scopes for staff vs. patient.
 * ----------------------------------------------------------------------------
 *  Depends : (none). Old-browser-safe: no async/await, no optional chaining.
 *
 *  Contract source: dashboard.drbastaninejad.com/docs/API_CONTRACT.md
 *    - POST /api/v1/auth/otp/verify returns { data: { token, expires_at, user } }
 *    - Bearer token is sent as `Authorization: Bearer <token>` on subsequent
 *      authed calls.
 *
 *  Storage note: browser SPAs cannot set HttpOnly cookies from JS. This module
 *  uses sessionStorage (cleared on tab close) + a strict per-scope key. The
 *  Backend track is expected to expose a cookie-based session in a future
 *  iteration; when that lands, this module becomes a thin wrapper.
 *
 *  Scopes:
 *    'staff'   — CRM sessions (dashboard, patients, calendar, EMR, billing, tasks)
 *    'patient' — patient portal sessions (overview, profile, records, ...)
 *  Scopes are NEVER shared — a staff token must not be readable to the patient
 *  portal, per SECURITY.md §3 and SPACE_COORDINATION_PROTOCOL.md §5.
 * ============================================================================
 */
(function (global) {
  'use strict';

  var PREFIX = 'mazcrm.session.';
  function keyFor(scope) { return PREFIX + scope; }

  function _read(scope) {
    try {
      var raw = sessionStorage.getItem(keyFor(scope));
      if (!raw) return null;
      var obj = JSON.parse(raw);
      if (!obj || !obj.token || !obj.expires_at) return null;
      if (Date.parse(obj.expires_at) <= Date.now()) {
        sessionStorage.removeItem(keyFor(scope));
        return null;
      }
      return obj;
    } catch (e) { return null; }
  }

  function save(scope, payload) {
    if (!payload || !payload.token) return;
    var obj = {
      token: payload.token,
      expires_at: payload.expires_at,
      user: payload.user || null,
      saved_at: new Date().toISOString(),
      scope: scope
    };
    try { sessionStorage.setItem(keyFor(scope), JSON.stringify(obj)); } catch (e) {}
  }

  function get(scope) { return _read(scope); }

  function token(scope) {
    var s = _read(scope);
    return s ? s.token : null;
  }

  function user(scope) {
    var s = _read(scope);
    return s ? s.user : null;
  }

  function isAuthed(scope) {
    return !!_read(scope);
  }

  function clear(scope) {
    try { sessionStorage.removeItem(keyFor(scope)); } catch (e) {}
  }

  /**
   * Called by api.js when a 401 comes back on an authed request.
   * Clears the offending scope and dispatches `mazcrm:session-expired` so
   * states.js can show the top banner.
   */
  function expire(scope) {
    clear(scope);
    try {
      document.dispatchEvent(new CustomEvent('mazcrm:session-expired', {
        detail: { scope: scope }
      }));
    } catch (e) {
      // IE11 fallback — no browser we target should hit this branch.
      var ev = document.createEvent('Event');
      ev.initEvent('mazcrm:session-expired', true, true);
      ev.detail = { scope: scope };
      document.dispatchEvent(ev);
    }
  }

  /**
   * Redirect to the correct login page if the requested scope is not authed.
   * Call this at the top of every authed page after DOMContentLoaded.
   */
  function requireAuth(scope, loginHref) {
    if (isAuthed(scope)) return true;
    if (loginHref) window.location.href = loginHref;
    return false;
  }

  global.MAZCRM = global.MAZCRM || {};
  global.MAZCRM.session = {
    save: save,
    get: get,
    token: token,
    user: user,
    isAuthed: isAuthed,
    clear: clear,
    expire: expire,
    requireAuth: requireAuth
  };

}(window));

/* End of file — MAZ//ID · © 2026 Dr. Shahin Bastaninejad */
