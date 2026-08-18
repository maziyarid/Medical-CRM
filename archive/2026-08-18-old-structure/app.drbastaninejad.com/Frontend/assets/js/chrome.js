/*
 * ============================================================================
 *  MΛZ Medical CRM — Shell chrome (sidebar + topbar + footer injection)
 * ----------------------------------------------------------------------------
 *  Author  : MAZ//ID (Maziyar) · maziyarid@gmail.com
 *  License : Proprietary — © 2026 Maziyar / Dr. Shahin Bastaninejad
 * ============================================================================
 *  Renders the shared staff/patient shell client-side so every page stays
 *  DRY without a build step. Use data-nav="<key>" on <body> to highlight.
 * ============================================================================
 */
(function () {
  'use strict';

  var ICON = {
    dash:      '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
    patients:  '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="4"/><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6"/><circle cx="17" cy="6" r="2.5"/><path d="M15.5 12c2 0 4.5 1 5.5 3"/></svg>',
    intake:    '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M9 14h6M9 18h4"/></svg>',
    calendar:  '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/></svg>',
    emr:       '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h12a4 4 0 0 1 4 4v12H8a4 4 0 0 1-4-4Z"/><path d="M12 8v8M8 12h8"/></svg>',
    billing:   '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg>',
    tasks:     '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
    analytics: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/></svg>',
    settings:  '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    logout:    '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>',
    bell:      '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>',
    home:      '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>',
    profile:   '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-6 8-6s8 2 8 6"/></svg>',
    docs:      '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>'
  };

  var STAFF_NAV = [
    { key: 'dashboard',  label: 'داشبورد',            href: '../staff/dashboard.html',   icon: ICON.dash },
    { key: 'patients',   label: 'بیماران',            href: '../staff/patients.html',    icon: ICON.patients },
    { key: 'intake',     label: 'پذیرش و تشکیل پرونده', href: '../intake/intake.html',    icon: ICON.intake },
    { key: 'calendar',   label: 'تقویم و نوبت‌دهی',    href: '../staff/calendar.html',    icon: ICON.calendar },
    { key: 'emr',        label: 'پرونده الکترونیک',    href: '../staff/emr.html',         icon: ICON.emr },
    { key: 'billing',    label: 'صورتحساب و پرداخت',  href: '../staff/billing.html',     icon: ICON.billing },
    { key: 'tasks',      label: 'وظایف و کارها',       href: '../staff/tasks.html',       icon: ICON.tasks },
    { key: 'analytics',  label: 'گزارش‌ها',            href: '../staff/analytics.html',   icon: ICON.analytics },
    { key: 'settings',   label: 'تنظیمات',            href: '../staff/settings.html',    icon: ICON.settings }
  ];

  var PATIENT_NAV = [
    { key: 'overview',    label: 'نمای کلی',            href: '../patient/overview.html',       icon: ICON.home },
    { key: 'profile',     label: 'پروفایل من',          href: '../patient/profile.html',        icon: ICON.profile },
    { key: 'records',     label: 'پرونده پزشکی',        href: '../patient/records.html',        icon: ICON.emr },
    { key: 'appointments',label: 'نوبت‌ها و یادآور',    href: '../patient/appointments.html',   icon: ICON.calendar },
    { key: 'documents',   label: 'مستندات',             href: '../patient/documents.html',      icon: ICON.docs },
    { key: 'notifications',label:'اعلان‌ها و تنظیمات',  href: '../patient/notifications.html',  icon: ICON.bell }
  ];

  function brandBlock(kind) {
    var title = kind === 'patient'
      ? 'داشبورد بیمار<small>دکتر باستانی‌نژاد</small>'
      : 'CRM پزشکی<small>دکتر باستانی‌نژاد</small>';
    return '' +
      '<div class="sidebar-brand">' +
        '<div class="brand-mark" aria-hidden="true">MΛZ</div>' +
        '<div class="brand-text">' + title + '</div>' +
      '</div>';
  }

  function renderNav(items, active) {
    return items.map(function (n) {
      var cls = 'nav-item' + (n.key === active ? ' active' : '');
      return '<a class="' + cls + '" href="' + n.href + '">' + n.icon + '<span>' + n.label + '</span></a>';
    }).join('');
  }

  function renderSidebar(kind, active, user) {
    var nav = kind === 'patient' ? PATIENT_NAV : STAFF_NAV;
    var name = (user && user.name) || (kind === 'patient' ? 'مریم احمدی' : 'دکتر شاهین باستانی‌نژاد');
    var role = (user && user.role) || (kind === 'patient' ? 'بیمار' : 'پزشک — مدیر مرکز');
    var initials = name.split(' ').map(function (w) { return w[0]; }).slice(0,2).join('');
    return '' +
      '<nav class="sidebar" aria-label="ناوبری اصلی">' +
        brandBlock(kind) +
        renderNav(nav, active) +
        '<div class="sidebar-footer">' +
          '<div class="sidebar-user">' +
            '<div class="avatar-md">' + initials + '</div>' +
            '<div class="who">' + name + '<small>' + role + '</small></div>' +
          '</div>' +
          '<button class="logout-btn" type="button" data-logout-href="../auth/login.html" aria-label="خروج از حساب">' + ICON.logout + '<span>خروج از حساب</span></button>' +
        '</div>' +
      '</nav>';
  }

  function renderBottomNav(kind, active) {
    var nav = (kind === 'patient' ? PATIENT_NAV : STAFF_NAV).slice(0, 5);
    return '<nav class="bottom-nav" aria-label="ناوبری موبایل">' +
      nav.map(function (n) {
        return '<a class="' + (n.key === active ? 'active' : '') + '" href="' + n.href + '">' + n.icon + '<span>' + n.label + '</span></a>';
      }).join('') +
      '</nav>';
  }

  function renderFooter() {
    var y = new Date().getFullYear();
    return '' +
      '<footer class="brand-footer">' +
        '<div class="maz-sig">M<span class="z">Λ</span>Z <span style="opacity:.5">//</span> ID</div>' +
        '<div>ساخته شده توسط <a href="https://maziyarid.com" target="_blank" rel="noopener">Maziyar</a> — © ' + y + ' دکتر شاهین باستانی‌نژاد. تمامی حقوق محفوظ است.</div>' +
        '<div style="margin-top:4px">MΛZ Medical CRM · v1.0.0-mvp</div>' +
      '</footer>';
  }

  function mount() {
    var body = document.body;
    var kind = body.getAttribute('data-shell');    // "staff" | "patient" | null
    var active = body.getAttribute('data-nav') || '';
    if (!kind) return;

    var shell = document.querySelector('.app-shell');
    if (shell) {
      shell.insertAdjacentHTML('afterbegin', renderSidebar(kind, active));
      // Wire logout button — no inline onclick; delegate after insertion
      var logoutBtn = shell.querySelector('.logout-btn[data-logout-href]');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
          window.location.href = logoutBtn.getAttribute('data-logout-href');
        });
      }
    }
    var main = document.querySelector('.main');
    if (main && !main.querySelector('.brand-footer')) {
      main.insertAdjacentHTML('beforeend', renderFooter());
    }
    // Bottom nav for mobile
    if (!document.querySelector('.bottom-nav')) {
      document.body.insertAdjacentHTML('beforeend', renderBottomNav(kind, active));
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
  } else { mount(); }
}());

/* End of file — MAZ//ID · © 2026 Maziyar */
