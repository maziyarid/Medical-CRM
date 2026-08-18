/**
 * Exact-design localization layer for the compiled Persian React application
 * and native WordPress fallback views.
 *
 * It changes user-visible copy, direction, accessibility labels and phone
 * presentation only. Markup, CSS classes, animations, stable routes, option
 * values and API identifiers stay unchanged.
 */
(function () {
  'use strict';
  if (typeof window === 'undefined') return;

  var i18n = window.__DRB_I18N__ || {};
  var lang = String(i18n.lang || 'fa').toLowerCase();
  var phrases = i18n.phrases || {};
  if (lang === 'fa') return;

  var hasPersian = /[\u0600-\u06FF]/;
  var phraseKeys = Object.keys(phrases).filter(function (key) {
    return key && typeof phrases[key] === 'string' && phrases[key] !== '';
  }).sort(function (a, b) { return b.length - a.length; });

  function normalizePhrase(value) {
    var text = String(value || '');
    try { if (text.normalize) text = text.normalize('NFKC'); } catch (ignore) {}
    return text
      .replace(/[\u200c\u200d\u200e\u200f\u2066-\u2069]/g, ' ')
      .replace(/[يى]/g, 'ی')
      .replace(/ك/g, 'ک')
      .replace(/[ـًٌٍَُِّْ]/g, '')
      .replace(/[–—]/g, '-')
      .replace(/\s+/g, ' ')
      .trim();
  }

  var normalizedPhrases = Object.create(null);
  phraseKeys.forEach(function (source) {
    var normalized = normalizePhrase(source);
    if (normalized && !normalizedPhrases[normalized]) normalizedPhrases[normalized] = phrases[source];
  });

  function preserveOuterWhitespace(original, translated) {
    var leading = (original.match(/^\s*/) || [''])[0];
    var trailing = (original.match(/\s*$/) || [''])[0];
    return leading + translated + trailing;
  }

  function translateText(text) {
    if (!text || typeof text !== 'string' || !hasPersian.test(text)) return text;
    var trimmed = text.trim();
    if (!trimmed) return text;
    if (Object.prototype.hasOwnProperty.call(phrases, trimmed)) {
      return preserveOuterWhitespace(text, phrases[trimmed]);
    }
    var normalized = normalizePhrase(trimmed);
    if (normalized && Object.prototype.hasOwnProperty.call(normalizedPhrases, normalized)) {
      return preserveOuterWhitespace(text, normalizedPhrases[normalized]);
    }
    var out = text;
    for (var i = 0; i < phraseKeys.length; i++) {
      var source = phraseKeys[i];
      if (out.indexOf(source) !== -1) out = out.split(source).join(phrases[source]);
    }
    return out;
  }

  function asciiDigits(value) {
    var out = String(value || '')
      .replace(/[۰-۹]/g, function (d) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)); })
      .replace(/[٠-٩]/g, function (d) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)); });
    // LTR locales should not retain Persian/Arabic punctuation after otherwise
    // complete translation. Arabic keeps its native punctuation conventions.
    if (lang !== 'ar') out = out.replace(/٪/g, '%').replace(/،/g, ',').replace(/؛/g, ';').replace(/؟/g, '?');
    return out;
  }

  function iranE164(raw) {
    var digits = asciiDigits(raw).replace(/\D+/g, '');
    if (digits.indexOf('0098') === 0) digits = digits.slice(2);
    if (/^0\d{10}$/.test(digits)) digits = '98' + digits.slice(1);
    return /^98\d{10}$/.test(digits) ? '+' + digits : '';
  }

  function formatIranPhone(raw) {
    var e164 = iranE164(raw);
    if (!e164) return raw;
    var national = e164.slice(3);
    if (national.charAt(0) === '9') {
      return '+98 ' + national.slice(0, 3) + ' ' + national.slice(3, 6) + ' ' + national.slice(6);
    }
    return '+98 ' + national.slice(0, 2) + ' ' + national.slice(2, 6) + ' ' + national.slice(6);
  }

  function internationalizeIranPhones(text) {
    if (!text || typeof text !== 'string') return text;
    // Only replace candidates that normalize to an 11-digit Iranian national
    // number (or its +98/0098 form). This avoids touching ages, dates or IDs.
    return text.replace(/(?:\+|[۰-۹٠-٩0-9])[۰-۹٠-٩0-9\s().\-]{9,22}/g, function (candidate) {
      return iranE164(candidate) ? formatIranPhone(candidate) : candidate;
    });
  }

  function visibleText(text) {
    return asciiDigits(internationalizeIranPhones(translateText(text)));
  }

  function prepareStableOptionValue(node) {
    if (!node || node.tagName !== 'OPTION') return;
    // An <option> without a value attribute derives .value from its text. Freeze
    // the original Persian value before translating the visible text so API
    // payloads remain compatible with the existing dashboard contract.
    if (!node.hasAttribute('value')) node.setAttribute('value', node.value || node.textContent || '');
  }

  function translateAttributes(node) {
    ['placeholder', 'title', 'aria-label', 'alt'].forEach(function (attr) {
      if (!node.hasAttribute || !node.hasAttribute(attr)) return;
      var value = node.getAttribute(attr);
      var next = visibleText(value);
      if (next !== value) node.setAttribute(attr, next);
    });

    // Button values are UI copy. Never translate text/email/tel field values,
    // because those can be patient-entered data or stable form payloads.
    if (node.tagName === 'INPUT' && /^(submit|button|reset)$/i.test(node.type || '') && node.hasAttribute('value')) {
      var buttonValue = node.getAttribute('value');
      var translatedValue = visibleText(buttonValue);
      if (translatedValue !== buttonValue) node.setAttribute('value', translatedValue);
    }

    // Every non-Persian Iranian phone link must use an explicit +98 country code.
    if (node.tagName === 'A' && node.hasAttribute('href')) {
      var href = node.getAttribute('href') || '';
      if (/^tel:/i.test(href)) {
        var e164 = iranE164(href.replace(/^tel:/i, ''));
        if (e164 && href !== 'tel:' + e164) node.setAttribute('href', 'tel:' + e164);
        if (e164) node.classList.add('drb-phone-intl');
      }
    }
  }

  function walk(node) {
    if (!node) return;
    if (node.nodeType === 3) {
      var parent = node.parentElement;
      if (parent && parent.closest && parent.closest('script,style,noscript,code,pre,[data-drb-no-translate]')) return;
      var next = visibleText(node.nodeValue);
      if (next !== node.nodeValue) node.nodeValue = next;
      return;
    }
    if (node.nodeType !== 1) return;
    if (node.matches && node.matches('script,style,noscript,code,pre,[data-drb-no-translate]')) return;
    prepareStableOptionValue(node);
    translateAttributes(node);
    var child = node.firstChild;
    while (child) {
      var nextChild = child.nextSibling;
      walk(child);
      child = nextChild;
    }
  }

  function enforceDirection() {
    var activeDir = i18n.dir || (lang === 'ar' ? 'rtl' : 'ltr');
    var activeLang = i18n.htmlLang || lang;
    document.documentElement.setAttribute('lang', activeLang);
    document.documentElement.setAttribute('dir', activeDir);
    var reactRoot = document.querySelector('#root > .drb-react-root, #root > [lang="fa-IR"][dir="rtl"]');
    if (reactRoot) {
      reactRoot.setAttribute('dir', activeDir);
      reactRoot.setAttribute('lang', activeLang);
      reactRoot.classList.add('drb-react-root');
    }
    if (document.body) {
      document.body.classList.toggle('drb-rtl', activeDir === 'rtl');
      document.body.classList.toggle('drb-ltr', activeDir !== 'rtl');
      document.body.classList.add('drb-lang-' + lang);
    }
  }

  function run() {
    enforceDirection();
    if (document.title) document.title = visibleText(document.title);
    if (document.body) walk(document.body);
  }

  var scheduled = false;
  function schedule() {
    if (scheduled) return;
    scheduled = true;
    (window.requestAnimationFrame || window.setTimeout)(function () {
      scheduled = false;
      run();
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', schedule);
  else schedule();
  setTimeout(run, 300);
  setTimeout(run, 900);
  setTimeout(run, 2200);

  if (typeof MutationObserver !== 'undefined') {
    new MutationObserver(schedule).observe(document.documentElement, {
      childList: true,
      subtree: true,
      characterData: true,
      attributes: true,
      attributeFilter: ['placeholder', 'title', 'aria-label', 'alt', 'value', 'href']
    });
  }
})();
