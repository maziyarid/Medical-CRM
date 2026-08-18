/**
 * Dr. Bastaninejad — booking fetch patch.
 *
 * Two confirmed production bugs are fixed here without rebuilding the
 * minified React bundle:
 *
 *  (A) "Cannot read properties of undefined (reading 'appointment')"
 *      The bundle reads window.__DRB_FORMS_API__.appointment at submit time
 *      while evaluating the fetch argument. If the bootstrap inline script never
 *      ran (e.g. a native-template page where the main bundle is not enqueued,
 *      or a partial upload), the global is undefined and the submit handler
 *      throws BEFORE fetch is called — so this fetch wrapper cannot prevent it.
 *      The real guard is the PHP-side drb_ensure_forms_api_bootstrap() in
 *      inc/react-app.php, which guarantees the global exists on every page.
 *      This file's responsibility for bug A is limited to logging a clear warning
 *      when the global is missing, so a rejected request is diagnosable.
 *
 *  (B) Booking submit is rejected with 403 "نشست فرم منقضی شده است"
 *      The bundle sends only `Content-Type` and never the X-DRB-Form-Nonce
 *      header that inc/forms.php verifies with wp_verify_nonce(). We wrap
 *      window.fetch so every POST to our own REST endpoints
 *      (/wp-json/drb/v1/appointment and /contact) gets the nonce header
 *      attached automatically.
 *
 * Scope: only requests whose URL matches our REST endpoints are modified.
 * Every other fetch on the page is left untouched.
 */
(function () {
  'use strict';

  if (window.__DRB_FETCH_PATCHED__) {
    return;
  }
  window.__DRB_FETCH_PATCHED__ = true;

  var NONCE_HEADER = 'X-DRB-Form-Nonce';

  /** Get the forms API object if it is safe to use. */
  function getApi() {
    var api = window.__DRB_FORMS_API__;
    if (!api || typeof api !== 'object') {
      return null;
    }
    return api;
  }

  /** True when a request URL targets our own public form REST endpoints. */
  function isOurEndpoint(url) {
    if (!url) {
      return false;
    }
    var str = String(url);

    // 1) Compare against the configured endpoints first. rest_url() may emit an
    //    absolute URL, a pretty permalink, or a query-string form
    //    (?rest_route=/drb/v1/...). Matching the exact configured endpoint
    //    strings is the most reliable signal that this is our own request.
    var api = getApi();
    if (api) {
      var appt = api.appointment;
      var contact = api.contact;
      if (appt && str.indexOf(appt) !== -1) {
        return true;
      }
      if (contact && str.indexOf(contact) !== -1) {
        return true;
      }
    }

    // 2) Fallback regex for same-origin relative URLs and any absolute URL that
    //    hits the /wp-json/drb/v1/ namespace.
    if (/\/wp-json\/drb\/v1\/(appointment|contact)(?:[/?#]|$)/i.test(str)) {
      return true;
    }

    // 3) Query-string REST routing: ?rest_route=/drb/v1/(appointment|contact)
    if (/rest_route=[^&]*\/drb\/v1\/(appointment|contact)(?:[/?#]|$)/i.test(str)) {
      return true;
    }

    return false;
  }

  /** Show a clear Persian error in the console for ops triage. */
  function warn(message) {
    if (window.console && typeof console.warn === 'function') {
      console.warn('[DRB booking] ' + message);
    }
  }

  function procedureKey(value) {
    var text = String(value || '').toLowerCase();
    if (!text) return '';
    if (/ترمیم|ترميم|revision|revizyon|ревиз|повторн|révision|revision|revisión/.test(text)) return 'revision-rhinoplasty';
    if (/سپتو|septoplast/.test(text)) return 'septoplasty';
    if (/توربین|توربين|turbinoplast/.test(text)) return 'turbinoplasty';
    if (/fess|آندوسکو|اندوسكو|endoscop/.test(text)) return 'sinus-endoscopy-fess';
    if (/قوز|hump|höcker|bosse|горбин|giba|حدبة/.test(text)) return 'hump-removal';
    if (/استخوان|bony|osse|knochen|кост|kemik|ósea|عظم/.test(text)) return 'bony-rhinoplasty';
    if (/طبیعی|طبيعي|natural|naturel|natürlich|естеств|doğal/.test(text)) return 'natural-rhinoplasty';
    if (/رینو|رينو|rhinoplast|rinoplast|ринопласт/.test(text)) return 'primary-rhinoplasty';
    return '';
  }

  function addStableBookingFields(url, init) {
    if (!init || typeof init.body !== 'string' || !/appointment/i.test(String(url || ''))) return;
    try {
      var payload = JSON.parse(init.body);
      if (!payload || typeof payload !== 'object') return;
      if (!payload.procedureKey) payload.procedureKey = procedureKey(payload.procedure || payload.service || '');
      var locale = window.__DRB_I18N__ && window.__DRB_I18N__.lang;
      if (!payload.language && locale) payload.language = locale;

      // The compiled React form predates international booking fields. The
      // readable enhancement script adds them to the DOM; inject those values
      // into the existing JSON body without modifying React component state.
      if (locale && locale !== 'fa' && typeof document !== 'undefined') {
        var email = document.querySelector('[data-drb-intl-email]');
        var country = document.querySelector('[data-drb-intl-country]');
        var dial = document.querySelector('[data-drb-intl-dial]');
        if (email && !payload.email) payload.email = String(email.value || '').trim();
        if (country && !payload.country) payload.country = String(country.value || '').trim();
        if (dial && !payload.dialCode) payload.dialCode = String(dial.value || '').trim();

        // If React sends a national-format number, combine it with the required
        // calling code before the request leaves the browser. The server still
        // validates and normalizes independently, so this is convenience only.
        if (payload.phone && payload.dialCode && !/^\s*(?:\+|00)/.test(String(payload.phone))) {
          var dialCode = String(payload.dialCode).replace(/[^+0-9]/g, '');
          var national = String(payload.phone).replace(/\D+/g, '').replace(/^0+/, '');
          if (/^\+\d{1,4}$/.test(dialCode) && national) payload.phone = dialCode + national;
        }
      }
      init.body = JSON.stringify(payload);
    } catch (ignore) {}
  }

  function isAppointmentEndpoint(url) {
    return /appointment/i.test(String(url || ''));
  }

  function patientLoginLabel() {
    var strings = window.__DRB_I18N__ && window.__DRB_I18N__.strings;
    return strings && strings.nav_patient_login ? strings.nav_patient_login : 'Patient portal';
  }

  function showPatientLoginNotice(message, loginUrl) {
    if (typeof document === 'undefined' || !loginUrl) return;
    var form = null;
    var forms = document.querySelectorAll('form');
    for (var i = 0; i < forms.length; i++) {
      if (forms[i].querySelector('input[type="tel"]') && forms[i].querySelector('input[type="number"][min="18"]')) {
        form = forms[i];
        break;
      }
    }
    if (!form) return;
    var existing = form.querySelector('[data-drb-patient-login-notice]');
    if (existing) existing.remove();
    var box = document.createElement('div');
    box.setAttribute('data-drb-patient-login-notice', '1');
    box.setAttribute('role', 'alert');
    box.className = 'mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-start';
    var text = document.createElement('span');
    text.textContent = message || '';
    var sep = document.createTextNode(' ');
    var link = document.createElement('a');
    link.href = loginUrl;
    link.className = 'font-bold underline underline-offset-2';
    link.textContent = patientLoginLabel();
    box.appendChild(text);
    box.appendChild(sep);
    box.appendChild(link);
    form.appendChild(box);
  }

  function handleAppointmentResponse(url, response) {
    if (!response || !isAppointmentEndpoint(url) || (response.status !== 409 && response.status !== 429)) return response;
    try {
      response.clone().json().then(function (data) {
        var api = getApi() || {};
        var error = data && data.data ? data.data : {};
        var login = error.patientLogin || data.patientLogin || '';
        var message = (data && data.message) || '';
        // Only duplicate/cooldown responses carry patientLogin. A generic IP
        // rate-limit is also HTTP 429 but must not be misrepresented as an
        // existing patient record.
        if (login) showPatientLoginNotice(message, login);
      }).catch(function () {});
    } catch (ignore) {}
    return response;
  }

  var originalFetch = window.fetch;
  if (typeof originalFetch !== 'function') {
    warn('window.fetch is unavailable; cannot patch booking requests.');
    return;
  }

  window.fetch = function patchedFetch(input, init) {
    try {
      var url = typeof input === 'string' ? input : (input && input.url) ? input.url : '';
      if (isOurEndpoint(url)) {
        init = init || {};
        addStableBookingFields(url, init);
        if (typeof init === 'object' && init !== null) {
          init.headers = init.headers || {};
          // Headers may be a plain object, an array of pairs, or a Headers instance.
          if (init.headers instanceof Headers) {
            if (!init.headers.has(NONCE_HEADER)) {
              var api0 = getApi();
              if (api0 && api0.nonce) {
                init.headers.set(NONCE_HEADER, api0.nonce);
              } else {
                warn('forms API global missing; request will be rejected by the server.');
              }
            }
          } else if (Array.isArray(init.headers)) {
            var hasNonce = init.headers.some(function (pair) {
              return pair && pair[0] && pair[0].toLowerCase() === NONCE_HEADER.toLowerCase();
            });
            if (!hasNonce) {
              var api1 = getApi();
              if (api1 && api1.nonce) {
                init.headers.push([NONCE_HEADER, api1.nonce]);
              } else {
                warn('forms API global missing; request will be rejected by the server.');
              }
            }
          } else {
            var lowered = {};
            var keys = Object.keys(init.headers);
            for (var i = 0; i < keys.length; i++) {
              lowered[keys[i].toLowerCase()] = true;
            }
            if (!lowered[NONCE_HEADER.toLowerCase()]) {
              var api2 = getApi();
              if (api2 && api2.nonce) {
                init.headers[NONCE_HEADER] = api2.nonce;
              } else {
                warn('forms API global missing; request will be rejected by the server.');
              }
            }
          }
        }
      }
    } catch (patchError) {
      warn('fetch patch error: ' + (patchError && patchError.message ? patchError.message : String(patchError)));
    }
    var requestUrl = typeof input === 'string' ? input : (input && input.url) ? input.url : '';
    return originalFetch.call(window, input, init).then(function (response) {
      return handleAppointmentResponse(requestUrl, response);
    });
  };

  // Preserve a backdoor in case a future bundle ships its own nonce handling.
  window.__DRB_FETCH_ORIGINAL__ = originalFetch;
})();
