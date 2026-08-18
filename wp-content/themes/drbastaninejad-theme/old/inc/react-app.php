<?php

defined( 'ABSPATH' ) || exit;

function drb_find_app_asset( $pattern ) {
    $files = glob( DRB_THEME_DIR . '/assets/dist6/assets/' . $pattern );
    return empty( $files ) ? '' : $files[0];
}


/**
 * Load the localization runtime before the compiled React application.
 *
 * The React bundle is Persian-first. On translated hosts the runtime must be
 * available in the document head so direction and visible copy are corrected
 * before React paints/re-hydrates. Keeping this handle dependency-free also
 * lets native WordPress templates use the identical localization layer.
 */
function drb_enqueue_i18n_runtime_early() {
    if ( is_admin() || ! function_exists( 'drb_detect_lang' ) || 'fa' === drb_detect_lang() ) {
        return;
    }
    $path = DRB_THEME_DIR . '/assets/js/drb-i18n-runtime.js';
    if ( ! is_readable( $path ) ) {
        return;
    }
    wp_enqueue_script( 'drb-i18n-runtime', DRB_THEME_URI . '/assets/js/drb-i18n-runtime.js', array(), filemtime( $path ), false );
}
add_action( 'wp_enqueue_scripts', 'drb_enqueue_i18n_runtime_early', 5 );

function drb_enqueue_react_app() {
    $css_file = drb_find_app_asset( 'main-*.css' );
    $js_file = drb_find_app_asset( 'main-*.js' );
    if ( $css_file ) {
        wp_enqueue_style( 'drb-app', DRB_THEME_URI . '/assets/dist6/assets/' . basename( $css_file ), array(), filemtime( $css_file ) );
    }
    wp_enqueue_style( 'drb-theme', get_stylesheet_uri(), $css_file ? array( 'drb-app' ) : array(), DRB_THEME_VERSION );
    if ( $js_file && ! drb_use_native_template() ) {
        wp_enqueue_script( 'drb-app', DRB_THEME_URI . '/assets/dist6/assets/' . basename( $js_file ), array(), filemtime( $js_file ), true );
        $page_id = get_queried_object_id();
        $options = drb_get_theme_options();
        $gallery = array();
        if ( ! empty( $options['gallery_enabled'] ) ) {
            $case_ids = get_posts(
                array(
                    'post_type' => 'case_study', 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => -1,
                    'orderby' => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
                    'meta_key' => '_drb_consent_verified', 'meta_value' => '1',
                )
            );
            foreach ( $case_ids as $case_id ) {
                $case_images = function_exists( 'drb_case_gallery_meta' ) ? drb_case_gallery_meta( $case_id ) : array();
                $images = array();
                foreach ( $case_images as $case_image ) {
                    $attachment_id = absint( $case_image['id'] ?? 0 );
                    if ( ! $attachment_id || get_post_meta( $attachment_id, '_drb_gallery_quarantined', true ) ) continue;
                    $source = (string) get_post_meta( $attachment_id, '_drb_dist6_source', true );
                    $file = (string) get_attached_file( $attachment_id );
                    if ( preg_match( '#(?:/|\\\\)Doctor(?:/|\\\\)|استاد|professor|faculty#iu', $source . ' ' . $file . ' ' . get_the_title( $attachment_id ) ) ) continue;
                    $url = wp_get_attachment_image_url( $attachment_id, 'drb-case-cover' );
                    $thumb = wp_get_attachment_image_url( $attachment_id, 'medium' );
                    if ( ! $url ) continue;
                    $images[] = array(
                        'id' => $attachment_id,
                        'src' => $url,
                        'thumb' => $thumb ?: $url,
                        'label' => $case_image['label'] ?: get_the_title( $attachment_id ),
                        'interval' => $case_image['interval'],
                        'alt' => trim( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ),
                    );
                }
                if ( $images ) {
                    $cover = $images[0]['src'];
                    $gallery[] = array(
                        'id' => $case_id,
                        // Backward-compatible fields keep the bundled React component harmless
                        // until the multi-view gallery enhancement mounts.
                        'before' => $cover,
                        'after' => $cover,
                        'label' => get_post_meta( $case_id, '_drb_case_label', true ) ?: get_the_title( $case_id ),
                        'procedure' => get_post_meta( $case_id, '_drb_case_procedure', true ),
                        'interval' => get_post_meta( $case_id, '_drb_case_interval', true ),
                        'noseType' => get_post_meta( $case_id, '_drb_case_nose_type', true ),
                        'images' => $images,
                    );
                }
            }
        }
        $services = array();
        foreach ( get_posts( array( 'post_type' => 'service', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'DESC' ) ) ) as $item ) {
            if ( preg_match( '/(?:گوشتی|فانتزی|عروسکی|fleshy|fantasy)/iu', $item->post_title . ' ' . $item->post_name ) ) continue;
            $services[] = array(
                'id' => $item->ID, 'slug' => $item->post_name, 'title' => get_the_title( $item ),
                'excerpt' => get_the_excerpt( $item ), 'content' => apply_filters( 'the_content', $item->post_content ),
                'category' => get_post_meta( $item->ID, '_drb_service_category', true ),
                'lead' => get_post_meta( $item->ID, '_drb_service_lead', true ),
                'takeaways' => array_values( array_filter( preg_split( '/\R+/', get_post_meta( $item->ID, '_drb_service_takeaways', true ) ) ) ),
                'steps' => array_values( array_filter( preg_split( '/\R+/', get_post_meta( $item->ID, '_drb_service_steps', true ) ) ) ),
                'faqs' => drb_parse_service_faqs( get_post_meta( $item->ID, '_drb_service_faqs', true ) ),
                'url' => get_permalink( $item ),
            );
        }
        $faqs = array();
        foreach ( get_posts( array( 'post_type' => 'clinic_faq', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'ASC' ) ) ) as $item ) {
            $faqs[] = array( 'id' => $item->ID, 'cat' => get_post_meta( $item->ID, '_drb_faq_category', true ), 'q' => get_the_title( $item ), 'a' => wp_strip_all_tags( $item->post_content ) );
        }
        $stats = array();
        foreach ( get_posts( array( 'post_type' => 'clinic_stat', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'ASC' ) ) ) as $item ) {
            $stats[] = array( 'id' => $item->ID, 'label' => get_the_title( $item ), 'value' => get_post_meta( $item->ID, '_drb_stat_value', true ), 'sub' => wp_strip_all_tags( $item->post_content ) );
        }
        $posts = array();
        $needs_posts = is_home() || is_singular( 'post' ) || is_category() || is_tag() || ( is_page() && 'blog' === get_post_field( 'post_name', $page_id ) );
        foreach ( $needs_posts ? get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) ) : array() as $item ) {
            $posts[] = array(
                'id' => $item->ID, 'slug' => $item->post_name, 'title' => get_the_title( $item ), 'excerpt' => get_the_excerpt( $item ),
                'content' => apply_filters( 'the_content', $item->post_content ), 'date' => get_the_date( 'Y-m-d H:i:s', $item ),
                'modified' => get_the_modified_date( 'Y-m-d H:i:s', $item ), 'categories' => wp_get_post_categories( $item->ID, array( 'fields' => 'names' ) ),
                'tags' => wp_get_post_tags( $item->ID, array( 'fields' => 'names' ) ), 'url' => get_permalink( $item ),
                'thumbnail' => get_the_post_thumbnail_url( $item, 'large' ) ?: '',
            );
        }
        $menu = array();
        $locations = get_nav_menu_locations();
        if ( ! empty( $locations['primary'] ) ) foreach ( (array) wp_get_nav_menu_items( $locations['primary'] ) as $item ) {
            $menu[] = array( 'id' => $item->ID, 'label' => $item->title, 'href' => $item->url, 'parent' => (int) $item->menu_item_parent );
        }
        $page_data = $page_id ? array(
            'id' => $page_id,
            'slug' => get_post_field( 'post_name', $page_id ),
            'title' => get_the_title( $page_id ),
            'content' => apply_filters( 'the_content', get_post_field( 'post_content', $page_id ) ),
            'heroEyebrow' => get_post_meta( $page_id, '_drb_hero_eyebrow', true ),
            'heroTitle' => get_post_meta( $page_id, '_drb_hero_title', true ),
            'heroDescription' => get_post_meta( $page_id, '_drb_hero_description', true ),
            'primaryCtaText' => get_post_meta( $page_id, '_drb_primary_cta_text', true ),
            'primaryCtaUrl' => get_post_meta( $page_id, '_drb_primary_cta_url', true ),
            'secondaryCtaText' => get_post_meta( $page_id, '_drb_secondary_cta_text', true ),
            'secondaryCtaUrl' => get_post_meta( $page_id, '_drb_secondary_cta_url', true ),
            'introTitle' => get_post_meta( $page_id, '_drb_intro_title', true ),
            'introContent' => get_post_meta( $page_id, '_drb_intro_content', true ),
        ) : null;
        $current_service = null;
        if ( is_singular( 'service' ) ) foreach ( $services as $service ) {
            if ( (int) $service['id'] === (int) $page_id ) { $current_service = $service; break; }
        }
        $bootstrap = array(
            'themeUri' => esc_url_raw( DRB_THEME_URI ),
            'lang' => function_exists( 'drb_detect_lang' ) ? drb_detect_lang() : 'fa',
            'dir'  => function_exists( 'drb_is_rtl' ) ? ( drb_is_rtl() ? 'rtl' : 'ltr' ) : 'rtl',
            'api' => array(
                'contact' => esc_url_raw( wp_make_link_relative( rest_url( 'drb/v1/contact' ) ) ),
                'appointment' => esc_url_raw( wp_make_link_relative( rest_url( 'drb/v1/appointment' ) ) ),
                'nonce' => wp_create_nonce( 'drb_public_form' ),
                'patientLogin' => 'https://app.drbastaninejad.com/Frontend/pages/auth/patient-login.html',
            ),
            // The React build uses this flag to suppress client-side JSON-LD when SEO is managed server-side.
            'rankMath' => true,
            'site' => array(
                'doctorName' => $options['doctor_name'],
                'doctorTitle' => $options['doctor_title'],
                'phones' => array_values( array_map( static function ( $phone ) {
                    return function_exists( 'drb_format_iran_phone' ) ? drb_format_iran_phone( trim( (string) $phone ) ) : trim( (string) $phone );
                }, array_filter( preg_split( '/\R+/', $options['phones'] ) ) ) ),
                'address' => $options['address'],
                'clinicHours' => $options['clinic_hours'],
                'admissionNotice' => $options['admission_notice'],
                'revisionNotice' => $options['revision_notice'],
                'whatsapp' => $options['whatsapp'], 'instagram' => $options['instagram'], 'telegram' => $options['telegram'],
                'youtube' => $options['youtube'], 'aparat' => $options['aparat'],
                'galleryEnabled' => ! empty( $options['gallery_enabled'] ) && (bool) $gallery,
            ),
            'page' => $page_data, 'service' => $current_service,
            'services' => $services, 'faqs' => $faqs, 'posts' => $posts, 'stats' => $stats, 'menu' => $menu,
            'gallery' => $gallery,
        );

        // Use current live WordPress records as the source of truth, but localize
        // presentation fields in memory for translated hosts. No post/meta/option
        // is written here, and stable IDs/routes/API values are explicitly skipped.
        if ( 'fa' !== $bootstrap['lang'] && function_exists( 'drb_localize_presentation_tree' ) ) {
            foreach ( array( 'site', 'page', 'service', 'services', 'faqs', 'stats', 'menu', 'gallery' ) as $presentation_key ) {
                $bootstrap[$presentation_key] = drb_localize_presentation_tree( $bootstrap[$presentation_key], $bootstrap['lang'], $presentation_key );
            }
        }
        wp_add_inline_script(
            'drb-app',
            'window.__DRB_FORMS_API__=' . wp_json_encode( $bootstrap['api'] ) . ';window.__DRB_WP__=' . wp_json_encode( $bootstrap ) . ';',
            'before'
        );
        

        wp_enqueue_script( 'drb-dynamic-content', DRB_THEME_URI . '/assets/dynamic-content.js', array( 'drb-app' ), DRB_THEME_VERSION, true );
    }
}
add_action( 'wp_enqueue_scripts', 'drb_enqueue_react_app' );


/**
 * Guarantee the public forms API bootstrap is present on every page.
 *
 * The React bundle reads window.__DRB_FORMS_API__.appointment unconditionally
 * on submit. On native-template pages (blog / case study / archive / search) the
 * main app bundle is intentionally not enqueued, so without this fallback a
 * booking CTA rendered in a native template would throw
 * "Cannot read properties of undefined (reading 'appointment')".
 *
 * This runs on wp_head at priority 0, before any module script, and only writes
 * the object when the main bundle did not already define it.
 */
function drb_ensure_forms_api_bootstrap() {
    if ( is_admin() ) {
        return;
    }
    $api = array(
        'contact'      => esc_url_raw( wp_make_link_relative( rest_url( 'drb/v1/contact' ) ) ),
        'appointment'  => esc_url_raw( wp_make_link_relative( rest_url( 'drb/v1/appointment' ) ) ),
        'nonce'        => wp_create_nonce( 'drb_public_form' ),
        'patientLogin' => 'https://app.drbastaninejad.com/Frontend/pages/auth/patient-login.html',
    );
    // Only define if the main bundle (drb-app inline script) did not already.
    echo '<script>(function(){if(window.__DRB_FORMS_API__){return;}window.__DRB_FORMS_API__='
        . wp_json_encode( $api ) . ';})();</script>' . "\n";
}
add_action( 'wp_head', 'drb_ensure_forms_api_bootstrap', 0 );

/**
 * Enqueue the booking fetch patch on all front-end pages.
 *
 * The minified React bundle sends only Content-Type on its POST requests and
 * never forwards the X-DRB-Form-Nonce header that inc/forms.php requires, so
 * every real submit is rejected with 403 "نشست فرم منقضی شده است". Rather than
 * rebuild the bundle, this readable patch wraps window.fetch to inject the nonce
 * header for our own endpoints and to guard against a missing global.
 *
 * Loaded in the document head with NO dependency on the React app handle.
 * The previous version declared a dependency on `drb-app`; when that handle was
 * not registered (native-template pages, failed enqueue, missing dist file)
 * WordPress dropped the patch entirely, so window.fetch was never wrapped and
 * every booking submit failed with 403. With empty deps the patch always loads.
 *
 * It is printed in wp_head so it wraps window.fetch before any submit handler
 * can capture a reference to the original fetch, and after
 * drb_ensure_forms_api_bootstrap() (wp_head priority 0) which guarantees
 * window.__DRB_FORMS_API__.nonce exists. It only touches requests whose URL
 * matches our REST endpoints, leaving every other fetch untouched.
 */
function drb_enqueue_booking_fetch_patch() {
    if ( is_admin() ) {
        return;
    }
    $path = DRB_THEME_DIR . '/assets/js/booking-fetch-patch.js';
    if ( ! file_exists( $path ) ) {
        return;
    }
    // No dependency on `drb-app`: the patch must load even when the React bundle
    // is not enqueued, otherwise the nonce header is never injected.
    wp_enqueue_script( 'drb-booking-fetch-patch', DRB_THEME_URI . '/assets/js/booking-fetch-patch.js', array(), filemtime( $path ), false );
}
add_action( 'wp_enqueue_scripts', 'drb_enqueue_booking_fetch_patch', 20 );

function drb_use_native_template() {
    return is_home() || is_singular( array( 'post', 'case_study' ) ) || is_archive() || is_search();
}

function drb_parse_service_faqs( $text ) {
    $items = array();
    foreach ( array_filter( preg_split( '/\R+/', (string) $text ) ) as $line ) {
        $parts = array_map( 'trim', explode( '|', $line, 2 ) );
        if ( count( $parts ) === 2 ) $items[] = array( 'question' => $parts[0], 'answer' => $parts[1] );
    }
    return $items;
}

function drb_module_script_tag( $tag, $handle, $src ) {
    if ( 'drb-app' !== $handle ) return $tag;
    return '<script type="module" src="' . esc_url( $src ) . '" id="drb-app-js"></script>';
}
add_filter( 'script_loader_tag', 'drb_module_script_tag', 10, 3 );

function drb_app_asset_is_ready() {
    return (bool) drb_find_app_asset( 'main-*.js' );
}
