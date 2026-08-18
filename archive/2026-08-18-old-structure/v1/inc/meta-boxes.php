<?php

defined( 'ABSPATH' ) || exit;

function drb_add_content_meta_boxes() {
    add_meta_box( 'drb-page-elements', 'المان‌های صفحه', 'drb_render_page_elements', 'page', 'normal', 'high' );
    add_meta_box( 'drb-service-elements', 'اطلاعات تخصصی خدمت', 'drb_render_service_elements', 'service', 'normal', 'high' );
    add_meta_box( 'drb-case-elements', 'تصاویر و مجوز انتشار', 'drb_render_case_elements', 'case_study', 'normal', 'high' );
    add_meta_box( 'drb-faq-elements', 'دسته‌بندی سؤال', 'drb_render_faq_elements', 'clinic_faq', 'side', 'default' );
    add_meta_box( 'drb-stat-elements', 'مقدار آمار', 'drb_render_stat_elements', 'clinic_stat', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'drb_add_content_meta_boxes' );

function drb_meta_field( WP_Post $post, $key, $label, $type = 'text', $description = '' ) {
    $value = get_post_meta( $post->ID, $key, true );
    echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label></p>';
    if ( 'textarea' === $type ) {
        echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" rows="5" class="widefat">' . esc_textarea( $value ) . '</textarea>';
    } elseif ( 'checkbox' === $type ) {
        echo '<label><input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="checkbox" value="1" ' . checked( (bool) $value, true, false ) . '> ' . esc_html( $description ) . '</label>';
        return;
    } else {
        echo '<input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ) . '" class="widefat">';
    }
    if ( $description ) {
        echo '<p class="description">' . esc_html( $description ) . '</p>';
    }
}

function drb_render_page_elements( WP_Post $post ) {
    wp_nonce_field( 'drb_save_elements', 'drb_elements_nonce' );
    drb_meta_field( $post, '_drb_hero_eyebrow', 'برچسب بالای عنوان' );
    drb_meta_field( $post, '_drb_hero_title', 'عنوان اصلی Hero' );
    drb_meta_field( $post, '_drb_hero_description', 'توضیح Hero', 'textarea' );
    echo '<hr><div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">';
    echo '<div>'; drb_meta_field( $post, '_drb_primary_cta_text', 'متن دکمه اصلی' ); drb_meta_field( $post, '_drb_primary_cta_url', 'لینک دکمه اصلی', 'url' ); echo '</div>';
    echo '<div>'; drb_meta_field( $post, '_drb_secondary_cta_text', 'متن دکمه دوم' ); drb_meta_field( $post, '_drb_secondary_cta_url', 'لینک دکمه دوم', 'url' ); echo '</div></div><hr>';
    drb_meta_field( $post, '_drb_intro_title', 'عنوان بخش معرفی' );
    drb_meta_field( $post, '_drb_intro_content', 'متن بخش معرفی', 'textarea' );
}

function drb_render_service_elements( WP_Post $post ) {
    wp_nonce_field( 'drb_save_elements', 'drb_elements_nonce' );
    drb_meta_field( $post, '_drb_service_category', 'برچسب دسته خدمت' );
    drb_meta_field( $post, '_drb_service_lead', 'توضیح کوتاه Hero', 'textarea' );
    drb_meta_field( $post, '_drb_service_takeaways', 'نکات کلیدی', 'textarea', 'هر مورد را در یک خط وارد کنید.' );
    drb_meta_field( $post, '_drb_service_steps', 'مراحل انجام', 'textarea', 'هر مرحله را در یک خط وارد کنید.' );
    drb_meta_field( $post, '_drb_service_faqs', 'سؤالات خدمت', 'textarea', 'هر خط: سؤال | پاسخ' );
}

function drb_render_case_elements( WP_Post $post ) {
    wp_nonce_field( 'drb_save_elements', 'drb_elements_nonce' );
    $gallery = drb_case_gallery_meta( $post->ID );
    echo '<div class="notice notice-info inline"><p><strong>حریم خصوصی:</strong> فقط تصاویر دارای رضایت مکتوب معتبر، تطبیق هویت و بازبینی پزشک بارگذاری شوند. نام یا اطلاعات شناسایی بیمار را در عنوان عمومی، کپشن یا نام فایل وارد نکنید.</p></div>';
    echo '<div class="drb-case-gallery-editor">';
    echo '<input type="hidden" class="drb-case-gallery-json" name="_drb_case_gallery" value="' . esc_attr( wp_json_encode( $gallery ) ) . '">';
    echo '<div class="drb-case-gallery-toolbar"><div><strong>نماهای این مراجعه</strong><p class="description">همه تصاویر متعلق به یک بیمار و یک مراجعه را با هم انتخاب کنید؛ ترتیب و عنوان هر نما قابل ویرایش است.</p></div><button type="button" class="button button-primary drb-select-case-gallery">انتخاب چند تصویر</button></div>';
    echo '<div class="drb-case-gallery-rows">';
    foreach ( $gallery as $item ) drb_render_case_gallery_row( $item );
    echo '</div><p class="drb-case-gallery-empty"' . ( $gallery ? ' hidden' : '' ) . '>هنوز تصویری برای این مراجعه انتخاب نشده است.</p></div><hr>';
    drb_meta_field( $post, '_drb_case_label', 'برچسب نمونه' );
    drb_meta_field( $post, '_drb_case_procedure', 'نوع جراحی' );
    drb_meta_field( $post, '_drb_case_nose_type', 'نوع بینی' );
    drb_meta_field( $post, '_drb_case_patient_gender', 'جنسیت بیمار' );
    drb_meta_field( $post, '_drb_case_interval', 'فاصله زمانی تصویر بعد از جراحی', 'text', 'مثال: ۱۲ ماه پس از جراحی' );
    drb_meta_field( $post, '_drb_consent_reference', 'کد مرجع رضایت‌نامه', 'text', 'کد داخلی؛ فایل رضایت‌نامه را عمومی بارگذاری نکنید.' );
    drb_meta_field( $post, '_drb_reviewed_at', 'تاریخ بازبینی', 'date' );
    drb_meta_field( $post, '_drb_consent_verified', 'رضایت انتشار', 'checkbox', 'تطبیق تصاویر و رضایت معتبر انتشار تأیید شده است.' );
    echo '<p class="description" style="color:#b32d2e">تا زمانی که این گزینه فعال نباشد، نمونه در فرانت‌اند و sitemap منتشر نمی‌شود.</p>';
}

function drb_case_gallery_meta( $post_id ) {
    $value = get_post_meta( $post_id, '_drb_case_gallery', true );
    $items = is_array( $value ) ? $value : json_decode( (string) $value, true );
    if ( ! is_array( $items ) ) return array();
    $clean = array();
    foreach ( $items as $item ) {
        $id = absint( $item['id'] ?? 0 );
        if ( ! $id ) continue;
        $clean[] = array(
            'id' => $id,
            'label' => sanitize_text_field( $item['label'] ?? '' ),
            'interval' => sanitize_text_field( $item['interval'] ?? '' ),
        );
    }
    return $clean;
}

function drb_render_case_gallery_row( $item ) {
    $id = absint( $item['id'] ?? 0 );
    $src = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
    echo '<div class="drb-case-gallery-row" data-id="' . esc_attr( $id ) . '">';
    echo '<span class="drb-case-gallery-handle" aria-hidden="true">⋮⋮</span>';
    echo $src ? '<img src="' . esc_url( $src ) . '" alt="">' : '<span class="drb-case-gallery-thumb">تصویر</span>';
    echo '<input type="text" class="drb-case-gallery-label" value="' . esc_attr( $item['label'] ?? '' ) . '" placeholder="نام نما؛ مثال: نمای روبه‌رو" aria-label="نام نما">';
    echo '<input type="text" class="drb-case-gallery-interval" value="' . esc_attr( $item['interval'] ?? '' ) . '" placeholder="فاصله زمانی؛ در صورت تفاوت" aria-label="فاصله زمانی تصویر">';
    echo '<div class="drb-case-gallery-actions"><button type="button" class="button-link drb-case-gallery-up" aria-label="انتقال به بالا">↑</button><button type="button" class="button-link drb-case-gallery-down" aria-label="انتقال به پایین">↓</button><button type="button" class="button-link-delete drb-case-gallery-remove">حذف</button></div></div>';
}

function drb_media_field( WP_Post $post, $key, $label ) {
    $id = absint( get_post_meta( $post->ID, $key, true ) );
    echo '<div><p><strong>' . esc_html( $label ) . '</strong></p><div class="drb-media-field"><div class="drb-media-preview">';
    echo $id ? wp_get_attachment_image( $id, 'medium' ) : '<span>بدون تصویر</span>';
    echo '</div><div><input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $id ) . '"><p><button type="button" class="button drb-select-media">انتخاب از کتابخانه</button></p><p><button type="button" class="button-link-delete drb-remove-media" ' . disabled( $id, 0, false ) . '>حذف انتخاب</button></p></div></div></div>';
}

function drb_render_faq_elements( WP_Post $post ) {
    wp_nonce_field( 'drb_save_elements', 'drb_elements_nonce' );
    drb_meta_field( $post, '_drb_faq_category', 'دسته سؤال' );
}

function drb_render_stat_elements( WP_Post $post ) {
    wp_nonce_field( 'drb_save_elements', 'drb_elements_nonce' );
    drb_meta_field( $post, '_drb_stat_value', 'مقدار نمایشی', 'text', 'مثال: +۱۸ یا ۹۸٪. توضیح تکمیلی را در ویرایشگر اصلی بنویسید.' );
}

function drb_save_content_elements( $post_id ) {
    if ( ! isset( $_POST['drb_elements_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['drb_elements_nonce'] ) ), 'drb_save_elements' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['_drb_case_gallery'] ) ) {
        $raw_gallery = json_decode( wp_unslash( $_POST['_drb_case_gallery'] ), true );
        $gallery = array();
        foreach ( is_array( $raw_gallery ) ? $raw_gallery : array() as $item ) {
            $id = absint( $item['id'] ?? 0 );
            if ( ! $id || 'attachment' !== get_post_type( $id ) ) continue;
            $gallery[] = array(
                'id' => $id,
                'label' => sanitize_text_field( $item['label'] ?? '' ),
                'interval' => sanitize_text_field( $item['interval'] ?? '' ),
            );
        }
        update_post_meta( $post_id, '_drb_case_gallery', wp_json_encode( $gallery ) );
    }

    $textarea_fields = array( '_drb_hero_description', '_drb_intro_content', '_drb_service_lead', '_drb_service_takeaways', '_drb_service_steps', '_drb_service_faqs' );
    $url_fields = array( '_drb_primary_cta_url', '_drb_secondary_cta_url' );
    $integer_fields = array( '_drb_before_image_id', '_drb_after_image_id' );
    $all_fields = array(
        '_drb_hero_eyebrow', '_drb_hero_title', '_drb_hero_description', '_drb_primary_cta_text', '_drb_primary_cta_url',
        '_drb_secondary_cta_text', '_drb_secondary_cta_url', '_drb_intro_title', '_drb_intro_content', '_drb_service_category',
        '_drb_service_lead', '_drb_service_takeaways', '_drb_service_steps', '_drb_service_faqs', '_drb_before_image_id',
        '_drb_after_image_id', '_drb_case_label', '_drb_case_procedure', '_drb_case_nose_type', '_drb_case_patient_gender',
        '_drb_case_interval', '_drb_consent_reference', '_drb_reviewed_at', '_drb_faq_category', '_drb_stat_value',
    );
    foreach ( $all_fields as $field ) {
        if ( ! isset( $_POST[ $field ] ) ) continue;
        $raw = wp_unslash( $_POST[ $field ] );
        $value = in_array( $field, $url_fields, true ) ? esc_url_raw( $raw ) : ( in_array( $field, $integer_fields, true ) ? absint( $raw ) : ( in_array( $field, $textarea_fields, true ) ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw ) ) );
        update_post_meta( $post_id, $field, $value );
    }
    update_post_meta( $post_id, '_drb_consent_verified', isset( $_POST['_drb_consent_verified'] ) ? 1 : 0 );
}
add_action( 'save_post', 'drb_save_content_elements' );
