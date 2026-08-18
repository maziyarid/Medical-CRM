<?php

defined( 'ABSPATH' ) || exit;

function drb_theme_setup() {
    load_theme_textdomain( 'drb-theme', DRB_THEME_DIR . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'editor-styles' );
    add_theme_support(
        'html5',
        array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
    );
    add_theme_support(
        'custom-logo',
        array(
            'height' => 120,
            'width' => 360,
            'flex-height' => true,
            'flex-width' => true,
        )
    );
    register_nav_menus(
        array(
            'primary' => 'منوی اصلی',
            'footer' => 'منوی فوتر',
        )
    );
    // One 16:9 master works for both the article hero and archive cards.
    add_image_size( 'drb-article-hero', 1600, 900, true );
    add_image_size( 'drb-card', 720, 405, true );
    add_image_size( 'drb-case-cover', 1080, 1080, true );
    add_image_size( 'drb-portrait', 1200, 1600, false );
}
add_action( 'after_setup_theme', 'drb_theme_setup' );

function drb_theme_content_width() {
    $GLOBALS['content_width'] = apply_filters( 'drb_theme_content_width', 1200 );
}
add_action( 'after_setup_theme', 'drb_theme_content_width', 0 );

function drb_theme_activate() {
    drb_register_content_types();
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'drb_theme_activate' );

function drb_theme_blog_rewrite() {
    add_rewrite_rule( '^blog/([^/]+)/?$', 'index.php?name=$matches[1]', 'top' );
}
add_action( 'init', 'drb_theme_blog_rewrite', 20 );
