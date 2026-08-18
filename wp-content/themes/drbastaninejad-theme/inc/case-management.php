<?php
defined( 'ABSPATH' ) || exit;

function drb_case_admin_columns( $columns ) {
    return array( 'cb' => $columns['cb'], 'title' => 'عنوان/کد نمونه', 'drb_gallery' => 'گالری مراجعه', 'drb_procedure' => 'نوع جراحی', 'drb_consent' => 'رضایت', 'drb_review' => 'بازبینی پزشکی', 'date' => 'تاریخ' );
}
add_filter( 'manage_case_study_posts_columns', 'drb_case_admin_columns' );

function drb_case_admin_column( $column, $id ) {
    if ( 'drb_gallery' === $column ) {
        $gallery = function_exists( 'drb_case_gallery_meta' ) ? drb_case_gallery_meta( $id ) : array();
        if ( $gallery ) echo wp_get_attachment_image( $gallery[0]['id'], array( 80, 80 ), false, array( 'style' => 'width:64px;height:64px;object-fit:cover;border-radius:8px' ) ) . '<br><small>' . esc_html( count( $gallery ) ) . ' نما</small>';
        else echo '<span class="drb-warn">ثبت نشده</span>';
    } elseif ( 'drb_procedure' === $column ) echo esc_html( get_post_meta( $id, '_drb_case_procedure', true ) ?: '—' );
    elseif ( 'drb_consent' === $column ) echo get_post_meta( $id, '_drb_consent_verified', true ) ? '<span class="drb-badge drb-badge--ok">تأیید</span>' : '<span class="drb-badge drb-badge--blocked">مسدود</span>';
    elseif ( 'drb_review' === $column ) echo esc_html( get_post_meta( $id, '_drb_reviewed_at', true ) ?: 'انجام نشده' );
}
add_action( 'manage_case_study_posts_custom_column', 'drb_case_admin_column', 10, 2 );

function drb_case_row_actions( $actions, $post ) {
    if ( 'case_study' === $post->post_type && ! get_post_meta( $post->ID, '_drb_consent_verified', true ) ) unset( $actions['view'] );
    return $actions;
}
add_filter( 'post_row_actions', 'drb_case_row_actions', 10, 2 );

function drb_case_prevent_unsafe_publish( $data, $postarr ) {
    if ( 'case_study' !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) return $data;
    $id = absint( $postarr['ID'] ?? 0 );
    $consent = isset( $_POST['_drb_consent_verified'] ) || ( $id && get_post_meta( $id, '_drb_consent_verified', true ) );
    $posted_gallery = isset( $_POST['_drb_case_gallery'] ) ? json_decode( wp_unslash( $_POST['_drb_case_gallery'] ), true ) : null;
    $gallery = is_array( $posted_gallery ) ? $posted_gallery : ( $id && function_exists( 'drb_case_gallery_meta' ) ? drb_case_gallery_meta( $id ) : array() );
    $unsafe_image = static function( $attachment_id ) {
        if ( ! $attachment_id || get_post_meta( $attachment_id, '_drb_gallery_quarantined', true ) ) return true;
        $source = (string) get_post_meta( $attachment_id, '_drb_dist6_source', true );
        $file = (string) get_attached_file( $attachment_id );
        $title = (string) get_the_title( $attachment_id );
        return (bool) preg_match( '#(?:/|\\\\)Doctor(?:/|\\\\)|استاد|professor|faculty#iu', $source . ' ' . $file . ' ' . $title );
    };
    $safe_gallery = ! empty( $gallery );
    foreach ( (array) $gallery as $item ) if ( $unsafe_image( absint( $item['id'] ?? 0 ) ) ) { $safe_gallery = false; break; }
    if ( ! $consent || ! $safe_gallery ) { $data['post_status'] = 'draft'; add_filter( 'redirect_post_location', function( $location ) { return add_query_arg( 'drb_case_blocked', '1', $location ); } ); }
    return $data;
}
add_filter( 'wp_insert_post_data', 'drb_case_prevent_unsafe_publish', 10, 2 );

function drb_case_blocked_notice() { if ( isset( $_GET['drb_case_blocked'] ) ) echo '<div class="notice notice-error"><p>انتشار مسدود شد: دست‌کم یک تصویر بازبینی‌شده در گالری مراجعه، تأیید رضایت معتبر و نبودن تصویر پزشک/استاد الزامی است. نمونه به‌صورت پیش‌نویس ذخیره شد.</p></div>'; }
add_action( 'admin_notices', 'drb_case_blocked_notice' );
