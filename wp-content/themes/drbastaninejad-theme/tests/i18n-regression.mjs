import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const theme = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const read = (relative) => fs.readFileSync(path.join(theme, relative), 'utf8');
const json = (relative) => JSON.parse(read(relative));
const languages = ['ar', 'de', 'en', 'es', 'fr', 'ru', 'tr'];

function scalarPaths(value, prefix = [], output = []) {
  if (Array.isArray(value)) {
    value.forEach((child, index) => scalarPaths(child, prefix.concat(String(index)), output));
  } else if (value && typeof value === 'object') {
    Object.keys(value).forEach((key) => scalarPaths(value[key], prefix.concat(key), output));
  } else {
    output.push(prefix.join('.'));
  }
  return output;
}

function phpPack(relative) {
  const source = read(relative);
  const result = {};
  const pattern = /^\s*'([^']+)'\s*=>\s*'((?:\\'|[^'])*)'/gm;
  let match;
  while ((match = pattern.exec(source))) result[match[1]] = match[2].replaceAll("\\'", "'");
  return result;
}

const baseReactKeys = Object.keys(json('languages/react/en.json'));
const baseContentPaths = scalarPaths(json('languages/content/pack_en.json'));
const basePhpKeys = Object.keys(phpPack('languages/en.php'));
for (const language of languages) {
  assert.deepEqual(Object.keys(json(`languages/react/${language}.json`)), baseReactKeys, `${language} React key parity`);
  assert.deepEqual(scalarPaths(json(`languages/content/pack_${language}.json`)), baseContentPaths, `${language} content structure parity`);
  assert.deepEqual(Object.keys(phpPack(`languages/${language}.php`)), basePhpKeys, `${language} PHP key parity`);
}

const arReact = json('languages/react/ar.json');
const arContent = json('languages/content/pack_ar.json');
const arPhp = phpPack('languages/ar.php');
assert.equal(Object.values(arReact).filter((value) => typeof value !== 'string' || value === '').length, 0, 'Arabic React values must be populated');

const sharedScriptAllowlist = new Set(['بعد', 'قبل', 'ممنوع']);
const sourceEqualsTarget = Object.entries(arReact)
  .filter(([source, target]) => source === target && !sharedScriptAllowlist.has(source));
assert.deepEqual(sourceEqualsTarget, [], 'unexpected Persian source copied into Arabic target');

function suspiciousPersianValues(value, currentPath = [], output = []) {
  if (Array.isArray(value)) {
    value.forEach((child, index) => suspiciousPersianValues(child, currentPath.concat(String(index)), output));
  } else if (value && typeof value === 'object') {
    for (const [key, child] of Object.entries(value)) suspiciousPersianValues(child, currentPath.concat(key), output);
  } else if (typeof value === 'string' && !currentPath.includes('source_slug_fa') && /[پچژگکی]/u.test(value)) {
    output.push([currentPath.join('.'), value]);
  }
  return output;
}
assert.deepEqual(suspiciousPersianValues(arReact), [], 'Persian-specific glyph in Arabic React value');
assert.deepEqual(suspiciousPersianValues(arContent), [], 'Persian-specific glyph in Arabic content value');

const phpGlossary = {
  site_name: 'د. شاهين باستاني نجاد',
  nav_home: 'الرئيسية',
  nav_about: 'عن الطبيب',
  nav_contact: 'تواصل معنا',
  cta_book: 'احجز موعدًا',
  cta_contact: 'اتصل بالعيادة',
  form_success: 'تم تسجيل طلبك بنجاح.',
  form_invalid_phone: 'أدخل رقم هاتف صالحًا.',
  form_phone_intl: 'رقم الهاتف',
};
for (const [key, expected] of Object.entries(phpGlossary)) {
  assert.equal(arPhp[key], expected, `Arabic PHP glossary: ${key}`);
  assert.equal(arContent.chrome[key], expected, `Arabic content glossary: ${key}`);
}

const phraseGlossary = {
  'درباره': 'عن الطبيب',
  'تماس': 'تواصل معنا',
  'تماس با ما': 'تواصل معنا',
  'رزرو نوبت': 'احجز موعدًا',
  'رزرو نوبت آنلاین': 'احجز موعدًا عبر الإنترنت',
  'ارتباط سریع': 'تواصل سريع',
  'محرمانه و امن': 'سري وآمن',
  '۱ پیام جدید': 'رسالة جديدة',
  'تماس با کلینیک': 'اتصل بالعيادة',
  'فرمت شماره اشتباه است (مثال: 09121234567)': 'أدخل رقم هاتف صالحًا.',
  '۰۹۱۲ *** ****': '912 123 4567',
};
for (const [source, expected] of Object.entries(phraseGlossary)) {
  assert.equal(arReact[source], expected, `Arabic React glossary: ${source}`);
  assert.equal(arContent.forms[source], expected, `Arabic content phrase: ${source}`);
}

const serializedArabic = JSON.stringify({ arReact, arContent });
assert(!serializedArabic.includes('شاهين بستاني نجاد'), 'obsolete doctor-name spelling');
assert(!serializedArabic.includes('رسالة جديدة واحدة'), 'unnatural Arabic singular');
assert(!serializedArabic.includes('خاص وآمن'), 'weak confidentiality wording');

const runtime = read('assets/js/drb-i18n-runtime.js');
assert(runtime.includes('new WeakMap()'), 'runtime output cache');
assert(runtime.includes('record.addedNodes'), 'targeted added-node processing');
assert(runtime.includes('observer.observe(root'), 'observer must be scoped to #root');
assert(!runtime.includes('observe(document.documentElement'), 'no document-wide observer');
assert(!runtime.includes('setTimeout(run'), 'no delayed whole-page passes');

const switcherPhp = read('inc/lang-chrome.php');
assert(switcherPhp.includes('data-drb-no-translate'), 'server-localized switcher boundary');
assert(switcherPhp.includes('aria-current="page"'), 'active language semantics');
assert(switcherPhp.includes('aria-hidden="true" inert'), 'closed language links must not be tabbable');
assert(!switcherPhp.includes('role="listbox"'), 'links must not pretend to be a listbox');
assert(!switcherPhp.includes('role="option"'), 'links must not pretend to be options');
const legacySwitcher = read('assets/inc/lang-chrome.php');
assert(legacySwitcher.includes("require_once $drb_canonical_lang_chrome"), 'legacy switcher must delegate to canonical implementation');
assert(!legacySwitcher.includes('function drb_language_dropdown_html'), 'no stale duplicate switcher implementation');

const booking = read('assets/js/booking-international.js');
assert(booking.includes("phone.placeholder = '912 123 4567'"), 'national-number placeholder');
assert(!booking.includes("phone.placeholder = '+989"), 'country code must not be duplicated');

const i18nPhp = read('inc/i18n.php');
const helperBody = i18nPhp.slice(i18nPhp.indexOf('function __t'), i18nPhp.indexOf('function theme_t'));
assert(helperBody.includes("drb_load_pack('en')"), 'English fallback');
assert(!helperBody.includes("drb_load_pack('fa')"), 'non-Persian locales must not fall back to Persian');

const localizedContent = read('inc/localized-content.php');
assert(localizedContent.includes('foreach ( drb_localized_page_map()'), 'route-map-driven link rewriting');
assert(localizedContent.includes("add_filter( 'the_content', 'drb_localized_content_html'"), 'content filter registration');

const styleVersion = read('style.css').match(/^Version:\s*(\S+)/m)?.[1];
const functionVersion = read('functions.php').match(/DRB_THEME_VERSION',\s*'([^']+)'/)?.[1];
const manifestVersion = json('languages/live-source-manifest.json').release;
assert.equal(styleVersion, functionVersion, 'style/functions release version parity');
assert.equal(manifestVersion, functionVersion, 'localization manifest release version parity');

process.stdout.write(JSON.stringify({
  status: 'passed',
  release: functionVersion,
  reactKeysPerLocale: baseReactKeys.length,
  contentScalarPathsPerLocale: baseContentPaths.length,
  phpKeysPerLocale: basePhpKeys.length,
  arabicGlossaryAssertions: Object.keys(phpGlossary).length + Object.keys(phraseGlossary).length,
}) + '\n');
