(function () {
  'use strict';

  function activateTab(guide, name, focus) {
    var tabs = Array.prototype.slice.call(guide.querySelectorAll('[data-guide-tab]'));
    tabs.forEach(function (tab) {
      var active = tab.getAttribute('data-guide-tab') === name;
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
      tab.tabIndex = active ? 0 : -1;
      if (active && focus) tab.focus();
    });
    guide.querySelectorAll('[data-guide-panel]').forEach(function (panel) {
      panel.hidden = panel.getAttribute('data-guide-panel') !== name;
    });
    if (window.history && window.history.replaceState) {
      window.history.replaceState(null, '', '#guide-' + name);
    }
  }

  function setupGuide(guide) {
    var tabs = Array.prototype.slice.call(guide.querySelectorAll('[data-guide-tab]'));
    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function () {
        activateTab(guide, tab.getAttribute('data-guide-tab'), false);
      });
      tab.addEventListener('keydown', function (event) {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight' && event.key !== 'Home' && event.key !== 'End') return;
        event.preventDefault();
        var next = index;
        if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = tabs.length - 1;
        else next = (index + (event.key === 'ArrowLeft' ? 1 : -1) + tabs.length) % tabs.length;
        activateTab(guide, tabs[next].getAttribute('data-guide-tab'), true);
      });
    });

    var requested = window.location.hash.replace('#guide-', '');
    if (tabs.some(function (tab) { return tab.getAttribute('data-guide-tab') === requested; })) {
      activateTab(guide, requested, false);
    }

    guide.querySelectorAll('[data-drb-food-filter]').forEach(function (filter) {
      filter.querySelectorAll('[data-food-filter]').forEach(function (button) {
        button.addEventListener('click', function () {
          var state = button.getAttribute('data-food-filter');
          filter.querySelectorAll('[data-food-filter]').forEach(function (item) { item.classList.toggle('is-active', item === button); });
          filter.querySelectorAll('[data-food-status]').forEach(function (card) {
            card.hidden = state !== 'all' && card.getAttribute('data-food-status') !== state;
          });
        });
      });
    });
  }

  document.querySelectorAll('[data-drb-guide]').forEach(setupGuide);
}());
