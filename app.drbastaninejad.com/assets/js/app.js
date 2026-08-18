/*
 * ============================================================================
 *  MΛZ Medical CRM — Shared client-side utilities
 * ----------------------------------------------------------------------------
 *  Author  : MAZ//ID (Maziyar) · maziyarid@gmail.com
 *  License : Proprietary — © 2026 Maziyar / Dr. Shahin Bastaninejad
 *  Depends : (none)
 * ============================================================================
 *  Frontend-only presentation and validation helpers. Production API access lives
 *  in shared/api.js and assets/js/api.js and targets the dashboard backend.
 * ============================================================================
 */
(function (global) {
  'use strict';

  /* ---------- Persian-digit normalization ---------- */
  var FA = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
  var AR = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
  function toEnDigits(s) {
    if (s == null) return s;
    s = String(s);
    for (var i = 0; i < 10; i++) {
      s = s.replace(new RegExp(FA[i], 'g'), i).replace(new RegExp(AR[i], 'g'), i);
    }
    return s;
  }
  function toFaDigits(s) {
    if (s == null) return s;
    return String(s).replace(/[0-9]/g, function (d) { return FA[+d]; });
  }

  /* ---------- Iranian validators ---------- */
  function isValidMobile(m) {
    m = toEnDigits(m || '').replace(/\D/g, '');
    return /^09\d{9}$/.test(m);
  }
  function isValidNationalId(id) {
    id = toEnDigits(id || '').replace(/\D/g, '');
    if (!/^\d{10}$/.test(id)) return false;
    if (/^(\d)\1{9}$/.test(id)) return false;
    var check = +id[9];
    var sum = 0;
    for (var i = 0; i < 9; i++) sum += (+id[i]) * (10 - i);
    var r = sum % 11;
    return (r < 2 && check === r) || (r >= 2 && check === 11 - r);
  }

  /* ---------- Jalali date (approximate, presentation only) ---------- */
  var faMonths = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور',
                  'مهر','آبان','آذر','دی','بهمن','اسفند'];
  function gregorianToJalali(gy, gm, gd) {
    var g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
    var jy = gy <= 1600 ? 0 : 979;
    gy -= gy <= 1600 ? 621 : 1600;
    var gy2 = (gm > 2) ? (gy + 1) : gy;
    var days = 365*gy + Math.floor((gy2+3)/4) - Math.floor((gy2+99)/100) + Math.floor((gy2+399)/400) - 80 + gd + g_d_m[gm-1];
    jy += 33 * Math.floor(days / 12053);
    days %= 12053;
    jy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) { jy += Math.floor((days-1) / 365); days = (days-1) % 365; }
    var jm = (days < 186) ? 1 + Math.floor(days/31) : 7 + Math.floor((days-186)/30);
    var jd = 1 + ((days < 186) ? (days % 31) : ((days-186) % 30));
    return [jy, jm, jd];
  }
  function formatJalali(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    var j = gregorianToJalali(d.getFullYear(), d.getMonth()+1, d.getDate());
    return toFaDigits(j[0] + '/' + String(j[1]).padStart(2,'0') + '/' + String(j[2]).padStart(2,'0'));
  }
  function jalaliMonthName(m) { return faMonths[m-1] || ''; }

  /* ---------- Toast ---------- */
  function toast(msg, type) {
    var container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    var el = document.createElement('div');
    el.className = 'toast ' + (type || '');
    el.textContent = msg;
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    container.appendChild(el);
    setTimeout(function () { el.remove(); }, 3800);
  }

  /* ---------- API namespace -----------------------------------------------
   * Kept empty here so assets/js/api.js can attach the real dashboard client.
   * No fabricated patient, appointment or KPI records are shipped.
   */
  var api = {};

  /* ---------- Signature Pad (Pointer Events, touch-safe) ---------- */
  function initSignaturePad(canvas) {
    if (!canvas) return null;
    var ctx = canvas.getContext('2d');
    var drawing = false, empty = true;
    function resize() {
      var dpr = window.devicePixelRatio || 1;
      var rect = canvas.getBoundingClientRect();
      canvas.width = rect.width * dpr;
      canvas.height = rect.height * dpr;
      ctx.scale(dpr, dpr);
      ctx.lineWidth = 2.2; ctx.lineCap = 'round'; ctx.strokeStyle = '#25272C';
    }
    resize();
    window.addEventListener('resize', resize);
    function pos(e) {
      var rect = canvas.getBoundingClientRect();
      return [e.clientX - rect.left, e.clientY - rect.top];
    }
    canvas.addEventListener('pointerdown', function (e) {
      e.preventDefault(); drawing = true; empty = false;
      canvas.setPointerCapture(e.pointerId);
      var p = pos(e); ctx.beginPath(); ctx.moveTo(p[0], p[1]);
    });
    canvas.addEventListener('pointermove', function (e) {
      if (!drawing) return;
      var p = pos(e); ctx.lineTo(p[0], p[1]); ctx.stroke();
    });
    ['pointerup','pointerleave','pointercancel'].forEach(function (evt) {
      canvas.addEventListener(evt, function () { drawing = false; });
    });
    return {
      clear: function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height); empty = true;
      },
      isEmpty: function () { return empty; },
      toDataURL: function () { return canvas.toDataURL('image/png'); }
    };
  }

  /* ---------- HTML escape (shared by all patient-portal pages from P1-B onward) --
   *
   * Rule (SPACE_COORDINATION_PROTOCOL §6): Every server-returned string written
   * into the DOM via .innerHTML or a template literal must pass through escHtml().
   * Define once here; reference as MAZCRM.escHtml(str) on every page.
   * Do NOT redefine per-page.
   */
  function escHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /* ---------- Export ---------- */
  global.MAZCRM = {
    toEnDigits: toEnDigits,
    toFaDigits: toFaDigits,
    isValidMobile: isValidMobile,
    isValidNationalId: isValidNationalId,
    formatJalali: formatJalali,
    jalaliMonthName: jalaliMonthName,
    toast: toast,
    api: api,
    initSignaturePad: initSignaturePad,
    escHtml: escHtml,
    version: '1.1.0-production',
    author: 'MAZ//ID'
  };

}(window));

/*
 * End of file — MAZ//ID · © 2026 Maziyar
 */
