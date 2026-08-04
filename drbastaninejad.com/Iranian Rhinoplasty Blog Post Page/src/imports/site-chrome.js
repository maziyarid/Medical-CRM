/* MAZ//ID · drbastaninejad.com — site-chrome.js
 * Handles: mega-menu, hamburger, mobile nav, chaty floater, scroll animations
 * Vanilla ES5-compatible, no framework dependencies.
 */
'use strict';

/* ── 1. NAV: hamburger + mobile panel ──────────────────────────────────────── */
(function () {
  var toggle   = document.getElementById('nav-toggle');
  var panel    = document.getElementById('nav-mobile');
  var backdrop = document.getElementById('nav-backdrop');
  var closeBtn = document.getElementById('nm-close');

  if (!toggle || !panel) return;

  function openNav() {
    panel.classList.add('open');
    document.body.classList.add('nav-open');
    document.body.style.overflow = 'hidden';
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'بستن منو');
    if (backdrop) backdrop.classList.add('visible');
    /* move focus to close button for a11y */
    if (closeBtn) setTimeout(function () { closeBtn.focus(); }, 50);
  }

  function closeNav() {
    panel.classList.remove('open');
    document.body.classList.remove('nav-open');
    document.body.style.overflow = '';
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'باز کردن منو');
    if (backdrop) backdrop.classList.remove('visible');
    /* return focus to toggle */
    toggle.focus();
  }

  toggle.addEventListener('click', function (e) {
    e.stopPropagation();
    if (panel.classList.contains('open')) { closeNav(); } else { openNav(); }
  });

  if (closeBtn)  closeBtn.addEventListener('click', closeNav);
  if (backdrop)  backdrop.addEventListener('click', closeNav);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && panel.classList.contains('open')) closeNav();
  });

  /* Close when any <a> inside mobile panel is clicked
     (handles clicks on child <i> icons via closest()) */
  panel.addEventListener('click', function (e) {
    var link = e.target.closest('a');
    if (link && panel.contains(link)) {
      /* Small delay so the browser follows the href first */
      setTimeout(closeNav, 80);
    }
  });
}());

/* ── 2. NAV: active link by URL ────────────────────────────────────────────── */
(function () {
  var page = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links > li > a, .nm-item').forEach(function (a) {
    var href = (a.getAttribute('href') || '').split('/').pop();
    if (href === page) {
      a.classList.add('active');
      a.setAttribute('aria-current', 'page');
    }
  });
}());

/* ── 3. NAV: scroll shadow ─────────────────────────────────────────────────── */
(function () {
  var nav = document.querySelector('.site-nav');
  if (!nav) return;
  var handler = function () {
    nav.classList.toggle('scrolled', window.scrollY > 8);
  };
  window.addEventListener('scroll', handler, { passive: true });
  handler(); // run once on load
}());

/* ── 4. MEGA-MENU: keyboard accessibility ──────────────────────────────────── */
(function () {
  document.querySelectorAll('.nav-mega, .nav-dropdown').forEach(function (menu) {
    var parent = menu.closest('li');
    if (!parent) return;

    /* Show/hide on focus-within using CSS class — works with visibility approach */
    parent.addEventListener('focusin', function () {
      parent.classList.add('mega-focus');
    });
    parent.addEventListener('focusout', function (e) {
      if (!parent.contains(e.relatedTarget)) {
        parent.classList.remove('mega-focus');
      }
    });
  });
}());

/* ── 5. CHATY floating contact button ──────────────────────────────────────── */
(function () {
  var trigger  = document.getElementById('chaty-trigger');
  var channels = document.getElementById('chaty-channels');
  var wrap     = document.getElementById('chaty-wrap');
  if (!trigger || !channels) return;

  var isOpen = false;

  function openChaty() {
    isOpen = true;
    channels.classList.add('open');
    trigger.classList.add('open');
    trigger.setAttribute('aria-expanded', 'true');
    /* Fade out badge (querySelector — it has a class, not an id) */
    var badge = trigger.querySelector('.chaty-badge');
    if (badge && badge.style.display !== 'none') {
      badge.style.transition = 'opacity .3s ease';
      badge.style.opacity = '0';
      setTimeout(function () { badge.style.display = 'none'; }, 300);
    }
  }

  function closeChaty() {
    isOpen = false;
    channels.classList.remove('open');
    trigger.classList.remove('open');
    trigger.setAttribute('aria-expanded', 'false');
  }

  trigger.addEventListener('click', function (e) {
    e.stopPropagation();
    if (isOpen) { closeChaty(); } else { openChaty(); }
  });

  /* Close on outside click */
  document.addEventListener('click', function (e) {
    if (isOpen && wrap && !wrap.contains(e.target)) {
      closeChaty();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isOpen) closeChaty();
  });

  /* Close chaty when a channel link is tapped (mobile: prevents lingering open) */
  channels.addEventListener('click', function (e) {
    var link = e.target.closest('a');
    if (link) setTimeout(closeChaty, 200);
  });
}());

/* ── 6. SCROLL REVEAL ──────────────────────────────────────────────────────── */
(function () {
  if (!('IntersectionObserver' in window)) {
    /* Fallback: reveal everything immediately */
    document.querySelectorAll('[data-reveal]').forEach(function (el) {
      el.classList.add('revealed');
    });
    return;
  }

  var obs = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('revealed');
        obs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('[data-reveal]').forEach(function (el) {
    obs.observe(el);
  });

  /* Also handle legacy .reveal class from premium.js */
  document.querySelectorAll('.reveal:not([data-reveal])').forEach(function (el) {
    el.setAttribute('data-reveal', '');
    obs.observe(el);
  });
}());

/* ── 7. SMOOTH BACK-TO-TOP ─────────────────────────────────────────────────── */
(function () {
  var btn = document.getElementById('back-to-top');
  if (!btn) return;
  window.addEventListener('scroll', function () {
    btn.classList.toggle('visible', window.scrollY > 400);
  }, { passive: true });
  btn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}());

/* ── 8. COUNTER ANIMATION (stats) ─────────────────────────────────────────── */
(function () {
  var counters = document.querySelectorAll('[data-count]');
  if (!counters.length || !('IntersectionObserver' in window)) return;

  function animateCount(el, target, suffix) {
    var start = 0;
    var duration = 1200;
    var startTime = null;
    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      var ease = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(ease * target) + (suffix || '');
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  var cObs = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      var el = entry.target;
      var val = parseInt(el.getAttribute('data-count'), 10);
      var sfx = el.getAttribute('data-suffix') || '';
      animateCount(el, val, sfx);
      cObs.unobserve(el);
    });
  }, { threshold: 0.5 });

  counters.forEach(function (el) { cObs.observe(el); });
}());

/* ── 9. CERTIFICATE LIGHTBOX ───────────────────────────────────────────────── */
(function () {
  /* Build the lightbox DOM once */
  var lb = document.createElement('div');
  lb.className = 'cert-lightbox';
  lb.setAttribute('role', 'dialog');
  lb.setAttribute('aria-modal', 'true');
  lb.setAttribute('aria-label', 'گواهی‌نامه');

  var closeBtn = document.createElement('button');
  closeBtn.className = 'cert-lightbox-close';
  closeBtn.setAttribute('aria-label', 'بستن');
  closeBtn.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';

  var img = document.createElement('img');
  img.alt = '';

  var caption = document.createElement('div');
  caption.className = 'cert-lightbox-caption';

  lb.appendChild(closeBtn);
  lb.appendChild(img);
  lb.appendChild(caption);
  document.body.appendChild(lb);

  function openLB(src, cap) {
    img.src = src;
    caption.textContent = cap || '';
    lb.classList.add('open');
    document.body.style.overflow = 'hidden';
    closeBtn.focus();
  }
  function closeLB() {
    lb.classList.remove('open');
    document.body.style.overflow = '';
  }

  closeBtn.addEventListener('click', closeLB);
  lb.addEventListener('click', function (e) { if (e.target === lb) closeLB(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && lb.classList.contains('open')) closeLB();
  });

  /* Wire up all .cert-card elements */
  function initCertCards() {
    document.querySelectorAll('.cert-card').forEach(function (card) {
      /* Make accessible */
      if (!card.getAttribute('role')) card.setAttribute('role', 'button');
      if (!card.getAttribute('tabindex')) card.setAttribute('tabindex', '0');

      var imgEl = card.querySelector('img');
      var capEl = card.querySelector('p, figcaption, [class*="caption"]');
      var src   = imgEl ? imgEl.src : '';
      var cap   = capEl ? capEl.textContent.trim() : (imgEl ? imgEl.alt : '');

      card.addEventListener('click', function () { if (src) openLB(src, cap); });
      card.addEventListener('keydown', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && src) {
          e.preventDefault();
          openLB(src, cap);
        }
      });
    });
  }
  initCertCards();
}());

/* ── 10. PARTICLE CANVAS — subtle floating dots ─────────────────────────── */
(function () {
  /* Only create if not prefers-reduced-motion and canvas supported */
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (typeof HTMLCanvasElement === 'undefined') return;

  var canvas = document.createElement('canvas');
  canvas.id = 'particle-canvas';
  canvas.setAttribute('aria-hidden', 'true');
  /* Append to body so it sits under all content naturally via CSS z-index */
  document.body.appendChild(canvas);

  var ctx = canvas.getContext('2d');
  var W, H, particles;
  var COLOR = 'rgba(47,125,50,';

  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }
  resize();
  window.addEventListener('resize', resize, { passive: true });

  function Particle() {
    this.x = Math.random() * W;
    this.y = Math.random() * H;
    this.r = Math.random() * 2 + 0.5;
    this.vx = (Math.random() - .5) * 0.3;
    this.vy = (Math.random() - .5) * 0.3;
    this.alpha = Math.random() * 0.25 + 0.05;
  }
  Particle.prototype.update = function () {
    this.x += this.vx;
    this.y += this.vy;
    if (this.x < 0) this.x = W;
    if (this.x > W) this.x = 0;
    if (this.y < 0) this.y = H;
    if (this.y > H) this.y = 0;
  };
  Particle.prototype.draw = function () {
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
    ctx.fillStyle = COLOR + this.alpha + ')';
    ctx.fill();
  };

  var COUNT = Math.min(60, Math.floor(W * H / 20000));
  particles = [];
  for (var i = 0; i < COUNT; i++) particles.push(new Particle());

  var raf;
  function draw() {
    ctx.clearRect(0, 0, W, H);
    particles.forEach(function (p) { p.update(); p.draw(); });
    /* Draw connecting lines between close particles */
    for (var a = 0; a < particles.length; a++) {
      for (var b = a + 1; b < particles.length; b++) {
        var dx = particles[a].x - particles[b].x;
        var dy = particles[a].y - particles[b].y;
        var dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 120) {
          ctx.beginPath();
          ctx.moveTo(particles[a].x, particles[a].y);
          ctx.lineTo(particles[b].x, particles[b].y);
          ctx.strokeStyle = COLOR + (0.04 * (1 - dist / 120)) + ')';
          ctx.lineWidth = 0.6;
          ctx.stroke();
        }
      }
    }
    raf = requestAnimationFrame(draw);
  }
  draw();

  /* Pause when tab is hidden to save CPU */
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) cancelAnimationFrame(raf);
    else draw();
  });
}());

/* ── 11. TRUST MARQUEE — inject marquee band before trust-strip-v2 ─────── */
(function () {
  var strip = document.querySelector('.trust-strip-v2');
  if (!strip) return;

  var items = [
    { icon: 'fa-solid fa-graduation-cap',   text: 'عضو هیئت علمی TUMS' },
    { icon: 'fa-solid fa-award',             text: 'رتبه ۴ بورد تخصصی کشوری' },
    { icon: 'fa-solid fa-user-doctor',       text: 'بیش از ۲۰۰۰ عمل موفق' },
    { icon: 'fa-solid fa-star',              text: 'دبیر کمیته علمی رینولوژی' },
    { icon: 'fa-solid fa-globe',             text: 'سخنران کنگره‌های بین‌المللی' },
    { icon: 'fa-solid fa-clock',             text: 'بیش از ۱۵ سال تجربه تخصصی' },
    { icon: 'fa-solid fa-hospital',          text: 'بیمارستان امیراعلم تهران' },
    { icon: 'fa-solid fa-microscope',        text: 'فوق‌تخصص جراحی رینوپلاستی' }
  ];

  /* Build inner HTML — duplicate the list for seamless loop */
  function buildTrack(items) {
    return items.map(function (it) {
      return '<span class="trust-marquee-item">' +
        '<i class="' + it.icon + '" aria-hidden="true"></i>' +
        it.text +
        '</span>' +
        '<span class="trust-marquee-sep" aria-hidden="true"></span>';
    }).join('');
  }

  var wrap = document.createElement('div');
  wrap.className = 'trust-marquee-wrap';
  wrap.setAttribute('aria-hidden', 'true'); /* decorative */

  var track = document.createElement('div');
  track.className = 'trust-marquee-track';
  /* Single copy — static strip, no loop needed */
  track.innerHTML = buildTrack(items);

  wrap.appendChild(track);
  strip.parentNode.insertBefore(wrap, strip);
}());
