<?php
defined( 'ABSPATH' ) || exit;

function drb_admin_assets( $hook ) {
    if ( false === strpos( $hook, 'drb' ) && ! in_array( $hook, array( 'index.php', 'post.php', 'post-new.php' ), true ) ) return;
    wp_enqueue_style( 'drb-admin', DRB_THEME_URI . '/assets/admin.css', array(), DRB_THEME_VERSION );
    wp_enqueue_media(); wp_enqueue_script( 'drb-admin', DRB_THEME_URI . '/assets/admin.js', array( 'jquery' ), DRB_THEME_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'drb_admin_assets' );

function drb_dashboard_widgets() {
    wp_add_dashboard_widget( 'drb_content_overview', 'وضعیت محتوای سایت', 'drb_widget_content_overview' );
    wp_add_dashboard_widget( 'drb_source_integrity', 'سلامت بسته ترجمه', 'drb_widget_source_integrity' );
    wp_add_dashboard_widget( 'drb_leads_overview', 'پیام‌ها و درخواست‌های نوبت', 'drb_widget_leads_overview' );
    wp_add_dashboard_widget( 'drb_gallery_readiness', 'آمادگی گالری قبل و بعد', 'drb_widget_gallery_readiness' );
    wp_add_dashboard_widget( 'drb_public_stats', 'آمار عمومی نمایش‌داده‌شده در سایت', 'drb_widget_public_stats' );
}

function drb_widget_public_stats() {
    $rows = array();
    foreach ( get_posts( array( 'post_type' => 'clinic_stat', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'ASC' ) ) ) as $item ) {
        $rows[] = array( get_the_title( $item ), get_post_meta( $item->ID, '_drb_stat_value', true ), get_edit_post_link( $item->ID ) );
    }
    if ( ! $rows ) { echo '<p>آمار هنوز وارد نشده است.</p>'; return; }
    echo '<div class="drb-stat-rows">'; foreach ( $rows as $row ) echo '<a href="' . esc_url( $row[2] ) . '"><span>' . esc_html( $row[0] ) . '</span><strong>' . esc_html( $row[1] ) . '</strong></a>'; echo '</div>';
    echo '<p class="description">هر عدد را فقط بر اساس مدرک قابل اتکا ویرایش یا منتشر کنید.</p>';
}
add_action( 'wp_dashboard_setup', 'drb_dashboard_widgets' );

function drb_count_status( $type, $status = 'publish' ) { $c = wp_count_posts( $type ); return isset( $c->$status ) ? (int) $c->$status : 0; }

function drb_widget_content_overview() {
    $rows = array(
        array( 'نوشته‌های منتشرشده', drb_count_status( 'post' ), admin_url( 'edit.php' ) ),
        array( 'صفحات منتشرشده', drb_count_status( 'page' ), admin_url( 'edit.php?post_type=page' ) ),
        array( 'خدمات', drb_count_status( 'service' ), admin_url( 'edit.php?post_type=service' ) ),
        array( 'سؤالات متداول', drb_count_status( 'clinic_faq' ), admin_url( 'edit.php?post_type=clinic_faq' ) ),
        array( 'گواهینامه‌ها', drb_count_status( 'certificate' ), admin_url( 'edit.php?post_type=certificate' ) ),
    ); drb_render_stat_rows( $rows );
    echo '<p class="drb-widget-actions"><a class="button button-primary" href="' . esc_url( admin_url( 'themes.php?page=drb-source-import' ) ) . '">بررسی بسته ترجمه</a> <a class="button" href="' . esc_url( admin_url( 'themes.php?page=drb-theme-options' ) ) . '">تنظیمات کلینیک</a></p>';
}

function drb_widget_source_integrity() {
    $s = drb_source_status();
    $rows = array();
    foreach ( (array) ( $s['language_checks'] ?? array() ) as $lang => $check ) {
        $rows[] = array( strtoupper( $lang ) . ' ترجمه', ! empty( $check['ok'] ) ? (int) ( $check['react'] ?? 0 ) : 'ناقص', '' );
    }
    drb_render_stat_rows( $rows );
    echo '<p><strong>آخرین بررسی غیرمخرب:</strong> ' . esc_html( $s['imported_at'] ?: 'انجام نشده' ) . '</p>';
    echo '<p class="description">محتوای فارسی زنده منبع اصلی است و توسط ابزار ترجمه تغییر نمی‌کند.</p>';
    echo '<p><strong>Rank Math:</strong> ' . ( defined( 'RANK_MATH_VERSION' ) ? '<span class="drb-ok">فعال</span>' : '<span class="drb-warn">فعال نیست</span>' ) . '</p>';
}

function drb_widget_leads_overview() {
    // Never let CRM bridge failures white-screen wp-admin.
    $contact_count = 0;
    try {
        if ( post_type_exists( 'drb_submission' ) ) {
            $contacts = new WP_Query( array(
                'post_type'              => 'drb_submission',
                'post_status'            => 'private',
                'posts_per_page'         => 1,
                'fields'                 => 'ids',
                'meta_key'               => '_drb_submission_type',
                'meta_value'             => 'contact',
                'no_found_rows'          => false,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ) );
            $contact_count = (int) $contacts->found_posts;
        }
    } catch ( Throwable $e ) {
        $contact_count = 0;
    }

    $rows = array(
        array( 'پیام‌های تماس وردپرس', $contact_count, admin_url( 'edit.php?post_type=drb_submission' ) ),
    );

    $stats = function_exists( 'drb_dashboard_booking_stats' ) ? drb_dashboard_booking_stats() : new WP_Error( 'missing', 'stats fn missing' );
    if ( ! is_wp_error( $stats ) && is_array( $stats ) ) {
        $rows[] = array( 'درخواست‌های نوبت CRM', (int) ( $stats['total'] ?? 0 ), admin_url( 'admin.php?page=drb-booking-sms' ) );
        $rows[] = array( 'نوبت‌های ۷ روز اخیر', (int) ( $stats['last_7_days'] ?? 0 ), admin_url( 'admin.php?page=drb-booking-sms' ) );
        $rows[] = array( 'خطای پیامک نوبت', (int) ( $stats['sms_failed'] ?? 0 ), admin_url( 'admin.php?page=drb-booking-sms' ) );
    } else {
        $rows[] = array( 'اتصال CRM', 'نیازمند بررسی', admin_url( 'admin.php?page=drb-booking-sms' ) );
    }

    drb_render_stat_rows( $rows );
    echo '<p>رزروهای بالینی در WordPress ذخیره نمی‌شوند؛ این ویجت فقط شمارنده‌های غیرشخصی CRM را نمایش می‌دهد.</p>';
}

function drb_widget_gallery_readiness() {
    $verified = array();
    if ( post_type_exists( 'case_study' ) ) {
        $verified = get_posts( array(
            'post_type' => 'case_study', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids',
            'meta_key' => '_drb_consent_verified', 'meta_value' => '1',
        ) );
    }
    $all = function_exists( 'drb_count_status' ) ? drb_count_status( 'case_study' ) : 0;
    $options = function_exists( 'drb_get_theme_options' ) ? drb_get_theme_options() : array();
    if ( ! is_array( $options ) ) {
        $options = array();
    }
    drb_render_stat_rows( array(
        array( 'کل نمونه‌ها', (int) $all, admin_url( 'edit.php?post_type=case_study' ) ),
        array( 'دارای رضایت معتبر', count( $verified ), admin_url( 'edit.php?post_type=case_study' ) ),
    ) );
    echo '<p><strong>انتشار عمومی:</strong> ' . ( ! empty( $options['gallery_enabled'] ) ? '<span class="drb-ok">فعال</span>' : '<span class="drb-warn">خاموش</span>' ) . '</p>';
    echo '<p><a class="button" href="' . esc_url( admin_url( 'post-new.php?post_type=case_study' ) ) . '">افزودن نمونه امن</a></p>';
}

function drb_render_stat_rows( $rows ) {
    echo '<div class="drb-stat-rows">';
    foreach ( (array) $rows as $row ) {
        $label = isset( $row[0] ) ? (string) $row[0] : '';
        $value = $row[1] ?? '';
        $href  = ! empty( $row[2] ) ? (string) $row[2] : '';
        // PHP 8+: number_format_i18n() fatals on non-numeric strings (e.g. CRM offline label).
        if ( is_numeric( $value ) ) {
            $display = number_format_i18n( (float) $value );
        } else {
            $display = (string) $value;
        }
        echo '<a' . ( $href !== '' ? ' href="' . esc_url( $href ) . '"' : '' ) . '>';
        echo '<span>' . esc_html( $label ) . '</span>';
        echo '<strong>' . esc_html( $display ) . '</strong>';
        echo '</a>';
    }
    echo '</div>';
}

function drb_admin_bar_health( WP_Admin_Bar $bar ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( ! function_exists( 'drb_source_status' ) ) {
        return;
    }
    $s = drb_source_status();
    if ( ! is_array( $s ) ) {
        return;
    }
    $ok = ! empty( $s['language_checks'] );
    foreach ( (array) ( $s['language_checks'] ?? array() ) as $check ) {
        if ( empty( $check['ok'] ) ) { $ok = false; break; }
    }
    $bar->add_node( array(
        'id'    => 'drb-health',
        'title' => $ok ? 'ترجمه‌ها: آماده' : 'ترجمه‌ها: نیازمند بررسی',
        'href'  => admin_url( 'themes.php?page=drb-source-import' ),
    ) );
}
add_action( 'admin_bar_menu', 'drb_admin_bar_health', 90 );
