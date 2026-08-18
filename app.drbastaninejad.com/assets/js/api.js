/* MAZ//ID · Dr. Shahin Bastaninejad Medical Platform */
/*
 * ============================================================================
 *  API adapter — single source consuming dashboard.drbastaninejad.com/docs/
 *                API_CONTRACT.md verbatim. No endpoint invented here.
 * ----------------------------------------------------------------------------
 *  Depends : session.js (token retrieval + 401 handling).
 *  Old-browser-safe: XMLHttpRequest only, no fetch, no Promise polyfill needed
 *  (we ship a tiny then-only Promise-ish; if native Promise exists we use it).
 *
 *  Response envelope (per API_CONTRACT.md):
 *    { "data": ..., "meta": {...}|null, "errors": null|[{field,message}] }
 *
 *  Every endpoint here has a comment referencing its section in the contract.
 *  Anything not documented in the contract is marked TODO(API-CONTRACT) and
 *  its call is *deliberately* left un-implemented so the request has to be
 *  routed through UNIFIED_MASTER_PLAN.md first (per SPACE_COORDINATION_PROTOCOL.md §6).
 * ============================================================================
 */
(function (global) {
  'use strict';

  // ---------- Base URL: same-origin by default; overridable via <meta> tag ---
  function apiBase() {
    var meta = document.querySelector('meta[name="mazcrm-api-base"]');
    if (meta && meta.getAttribute('content')) return meta.getAttribute('content').replace(/\/+$/, '');
    return 'https://dashboard.drbastaninejad.com';
  }

  // ---------- Native Promise or fallback --------------------------------------
  var P = global.Promise || (function () {
    // Minimal thenable if the browser lacks Promise (very rare — targeting IE11+).
    function T() {
      this._c = []; this._v = null; this._s = 0; // 0 pending, 1 resolved, 2 rejected
      var self = this;
      this._resolve = function (v) { if (self._s) return; self._s = 1; self._v = v; self._c.forEach(function (h) { h[0](v); }); };
      this._reject  = function (e) { if (self._s) return; self._s = 2; self._v = e; self._c.forEach(function (h) { h[1](e); }); };
    }
    T.prototype.then = function (ok, err) {
      var nx = new T();
      var wrap = [
        function (v) { try { var r = ok ? ok(v) : v; if (r && r.then) r.then(nx._resolve, nx._reject); else nx._resolve(r); } catch (e) { nx._reject(e); } },
        function (e) { if (err) { try { var r = err(e); if (r && r.then) r.then(nx._resolve, nx._reject); else nx._resolve(r); } catch (ex) { nx._reject(ex); } } else nx._reject(e); }
      ];
      if (this._s === 0) this._c.push(wrap);
      else wrap[this._s - 1](this._v);
      return nx;
    };
    T.prototype['catch'] = function (err) { return this.then(null, err); };
    return T;
  }());

  // ---------- ApiError shape --------------------------------------------------
  function ApiError(status, envelope, opts) {
    this.name = 'ApiError';
    this.status = status;                                    // HTTP status
    this.errors = (envelope && envelope.errors) || null;     // [{field,message}] | null
    this.message = (this.errors && this.errors.length ? this.errors[0].message : (opts && opts.message) || ('HTTP ' + status));
    this.envelope = envelope || null;
    this.isOffline = !!(opts && opts.isOffline);
  }
  ApiError.prototype = new Error();

  // ---------- Low-level request ----------------------------------------------
  function request(method, path, body, opts) {
    opts = opts || {};
    var url = apiBase() + path;
    var scope = opts.scope || null; // 'staff' | 'patient' | null (public)

    return new P(function (resolve, reject) {
      // Fast-fail on offline — matches ApiError.isOffline for states.fromError
      if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        return reject(new ApiError(0, null, { isOffline: true, message: 'offline' }));
      }
      var xhr = new XMLHttpRequest();
      xhr.open(method, url, true);
      xhr.setRequestHeader('Accept', 'application/json');
      if (body) xhr.setRequestHeader('Content-Type', 'application/json;charset=utf-8');
      if (scope && global.MAZCRM && global.MAZCRM.session) {
        var tok = global.MAZCRM.session.token(scope);
        if (tok) xhr.setRequestHeader('Authorization', 'Bearer ' + tok);
      }
      if (opts.headers) {
        Object.keys(opts.headers).forEach(function (k) { xhr.setRequestHeader(k, opts.headers[k]); });
      }
      xhr.timeout = opts.timeout || 30000;
      xhr.ontimeout = function () { reject(new ApiError(0, null, { message: 'timeout' })); };
      xhr.onerror   = function () { reject(new ApiError(0, null, { isOffline: true, message: 'network' })); };
      xhr.onload    = function () {
        var env = null;
        try { env = xhr.responseText ? JSON.parse(xhr.responseText) : null; } catch (e) { env = null; }
        if (xhr.status >= 200 && xhr.status < 300) {
          resolve({ status: xhr.status, envelope: env, data: env ? env.data : null });
          return;
        }
        // 401 on an authed request means the token is invalid/expired
        if (xhr.status === 401 && scope && global.MAZCRM && global.MAZCRM.session) {
          global.MAZCRM.session.expire(scope);
        }
        reject(new ApiError(xhr.status, env, {}));
      };
      xhr.send(body ? JSON.stringify(body) : null);
    });
  }

  // ---------- Public helpers --------------------------------------------------
  function get(path, opts)         { return request('GET',    path, null, opts); }
  function post(path, body, opts)  { return request('POST',   path, body, opts); }
  function patch(path, body, opts) { return request('PATCH',  path, body, opts); }
  function del(path, opts)         { return request('DELETE', path, null, opts); }

  // ---------- Contract-bound endpoints ---------------------------------------
  // All routes below reference sections of dashboard.drbastaninejad.com/docs/API_CONTRACT.md.
  var v1 = {

    // ---- Phase A — Intake (API_CONTRACT.md §"Phase A — Intake Write Path") ----
    intakeSubmit: function (payload) {
      // POST /api/v1/intakes (public). Returns 201 (new) | 200 (idempotent) | 422.
      return post('/api/v1/intakes', payload);
    },
    intakeList: function (params) {
      // GET /api/v1/intakes (staff, permission intakes.view)
      var qs = _qs(params);
      return get('/api/v1/intakes' + qs, { scope: 'staff' });
    },

    // ---- Phase B — Auth (API_CONTRACT.md §"Phase B — OTP Authentication") ----
    otpSend: function (mobile, audience) {
      // POST /api/v1/auth/otp/send (public). audience: patient|staff.
      return post('/api/v1/auth/otp/send', { mobile: mobile, audience: audience || 'patient' });
    },
    otpVerify: function (mobile, otp, audience) {
      // POST /api/v1/auth/otp/verify (public). 200 | 401 | 410 | 422.
      // On success the caller MUST persist the returned token via
      // MAZCRM.session.save(scope, data).
      return post('/api/v1/auth/otp/verify', { mobile: mobile, otp: otp, audience: audience || 'patient' });
    },

    // ---- CRM endpoints already used by the SPA (dashboard.drbastaninejad.com) --
    // (Documented in dashboard.drbastaninejad.com/README.md; source of truth for
    //  request/response shape is those PHP controllers + the SPA calls.)
    dashboardOverview: function () {
      // GET /api/v1/dashboard/overview (staff, permission dashboard.view)
      return get('/api/v1/dashboard/overview', { scope: 'staff' });
    },
    patientsList: function (params) {
      // GET /api/v1/patients?q=&page=&per_page=
      return get('/api/v1/patients' + _qs(params), { scope: 'staff' });
    },
    patientGet: function (id) {
      // GET /api/v1/patients/{id} -> { patient, timeline }
      return get('/api/v1/patients/' + encodeURIComponent(id), { scope: 'staff' });
    },
    patientCreate: function (payload) {
      // POST /api/v1/patients (staff-initiated create — dedupes on mobile server-side)
      return post('/api/v1/patients', payload, { scope: 'staff' });
    },
    patientUpdate: function (id, payload) {
      return request('PUT', '/api/v1/patients/' + encodeURIComponent(id), payload, { scope: 'staff' });
    },
    appointmentsInRange: function (from, to, providerId) {
      // GET /api/v1/appointments?from=&to=&provider_id=
      return get('/api/v1/appointments' + _qs({ from: from, to: to, provider_id: providerId }), { scope: 'staff' });
    },
    appointmentCreate: function (payload) {
      // POST /api/v1/appointments — 409 on conflict
      return post('/api/v1/appointments', payload, { scope: 'staff' });
    },
    appointmentReschedule: function (id, scheduled_at) {
      // PATCH /api/v1/appointments/{id}/reschedule — 409 on conflict
      return patch('/api/v1/appointments/' + encodeURIComponent(id) + '/reschedule', { scheduled_at: scheduled_at }, { scope: 'staff' });
    },
    appointmentStatus: function (id, status) {
      // PATCH /api/v1/appointments/{id}/status
      return patch('/api/v1/appointments/' + encodeURIComponent(id) + '/status', { status: status }, { scope: 'staff' });
    },
    emrTemplates: function () {
      // GET /api/v1/emr/templates
      return get('/api/v1/emr/templates', { scope: 'staff' });
    },
    emrForPatient: function (patientId) {
      // GET /api/v1/patients/{id}/emr
      return get('/api/v1/patients/' + encodeURIComponent(patientId) + '/emr', { scope: 'staff' });
    },
    emrCreate: function (patientId, payload) {
      // POST /api/v1/patients/{id}/emr — canonical backend route.
      return post('/api/v1/patients/' + encodeURIComponent(patientId) + '/emr', payload, { scope: 'staff' });
    },
    aiEmrDraft: function (payload) {
      // POST /api/v1/ai/emr-draft — response always includes requires_review:true
      return post('/api/v1/ai/emr-draft', payload, { scope: 'staff' });
    }

    // Additional canonical patient/staff methods are exposed by Frontend/shared/api.js.
  };

  function _qs(params) {
    if (!params) return '';
    var parts = [];
    Object.keys(params).forEach(function (k) {
      var v = params[k];
      if (v === undefined || v === null || v === '') return;
      parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(v));
    });
    return parts.length ? ('?' + parts.join('&')) : '';
  }

  global.MAZCRM = global.MAZCRM || {};
  global.MAZCRM.api = global.MAZCRM.api || {};
  global.MAZCRM.api.v1 = v1;
  global.MAZCRM.api.request = request;
  global.MAZCRM.api.get = get;
  global.MAZCRM.api.post = post;
  global.MAZCRM.api.patch = patch;
  global.MAZCRM.api.del = del;
  global.MAZCRM.api.ApiError = ApiError;

}(window));

/* End of file — MAZ//ID · © 2026 Dr. Shahin Bastaninejad */
