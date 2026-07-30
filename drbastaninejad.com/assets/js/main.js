/* MAZ//ID · drbastaninejad.com — Site JS · vanilla, no framework */
'use strict';

/* ── Mobile navigation toggle ────────────────────────────────────────────── */
(function () {
  var toggle = document.getElementById('nav-toggle');
  var mobile = document.getElementById('nav-mobile');
  if (!toggle || !mobile) return;

  toggle.addEventListener('click', function () {
    var open = mobile.classList.toggle('open');
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'بستن منو' : 'باز کردن منو');
  });

  /* Close on outside click */
  document.addEventListener('click', function (e) {
    if (!toggle.contains(e.target) && !mobile.contains(e.target)) {
      mobile.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });

  /* Close on Escape */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && mobile.classList.contains('open')) {
      mobile.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.focus();
    }
  });
}());

/* ── Active nav link highlighting ───────────────────────────────────────── */
(function () {
  var links = document.querySelectorAll('.nav-links a, .nav-mobile a');
  var current = window.location.pathname.split('/').pop() || 'index.html';
  links.forEach(function (a) {
    var href = a.getAttribute('href') || '';
    if (href === current || (current === '' && href === 'index.html')) {
      a.classList.add('active');
      a.setAttribute('aria-current', 'page');
    }
  });
}());

/* ── Sticky nav shadow ───────────────────────────────────────────────────── */
(function () {
  var nav = document.querySelector('.site-nav');
  if (!nav) return;
  var obs = new IntersectionObserver(
    function (entries) {
      nav.classList.toggle('scrolled', !entries[0].isIntersecting);
    },
    { rootMargin: '-1px 0px 0px 0px', threshold: 1 }
  );
  var sentinel = document.createElement('div');
  sentinel.setAttribute('aria-hidden', 'true');
  sentinel.style.cssText = 'position:absolute;top:0;height:1px;width:100%;pointer-events:none';
  document.body.insertBefore(sentinel, document.body.firstChild);
  obs.observe(sentinel);
}());

/* ── Before/After comparison slider ─────────────────────────────────────── */
(function () {
  var sliders = document.querySelectorAll('[data-ba-slider]');
  sliders.forEach(function (wrap) {
    var after    = wrap.querySelector('.ba-after');
    var divider  = wrap.querySelector('.ba-divider');
    var handle   = wrap.querySelector('.ba-handle');
    if (!after || !divider) return;

    var dragging = false;

    function setPos(clientX) {
      var rect = wrap.getBoundingClientRect();
      var pct  = Math.min(1, Math.max(0, (clientX - rect.left) / rect.width));
      var inv  = document.documentElement.dir === 'rtl' ? 1 - pct : pct;
      var pctPx = inv * 100;
      after.style.clipPath = 'inset(0 ' + (100 - pctPx) + '% 0 0)';
      divider.style.left   = pctPx + '%';
      if (handle) handle.style.left = pctPx + '%';
    }

    /* Pointer events (covers touch + mouse + pen) */
    wrap.addEventListener('pointerdown', function (e) {
      e.preventDefault();
      dragging = true;
      wrap.setPointerCapture(e.pointerId);
      setPos(e.clientX);
    });
    wrap.addEventListener('pointermove', function (e) {
      if (!dragging) return;
      setPos(e.clientX);
    });
    wrap.addEventListener('pointerup',     function () { dragging = false; });
    wrap.addEventListener('pointercancel', function () { dragging = false; });

    /* Keyboard accessibility */
    wrap.setAttribute('tabindex', '0');
    wrap.setAttribute('role', 'slider');
    wrap.setAttribute('aria-label', 'مقایسه قبل و بعد');
    wrap.addEventListener('keydown', function (e) {
      var step = 0.05;
      var cur  = parseFloat(divider.style.left || '50') / 100;
      if (e.key === 'ArrowLeft' || e.key === 'ArrowDown')  cur -= step;
      if (e.key === 'ArrowRight' || e.key === 'ArrowUp')   cur += step;
      setPos(wrap.getBoundingClientRect().left + cur * wrap.getBoundingClientRect().width);
    });

    /* Initialise at 50% */
    setPos(wrap.getBoundingClientRect().left + wrap.getBoundingClientRect().width / 2);
  });
}());

/* ── Gallery filter pills ────────────────────────────────────────────────── */
(function () {
  var pills  = document.querySelectorAll('.filter-pill');
  var cards  = document.querySelectorAll('[data-category]');
  if (!pills.length) return;

  pills.forEach(function (pill) {
    pill.addEventListener('click', function () {
      pills.forEach(function (p) { p.classList.remove('active'); p.setAttribute('aria-pressed', 'false'); });
      pill.classList.add('active');
      pill.setAttribute('aria-pressed', 'true');

      var cat = pill.dataset.filter;
      cards.forEach(function (card) {
        var show = cat === 'all' || card.dataset.category === cat;
        card.style.display = show ? '' : 'none';
      });
    });
  });
}());

/* ── Lazy image loading (IntersectionObserver) ───────────────────────────── */
(function () {
  if (!('IntersectionObserver' in window)) return;
  var images = document.querySelectorAll('img[data-src]');
  var obs = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      var img = entry.target;
      img.src = img.dataset.src;
      img.removeAttribute('data-src');
      obs.unobserve(img);
    });
  }, { rootMargin: '200px' });
  images.forEach(function (img) { obs.observe(img); });
}());

/* ── Contact / inquiry form ──────────────────────────────────────────────── */
/*
 * Wires drbastaninejad.com/contact.html → POST /api/v1/inquiries
 *
 * Confirmed from docs/API_CONTRACT.md (2026-07-30):
 *   Request body keys : name, phone, message
 *   Success           : HTTP 201, {"success":true,"data":{"inquiry_id":N}}
 *   Validation error  : HTTP 422, {"success":false,"error":{"code":"VALIDATION_FAILED","fields":{...}}}
 *   Rate limited      : HTTP 429, {"success":false,"error":{"code":"INQUIRY_RATE_LIMITED",...}}
 *                       ⚠ Retry-After shape unconfirmed — showing generic message (see HTML comment)
 *   Server/network err: any other status or network failure → generic banner
 *
 * This module has NO dependency on api.js, sessionStorage tokens, or CRM state.
 * It is used only on the public marketing site.
 *
 * Persian digit normalisation: converts ۰–۹ / ٠–٩ → 0–9 before sending.
 */
(function () {
  'use strict';

  var form       = document.getElementById('inquiry-form');
  if (!form) return;   /* not on contact.html — bail silently */

  var fldName    = document.getElementById('contact-name');
  var fldPhone   = document.getElementById('contact-phone');
  var fldMessage = document.getElementById('contact-message');

  var grpName    = document.getElementById('grp-name');
  var grpPhone   = document.getElementById('grp-phone');
  var grpMessage = document.getElementById('grp-message');

  var errName    = document.getElementById('err-name');
  var errPhone   = document.getElementById('err-phone');
  var errMessage = document.getElementById('err-message');

  var banSuccess   = document.getElementById('inquiry-success');
  var banRateLimit = document.getElementById('inquiry-rate-limit');
  var banError     = document.getElementById('inquiry-error');
  var submitBtn    = document.getElementById('inquiry-submit');

  /* API base — same origin as app subdomain (no CRM session involved) */
  var API_BASE = 'https://app.drbastaninejad.com/api/v1';

  /* ── Helpers ── */

  function normPersianDigits(s) {
    return String(s).replace(/[۰-۹]/g, function (d) {
      return String(d.charCodeAt(0) - 0x06F0);
    }).replace(/[٠-٩]/g, function (d) {
      return String(d.charCodeAt(0) - 0x0660);
    });
  }

  function hideBanners() {
    [banSuccess, banRateLimit, banError].forEach(function (b) {
      if (b) b.removeAttribute('data-visible');
    });
  }

  function showBanner(el) {
    hideBanners();
    if (el) el.setAttribute('data-visible', '');
  }

  function clearFieldError(grp, err) {
    if (grp) grp.classList.remove('has-error');
    if (err) err.textContent = '';
  }

  function setFieldError(grp, err, msg) {
    if (grp) grp.classList.add('has-error');
    if (err) err.textContent = msg;
  }

  function clearAllErrors() {
    clearFieldError(grpName,    errName);
    clearFieldError(grpPhone,   errPhone);
    clearFieldError(grpMessage, errMessage);
  }

  function setBusy(busy) {
    form.classList.toggle('inquiry-form--busy', busy);
    if (submitBtn) submitBtn.disabled = busy;
  }

  /* Persian field-name → error element map for 422 response */
  var fieldMap = {
    name:    { grp: grpName,    err: errName,    label: 'نام' },
    phone:   { grp: grpPhone,   err: errPhone,   label: 'شماره موبایل' },
    message: { grp: grpMessage, err: errMessage, label: 'پیام' }
  };

  /* ── Submit handler ── */

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearAllErrors();
    hideBanners();

    var name    = (fldName    ? fldName.value.trim()    : '');
    var phone   = normPersianDigits(fldPhone   ? fldPhone.value.trim()   : '');
    var message = (fldMessage ? fldMessage.value.trim() : '');

    /* Client-side guard (server always re-validates) */
    var hasClientError = false;
    if (!name) {
      setFieldError(grpName, errName, 'نام الزامی است.');
      hasClientError = true;
    }
    if (!phone) {
      setFieldError(grpPhone, errPhone, 'شماره موبایل الزامی است.');
      hasClientError = true;
    }
    if (!message || message.length < 10) {
      setFieldError(grpMessage, errMessage, 'پیام باید حداقل ۱۰ کاراکتر داشته باشد.');
      hasClientError = true;
    }
    if (hasClientError) {
      /* Focus first error field */
      var firstErr = form.querySelector('.form-group.has-error input, .form-group.has-error textarea');
      if (firstErr) firstErr.focus();
      return;
    }

    setBusy(true);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', API_BASE + '/inquiries', true);
    xhr.setRequestHeader('Content-Type', 'application/json; charset=utf-8');
    xhr.timeout = 15000;

    xhr.onload = function () {
      setBusy(false);

      var resp;
      try { resp = JSON.parse(xhr.responseText); } catch (_) { resp = null; }

      if (xhr.status === 201 && resp && resp.success) {
        /* Terminal success — show banner, reset form, scroll banner into view */
        form.reset();
        clearAllErrors();
        showBanner(banSuccess);
        if (banSuccess) banSuccess.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        return;
      }

      if (xhr.status === 422 && resp && resp.error && resp.error.fields) {
        /* Field-level validation errors from server */
        var fields = resp.error.fields;
        Object.keys(fields).forEach(function (key) {
          var entry = fieldMap[key];
          if (entry) {
            setFieldError(entry.grp, entry.err, fields[key]);
          }
        });
        /* Focus first field with an error */
        var firstErr = form.querySelector('.form-group.has-error input, .form-group.has-error textarea');
        if (firstErr) firstErr.focus();
        return;
      }

      if (xhr.status === 429) {
        /*
         * docs/API_CONTRACT.md v1.2 (2026-07-31, Blackbox AI) confirmed:
         *   HTTP header:  Retry-After: <seconds>
         *   Body:         {"success":false,"error":{"code":"INQUIRY_RATE_LIMITED",
         *                   "retry_after": <seconds>}}
         * Show generic banner + countdown from error.retry_after.
         */
        var retryAfterSec = (resp && resp.error && resp.error.retry_after)
          ? parseInt(resp.error.retry_after, 10)
          : 1800; /* default 30 min if body unreadable */

        showBanner(banRateLimit);
        if (banRateLimit) {
          /* start countdown if a <span id="rate-limit-countdown"> exists in HTML */
          var countdownEl = document.getElementById('rate-limit-countdown');
          if (countdownEl && retryAfterSec > 0) {
            var remaining = retryAfterSec;
            countdownEl.textContent = Math.ceil(remaining / 60) + ' دقیقه';
            var tick = setInterval(function () {
              remaining -= 30;
              if (remaining <= 0) {
                clearInterval(tick);
                countdownEl.textContent = 'اکنون می‌توانید دوباره ارسال کنید.';
              } else {
                countdownEl.textContent = Math.ceil(remaining / 60) + ' دقیقه';
              }
            }, 30000);
          }
          banRateLimit.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        return;
      }

      /* 500 / unexpected status — generic error, no server detail */
      showBanner(banError);
      if (banError) banError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    xhr.onerror = xhr.ontimeout = function () {
      setBusy(false);
      showBanner(banError);
      if (banError) banError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    xhr.send(JSON.stringify({ name: name, phone: phone, message: message }));
  });

  /* Clear field errors on input so red border disappears as user types */
  [
    { fld: fldName,    grp: grpName,    err: errName    },
    { fld: fldPhone,   grp: grpPhone,   err: errPhone   },
    { fld: fldMessage, grp: grpMessage, err: errMessage }
  ].forEach(function (pair) {
    if (pair.fld) {
      pair.fld.addEventListener('input', function () {
        clearFieldError(pair.grp, pair.err);
        hideBanners();
      });
    }
  });

}());

/* End of file — MAZ//ID · © 2026 Dr. Shahin Bastaninejad */
