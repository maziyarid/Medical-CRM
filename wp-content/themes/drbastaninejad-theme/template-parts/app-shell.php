<?php
defined( 'ABSPATH' ) || exit;
$drb_is_booking = is_page( 'booking' ) || 'booking' === get_post_field( 'post_name', get_queried_object_id() );
$drb_is_contact = is_page( 'contact' ) || 'contact' === get_post_field( 'post_name', get_queried_object_id() );
?>
<?php
$drb_server_title = get_the_title() ?: get_bloginfo( 'name' );
if ( is_front_page() ) {
    $drb_server_title = 'دکتر شاهین باستانی‌نژاد؛ جراحی بینی و خدمات گوش، حلق و بینی';
} elseif ( function_exists( 'drb_detect_lang' ) && 'fa' !== drb_detect_lang() && function_exists( 'drb_localized_page' ) ) {
    $drb_local_page = drb_localized_page();
    if ( ! empty( $drb_local_page['title'] ) ) $drb_server_title = (string) $drb_local_page['title'];
}
?>
<div id="root">
    <main class="drb-app-prerender" data-drb-prerender aria-label="<?php echo esc_attr( $drb_server_title ); ?>">
        <h1><?php echo esc_html( $drb_server_title ); ?></h1>
    </main>
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
<?php if ( function_exists( 'drb_detect_lang' ) && 'fa' !== drb_detect_lang() && function_exists( 'drb_is_clean_localized_route' ) && drb_is_clean_localized_route() ) :
    $drb_local_nav_items = array(
        'home'              => __t( 'nav_home' ),
        'about'             => __t( 'nav_about' ),
        'gallery'           => __t( 'nav_gallery' ),
        'articles'          => __t( 'nav_blog' ),
        'after-splint-care' => (string) ( drb_localized_page( 'after-splint-care' )['title'] ?? 'After splint care' ),
        'faq'               => __t( 'nav_faq' ),
        'contact'           => __t( 'nav_contact' ),
        'booking'           => __t( 'nav_booking' ),
        'privacy'           => __t( 'privacy' ),
    );
?>
<nav class="drb-localized-server-nav" aria-label="<?php echo esc_attr( __t( 'nav_primary_label' ) ); ?>">
    <div class="drb-localized-server-nav__inner">
        <?php foreach ( $drb_local_nav_items as $drb_local_nav_key => $drb_local_nav_label ) : ?>
            <a href="<?php echo esc_url( drb_localized_url( $drb_local_nav_key, drb_detect_lang() ) ); ?>"<?php echo drb_current_localized_key() === $drb_local_nav_key ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $drb_local_nav_label ); ?></a>
        <?php endforeach; ?>
    </div>
</nav>
<?php endif; ?>
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
        <?php if ( $drb_is_contact ) : ?>
        <p><label for="drb-ns-email"><?php echo esc_html( drb_translate_phrase( 'ایمیل' ) ); ?></label><br>
        <input id="drb-ns-email" name="email" type="email" required autocomplete="email" inputmode="email"></p>
        <?php endif; ?>
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