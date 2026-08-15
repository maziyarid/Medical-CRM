<?php

defined( 'ABSPATH' ) || exit;

function drb_register_content_types() {
    register_post_type(
        'service',
        array(
            'labels' => array(
                'name' => 'خدمات', 'singular_name' => 'خدمت', 'add_new_item' => 'افزودن خدمت',
                'edit_item' => 'ویرایش خدمت', 'all_items' => 'همه خدمات', 'menu_name' => 'خدمات',
            ),
            // Services keep their native public URLs for server-side previews
            // and search-engine friendly landing pages.
            'public' => true,
            'show_in_rest' => false,
            'has_archive' => 'services',
            'rewrite' => array( 'slug' => 'services', 'with_front' => false ),
            'menu_icon' => 'dashicons-heart',
            'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes', 'custom-fields' ),
        )
    );

    register_post_type(
        'case_study',
        array(
            'labels' => array(
                'name' => 'نمونه‌کارها', 'singular_name' => 'نمونه‌کار', 'add_new_item' => 'افزودن نمونه‌کار',
                'edit_item' => 'ویرایش نمونه‌کار', 'all_items' => 'همه نمونه‌کارها', 'menu_name' => 'نمونه‌کارها',
            ),
            'public' => true,
            'show_in_rest' => false,
            'has_archive' => false,
            'rewrite' => array( 'slug' => 'gallery', 'with_front' => false ),
            'menu_icon' => 'dashicons-format-gallery',
            'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
        )
    );

    register_post_type(
        'clinic_faq',
        array(
            'labels' => array(
                'name' => 'سؤالات متداول', 'singular_name' => 'سؤال متداول', 'add_new_item' => 'افزودن سؤال',
                'edit_item' => 'ویرایش سؤال', 'all_items' => 'همه سؤالات', 'menu_name' => 'سؤالات متداول',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => false,
            'menu_icon' => 'dashicons-editor-help',
            'supports' => array( 'title', 'editor', 'page-attributes', 'revisions', 'custom-fields' ),
        )
    );

    register_post_type(
        'certificate',
        array(
            'labels' => array(
                'name' => 'گواهینامه‌ها', 'singular_name' => 'گواهینامه', 'add_new_item' => 'افزودن گواهینامه',
                'edit_item' => 'ویرایش گواهینامه', 'all_items' => 'همه گواهینامه‌ها', 'menu_name' => 'گواهینامه‌ها',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => false,
            'menu_icon' => 'dashicons-awards',
            'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields' ),
        )
    );

    register_post_type(
        'drb_submission',
        array(
            'labels' => array( 'name' => 'درخواست‌های سایت', 'singular_name' => 'درخواست', 'menu_name' => 'درخواست‌های سایت', 'edit_item' => 'مشاهده درخواست' ),
            'public' => false, 'show_ui' => true, 'show_in_rest' => false,
            'menu_icon' => 'dashicons-email-alt', 'capability_type' => 'post',
            'supports' => array( 'title', 'editor', 'custom-fields' ),
        )
    );

    register_post_type( 'clinic_stat', array(
        'labels' => array( 'name' => 'آمار و افتخارات', 'singular_name' => 'آمار', 'menu_name' => 'آمار و افتخارات', 'add_new_item' => 'افزودن آمار', 'edit_item' => 'ویرایش آمار' ),
        'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'menu_icon' => 'dashicons-chart-bar',
        'supports' => array( 'title', 'editor', 'page-attributes', 'custom-fields' ),
    ) );

    register_post_type( 'testimonial', array(
        'labels' => array( 'name' => 'نظرات مراجعان', 'singular_name' => 'نظر مراجعه‌کننده', 'menu_name' => 'نظرات مراجعان', 'add_new_item' => 'افزودن نظر', 'edit_item' => 'ویرایش نظر' ),
        'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'menu_icon' => 'dashicons-format-quote',
        'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields' ),
    ) );
}
add_action( 'init', 'drb_register_content_types' );

function drb_register_public_meta() {
    $definitions = array(
        '_drb_hero_eyebrow' => 'string', '_drb_hero_title' => 'string', '_drb_hero_description' => 'string',
        '_drb_primary_cta_text' => 'string', '_drb_primary_cta_url' => 'string',
        '_drb_secondary_cta_text' => 'string', '_drb_secondary_cta_url' => 'string',
        '_drb_intro_title' => 'string', '_drb_intro_content' => 'string',
        '_drb_service_category' => 'string', '_drb_service_lead' => 'string',
        '_drb_service_takeaways' => 'string', '_drb_service_steps' => 'string', '_drb_service_faqs' => 'string',
        '_drb_before_image_id' => 'integer', '_drb_after_image_id' => 'integer', '_drb_consent_verified' => 'boolean',
        '_drb_reviewed_at' => 'string', '_drb_case_label' => 'string', '_drb_faq_category' => 'string',
        '_drb_case_procedure' => 'string', '_drb_case_nose_type' => 'string', '_drb_case_patient_gender' => 'string',
        '_drb_case_interval' => 'string', '_drb_consent_reference' => 'string', '_drb_case_gallery' => 'string',
    );
    foreach ( array( 'page', 'service', 'case_study', 'clinic_faq' ) as $post_type ) {
        foreach ( $definitions as $key => $type ) {
            register_post_meta(
                $post_type,
                $key,
                array(
                    'type' => $type,
                    'single' => true,
                    'show_in_rest' => true,
                    'sanitize_callback' => 'string' === $type ? 'sanitize_textarea_field' : null,
                    'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
                )
            );
        }
    }
}
add_action( 'init', 'drb_register_public_meta', 20 );
