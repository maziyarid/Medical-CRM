<?php

declare(strict_types=1);

namespace DRBLocalization;

const DRB_LANGS = ['fa', 'en', 'ar', 'tr', 'de', 'fr', 'es', 'ru'];
const DRB_RTL_LANGS = ['fa', 'ar'];

function drb_current_lang(): string
{
    static $lang;
    if ($lang !== null) return $lang;
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host);
    $candidate = explode('.', $host)[0] ?? 'fa';
    if (in_array($candidate, DRB_LANGS, true) && $candidate !== 'fa') return $lang = $candidate;
    if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['lang'])) {
        $query = sanitize_key((string) $_GET['lang']);
        if (in_array($query, DRB_LANGS, true)) return $lang = $query;
    }
    return $lang = 'fa';
}

function drb_pack(?string $lang = null): array
{
    static $cache = [];
    $lang = $lang ?: drb_current_lang();
    if (isset($cache[$lang])) return $cache[$lang];
    $file = __DIR__ . '/packs/pack_' . $lang . '.json';
    if (!is_readable($file)) return $cache[$lang] = [];
    $decoded = json_decode((string) file_get_contents($file), true);
    return $cache[$lang] = is_array($decoded) ? $decoded : [];
}

function drb_t(string $key, ?string $fallback = null): string
{
    $pack = drb_pack();
    return (string) ($pack['chrome'][$key] ?? $fallback ?? $key);
}

function drb_lang_host(string $lang): string
{
    $base = defined('DRB_BASE_DOMAIN') ? (string) DRB_BASE_DOMAIN : 'drbastaninejad.com';
    return $lang === 'fa' ? $base : $lang . '.' . $base;
}

function drb_lang_url(string $lang, ?int $post_id = null): string
{
    $path = '/';
    $post_id = $post_id ?: (is_singular() ? get_queried_object_id() : 0);
    if ($post_id) {
        $group = get_post_meta($post_id, 'drb_lang_group_id', true);
        if ($group) {
            $siblings = get_posts(['post_type' => 'any', 'post_status' => 'publish', 'numberposts' => 1,
                'meta_query' => [['key' => 'drb_lang_group_id', 'value' => $group], ['key' => 'drb_lang_code', 'value' => $lang]]]);
            if ($siblings) $path = (string) wp_parse_url(get_permalink($siblings[0]), PHP_URL_PATH);
        }
    }
    return 'https://' . drb_lang_host($lang) . $path;
}

function drb_language_switcher($args = []): string
{
    $labels = ['fa'=>'فارسی','en'=>'English','ar'=>'العربية','tr'=>'Türkçe','de'=>'Deutsch','fr'=>'Français','es'=>'Español','ru'=>'Русский'];
    $current = drb_current_lang();
    $html = '<nav class="drb-language-switcher" aria-label="' . esc_attr(drb_t('lang_switcher_label', 'Language')) . '"><ul>';
    foreach ($labels as $code => $label) {
        $html .= '<li><a hreflang="' . esc_attr($code) . '" lang="' . esc_attr($code) . '" href="' . esc_url(drb_lang_url($code)) . '"' . ($code === $current ? ' aria-current="page"' : '') . '>' . esc_html($label) . '</a></li>';
    }
    return $html . '</ul></nav>';
}

add_shortcode('drb_language_switcher', __NAMESPACE__ . '\\drb_language_switcher');
add_filter('locale', static fn() => ['fa'=>'fa_IR','ar'=>'ar','en'=>'en_US','tr'=>'tr_TR','de'=>'de_DE','fr'=>'fr_FR','es'=>'es_ES','ru'=>'ru_RU'][drb_current_lang()]);
add_filter('redirect_canonical', static function ($redirect, $requested) {
    return drb_current_lang() !== 'fa' ? false : $redirect;
}, 10, 2);
add_filter('language_attributes', static function (string $output): string {
    $lang = drb_current_lang();
    return 'lang="' . esc_attr($lang === 'fa' ? 'fa-IR' : $lang) . '" dir="' . (in_array($lang, DRB_RTL_LANGS, true) ? 'rtl' : 'ltr') . '"';
});
add_filter('body_class', static fn(array $classes): array => array_merge($classes, ['drb-lang-' . drb_current_lang(), in_array(drb_current_lang(), DRB_RTL_LANGS, true) ? 'drb-rtl' : 'drb-ltr']));
add_filter('pre_get_document_title', static fn(string $title): string => is_front_page() ? drb_t('seo_home_title', $title) : $title);
add_filter('rank_math/frontend/canonical', static fn() => drb_lang_url(drb_current_lang()));

add_action('init', static function (): void {
    remove_action('wp_head', 'rel_canonical');
});

add_action('wp_head', static function (): void {
    foreach (DRB_LANGS as $lang) echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url(drb_lang_url($lang)) . '">' . "\n";
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url(drb_lang_url('fa')) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url(drb_lang_url(drb_current_lang())) . '">' . "\n";
}, 2);

add_action('admin_menu', static function (): void {
    add_management_page('DRB Localization', 'DRB Localization', 'manage_options', 'drb-localization', __NAMESPACE__ . '\\drb_localization_admin');
});

function drb_localization_admin(): void
{
    if (!current_user_can('manage_options')) return;
    $result = '';
    if (isset($_POST['drb_import'])) {
        check_admin_referer('drb_import_localization');
        $result = drb_import_pages() . ' translated pages created or updated.';
    }
    echo '<div class="wrap"><h1>DRB Localization</h1><p>Imports the seven translated page sets and associates them by language group. Existing matching translated pages are updated; Persian source pages are never overwritten.</p>';
    if ($result) echo '<div class="notice notice-success"><p>' . esc_html($result) . '</p></div>';
    echo '<form method="post">'; wp_nonce_field('drb_import_localization');
    submit_button('Import/update translated pages', 'primary', 'drb_import'); echo '</form></div>';
}

function drb_import_pages(): int
{
    $count = 0;
    foreach (array_diff(DRB_LANGS, ['fa']) as $lang) {
        $pack = drb_pack($lang);
        foreach (($pack['pages'] ?? []) as $page) {
            $source_slug = sanitize_title((string) ($page['source_slug_fa'] ?? ''));
            $slug = sanitize_title((string) ($page['slug'] ?? ''));
            if (!$slug || !$source_slug) continue;
            $source = get_page_by_path($source_slug, OBJECT, 'page');
            $group = $source ? (string) get_post_meta($source->ID, 'drb_lang_group_id', true) : '';
            if (!$group) $group = 'page:' . $source_slug;
            if ($source) { update_post_meta($source->ID, 'drb_lang_group_id', $group); update_post_meta($source->ID, 'drb_lang_code', 'fa'); }
            $existing = get_posts(['post_type'=>'page','post_status'=>'any','numberposts'=>1,'meta_query'=>[['key'=>'drb_lang_group_id','value'=>$group],['key'=>'drb_lang_code','value'=>$lang]]]);
            $post = ['post_type'=>'page','post_status'=>'publish','post_title'=>wp_strip_all_tags((string)$page['title']), 'post_name'=>$slug, 'post_content'=>wp_kses_post((string)$page['content_html'])];
            $id = $existing ? wp_update_post($post + ['ID'=>$existing[0]->ID], true) : wp_insert_post($post, true);
            if (is_wp_error($id)) continue;
            update_post_meta($id, 'drb_lang_group_id', $group); update_post_meta($id, 'drb_lang_code', $lang);
            update_post_meta($id, 'rank_math_title', sanitize_text_field((string)$page['seo_title']));
            update_post_meta($id, 'rank_math_description', sanitize_text_field((string)$page['seo_description']));
            $count++;
        }
        update_option('drb_localized_services_' . $lang, $pack['services'] ?? [], false);
        update_option('drb_localized_faqs_' . $lang, $pack['faqs'] ?? [], false);
        update_option('drb_localized_forms_' . $lang, $pack['forms'] ?? [], false);
        update_option('drb_localized_gallery_' . $lang, $pack['gallery_labels'] ?? [], false);
    }
    return $count;
}

function drb_localized_collection(string $type, ?string $lang = null): array
{
    $allowed = ['services', 'faqs', 'forms', 'gallery'];
    if (!in_array($type, $allowed, true)) return [];
    $value = get_option('drb_localized_' . $type . '_' . ($lang ?: drb_current_lang()), []);
    return is_array($value) ? $value : [];
}
