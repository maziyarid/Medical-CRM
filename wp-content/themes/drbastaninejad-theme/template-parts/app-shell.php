<?php
defined( 'ABSPATH' ) || exit;
$drb_is_booking = is_page( 'booking' ) || 'booking' === get_post_field( 'post_name', get_queried_object_id() );
$drb_is_contact = is_page( 'contact' ) || 'contact' === get_post_field( 'post_name', get_queried_object_id() );
?>
<div id="root">
    <?php if ( ! drb_app_asset_is_ready() ) : ?>
        <main style="max-width:800px;margin:80px auto;padding:24px;font-family:Tahoma,sans-serif" dir="<?php echo esc_attr( drb_is_rtl() ? 'rtl' : 'ltr' ); ?>">
            <h1><?php echo esc_html( get_the_title() ?: get_bloginfo( 'name' ) ); ?></h1>
            <?php if ( current_user_can( 'manage_options' ) ) : ?>
                <p><?php echo esc_html( drb_translate_phrase( 'فایل‌های Build رابط کاربری در پوشه assets/app پیدا نشدند.' ) ); ?></p>
            <?php else : ?>
                <?php the_content(); ?>
            <?php endif; ?>
        </main>
    <?php endif; ?>
</div>
<noscript>
  <div style="max-width:640px;margin:40px auto;padding:20px;font-family:Tahoma,sans-serif" dir="<?php echo esc_attr( drb_is_rtl() ? 'rtl' : 'ltr' ); ?>">
    <p><?php echo esc_html( drb_translate_phrase( 'برای استفاده کامل از سایت، JavaScript مرورگر را فعال کنید.' ) ); ?></p>
    <?php if ( $drb_is_booking || $drb_is_contact ) : ?>
      <form method="post" action="<?php echo esc_url( rest_url( $drb_is_booking ? 'drb/v1/appointment' : 'drb/v1/contact' ) ); ?>">
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'drb_public_form' ) ); ?>">
        <p><label for="drb-ns-name"><?php echo esc_html( drb_translate_phrase( 'نام و نام خانوادگی' ) ); ?></label><br>
        <input id="drb-ns-name" name="name" required maxlength="200" autocomplete="name"></p>
        <p><label for="drb-ns-phone"><?php echo esc_html( drb_translate_phrase( 'شماره موبایل' ) ); ?></label><br>
        <input id="drb-ns-phone" name="phone" required inputmode="tel" autocomplete="tel"></p>
        <?php if ( $drb_is_booking ) : ?>
        <p><label for="drb-ns-age"><?php echo esc_html( drb_translate_phrase( 'سن' ) ); ?></label><br>
        <input id="drb-ns-age" name="age" required inputmode="numeric"></p>
        <p><label><input type="checkbox" name="eligibilityConfirmed" value="1" required> <?php echo esc_html( drb_translate_phrase( 'شرایط پذیرش را تأیید می‌کنم' ) ); ?></label></p>
        <?php else : ?>
        <p><label for="drb-ns-message"><?php echo esc_html( drb_translate_phrase( 'پیام' ) ); ?></label><br>
        <textarea id="drb-ns-message" name="message" rows="4"></textarea></p>
        <?php endif; ?>
        <button type="submit"><?php echo esc_html( drb_translate_phrase( 'ارسال' ) ); ?></button>
      </form>
    <?php endif; ?>
  </div>
</noscript>
