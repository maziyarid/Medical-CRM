/**
 * Targeted localization bridge for the compiled Persian React application.
 *
 * The source bundle is still Persian-first, so this bridge translates visible
 * presentation strings until the React source can be rebuilt around keyed
 * translations. It deliberately observes only #root, processes only changed
 * nodes, and caches its own output to avoid React/Framer Motion translation
 * churn. Server-rendered localized chrome can opt out with
 * data-drb-no-translate.
 */
(function () {
  'use strict';
  if (typeof window === 'undefined') return;

  var i18n = window.__DRB_I18N__ || {};
  var lang = String(i18n.lang || 'fa').toLowerCase();
  var phrases = i18n.phrases || {};
  if (lang === 'fa') return;

  var translatedText = new WeakMap();
  var translatedAttrs = new WeakMap();
  var targetPhrases = Object.create(null);
  var persianSpecific = /[\u067e\u0686\u0698\u06af\u06a9\u06cc]/;
  var phraseKeys = Object.keys(phrases).filter(function (key) {
    return key && typeof phrases[key] === 'string' && phrases[key] !== '';
  }).sort(function (a, b) { return b.length - a.length; });
  var replaceablePhraseKeys = phraseKeys.filter(function (key) {
    return persianSpecific.test(key);
  });

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
    var normalizedSource = normalizePhrase(source);
    var normalizedTarget = normalizePhrase(phrases[source]);
    if (normalizedSource && !normalizedPhrases[normalizedSource]) {
      normalizedPhrases[normalizedSource] = phrases[source];
    }
    if (normalizedTarget) targetPhrases[normalizedTarget] = true;
  });

  function preserveOuterWhitespace(original, translated) {
    var leading = (original.match(/^\s*/) || [''])[0];
    var trailing = (original.match(/\s*$/) || [''])[0];
    return leading + translated + trailing;
  }

  function translateText(text) {
    if (!text || typeof text !== 'string') return text;
    var trimmed = text.trim();
    if (!trimmed) return text;

    if (Object.prototype.hasOwnProperty.call(phrases, trimmed)) {
      return preserveOuterWhitespace(text, phrases[trimmed]);
    }

    var normalized = normalizePhrase(trimmed);
    if (normalized && Object.prototype.hasOwnProperty.call(normalizedPhrases, normalized)) {
      return preserveOuterWhitespace(text, normalizedPhrases[normalized]);
    }

    // Arabic must not be treated as Persian merely because the scripts overlap.
    if (normalized && targetPhrases[normalized]) return text;

    // Embedded replacements are restricted to source phrases containing
    // Persian-specific glyphs. Shared-script phrases use exact matching above.
    if (!persianSpecific.test(text)) return text;
    var out = text;
    for (var i = 0; i < replaceablePhraseKeys.length; i++) {
      var source = replaceablePhraseKeys[i];
      if (out.indexOf(source) !== -1) out = out.split(source).join(phrases[source]);
    }
    return out;
  }

  function asciiDigits(value) {
    var out = String(value || '')
      .replace(/[۰-۹]/g, function (digit) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)); })
      .replace(/[٠-٩]/g, function (digit) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)); });
    if (lang !== 'ar') {
      out = out.replace(/٪/g, '%').replace(/،/g, ',').replace(/؛/g, ';').replace(/؟/g, '?');
    }
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
    return text.replace(/(?:\+|[۰-۹٠-٩0-9])[۰-۹٠-٩0-9\s().\-]{9,22}/g, function (candidate) {
      return iranE164(candidate) ? formatIranPhone(candidate) : candidate;
    });
  }

  function visibleText(text) {
    return asciiDigits(internationalizeIranPhones(translateText(text)));
  }

  function shouldSkipElement(element) {
    return !!(
      element &&
      element.closest &&
      element.closest('script,style,noscript,code,pre,[data-drb-no-translate]')
    );
  }

  function prepareStableOptionValue(node) {
    if (!node || node.tagName !== 'OPTION' || node.hasAttribute('value')) return;
    node.setAttribute('value', node.value || node.textContent || '');
  }

  function translateTextNode(node) {
    var current = node.nodeValue || '';
    if (!current || shouldSkipElement(node.parentElement)) return;
    if (translatedText.get(node) === current) return;
    var next = visibleText(current);
    translatedText.set(node, next);
    if (next !== current) node.nodeValue = next;
  }

  function translateAttributes(node) {
    if (!node || node.nodeType !== 1 || shouldSkipElement(node)) return;
    var cache = translatedAttrs.get(node) || Object.create(null);

    ['placeholder', 'title', 'aria-label', 'alt'].forEach(function (attr) {
      if (!node.hasAttribute(attr)) return;
      var value = node.getAttribute(attr) || '';
      if (cache[attr] === value) return;
      var next = visibleText(value);
      cache[attr] = next;
      if (next !== value) node.setAttribute(attr, next);
    });

    if (node.tagName === 'INPUT' && /^(submit|button|reset)$/i.test(node.type || '') && node.hasAttribute('value')) {
      var buttonValue = node.getAttribute('value') || '';
      if (cache.value !== buttonValue) {
        var translatedValue = visibleText(buttonValue);
        cache.value = translatedValue;
        if (translatedValue !== buttonValue) node.setAttribute('value', translatedValue);
      }
    }

    if (node.tagName === 'A' && node.hasAttribute('href')) {
      var href = node.getAttribute('href') || '';
      if (cache.href !== href && /^tel:/i.test(href)) {
        var e164 = iranE164(href.replace(/^tel:/i, ''));
        var nextHref = e164 ? 'tel:' + e164 : href;
        cache.href = nextHref;
        if (nextHref !== href) node.setAttribute('href', nextHref);
        if (e164) node.classList.add('drb-phone-intl');
      }
    }

    translatedAttrs.set(node, cache);
  }

  function walk(node) {
    if (!node) return;
    if (node.nodeType === 3) {
      translateTextNode(node);
      return;
    }
    if (node.nodeType !== 1 || shouldSkipElement(node)) return;
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

  function rootHasContent(root) {
    return !!(root && (root.firstElementChild || String(root.textContent || '').trim()));
  }

  function revealRoot() {
    var reveal = function () {
      document.documentElement.classList.remove('drb-i18n-pending');
      document.documentElement.classList.add('drb-i18n-ready');
    };
    (window.requestAnimationFrame || window.setTimeout)(reveal);
  }

  function attach(root) {
    enforceDirection();
    if (document.title) document.title = visibleText(document.title);
    walk(root);
    var initialLocalized = rootHasContent(root);
    if (initialLocalized) revealRoot();

    if (typeof MutationObserver === 'undefined') {
      revealRoot();
      return;
    }

    var observer = new MutationObserver(function (records) {
      records.forEach(function (record) {
        if (record.type === 'characterData') {
          walk(record.target);
          return;
        }
        if (record.type === 'attributes') {
          translateAttributes(record.target);
          return;
        }
        Array.prototype.forEach.call(record.addedNodes || [], walk);
      });
      enforceDirection();
      if (!initialLocalized && rootHasContent(root)) {
        initialLocalized = true;
        revealRoot();
      }
    });

    observer.observe(root, {
      childList: true,
      subtree: true,
      characterData: true,
      attributes: true,
      attributeFilter: ['placeholder', 'title', 'aria-label', 'alt', 'value', 'href']
    });
    window.__DRB_I18N_OBSERVER__ = observer;
  }

  function start() {
    var root = document.getElementById('root');
    if (root) {
      attach(root);
      return;
    }

    if (typeof MutationObserver === 'undefined' || !document.body) {
      revealRoot();
      return;
    }

    // Watch only until the application root is created, then disconnect.
    var bootstrapObserver = new MutationObserver(function () {
      var candidate = document.getElementById('root');
      if (!candidate) return;
      bootstrapObserver.disconnect();
      attach(candidate);
    });
    bootstrapObserver.observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
  } else {
    start();
  }
})();
