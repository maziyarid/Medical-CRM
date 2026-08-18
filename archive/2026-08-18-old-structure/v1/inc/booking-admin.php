<?php
defined( 'ABSPATH' ) || exit;

function drb_booking_admin_menu() {
    add_menu_page(
        'نوبت و پیامک', 'نوبت و پیامک', 'manage_options', 'drb-booking-sms',
        'drb_booking_admin_page', 'dashicons-calendar-alt', 58
    );
}
add_action( 'admin_menu', 'drb_booking_admin_menu' );

function drb_booking_register_settings() {
    register_setting( 'drb_booking_sms', 'drb_booking_sms_template', array(
        'type' => 'string',
        'sanitize_callback' => static function( $value ) { return mb_substr( sanitize_textarea_field( $value ), 0, 800 ); },
        'default' => 'درخواست نوبت شما ثبت شد. همکاران کلینیک برای هماهنگی تماس می‌گیرند. پنل بیمار: {login_url}',
    ) );
    register_setting( 'drb_booking_sms', 'drb_booking_cooldown_minutes', array(
        'type' => 'integer',
        'sanitize_callback' => static function( $value ) { return max( 1, min( 1440, absint( $value ) ) ); },
        'default' => 30,
    ) );
}
add_action( 'admin_init', 'drb_booking_register_settings' );

function drb_booking_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $stats = drb_dashboard_booking_stats();
    $bridge_ok = ! is_wp_error( $stats );
    ?>
    <div class="wrap" dir="rtl">
      <h1>نوبت و پیامک</h1>
      <p>متن تأیید و محدودیت ثبت نوبت در وردپرس قابل تنظیم است. نام کاربری، رمز یا API Key پیامک در وردپرس ذخیره نمی‌شود و فقط در محیط امن <code>dashboard.drbastaninejad.com</code> قرار می‌گیرد.</p>
      <div class="notice <?php echo $bridge_ok ? 'notice-success' : 'notice-error'; ?> inline"><p>
        <?php if ( $bridge_ok ) : ?>اتصال امن وردپرس به داشبورد برقرار است.<?php else : ?>اتصال داشبورد برقرار نیست: <?php echo esc_html( $stats->get_error_message() ); ?><?php endif; ?>
      </p></div>
      <table class="widefat striped" style="max-width:900px;margin:16px 0"><tbody>
        <tr><th style="width:280px">Dashboard API</th><td><code><?php echo esc_html( drb_dashboard_api_base() ); ?></code></td></tr>
        <tr><th>Bridge secret</th><td><?php echo '' !== drb_booking_bridge_secret() ? 'تنظیم شده ✓' : 'تنظیم نشده ✗'; ?></td></tr>
        <tr><th>TLS verification</th><td>فعال و اجباری ✓</td></tr>
        <tr><th>Booking timeout</th><td><?php echo esc_html( drb_booking_bridge_timeout() ); ?> ثانیه</td></tr>
        <?php if ( ! $bridge_ok ) :
          $edata = $stats->get_error_data();
          $support = is_array( $edata ) ? ( $edata['supportCode'] ?? '' ) : '';
          $technical = is_array( $edata ) ? ( $edata['technicalMessage'] ?? '' ) : '';
        ?>
        <tr><th>کد تشخیص</th><td><code><?php echo esc_html( $support ?: $stats->get_error_code() ); ?></code></td></tr>
        <?php if ( $technical ) : ?><tr><th>خطای فنی وردپرس</th><td><code style="white-space:pre-wrap"><?php echo esc_html( $technical ); ?></code></td></tr><?php endif; ?>
        <?php endif; ?>
      </tbody></table>
      <?php if ( $bridge_ok ) : ?>
      <table class="widefat striped" style="max-width:760px;margin:16px 0"><tbody>
        <tr><th>کل درخواست‌های نوبت در CRM</th><td><?php echo esc_html( $stats['total'] ?? 0 ); ?></td></tr>
        <tr><th>۷ روز اخیر</th><td><?php echo esc_html( $stats['last_7_days'] ?? 0 ); ?></td></tr>
        <tr><th>در انتظار بررسی</th><td><?php echo esc_html( $stats['pending'] ?? 0 ); ?></td></tr>
        <tr><th>پیامک موفق</th><td><?php echo esc_html( $stats['sms_sent'] ?? 0 ); ?></td></tr>
        <tr><th>پیامک ناموفق</th><td><?php echo esc_html( $stats['sms_failed'] ?? 0 ); ?></td></tr>
      </tbody></table>
      <?php endif; ?>
      <form method="post" action="options.php" style="max-width:760px">
        <?php settings_fields( 'drb_booking_sms' ); ?>
        <table class="form-table"><tbody>
          <tr><th scope="row"><label for="drb_booking_sms_template">متن پیامک تأیید نوبت</label></th><td>
            <textarea class="large-text" rows="5" maxlength="800" id="drb_booking_sms_template" name="drb_booking_sms_template"><?php echo esc_textarea( drb_booking_sms_template() ); ?></textarea>
            <p class="description">توکن‌های مجاز: <code>{name}</code> و <code>{login_url}</code>. لینک ورود از تنظیمات امن داشبورد جایگزین می‌شود.</p>
          </td></tr>
          <tr><th scope="row"><label for="drb_booking_cooldown_minutes">فاصله ثبت تکراری همان موبایل</label></th><td>
            <input type="number" min="1" max="1440" id="drb_booking_cooldown_minutes" name="drb_booking_cooldown_minutes" value="<?php echo esc_attr( drb_booking_cooldown_minutes() ); ?>"> دقیقه
          </td></tr>
        </tbody></table>
        <?php submit_button( 'ذخیره تنظیمات' ); ?>
      </form>
      <h2>تنظیمات سرور و TSMS</h2>
      <p>در <code>wp-config.php</code> وردپرس فقط <code>DRB_WORDPRESS_BRIDGE_SECRET</code> قرار می‌گیرد و باید دقیقاً با <code>WORDPRESS_BRIDGE_SECRET</code> در <code>.env</code> داشبورد یکسان باشد.</p>
      <p><strong>TSMS داخل وردپرس تنظیم نمی‌شود.</strong> کلید/نام کاربری یا رمز TSMS، خط/فرستنده، URL provider و انتخاب provider باید فقط در <code>.env</code> داشبورد و با همان نام متغیرهایی که <code>SmsProviderChain</code> می‌خواند تنظیم شوند. این صفحه عمداً مقدار هیچ secret پیامکی را نمایش نمی‌دهد.</p>
      <p>اگر اتصال داشبورد در جدول بالا سبز است ولی «پیامک ناموفق» افزایش پیدا می‌کند، ثبت نوبت سالم است و مشکل فقط از تنظیمات TSMS/SmsProviderChain است. اگر اتصال قرمز است، ابتدا TLS/DNS/bridge را رفع کنید؛ TSMS هنوز وارد مسیر اجرا نشده است.</p>
    </div>
    <?php
}
