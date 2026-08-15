<?php

defined( 'ABSPATH' ) || exit;

function drb_theme_option_defaults() {
    return array(
        'doctor_name' => 'دکتر شاهین باستانی‌نژاد',
        'doctor_title' => 'جراح و متخصص گوش، گلو و بینی؛ جراح پلاستیک بینی',
        'phones' => "۰۲۱۸۶۰۸۷۲۵۰\n۰۲۱۸۸۲۰۵۶۰۶\n۰۹۹۱۲۴۹۶۶۵۹",
        'address' => 'تهران، خیابان نلسون ماندلا، خیابان صانعی، ساختمان نور، پلاک ۱، واحد ۶',
        'clinic_hours' => 'شنبه تا سه‌شنبه، از ساعت ۱۵:۰۰ تا ۱۸:۰۰',
        'admission_notice' => 'پذیرش فقط در بازه اعلام‌شده انجام می‌شود و مراجعینی که بعد از ساعت ۱۸:۰۰ در مطب حضور یابند، به هیچ‌وجه پذیرش نخواهند شد.',
        'revision_notice' => 'بررسی جراحی ترمیمی فقط پس از گذشت کامل ۲۴ ماه (۲ سال) از جراحی قبلی انجام می‌شود.',
        'whatsapp' => 'https://wa.me/989912496659',
        'instagram' => 'https://www.instagram.com/dr.shahin.bastaninejad/',
        'telegram' => 'https://t.me/dr_bastaninejad',
        'youtube' => 'https://www.youtube.com/@Drshahinbastaninejad',
        'aparat' => 'https://www.aparat.com/Drshahinbastaninejad',
        'gallery_enabled' => 0,
        'default_social_image' => '',
        'google_analytics_enabled' => 1,
        'google_analytics_id' => 'G-7MMJ2J4TY7',
        'clarity_enabled' => 1,
        'clarity_id' => 'xzserqjwxc',
    );
}

function drb_get_theme_options() {
    return wp_parse_args( get_option( 'drb_theme_options', array() ), drb_theme_option_defaults() );
}

function drb_sanitize_theme_options( $input ) {
    $defaults = drb_theme_option_defaults();
    $output = array();
    foreach ( $defaults as $key => $default ) {
        $value = isset( $input[ $key ] ) ? $input[ $key ] : $default;
        if ( in_array( $key, array( 'whatsapp', 'instagram', 'telegram', 'youtube', 'aparat', 'default_social_image' ), true ) ) {
            $output[ $key ] = esc_url_raw( $value );
        } elseif ( in_array( $key, array( 'gallery_enabled', 'google_analytics_enabled', 'clarity_enabled' ), true ) ) {
            $output[ $key ] = empty( $value ) ? 0 : 1;
        } elseif ( 'google_analytics_id' === $key ) {
            $output[ $key ] = preg_match( '/^G-[A-Z0-9]+$/', (string) $value ) ? strtoupper( $value ) : '';
        } elseif ( 'clarity_id' === $key ) {
            $output[ $key ] = preg_match( '/^[a-z0-9]+$/', (string) $value ) ? strtolower( $value ) : '';
        } elseif ( in_array( $key, array( 'phones', 'address', 'admission_notice', 'revision_notice' ), true ) ) {
            $output[ $key ] = sanitize_textarea_field( $value );
        } else {
            $output[ $key ] = sanitize_text_field( $value );
        }
    }
    return $output;
}

function drb_register_theme_settings() {
    register_setting( 'drb_theme_settings', 'drb_theme_options', array( 'sanitize_callback' => 'drb_sanitize_theme_options', 'default' => drb_theme_option_defaults() ) );
}
add_action( 'admin_init', 'drb_register_theme_settings' );

function drb_register_theme_options_page() {
    add_theme_page( 'تنظیمات کلینیک', 'تنظیمات کلینیک', 'manage_options', 'drb-theme-options', 'drb_render_theme_options_page' );
}
add_action( 'admin_menu', 'drb_register_theme_options_page' );

function drb_render_theme_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $options = drb_get_theme_options();
    $fields = array(
        'doctor_name' => array( 'نام پزشک', 'text' ), 'doctor_title' => array( 'عنوان تخصصی', 'text' ),
        'phones' => array( 'شماره‌های تماس؛ هر شماره یک خط', 'textarea' ), 'address' => array( 'آدرس کامل', 'textarea' ),
        'clinic_hours' => array( 'ساعات پذیرش', 'text' ),
        'admission_notice' => array( 'قانون آخرین زمان پذیرش', 'textarea' ),
        'revision_notice' => array( 'قانون جراحی ترمیمی', 'textarea' ),
        'whatsapp' => array( 'واتساپ', 'url' ),
        'instagram' => array( 'اینستاگرام', 'url' ), 'telegram' => array( 'تلگرام', 'url' ),
        'youtube' => array( 'یوتیوب', 'url' ), 'aparat' => array( 'آپارات', 'url' ),
        'default_social_image' => array( 'تصویر پیش‌فرض شبکه‌های اجتماعی', 'url' ),
        'google_analytics_id' => array( 'شناسه Google Analytics', 'text' ),
        'clarity_id' => array( 'شناسه Microsoft Clarity', 'text' ),
    );
    ?>
    <div class="wrap" dir="rtl">
        <h1>تنظیمات کلینیک و اطلاعات عمومی</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'drb_theme_settings' ); ?>
            <table class="form-table" role="presentation">
                <?php foreach ( $fields as $key => $field ) : ?>
                    <tr>
                        <th scope="row"><label for="drb-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
                        <td>
                            <?php if ( 'textarea' === $field[1] ) : ?>
                                <textarea id="drb-<?php echo esc_attr( $key ); ?>" name="drb_theme_options[<?php echo esc_attr( $key ); ?>]" rows="4" class="large-text"><?php echo esc_textarea( $options[ $key ] ); ?></textarea>
                            <?php else : ?>
                                <input id="drb-<?php echo esc_attr( $key ); ?>" type="<?php echo esc_attr( $field[1] ); ?>" name="drb_theme_options[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $options[ $key ] ); ?>" class="large-text" <?php echo 'url' === $field[1] ? 'dir="ltr"' : ''; ?>>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th scope="row">انتشار گالری</th>
                    <td>
                        <input type="hidden" name="drb_theme_options[gallery_enabled]" value="0">
                        <label><input type="checkbox" name="drb_theme_options[gallery_enabled]" value="1" <?php checked( $options['gallery_enabled'], 1 ); ?>> گالری تأییدشده در سایت نمایش داده شود.</label>
                        <p class="description">هر نمونه علاوه بر این گزینه، باید در نوشته نمونه‌کار نیز رضایت انتشار تأییدشده داشته باشد.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">ابزارهای تحلیل ترافیک</th>
                    <td>
                        <input type="hidden" name="drb_theme_options[google_analytics_enabled]" value="0">
                        <label><input type="checkbox" name="drb_theme_options[google_analytics_enabled]" value="1" <?php checked( $options['google_analytics_enabled'], 1 ); ?>> Google Analytics فعال باشد.</label><br>
                        <input type="hidden" name="drb_theme_options[clarity_enabled]" value="0">
                        <label><input type="checkbox" name="drb_theme_options[clarity_enabled]" value="1" <?php checked( $options['clarity_enabled'], 1 ); ?>> Microsoft Clarity فعال باشد.</label>
                        <p class="description">شناسه‌ها از فیلدهای بالا قابل ویرایش‌اند. در صورت استفاده از سامانه مدیریت رضایت کوکی، این گزینه‌ها را تا زمان اخذ رضایت غیرفعال نگه دارید.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php if ( current_user_can( 'activate_plugins' ) ) : ?>
            <p><a class="button" href="<?php echo esc_url( admin_url( 'options-general.php?page=drb-forms' ) ); ?>">مدیریت ایمیل‌های دریافت‌کننده فرم‌ها</a></p>
        <?php endif; ?>
    </div>
    <?php
}
