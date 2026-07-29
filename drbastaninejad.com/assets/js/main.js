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

/* End of file — MAZ//ID · © 2026 Dr. Shahin Bastaninejad */
