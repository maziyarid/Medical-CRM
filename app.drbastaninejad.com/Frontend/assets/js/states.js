/* MAZ//ID · Dr. Shahin Bastaninejad Medical Platform */
/*
 * ============================================================================
 *  Cross-cutting state helpers — matches states.css.
 * ----------------------------------------------------------------------------
 *  Depends : (none — pure DOM). Compatible with old-browser targets: no
 *            optional chaining, no nullish coalescing, no async/await.
 *
 *  API (attached to window.MAZCRM.states):
 *    set(hostEl, state)        toggle 'loading'|'ready'|'empty'|'error'|'forbidden'|'offline'
 *    fromError(err)            infer state from an APIError (or generic Error)
 *    renderPanel(opts)         return HTML for a .state-panel with icon/title/text/actions
 *    bindOfflineBanner()       show/hide bottom banner on window online/offline
 *    bindSessionExpiredBanner()show top banner when session:expired custom event fires
 *    withSubmit(formEl, fn)    disable form + button + spinner while fn() runs
 * ============================================================================
 */
(function (global) {
  'use strict';

  var STATES = ['loading', 'ready', 'empty', 'error', 'forbidden', 'offline'];

  function set(host, state) {
    if (!host || STATES.indexOf(state) < 0) return;
    host.setAttribute('data-state', state);
  }

  /**
   * Map an APIError (from api.js) or a generic Error into a UI state name.
   * Returns 'forbidden' for 401/403, 'offline' for network, 'error' otherwise.
   */
  function fromError(err) {
    if (!err) return 'error';
    if (err.isOffline) return 'offline';
    var s = err.status;
    if (s === 401 || s === 403) return 'forbidden';
    return 'error';
  }

  /**
   * Build a state-panel HTML string. All fields optional except title.
   * icon defaults per state; pass icon:'' to omit.
   */
  function renderPanel(opts) {
    opts = opts || {};
    var stateName = opts.state || 'empty';
    var iconMap   = { empty:'🌿', error:'⚠️', forbidden:'🔒', offline:'📡' };
    var icon = (opts.icon === '') ? '' : (opts.icon || iconMap[stateName] || '');
    var actions = '';
    if (opts.actions && opts.actions.length) {
      actions = '<div class="state-actions">' +
        opts.actions.map(function (a) {
          var cls = 'btn ' + (a.variant || 'btn-secondary') + ' btn-sm';
          if (a.href) return '<a class="' + cls + '" href="' + a.href + '">' + a.label + '</a>';
          return '<button type="button" class="' + cls + '" data-action="' + (a.name || '') + '">' + a.label + '</button>';
        }).join('') +
      '</div>';
    }
    return '' +
      '<div class="state-panel state-' + stateName + '" role="status" aria-live="polite">' +
        (icon ? '<div class="state-icon" aria-hidden="true">' + icon + '</div>' : '') +
        '<h3>' + (opts.title || '') + '</h3>' +
        (opts.text ? '<p>' + opts.text + '</p>' : '') +
        actions +
      '</div>';
  }

  /**
   * Show a persistent bottom banner while navigator.onLine === false.
   */
  function bindOfflineBanner() {
    var banner = null;
    function show() {
      if (banner) return;
      banner = document.createElement('div');
      banner.className = 'offline-banner';
      banner.setAttribute('role', 'alert');
      banner.textContent = 'اتصال اینترنت شما قطع شده است — تغییرات ذخیره نمی‌شوند.';
      document.body.appendChild(banner);
    }
    function hide() {
      if (!banner) return;
      banner.remove();
      banner = null;
    }
    window.addEventListener('online', hide);
    window.addEventListener('offline', show);
    if (typeof navigator !== 'undefined' && navigator.onLine === false) show();
  }

  /**
   * Listen for a `mazcrm:session-expired` custom event (dispatched by session.js
   * when the server returns 401 on a token-bearing request) and show a sticky
   * top banner with a "Re-login" action that navigates to the correct login URL.
   */
  function bindSessionExpiredBanner() {
    document.addEventListener('mazcrm:session-expired', function (e) {
      if (document.querySelector('.session-banner')) return;
      var scope = (e && e.detail && e.detail.scope) || 'staff';
      var loginHref = scope === 'patient' ? '../auth/patient-login.html' : '../auth/login.html';
      var banner = document.createElement('div');
      banner.className = 'session-banner';
      banner.setAttribute('role', 'alert');
      banner.innerHTML =
        '<span>نشست شما به پایان رسیده است. برای ادامه دوباره وارد شوید.</span>' +
        '<span class="session-banner-actions">' +
          '<a class="btn btn-primary" href="' + loginHref + '">ورود مجدد</a>' +
        '</span>';
      document.body.insertBefore(banner, document.body.firstChild);
    });
  }

  /**
   * Disable a form + primary submit button + show spinner while fn() runs.
   * fn is expected to return a Promise. The button's original innerHTML is
   * restored on resolve/reject.
   *
   *   MAZCRM.states.withSubmit(formEl, function () { return api.submit(...); })
   *     .then(...).catch(...);
   */
  function withSubmit(form, fn) {
    var btn = form.querySelector('[type="submit"]');
    var oldHtml = btn ? btn.innerHTML : null;
    form.classList.add('form-busy');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner" aria-hidden="true"></span> <span>در حال ارسال…</span>';
    }
    var release = function () {
      form.classList.remove('form-busy');
      if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; }
    };
    var p;
    try { p = fn(); } catch (e) { release(); return Promise.reject(e); }
    if (!p || typeof p.then !== 'function') { release(); return Promise.resolve(p); }
    return p.then(function (v) { release(); return v; }, function (e) { release(); throw e; });
  }

  global.MAZCRM = global.MAZCRM || {};
  global.MAZCRM.states = {
    set: set,
    fromError: fromError,
    renderPanel: renderPanel,
    bindOfflineBanner: bindOfflineBanner,
    bindSessionExpiredBanner: bindSessionExpiredBanner,
    withSubmit: withSubmit
  };

}(window));

/* End of file — MAZ//ID · © 2026 Dr. Shahin Bastaninejad */
