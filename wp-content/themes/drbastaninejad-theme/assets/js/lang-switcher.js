(function () {
  'use strict';

  function setOpen(root, open, restoreFocus) {
    var btn = root.querySelector('.drb-langdd__btn');
    var menu = root.querySelector('.drb-langdd__menu');
    root.classList.toggle('is-open', open);
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (menu) {
      menu.setAttribute('aria-hidden', open ? 'false' : 'true');
      if (open) menu.removeAttribute('inert');
      else menu.setAttribute('inert', '');
      menu.querySelectorAll('a').forEach(function (link) {
        link.tabIndex = open ? 0 : -1;
      });
    }
    if (!open && restoreFocus && btn && typeof btn.focus === 'function') btn.focus();
  }

  function closeAll(except, restoreFocus) {
    document.querySelectorAll('[data-drb-langdd].is-open').forEach(function (root) {
      if (except && root === except) return;
      setOpen(root, false, !!restoreFocus);
    });
  }

  function initOne(root) {
    if (!root || root.dataset.drbLangddReady === '1') return;
    var btn = root.querySelector('.drb-langdd__btn');
    var menu = root.querySelector('.drb-langdd__menu');
    if (!btn || !menu) return;
    root.dataset.drbLangddReady = '1';
    root.setAttribute('data-drb-no-translate', '');
    if (menu.hasAttribute('hidden')) menu.removeAttribute('hidden');
    setOpen(root, root.classList.contains('is-open'), false);

    function openAndFocusFirst() {
      closeAll(root, false);
      setOpen(root, true, false);
      var first = menu.querySelector('a');
      if (first) first.focus();
    }

    btn.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var open = !root.classList.contains('is-open');
      closeAll(root, false);
      setOpen(root, open, false);
    });

    btn.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        openAndFocusFirst();
      }
    });

    root.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape' || !root.classList.contains('is-open')) return;
      event.preventDefault();
      event.stopPropagation();
      setOpen(root, false, true);
    });

    root.addEventListener('focusout', function (event) {
      if (event.relatedTarget && root.contains(event.relatedTarget)) return;
      setOpen(root, false, false);
    });
  }

  document.addEventListener('click', function () { closeAll(null, false); });

  function makeCloneIdsUnique(clone) {
    var menu = clone.querySelector('.drb-langdd__menu');
    var btn = clone.querySelector('.drb-langdd__btn');
    if (!menu || !btn) return;
    var base = menu.id || 'drb-language-menu';
    var suffix = '-react';
    var next = base + suffix;
    var counter = 2;
    while (document.getElementById(next)) {
      next = base + suffix + '-' + counter;
      counter++;
    }
    menu.id = next;
    btn.setAttribute('aria-controls', next);
  }

  /** Clone the already-localized server switcher into the compiled React nav. */
  function mountIntoReactNav() {
    var source = document.querySelector('[data-drb-langdd]:not(.drb-langdd--react)');
    var nav = document.querySelector('#root .site-nav');
    if (!source || !nav) return false;
    if (nav.querySelector('[data-drb-langdd]')) {
      document.documentElement.classList.add('drb-react-nav-ready');
      return true;
    }

    var clone = source.cloneNode(true);
    clone.classList.remove('is-open');
    clone.classList.add('drb-langdd--react');
    clone.removeAttribute('data-drb-langdd-ready');
    makeCloneIdsUnique(clone);
    nav.appendChild(clone);
    initOne(clone);
    document.documentElement.classList.add('drb-react-nav-ready');
    return true;
  }

  function observeReactRoot(root) {
    if (mountIntoReactNav() || typeof MutationObserver !== 'function') return;
    var observer = new MutationObserver(function (records) {
      var navMayExist = records.some(function (record) {
        return record.addedNodes && record.addedNodes.length;
      });
      if (navMayExist && mountIntoReactNav()) observer.disconnect();
    });
    observer.observe(root, { childList: true, subtree: true });
  }

  function watchForReactRoot() {
    var root = document.getElementById('root');
    if (root) {
      observeReactRoot(root);
      return;
    }
    if (typeof MutationObserver !== 'function' || !document.body) return;
    var observer = new MutationObserver(function () {
      var candidate = document.getElementById('root');
      if (!candidate) return;
      observer.disconnect();
      observeReactRoot(candidate);
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }

  function boot() {
    document.querySelectorAll('[data-drb-langdd]').forEach(initOne);
    watchForReactRoot();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
