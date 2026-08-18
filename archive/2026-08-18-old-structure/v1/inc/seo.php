<?php
defined( 'ABSPATH' ) || exit;

function drb_seo_description() {
    if ( is_singular() ) {
        $id = get_queried_object_id();
        $custom = get_post_meta( $id, 'rank_math_description', true );
        if ( $custom ) return wp_strip_all_tags( $custom );
        $excerpt = get_the_excerpt( $id );
        if ( $excerpt ) return wp_strip_all_tags( $excerpt );
    }
    return wp_strip_all_tags( get_bloginfo( 'description' ) );
}

function drb_output_seo_fallback() {
    if ( defined( 'RANK_MATH_VERSION' ) ) return;
    $description = wp_trim_words( drb_seo_description(), 28, '' );
    $request_path = isset( $GLOBALS['wp']->request ) ? $GLOBALS['wp']->request : '';
    $canonical = ( function_exists( 'drb_detect_lang' ) && 'fa' !== drb_detect_lang() && function_exists( 'drb_localized_url' ) )
        ? drb_localized_url( drb_current_localized_key() )
        : ( is_singular() ? get_permalink() : home_url( '/' . ltrim( $request_path, '/' ) ) );
    $title = wp_get_document_title();
    if ( function_exists( 'drb_detect_lang' ) && 'fa' !== drb_detect_lang() && function_exists( 'drb_localized_page' ) ) {
        $localized_page = drb_localized_page();
        if ( ! empty( $localized_page['seo_title'] ) ) $title = $localized_page['seo_title'];
        if ( ! empty( $localized_page['seo_description'] ) ) $description = $localized_page['seo_description'];
    }
    $image = '';
    if ( is_singular() && has_post_thumbnail() ) $image = get_the_post_thumbnail_url( get_queried_object_id(), 'full' );
    if ( ! $image ) $image = drb_get_theme_options()['default_social_image'];
    echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr( function_exists( 'drb_html_lang' ) ? str_replace( '-', '_', drb_html_lang() ) : 'fa_IR' ) . '"><meta property="og:type" content="' . ( is_singular( 'post' ) ? 'article' : 'website' ) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $title ) . '"><meta property="og:description" content="' . esc_attr( $description ) . '"><meta property="og:url" content="' . esc_url( $canonical ) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    if ( $image ) echo '<meta property="og:image" content="' . esc_url( $image ) . '"><meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
}
add_action( 'wp_head', 'drb_output_seo_fallback', 2 );

function drb_replace_core_canonical() {
    if ( ! defined( 'RANK_MATH_VERSION' ) ) remove_action( 'wp_head', 'rel_canonical' );
}
add_action( 'wp', 'drb_replace_core_canonical' );

function drb_output_medical_schema() {
    if ( defined( 'RANK_MATH_VERSION' ) ) return;
    $o = drb_get_theme_options();
    $graph = array(
        array(
            '@type' => 'MedicalClinic', '@id' => home_url( '/#clinic' ), 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ),
            'description' => drb_seo_description(), 'telephone' => preg_split( '/\R+/', $o['phones'] ),
            'address' => array( '@type' => 'PostalAddress', 'streetAddress' => $o['address'], 'addressCountry' => 'IR' ),
            'medicalSpecialty' => 'Otolaryngology',
            'openingHoursSpecification' => array(
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => array( 'Saturday', 'Sunday', 'Monday', 'Tuesday' ),
                'opens' => '15:00', 'closes' => '18:00',
            ),
        ),
        array( '@type' => 'Person', '@id' => home_url( '/#doctor' ), 'name' => $o['doctor_name'], 'jobTitle' => $o['doctor_title'], 'worksFor' => array( '@id' => home_url( '/#clinic' ) ) ),
    );
    if ( is_singular( 'post' ) ) {
        $id = get_queried_object_id();
        $graph[] = array( '@type' => 'MedicalWebPage', '@id' => get_permalink( $id ) . '#article', 'url' => get_permalink( $id ), 'headline' => get_the_title( $id ), 'description' => drb_seo_description(), 'datePublished' => get_the_date( DATE_W3C, $id ), 'dateModified' => get_the_modified_date( DATE_W3C, $id ), 'author' => array( '@id' => home_url( '/#doctor' ) ), 'publisher' => array( '@id' => home_url( '/#clinic' ) ) );
    }
    if ( is_page( 'faq' ) ) {
        $questions = array();
        foreach ( get_posts( array( 'post_type' => 'clinic_faq', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'ASC' ) ) ) as $faq ) {
            $questions[] = array( '@type' => 'Question', 'name' => get_the_title( $faq ), 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $faq->post_content ) ) );
        }
        if ( $questions ) $graph[] = array( '@type' => 'FAQPage', '@id' => get_permalink() . '#faq', 'mainEntity' => $questions );
    }
    $schema = array( '@context' => 'https://schema.org', '@graph' => $graph );
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'drb_output_medical_schema', 30 );
