(function () {
  function closeAll(except) {
    document.querySelectorAll('[data-drb-langdd].is-open').forEach(function (el) {
      if (except && el === except) return;
      el.classList.remove('is-open');
      var btn = el.querySelector('.drb-langdd__btn');
      var menu = el.querySelector('.drb-langdd__menu');
      if (btn) btn.setAttribute('aria-expanded', 'false');
      if (menu) menu.setAttribute('aria-hidden', 'true');
    });
  }

  function initOne(root) {
    var btn = root.querySelector('.drb-langdd__btn');
    var menu = root.querySelector('.drb-langdd__menu');
    if (!btn || !menu) return;
    // Backward compatibility: older lang-chrome.php may still ship the menu
    // with the HTML `hidden` attribute, which forces display:none and breaks
    // the smooth CSS disclosure. Remove it and drive visibility via
    // aria-hidden + .is-open instead.
    if (menu.hasAttribute('hidden')) menu.removeAttribute('hidden');
    menu.setAttribute('aria-hidden', root.classList.contains('is-open') ? 'false' : 'true');

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var open = !root.classList.contains('is-open');
      closeAll(root);
      root.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      menu.setAttribute('aria-hidden', open ? 'false' : 'true');
    });

    btn.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        root.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        menu.setAttribute('aria-hidden', 'false');
        var first = menu.querySelector('a');
        if (first) first.focus();
      }
    });
  }

  document.addEventListener('click', function () { closeAll(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAll();
  });

  function boot() {
    document.querySelectorAll('[data-drb-langdd]').forEach(initOne);
    mountIntoReactNav();
    watchForReactNav();
  }

  /**
   * The React SPA renders its own primary navigation (.site-nav) which does not
   * include a language switcher. Clone the server-rendered dropdown into that
   * nav once it appears so SPA pages get the same switcher as native pages.
   * The clone is re-initialized so keyboard/click handlers are bound.
   */
  function mountIntoReactNav() {
    if (window.__DRB_LANG_REACT_MOUNTED__) return;
    var source = document.querySelector('[data-drb-langdd]');
    var nav = document.querySelector('.site-nav');
    if (!source || !nav || nav.querySelector('[data-drb-langdd]')) return;
    window.__DRB_LANG_REACT_MOUNTED__ = true;
    var clone = source.cloneNode(true);
    clone.classList.remove('is-open');
    var menu = clone.querySelector('.drb-langdd__menu');
    if (menu) menu.hidden = true;
    var btn = clone.querySelector('.drb-langdd__btn');
    if (btn) btn.setAttribute('aria-expanded', 'false');
    clone.classList.add('drb-langdd--react');
    nav.appendChild(clone);
    initOne(clone);
    // Signal that the React nav now carries the language switcher, so the
    // native header can be hidden for certain (it already is via drb-react-app).
    document.documentElement.classList.add('drb-react-nav-ready');
  }

  /** Watch for the React .site-nav appearing after initial boot (SPA mount). */
  function watchForReactNav() {
    if (window.__DRB_LANG_REACT_WATCH__) return;
    window.__DRB_LANG_REACT_WATCH__ = true;
    if (typeof MutationObserver !== 'function') return;
    var obs = new MutationObserver(function () { mountIntoReactNav(); });
    obs.observe(document.body, { childList: true, subtree: true });
    // Stop watching after the SPA has had plenty of time to mount, to avoid a
    // long-running observer on a busy page.
    setTimeout(function () { obs.disconnect(); }, 8000);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
