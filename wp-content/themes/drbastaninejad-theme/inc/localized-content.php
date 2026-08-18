<?php
/** Theme-native localized page/content layer for non-Persian language hosts. */
defined( 'ABSPATH' ) || exit;

function drb_content_pack( $lang = null ) {
    static $cache = array();
    $lang = $lang ?: drb_detect_lang();
    if ( isset( $cache[ $lang ] ) ) return $cache[ $lang ];
    $file = DRB_THEME_DIR . '/languages/content/pack_' . sanitize_key( $lang ) . '.json';
    if ( ! is_readable( $file ) ) return $cache[ $lang ] = array();
    $data = json_decode( (string) file_get_contents( $file ), true );
    return $cache[ $lang ] = is_array( $data ) ? $data : array();
}

function drb_localized_page_map() {
    // Stable React routes are deliberately language-neutral. This guarantees
    // every language host renders the exact same compiled UI instead of landing
    // on a translated slug that the SPA router does not know.
    return array(
        'home'                    => array( 'route' => '/', 'fallback_id' => 53, 'fa_fallback' => '/' ),
        'about'                   => array( 'route' => '/about/', 'fallback_id' => 35, 'fa_fallback' => '/about/' ),
        'contact'                 => array( 'route' => '/contact/', 'fallback_id' => 36, 'fa_fallback' => '/contact/' ),
        'booking'                 => array( 'route' => '/booking/', 'fallback_id' => 39, 'fa_fallback' => '/booking/' ),
        'faq'                     => array( 'route' => '/faq/', 'fallback_id' => 38, 'fa_fallback' => '/سوالات-متداول/' ),
        'gallery'                 => array( 'route' => '/gallery/', 'fallback_id' => 40, 'fa_fallback' => '/gallery/' ),
        'after-splint-care'       => array( 'route' => '/atl-removal/', 'fallback_id' => 52, 'fa_fallback' => '/atl-removal/' ),
        'privacy'                 => array( 'route' => '/legal/privacy/', 'fallback_id' => 3, 'fa_fallback' => '/سیاست-حفظ-حریم-خصوصی/' ),
        'articles'                => array( 'route' => '/blog/', 'fallback_id' => 34, 'fa_fallback' => '/blog/' ),
        // The compiled current React build has no separate doctor-profile route;
        // its public doctor profile is the About view. Keep language switching
        // inside a valid route rather than producing a client-side 404.
        'dr-shahin-bastaninejad' => array( 'route' => '/about/', 'fallback_id' => 37, 'fa_fallback' => '/دکتر-شاهین-باستانی-نژاد/' ),
    );
}

function drb_localized_key_from_source_slug( $source ) {
    $aliases = array(
        'صفحه-اصلی' => 'home', 'درباره-دکتر-شاهین-باستانی-نژاد' => 'about',
        'تماس-با-ما' => 'contact', 'دریافت-نوبت' => 'booking', 'سوالات-متداول' => 'faq',
        'گالری' => 'gallery', 'آتل-بینی' => 'after-splint-care',
        'سیاست-حفظ-حریم-خصوصی' => 'privacy', 'مقالات' => 'articles',
        'دکتر-شاهین-باستانی-نژاد' => 'dr-shahin-bastaninejad',
    );
    return $aliases[ $source ] ?? '';
}

/** Resolve the CURRENT live Persian page; historical IDs are last-resort only. */
function drb_live_source_page_id( $key ) {
    static $cache = array();
    if ( array_key_exists( $key, $cache ) ) return $cache[ $key ];

    if ( 'home' === $key ) {
        $front = absint( get_option( 'page_on_front' ) );
        if ( $front && 'publish' === get_post_status( $front ) ) return $cache[ $key ] = $front;
    }
    if ( 'articles' === $key ) {
        $posts_page = absint( get_option( 'page_for_posts' ) );
        if ( $posts_page && 'publish' === get_post_status( $posts_page ) ) return $cache[ $key ] = $posts_page;
    }

    $candidates = array(
        'home' => array( 'home', 'صفحه-اصلی' ),
        'about' => array( 'about', 'درباره-دکتر-شاهین-باستانی-نژاد', 'درباره-دکتر' ),
        'contact' => array( 'contact', 'تماس-با-ما' ),
        'booking' => array( 'booking', 'دریافت-نوبت', 'رزرو-نوبت' ),
        'faq' => array( 'faq', 'سوالات-متداول' ),
        'gallery' => array( 'gallery', 'گالری' ),
        'after-splint-care' => array( 'atl-removal', 'آتل-بینی', 'نکات-مهم-پس-از-برداشتن-آتل-بینی' ),
        'privacy' => array( 'legal/privacy', 'privacy', 'سیاست-حفظ-حریم-خصوصی' ),
        'articles' => array( 'blog', 'مقالات' ),
        'dr-shahin-bastaninejad' => array( 'دکتر-شاهین-باستانی-نژاد', 'dr-shahin-bastaninejad' ),
    );
    foreach ( (array) ( $candidates[ $key ] ?? array() ) as $path ) {
        $page = get_page_by_path( $path, OBJECT, 'page' );
        if ( $page instanceof WP_Post && 'publish' === $page->post_status ) return $cache[ $key ] = (int) $page->ID;
    }

    $map = drb_localized_page_map();
    $fallback = absint( $map[ $key ]['fallback_id'] ?? 0 );
    return $cache[ $key ] = ( $fallback && 'publish' === get_post_status( $fallback ) ? $fallback : 0 );
}

function drb_localized_page( $key = null, $lang = null ) {
    $lang = $lang ?: drb_detect_lang();
    if ( 'fa' === $lang ) return array();
    $key = $key ?: drb_current_localized_key();
    foreach ( (array) ( drb_content_pack( $lang )['pages'] ?? array() ) as $page ) {
        $candidate = drb_localized_key_from_source_slug( (string) ( $page['source_slug_fa'] ?? '' ) );
        if ( $candidate === $key ) return $page;
    }
    return array();
}

function drb_current_localized_key() {
    if ( ! empty( $GLOBALS['drb_localized_forced_key'] ) ) return (string) $GLOBALS['drb_localized_forced_key'];
    $id = get_queried_object_id();
    foreach ( array_keys( drb_localized_page_map() ) as $key ) {
        if ( $id && (int) drb_live_source_page_id( $key ) === (int) $id ) return $key;
    }
    $slug = $id ? urldecode( (string) get_post_field( 'post_name', $id ) ) : '';
    $live = array(
        'home'=>'home', 'about'=>'about', 'contact'=>'contact', 'booking'=>'booking',
        'faq'=>'faq', 'سوالات-متداول'=>'faq', 'gallery'=>'gallery', 'گالری'=>'gallery',
        'atl-removal'=>'after-splint-care', 'blog'=>'articles', 'مقالات'=>'articles',
        'دکتر-شاهین-باستانی-نژاد'=>'dr-shahin-bastaninejad',
    );
    return $live[ $slug ] ?? 'home';
}

function drb_localized_host( $lang ) {
    return function_exists( 'drb_language_host' ) ? drb_language_host( $lang ) : ( 'fa' === $lang ? 'https://drbastaninejad.com' : 'https://' . $lang . '.drbastaninejad.com' );
}

function drb_live_persian_path( $key ) {
    $id = drb_live_source_page_id( $key );
    if ( $id ) {
        $url = get_permalink( $id );
        $path = wp_parse_url( $url, PHP_URL_PATH );
        if ( is_string( $path ) && '' !== $path ) return '/' . ltrim( $path, '/' );
    }
    $map = drb_localized_page_map();
    return (string) ( $map[ $key ]['fa_fallback'] ?? '/' );
}

function drb_localized_path( $key, $lang = null ) {
    $lang = $lang ?: drb_detect_lang();
    if ( 'fa' === $lang ) return drb_live_persian_path( $key );
    $map = drb_localized_page_map();
    return (string) ( $map[ $key ]['route'] ?? '/' );
}

function drb_localized_url( $key, $lang = null ) {
    $lang = $lang ?: drb_detect_lang();
    return rtrim( drb_localized_host( $lang ), '/' ) . drb_localized_path( $key, $lang );
}

function drb_nav_url( $key ) {
    return drb_localized_url( $key, drb_detect_lang() );
}

function drb_localized_url_for_post( $post_id, $lang ) {
    if ( ! empty( $GLOBALS['drb_localized_forced_key'] ) ) return drb_localized_url( (string) $GLOBALS['drb_localized_forced_key'], $lang );
    foreach ( array_keys( drb_localized_page_map() ) as $key ) {
        if ( (int) drb_live_source_page_id( $key ) === (int) $post_id ) return drb_localized_url( $key, $lang );
    }
    return '';
}

/* Resolve stable localized routes to the CURRENT live Persian source page. */
add_filter( 'request', function ( $vars ) {
    if ( is_admin() || 'fa' === drb_detect_lang() ) return $vars;
    $path = trim( (string) parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH ), '/' );
    if ( '' === $path ) { $GLOBALS['drb_localized_forced_key'] = 'home'; $id = drb_live_source_page_id( 'home' ); return $id ? array( 'page_id' => $id ) : $vars; }
    foreach ( drb_localized_page_map() as $key => $row ) {
        $wanted = trim( (string) ( $row['route'] ?? '/' ), '/' );
        if ( $wanted && rawurldecode( $path ) === rawurldecode( $wanted ) ) {
            $GLOBALS['drb_localized_forced_key'] = $key;
            $source_id = drb_live_source_page_id( $key );
            return $source_id ? array( 'page_id' => $source_id ) : $vars;
        }
    }
    return $vars;
}, 1 );

function drb_localized_collection( $name, $lang = null ) {
    $pack = drb_content_pack( $lang ?: drb_detect_lang() );
    $value = $pack[ $name ] ?? array();
    return is_array( $value ) ? $value : array();
}

function drb_localized_forms_map() { return drb_localized_collection( 'forms' ); }
function drb_form_t( $source, $fallback = '' ) {
    $map = drb_localized_forms_map();
    if ( isset( $map[ $source ] ) && '' !== $map[ $source ] ) return (string) $map[ $source ];
    return '' !== $fallback ? (string) $fallback : (string) $source;
}
function drb_localized_content_html( $html ) {
    $html = (string) $html;
    if ( '' === $html || is_admin() || 'fa' === drb_detect_lang() ) return $html;

    $routes = array();
    foreach ( drb_localized_page_map() as $key => $page ) {
        foreach ( array( $page['route'] ?? '', $page['fa_fallback'] ?? '' ) as $path ) {
            if ( ! is_string( $path ) || '' === $path ) continue;
            $normalized = '/' . trim( rawurldecode( $path ), '/' );
            $routes[ '/' === $normalized ? '/' : $normalized . '/' ] = $key;
        }
    }

    $rewrite = static function ( $href ) use ( $routes ) {
        $href = (string) $href;
        if ( '' === $href || '#' === $href[0] || preg_match( '#^(?:mailto|tel|javascript):#i', $href ) ) return $href;

        $host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
        if ( '' !== $host ) {
            $allowed_hosts = array( 'drbastaninejad.com', 'www.drbastaninejad.com' );
            foreach ( DRB_LANGS as $code ) {
                $allowed_hosts[] = strtolower( (string) wp_parse_url( drb_localized_host( $code ), PHP_URL_HOST ) );
            }
            if ( ! in_array( $host, array_unique( $allowed_hosts ), true ) ) return $href;
        }

        $path = wp_parse_url( $href, PHP_URL_PATH );
        if ( ! is_string( $path ) ) return $href;
        $normalized = '/' . trim( rawurldecode( $path ), '/' );
        $normalized = '/' === $normalized ? '/' : $normalized . '/';
        if ( ! isset( $routes[ $normalized ] ) ) return $href;

        $target = drb_nav_url( $routes[ $normalized ] );
        $query = wp_parse_url( $href, PHP_URL_QUERY );
        $fragment = wp_parse_url( $href, PHP_URL_FRAGMENT );
        if ( is_string( $query ) && '' !== $query ) $target .= '?' . $query;
        if ( is_string( $fragment ) && '' !== $fragment ) $target .= '#' . $fragment;
        return $target;
    };

    if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
        $processor = new WP_HTML_Tag_Processor( $html );
        while ( $processor->next_tag( 'A' ) ) {
            $href = $processor->get_attribute( 'href' );
            if ( is_string( $href ) ) $processor->set_attribute( 'href', $rewrite( $href ) );
        }
        return $processor->get_updated_html();
    }

    // WordPress 6.4+ provides WP_HTML_Tag_Processor. This route-driven fallback
    // keeps older emergency installs functional without maintaining a second
    // hard-coded subset of links.
    foreach ( $routes as $path => $key ) {
        foreach ( array( '"', "'" ) as $quote ) {
            $html = str_replace( 'href=' . $quote . $path . $quote, 'href=' . $quote . esc_url( drb_nav_url( $key ) ) . $quote, $html );
        }
    }
    return $html;
}
add_filter( 'the_content', 'drb_localized_content_html', 25 );

/* Rank Math and social metadata must describe the localized virtual page. */
add_filter( 'rank_math/frontend/title', function ( $title ) {
    $page = drb_localized_page(); return $page['seo_title'] ?? $title;
}, 30 );
add_filter( 'rank_math/frontend/description', function ( $description ) {
    $page = drb_localized_page(); return $page['seo_description'] ?? $description;
}, 30 );
add_filter( 'rank_math/frontend/canonical', function ( $canonical ) {
    return 'fa' === drb_detect_lang() ? $canonical : drb_localized_url( drb_current_localized_key() );
}, 30 );
foreach ( array( 'facebook', 'twitter' ) as $network ) {
    add_filter( 'rank_math/opengraph/' . $network . '/title', function ( $title ) { $p = drb_localized_page(); return $p['seo_title'] ?? $title; }, 30 );
    add_filter( 'rank_math/opengraph/' . $network . '/description', function ( $description ) { $p = drb_localized_page(); return $p['seo_description'] ?? $description; }, 30 );
}
add_filter( 'rank_math/opengraph/facebook/url', function ( $url ) {
    return 'fa' === drb_detect_lang() ? $url : drb_localized_url( drb_current_localized_key() );
}, 30 );
add_filter( 'rank_math/opengraph/facebook/site_name', function ( $name ) { return 'fa' === drb_detect_lang() ? $name : __t( 'site_name' ); }, 30 );
add_filter( 'rank_math/json_ld', function ( $data ) {
    if ( 'fa' === drb_detect_lang() ) return $data;
    $page = drb_localized_page(); $url = drb_localized_url( drb_current_localized_key() ); $lang = drb_html_lang();
    foreach ( $data as &$node ) {
        if ( ! is_array( $node ) ) continue;
        $types = (array) ( $node['@type'] ?? array() );
        if ( in_array( 'WebPage', $types, true ) ) {
            $node['@id'] = $url . '#webpage'; $node['name'] = $page['seo_title'] ?? $page['title'] ?? ''; $node['description'] = $page['seo_description'] ?? ''; $node['url'] = $url; $node['inLanguage'] = $lang;
            if ( isset( $node['isPartOf']['@id'] ) ) $node['isPartOf']['@id'] = drb_localized_host( drb_detect_lang() ) . '/#website';
            if ( isset( $node['about']['@id'] ) ) $node['about']['@id'] = drb_localized_host( drb_detect_lang() ) . '/#person';
        }
        if ( in_array( 'WebSite', $types, true ) ) { $node['@id'] = drb_localized_host( drb_detect_lang() ) . '/#website'; $node['name'] = __t( 'site_name' ); $node['url'] = drb_localized_host( drb_detect_lang() ); $node['inLanguage'] = $lang; unset( $node['potentialAction'] ); }
        if ( in_array( 'Organization', $types, true ) || in_array( 'Person', $types, true ) ) { $node['@id'] = drb_localized_host( drb_detect_lang() ) . '/#person'; $node['name'] = __t( 'site_name' ); $node['url'] = drb_localized_host( drb_detect_lang() ); }
    }
    unset( $node ); return $data;
}, 30 );

/* Avoid main-domain feeds/oEmbed metadata on virtual localized pages. */
add_action( 'wp', function () {
    if ( 'fa' === drb_detect_lang() ) return;
    remove_action( 'wp_head', 'feed_links', 2 ); remove_action( 'wp_head', 'feed_links_extra', 3 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
} );

add_action( 'wp_enqueue_scripts', function () {
    if ( 'fa' === drb_detect_lang() ) return;
    $file = DRB_THEME_DIR . '/assets/js/localized-forms.js';
    if ( is_readable( $file ) ) wp_enqueue_script( 'drb-localized-forms', DRB_THEME_URI . '/assets/js/localized-forms.js', array(), filemtime( $file ), true );
}, 40 );
