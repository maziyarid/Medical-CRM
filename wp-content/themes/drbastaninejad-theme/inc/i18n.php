<?php
/**
 * DrB no-plugin multilingual core
 * Host/subdomain detection + PHP language packs + post meta association.
 * Languages: fa (default RTL), en, ar (RTL), tr, ru, fr, de, es (LTR)
 *
 * URL strategy:
 *   fa  → https://drbastaninejad.com/
 *   en  → https://en.drbastaninejad.com/
 *   ar  → https://ar.drbastaninejad.com/
 *   tr  → https://tr.drbastaninejad.com/
 *   ru  → https://ru.drbastaninejad.com/
 *   fr  → https://fr.drbastaninejad.com/
 *   de  → https://de.drbastaninejad.com/
 *   es  → https://es.drbastaninejad.com/
 *
 * Association: post meta drb_lang_code + drb_lang_group_id
 * Fallback for local/dev: ?lang=xx
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DRB_LANGS', ['fa', 'en', 'ar', 'tr', 'ru', 'fr', 'de', 'es']);
define('DRB_RTL_LANGS', ['fa', 'ar']);
define('DRB_DEFAULT_LANG', 'fa');

/**
 * Detect language from HTTP_HOST subdomain. Fallback to ?lang= then default.
 */
function drb_detect_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }

    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host); // strip port

    // Subdomain map
    $map = [
        'en.drbastaninejad.com' => 'en',
        'ar.drbastaninejad.com' => 'ar',
        'tr.drbastaninejad.com' => 'tr',
        'ru.drbastaninejad.com' => 'ru',
        'fr.drbastaninejad.com' => 'fr',
        'de.drbastaninejad.com' => 'de',
        'es.drbastaninejad.com' => 'es',
        'drbastaninejad.com'    => 'fa',
        'www.drbastaninejad.com'=> 'fa',
    ];
    if (isset($map[$host])) {
        $lang = $map[$host];
        return $lang;
    }

    // Temporary query fallback for DNS-not-yet-live environments
    $q = isset($_GET['lang']) ? strtolower(sanitize_text_field(wp_unslash($_GET['lang']))) : '';
    if (in_array($q, DRB_LANGS, true)) {
        $lang = $q;
        return $lang;
    }

    $lang = DRB_DEFAULT_LANG;
    return $lang;
}

function drb_is_rtl(?string $lang = null): bool
{
    $lang = $lang ?? drb_detect_lang();
    return in_array($lang, DRB_RTL_LANGS, true);
}

function drb_html_lang(?string $lang = null): string
{
    $lang = $lang ?? drb_detect_lang();
    $map = [
        'fa' => 'fa-IR',
        'en' => 'en',
        'ar' => 'ar',
        'tr' => 'tr',
        'ru' => 'ru',
        'fr' => 'fr',
        'de' => 'de',
        'es' => 'es',
    ];
    return $map[$lang] ?? $lang;
}

/**
 * Load language pack once.
 */
function drb_load_pack(?string $lang = null): array
{
    static $packs = [];
    $lang = $lang ?? drb_detect_lang();
    if (isset($packs[$lang])) {
        return $packs[$lang];
    }
    $file = get_template_directory() . '/languages/' . $lang . '.php';
    $pack = [];
    if (is_readable($file)) {
        $loaded = include $file;
        if (is_array($loaded)) {
            $pack = $loaded;
        }
    }
    $packs[$lang] = $pack;
    return $pack;
}

/**
 * Translate helper. Usage: __t('nav_home') or theme_t('nav_home')
 */
function __t(string $key, ?string $lang = null): string
{
    $lang = $lang ?? drb_detect_lang();
    $pack = drb_load_pack($lang);
    if (isset($pack[$key]) && $pack[$key] !== '') {
        return (string)$pack[$key];
    }

    // Never leak Persian into another locale. English is the neutral fallback;
    // a missing English key is returned verbatim so development can spot it.
    if ('en' !== $lang) {
        $english = drb_load_pack('en');
        if (isset($english[$key]) && $english[$key] !== '') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('[DRB i18n] Missing "%s" in locale "%s"; using English.', $key, $lang));
            }
            return (string)$english[$key];
        }
    }

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf('[DRB i18n] Missing translation key "%s" for locale "%s".', $key, $lang));
    }
    return $key;
}

function theme_t(string $key, ?string $lang = null): string
{
    return __t($key, $lang);
}


/**
 * Load the exact Persian -> localized React/runtime phrase map.
 * The current compiled React application remains the design source of truth;
 * these packs replace only visible copy and never mutate slugs/form values.
 */
function drb_load_react_phrase_pack(?string $lang = null): array
{
    static $cache = [];
    $lang = $lang ?? drb_detect_lang();
    if ($lang === 'fa') {
        return [];
    }
    if (isset($cache[$lang])) {
        return $cache[$lang];
    }
    $file = get_template_directory() . '/languages/react/' . sanitize_key($lang) . '.json';
    if (!is_readable($file)) {
        return $cache[$lang] = [];
    }
    $decoded = json_decode((string) file_get_contents($file), true);
    return $cache[$lang] = is_array($decoded) ? $decoded : [];
}

/** Normalize live Persian copy so harmless spacing/Unicode edits still match a phrase key. */
function drb_normalize_phrase_key(string $value): string
{
    $value = strtr($value, array(
        "\xE2\x80\x8C" => ' ', // ZWNJ
        "\xE2\x80\x8D" => ' ', // ZWJ
        'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', '–' => '-', '—' => '-',
    ));
    $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $value);
    $value = preg_replace('/\s+/u', ' ', $value);
    return trim((string) $value);
}

/** Translate a known source phrase for API/native-template responses. */
function drb_translate_phrase(string $source, ?string $lang = null): string
{
    static $normalized = array();
    $lang = $lang ?? drb_detect_lang();
    if ($lang === 'fa' || '' === $source) {
        return $source;
    }
    $map = drb_load_react_phrase_pack($lang);
    if (isset($map[$source]) && is_string($map[$source]) && $map[$source] !== '') {
        return $map[$source];
    }

    if (!isset($normalized[$lang])) {
        $normalized[$lang] = array();
        foreach ($map as $from => $to) {
            if (!is_string($from) || !is_string($to) || '' === $to) continue;
            $key = drb_normalize_phrase_key($from);
            if ('' !== $key && !isset($normalized[$lang][$key])) $normalized[$lang][$key] = $to;
        }
    }
    $key = drb_normalize_phrase_key($source);
    return isset($normalized[$lang][$key]) ? (string) $normalized[$lang][$key] : $source;
}

/**
 * Translate current live presentation copy without modifying WordPress content.
 * Exact/normalized matches are preferred, then known long phrases are replaced
 * inside a larger text node. This is intentionally request-time only.
 */
function drb_translate_live_text(string $source, ?string $lang = null): string
{
    static $ordered = array();
    static $targets = array();
    $lang = $lang ?? drb_detect_lang();
    if ('fa' === $lang || '' === $source) return $source;

    $exact = drb_translate_phrase($source, $lang);
    if ($exact !== $source) return $exact;

    if (!isset($ordered[$lang])) {
        $map = drb_load_react_phrase_pack($lang);
        $ordered[$lang] = array();
        $targets[$lang] = array();
        foreach ($map as $from => $to) {
            if (!is_string($from) || !is_string($to) || '' === $from || '' === $to) continue;
            $targets[$lang][drb_normalize_phrase_key($to)] = true;
            // Partial replacement is safe only when the source contains a
            // Persian-specific glyph. Shared Arabic/Persian words are handled
            // by exact matching so Arabic output cannot be reclassified.
            if (preg_match('/[\x{067E}\x{0686}\x{0698}\x{06AF}\x{06A9}\x{06CC}]/u', $from)) {
                $ordered[$lang][$from] = $to;
            }
        }
        uksort($ordered[$lang], static function ($a, $b) { return strlen((string) $b) <=> strlen((string) $a); });
    }
    if (isset($targets[$lang][drb_normalize_phrase_key($source)])) return $source;
    if (!preg_match('/[\x{067E}\x{0686}\x{0698}\x{06AF}\x{06A9}\x{06CC}]/u', $source)) return $source;
    $out = $source;
    foreach ($ordered[$lang] as $from => $to) {
        if (!is_string($from) || !is_string($to) || '' === $from || '' === $to) continue;
        if (false !== strpos($out, $from)) $out = str_replace($from, $to, $out);
    }
    return $out;
}

/** Translate text nodes inside trusted WordPress HTML while preserving its markup. */
function drb_translate_live_html(string $html, ?string $lang = null): string
{
    $lang = $lang ?? drb_detect_lang();
    if ('fa' === $lang || '' === $html || !preg_match('/[\x{0600}-\x{06FF}]/u', $html)) return $html;
    return (string) preg_replace_callback('/>([^<>]+)</u', static function ($match) use ($lang) {
        return '>' . drb_translate_live_text((string) $match[1], $lang) . '<';
    }, $html);
}

/** Localize a presentation-data tree while never touching IDs, routes, URLs or payload values. */
function drb_localize_presentation_tree($value, ?string $lang = null, string $key = '')
{
    $lang = $lang ?? drb_detect_lang();
    if ('fa' === $lang) return $value;
    $stable = array('id','slug','url','href','src','thumb','before','after','thumbnail','primaryCtaUrl','secondaryCtaUrl','themeUri','contact','appointment','nonce','patientLogin','whatsapp','instagram','telegram','youtube','aparat','language');
    if (in_array($key, $stable, true)) return $value;
    if (is_array($value)) {
        foreach ($value as $child_key => $child) $value[$child_key] = drb_localize_presentation_tree($child, $lang, is_string($child_key) ? $child_key : $key);
        return $value;
    }
    if (!is_string($value) || '' === $value) return $value;
    if (false !== strpos($value, '<') && false !== strpos($value, '>')) return drb_translate_live_html($value, $lang);
    return drb_translate_live_text($value, $lang);
}


/** Canonical public host for a supported language. */
function drb_language_host(?string $lang = null): string
{
    $lang = $lang ?? drb_detect_lang();
    $hosts = [
        'fa' => 'https://drbastaninejad.com',
        'en' => 'https://en.drbastaninejad.com',
        'ar' => 'https://ar.drbastaninejad.com',
        'tr' => 'https://tr.drbastaninejad.com',
        'ru' => 'https://ru.drbastaninejad.com',
        'fr' => 'https://fr.drbastaninejad.com',
        'de' => 'https://de.drbastaninejad.com',
        'es' => 'https://es.drbastaninejad.com',
    ];
    return $hosts[$lang] ?? $hosts['fa'];
}

/** Build a language-host URL for a stable React route. */
function drb_route_url(string $path = '/', ?string $lang = null): string
{
    $path = '/' . ltrim($path, '/');
    return rtrim(drb_language_host($lang), '/') . $path;
}

/** Convert Persian/Arabic numerals to ASCII without changing other characters. */
function drb_ascii_digits(string $value): string
{
    return strtr($value, [
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
    ]);
}

/** Return an Iranian phone in E.164 digits (+98...), or an empty string. */
function drb_iran_phone_e164(string $raw): string
{
    $digits = preg_replace('/\D+/', '', drb_ascii_digits($raw));
    if (strpos($digits, '0098') === 0) $digits = substr($digits, 2);
    if (preg_match('/^0\d{10}$/', $digits)) $digits = '98' . substr($digits, 1);
    if (!preg_match('/^98\d{10}$/', $digits)) return '';
    return '+' . $digits;
}

/** International display format required on every non-Persian page. */
function drb_format_iran_phone(string $raw, ?string $lang = null, bool $href = false): string
{
    $lang = $lang ?? drb_detect_lang();
    if ($lang === 'fa') return $raw;
    $e164 = drb_iran_phone_e164($raw);
    if (!$e164) return $raw;
    if ($href) return 'tel:' . $e164;
    $national = substr($e164, 3); // remove +98
    if (strpos($national, '9') === 0) {
        return '+98 ' . substr($national, 0, 3) . ' ' . substr($national, 3, 3) . ' ' . substr($national, 6, 4);
    }
    return '+98 ' . substr($national, 0, 2) . ' ' . substr($national, 2, 4) . ' ' . substr($national, 6, 4);
}

/**
 * Build absolute URL for the same logical page in another language.
 * Uses post meta drb_lang_group_id + drb_lang_code when available.
 */
function drb_lang_url(string $target_lang, $post_id = null): string
{
    $base = drb_language_host($target_lang);

    if (!$post_id) {
        $post_id = get_queried_object_id();
    }

    // The content packs define the canonical translated route for each core page.
    // Prefer that route over copying the Persian/current path to another host.
    if (function_exists('drb_localized_url_for_post')) {
        $localized = drb_localized_url_for_post((int) $post_id, $target_lang);
        if ($localized) {
            return $localized;
        }
    }

    if (!$post_id) {
        return $base . '/';
    }

    $group = get_post_meta($post_id, 'drb_lang_group_id', true);
    if ($group) {
        $q = new WP_Query([
            'post_type'      => get_post_type($post_id) ?: 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'   => 'drb_lang_group_id',
                    'value' => $group,
                ],
                [
                    'key'   => 'drb_lang_code',
                    'value' => $target_lang,
                ],
            ],
            'no_found_rows'  => true,
        ]);
        if (!empty($q->posts)) {
            $sibling = (int)$q->posts[0];
            $path = wp_make_link_relative(get_permalink($sibling));
            // Strip host if any
            $path = preg_replace('#^https?://[^/]+#', '', $path);
            return rtrim($base, '/') . $path;
        }
    }

    // Fallback: same path on target host (works when slugs are language-neutral
    // or when content has not yet been associated).
    $path = wp_make_link_relative(get_permalink($post_id));
    $path = preg_replace('#^https?://[^/]+#', '', $path);
    return rtrim($base, '/') . ($path ?: '/');
}

/**
 * Language switcher markup (desktop + mobile friendly).
 */
function drb_language_switcher(string $context = 'header'): string
{
    $current = drb_detect_lang();
    $labels = [
        'fa' => 'فارسی',
        'en' => 'English',
        'ar' => 'العربية',
        'tr' => 'Türkçe',
        'ru' => 'Русский',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'es' => 'Español',
    ];
    $html = '<nav class="drb-lang-switcher drb-lang-switcher--' . esc_attr($context) . '" data-drb-no-translate aria-label="' . esc_attr(__t('lang_switcher_label')) . '">';
    $html .= '<ul class="drb-lang-list">';
    foreach (DRB_LANGS as $code) {
        $url = esc_url(drb_lang_url($code));
        $active = $code === $current ? ' is-active' : '';
        $html .= '<li class="drb-lang-item' . $active . '">';
        $html .= '<a href="' . $url . '" hreflang="' . esc_attr($code) . '" lang="' . esc_attr($code) . '"' . ( $code === $current ? ' aria-current="page"' : '' ) . '>';
        $html .= esc_html($labels[$code] ?? strtoupper($code));
        $html .= '</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Output <link rel="alternate" hreflang="..."> for all languages + x-default.
 */
function drb_output_hreflang(): void
{
    $post_id = get_queried_object_id();
    foreach (DRB_LANGS as $code) {
        $url = drb_lang_url($code, $post_id ?: null);
        echo '<link rel="alternate" hreflang="' . esc_attr($code) . '" href="' . esc_url($url) . '" />' . "\n";
    }
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url(drb_lang_url('fa', $post_id ?: null)) . '" />' . "\n";
}

/**
 * Set document language + direction early.
 */
function drb_bootstrap_html_attrs(): void
{
    // Filters for themes that use language_attributes()
    add_filter('language_attributes', function ($output) {
        $lang = drb_html_lang();
        $dir  = drb_is_rtl() ? 'rtl' : 'ltr';
        return 'lang="' . esc_attr($lang) . '" dir="' . esc_attr($dir) . '"';
    });
}

add_action('after_setup_theme', 'drb_bootstrap_html_attrs', 1);
add_action('wp_head', 'drb_output_hreflang', 2);

/**
 * Prevent WordPress canonical redirection from bouncing language subdomains
 * (en./ar./tr./ru./fr./de./es.drbastaninejad.com) back to the main domain.
 * Without this, visiting en.drbastaninejad.com/about/ is 301-redirected to
 * drbastaninejad.com/about/ and drb_detect_lang() then resolves to 'fa' —
 * exactly the "clicking a language returns Farsi" symptom.
 *
 * The subdomain must still be pointed at the WP document root in cPanel
 * (see R6 docs); this filter only stops WP itself from undoing it.
 */
add_filter('redirect_canonical', function ($redirect_url, $requested_url = '') {
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host);
    $lang_hosts = [
        'en.drbastaninejad.com', 'ar.drbastaninejad.com', 'tr.drbastaninejad.com',
        'ru.drbastaninejad.com', 'fr.drbastaninejad.com', 'de.drbastaninejad.com',
        'es.drbastaninejad.com',
    ];
    if (in_array($host, $lang_hosts, true)) {
        return false;
    }
    return $redirect_url;
}, 10, 2);

/**
 * Same-origin theme assets on language subdomains.
 *
 * WordPress enqueues theme assets (React bundle, CSS, JS) with absolute URLs
 * based on siteurl (https://drbastaninejad.com/wp-content/...). When the same
 * WP install is served from a language subdomain (en./ar./...drbastaninejad.com),
 * those absolute URLs are cross-origin and blocked by CORS — so the React SPA
 * never mounts and the page renders without its nav/app (white screen).
 *
 * This filter rewrites ONLY asset URLs whose host matches the main site host to
 * be root-relative (/wp-content/...), so they load same-origin from the
 * language subdomain. External scripts (gtag, fonts APIs, etc.) are untouched
 * because their hosts differ.
 */
function drb_asset_url_same_origin($url)
{
    if (!is_string($url) || $url === '') {
        return $url;
    }
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host);
    $lang_hosts = [
        'en.drbastaninejad.com', 'ar.drbastaninejad.com', 'tr.drbastaninejad.com',
        'ru.drbastaninejad.com', 'fr.drbastaninejad.com', 'de.drbastaninejad.com',
        'es.drbastaninejad.com',
    ];
    if (!in_array($host, $lang_hosts, true)) {
        return $url;
    }
    $home_host = (string) parse_url((string) home_url(), PHP_URL_HOST);
    if ($home_host === '') {
        return $url;
    }
    // Rewrite https://drbastaninejad.com/wp-content/...  ->  /wp-content/...
    $pattern = '#^https?://' . preg_quote($home_host, '#') . '(?::\d+)?(/|$)#i';
    if (preg_match($pattern, $url)) {
        $rel = preg_replace($pattern, '$1', $url);
        return '/' . ltrim($rel, '/');
    }
    return $url;
}
add_filter('script_loader_src', 'drb_asset_url_same_origin', 10, 1);
add_filter('style_loader_src', 'drb_asset_url_same_origin', 10, 1);

/**
 * Admin helper: when saving a page, allow setting lang code + group.
 * (Simple meta boxes; no plugin.)
 */
function drb_register_lang_meta(): void
{
    register_post_meta('page', 'drb_lang_code', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function () {
            return current_user_can('edit_pages');
        },
    ]);
    register_post_meta('page', 'drb_lang_group_id', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function () {
            return current_user_can('edit_pages');
        },
    ]);
}
add_action('init', 'drb_register_lang_meta');

/**
 * Convenience: filter body class for LTR/RTL chrome.
 */
add_filter('body_class', function ($classes) {
    $classes[] = drb_is_rtl() ? 'drb-rtl' : 'drb-ltr';
    $classes[] = 'drb-lang-' . drb_detect_lang();
    return $classes;
});

/**
 * Prefer language-pack SEO title/description on non-Persian hosts
 * so Rank Math / core do not keep serving the FA front-page meta.
 */
add_filter('pre_get_document_title', function ($title) {
    if (!function_exists('drb_detect_lang') || drb_detect_lang() === 'fa') {
        return $title;
    }
    if (is_front_page() || is_home()) {
        $seo = __t('seo_home_title');
        if ($seo && $seo !== 'seo_home_title') {
            return $seo;
        }
    }
    return $title;
}, 20);

// Meta descriptions are supplied by Rank Math filters or inc/seo.php; avoid duplicate tags here.
