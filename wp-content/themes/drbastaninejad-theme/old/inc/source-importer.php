<?php
/**
 * Read-only localization package verifier.
 *
 * IMPORTANT: the live Persian WordPress database is the content source of truth.
 * Historical WXR/React package files are references only and this module MUST
 * NEVER insert, update, delete, or migrate Persian posts, pages, FAQs, services,
 * stats, menus, media, SEO metadata, theme mods, or clinic settings.
 */
defined( 'ABSPATH' ) || exit;

function drb_source_files() {
    return array(
        'manifest' => DRB_THEME_DIR . '/languages/live-source-manifest.json',
    );
}

function drb_source_json( $key ) {
    $files = drb_source_files();
    if ( empty( $files[ $key ] ) || ! is_readable( $files[ $key ] ) ) return array();
    $value = json_decode( (string) file_get_contents( $files[ $key ] ), true );
    return is_array( $value ) ? $value : array();
}

/** Read-only inventory of bundled non-patient media. No files are imported. */
function drb_dist6_media_files() {
    $base = DRB_THEME_DIR . '/assets/dist6/images';
    $files = array();
    if ( ! is_dir( $base ) ) return $files;
    $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ) );
    foreach ( $iterator as $file ) {
        if ( ! $file->isFile() || ! preg_match( '/\.(?:jpe?g|png|webp|svg)$/i', $file->getFilename() ) ) continue;
        $files[] = $file->getPathname();
    }
    foreach ( array( 'logo.png', 'logo-white.svg', 'favicon-512.png' ) as $name ) {
        $candidate = DRB_THEME_DIR . '/assets/dist6/' . $name;
        if ( is_readable( $candidate ) ) $files[] = $candidate;
    }
    return array_values( array_unique( $files ) );
}

function drb_localization_package_hash() {
    $paths = array();
    foreach ( array( 'en', 'ar', 'tr', 'ru', 'fr', 'de', 'es' ) as $lang ) {
        $paths[] = DRB_THEME_DIR . '/languages/' . $lang . '.php';
        $paths[] = DRB_THEME_DIR . '/languages/react/' . $lang . '.json';
        $paths[] = DRB_THEME_DIR . '/languages/content/pack_' . $lang . '.json';
    }
    sort( $paths );
    $ctx = hash_init( 'sha256' );
    foreach ( $paths as $path ) {
        hash_update( $ctx, $path . "\n" );
        hash_update( $ctx, is_readable( $path ) ? hash_file( 'sha256', $path ) : 'MISSING' );
    }
    return hash_final( $ctx );
}

function drb_localization_package_checks() {
    $checks = array();
    foreach ( array( 'en', 'ar', 'tr', 'ru', 'fr', 'de', 'es' ) as $lang ) {
        $php_file = DRB_THEME_DIR . '/languages/' . $lang . '.php';
        $react_file = DRB_THEME_DIR . '/languages/react/' . $lang . '.json';
        $content_file = DRB_THEME_DIR . '/languages/content/pack_' . $lang . '.json';
        $php_pack = is_readable( $php_file ) ? include $php_file : array();
        $react = is_readable( $react_file ) ? json_decode( (string) file_get_contents( $react_file ), true ) : null;
        $content = is_readable( $content_file ) ? json_decode( (string) file_get_contents( $content_file ), true ) : null;
        $counts = array(
            'php'      => is_array( $php_pack ) ? count( $php_pack ) : 0,
            'react'    => is_array( $react ) ? count( $react ) : 0,
            'pages'    => is_array( $content ) ? count( (array) ( $content['pages'] ?? array() ) ) : 0,
            'gallery'  => is_array( $content ) ? count( (array) ( $content['gallery'] ?? array() ) ) : 0,
            'chrome'   => is_array( $content ) ? count( (array) ( $content['chrome'] ?? array() ) ) : 0,
            'forms'    => is_array( $content ) ? count( (array) ( $content['forms'] ?? array() ) ) : 0,
        );
        $checks[ $lang ] = $counts + array(
            'ok' => 84 === $counts['php']
                && 1025 === $counts['react']
                && 10 === $counts['pages']
                && 0 === $counts['gallery']
                && 84 === $counts['chrome']
                && 1025 === $counts['forms'],
        );
    }
    return $checks;
}

function drb_source_status() {
    $attachment_counts = wp_count_posts( 'attachment' );
    return array(
        // The live-source manifest records release provenance only; it is never
        // a target state for the live Persian database.
        'manifest'       => drb_source_json( 'manifest' ),
        'posts'          => (int) wp_count_posts( 'post' )->publish,
        'pages'          => (int) wp_count_posts( 'page' )->publish,
        'faqs'           => post_type_exists( 'clinic_faq' ) ? (int) wp_count_posts( 'clinic_faq' )->publish : 0,
        'media'          => isset( $attachment_counts->inherit ) ? (int) $attachment_counts->inherit : 0,
        'imported_at'    => get_option( 'drb_localization_verified_at', '' ),
        'source_hash'    => get_option( 'drb_localization_package_hash', '' ),
        'current_hash'   => drb_localization_package_hash(),
        'language_checks'=> drb_localization_package_checks(),
    );
}

function drb_register_source_import_page() {
    add_theme_page( 'بررسی بسته ترجمه', 'بررسی بسته ترجمه', 'manage_options', 'drb-source-import', 'drb_render_source_import_page' );
}
add_action( 'admin_menu', 'drb_register_source_import_page' );

function drb_render_source_import_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $s = drb_source_status();
    $all_ok = true;
    foreach ( $s['language_checks'] as $row ) if ( empty( $row['ok'] ) ) $all_ok = false;
    ?>
    <div class="wrap drb-admin" dir="rtl">
        <h1>بررسی بسته ترجمه و همگام‌سازی غیرمخرب</h1>
        <div class="notice notice-warning inline"><p><strong>محتوای فارسی زنده منبع اصلی است.</strong> فایل WXR و React قدیمی فقط مرجع ساختار هستند. این ابزار هیچ نوشته، صفحه، FAQ، خدمت، آمار، رسانه، منو، SEO، تنظیمات یا محتوای فارسی را ایجاد، ویرایش یا حذف نمی‌کند.</p></div>
        <?php if ( isset( $_GET['drb_verified'] ) ) : ?><div class="notice notice-success"><p>سلامت بسته ترجمه بررسی و ثبت شد؛ محتوای فارسی بدون تغییر باقی ماند.</p></div><?php endif; ?>
        <div class="drb-source-grid">
            <?php foreach ( $s['language_checks'] as $lang => $row ) : ?>
                <div><strong><?php echo esc_html( strtoupper( $lang ) ); ?></strong><span><?php echo esc_html( ( $row['ok'] ? '✓ ' : '✕ ' ) . $row['react'] . ' عبارت' ); ?></span></div>
            <?php endforeach; ?>
        </div>
        <p><strong>آخرین بررسی:</strong> <?php echo $s['imported_at'] ? esc_html( $s['imported_at'] ) : 'هنوز اجرا نشده'; ?></p>
        <p><strong>وضعیت فعلی:</strong> <?php echo $all_ok ? '<span class="drb-ok">کامل</span>' : '<span class="drb-warn">نیازمند بررسی</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="drb_import_source">
            <?php wp_nonce_field( 'drb_import_source', 'drb_source_nonce' ); submit_button( 'بررسی بسته ترجمه — بدون تغییر محتوای فارسی' ); ?>
        </form>
    </div><?php
}

/**
 * Legacy action name retained for existing admin links/bookmarks. The operation
 * is intentionally verification-only and cannot mutate Persian content.
 */
function drb_handle_source_import() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'دسترسی مجاز نیست.' );
    check_admin_referer( 'drb_import_source', 'drb_source_nonce' );
    $checks = drb_localization_package_checks();
    foreach ( $checks as $lang => $row ) {
        if ( empty( $row['ok'] ) ) wp_die( esc_html( 'بسته ترجمه ناقص است: ' . strtoupper( $lang ) ) );
    }
    update_option( 'drb_localization_verified_at', current_time( 'mysql' ), false );
    update_option( 'drb_localization_package_hash', drb_localization_package_hash(), false );
    wp_safe_redirect( add_query_arg( 'drb_verified', '1', admin_url( 'themes.php?page=drb-source-import' ) ) );
    exit;
}
add_action( 'admin_post_drb_import_source', 'drb_handle_source_import' );
