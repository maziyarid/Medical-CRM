<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$drb_header_options = function_exists( 'drb_get_theme_options' ) ? drb_get_theme_options() : array();
$drb_lang = function_exists( 'drb_detect_lang' ) ? drb_detect_lang() : 'fa';
$drb_home = function_exists( 'drb_nav_url' ) ? drb_nav_url( 'home' ) : home_url( '/' );
$drb_phone_raw = '';
if ( ! empty( $drb_header_options['phones'] ) ) {
    $drb_phone_rows = array_values( array_filter( preg_split( '/\R+/', (string) $drb_header_options['phones'] ) ) );
    $drb_phone_raw = isset( $drb_phone_rows[0] ) ? trim( (string) $drb_phone_rows[0] ) : '';
}
$drb_phone_display = function_exists( 'drb_format_iran_phone' ) ? drb_format_iran_phone( $drb_phone_raw, $drb_lang ) : $drb_phone_raw;
$drb_phone_href = function_exists( 'drb_format_iran_phone' ) ? drb_format_iran_phone( $drb_phone_raw, $drb_lang, true ) : 'tel:' . preg_replace( '/\D+/', '', $drb_phone_raw );
$drb_route = static function ( $path ) use ( $drb_lang ) {
    return function_exists( 'drb_route_url' ) ? drb_route_url( $path, $drb_lang ) : home_url( $path );
};
?>
<?php if ( function_exists( 'drb_use_native_template' ) && drb_use_native_template() ) : ?>
<header class="drb-native-header" role="banner">
  <div class="drb-native-header__inner">
    <a class="drb-native-brand" href="<?php echo esc_url( $drb_home ); ?>">
      <img src="<?php echo esc_url( DRB_THEME_URI . '/assets/dist6/logo.png' ); ?>" alt="<?php echo esc_attr( function_exists( '__t' ) ? __t( 'site_name' ) : get_bloginfo( 'name' ) ); ?>" width="36" height="36">
      <span><strong><?php echo esc_html( function_exists( '__t' ) ? __t( 'site_name' ) : get_bloginfo( 'name' ) ); ?></strong><small><?php echo esc_html( function_exists( '__t' ) ? __t( 'site_tagline' ) : get_bloginfo( 'description' ) ); ?></small></span>
    </a>

    <nav class="drb-native-desktop-nav" aria-label="<?php echo esc_attr( function_exists( '__t' ) ? __t( 'nav_primary_label' ) : 'Primary navigation' ); ?>">
      <a href="<?php echo esc_url( $drb_home ); ?>"><?php echo esc_html( __t( 'nav_home' ) ); ?></a>
      <a href="<?php echo esc_url( drb_nav_url( 'about' ) ); ?>"><?php echo esc_html( __t( 'nav_about' ) ); ?></a>
      <details class="drb-native-services-menu">
        <summary><?php echo esc_html( __t( 'nav_services' ) ); ?></summary>
        <div>
          <a href="<?php echo esc_url( $drb_route( '/services/rhinoplasty-primary/' ) ); ?>"><?php echo esc_html( __t( 'svc_rhinoplasty_primary' ) ); ?></a>
          <a href="<?php echo esc_url( $drb_route( '/services/rhinoplasty-revision/' ) ); ?>"><?php echo esc_html( __t( 'svc_rhinoplasty_revision' ) ); ?></a>
          <a href="<?php echo esc_url( $drb_route( '/services/rhinoplasty-bony/' ) ); ?>"><?php echo esc_html( __t( 'svc_rhinoplasty_bony' ) ); ?></a>
          <a href="<?php echo esc_url( $drb_route( '/services/rhinoplasty-natural/' ) ); ?>"><?php echo esc_html( __t( 'svc_rhinoplasty_natural' ) ); ?></a>
          <a href="<?php echo esc_url( $drb_route( '/services/hump-removal/' ) ); ?>"><?php echo esc_html( __t( 'svc_hump_removal' ) ); ?></a>
          <a href="<?php echo esc_url( $drb_route( '/services/septoplasty/' ) ); ?>"><?php echo esc_html( __t( 'svc_septoplasty' ) ); ?></a>
          <a href="<?php echo esc_url( $drb_route( '/services/turbinoplasty/' ) ); ?>"><?php echo esc_html( __t( 'svc_turbinoplasty' ) ); ?></a>
          <a href="<?php echo esc_url( $drb_route( '/services/sinus-endoscopy/' ) ); ?>"><?php echo esc_html( __t( 'svc_sinus_endoscopy' ) ); ?></a>
          <a href="<?php echo esc_url( $drb_route( '/services/' ) ); ?>"><?php echo esc_html( __t( 'cta_view_all' ) ); ?></a>
        </div>
      </details>
      <a href="<?php echo esc_url( drb_nav_url( 'gallery' ) ); ?>"><?php echo esc_html( __t( 'nav_gallery' ) ); ?></a>
      <a href="<?php echo esc_url( drb_nav_url( 'faq' ) ); ?>"><?php echo esc_html( __t( 'nav_faq' ) ); ?></a>
      <a href="<?php echo esc_url( drb_nav_url( 'articles' ) ); ?>"><?php echo esc_html( __t( 'nav_blog' ) ); ?></a>
      <a href="<?php echo esc_url( drb_nav_url( 'contact' ) ); ?>"><?php echo esc_html( __t( 'nav_contact' ) ); ?></a>
    </nav>

    <div class="drb-native-header__actions">
      <?php if ( $drb_phone_raw ) : ?><a class="drb-native-header__phone drb-phone-intl" href="<?php echo esc_url( $drb_phone_href ); ?>"><?php echo esc_html( $drb_phone_display ); ?></a><?php endif; ?>
      <a class="drb-native-header__booking" href="<?php echo esc_url( drb_nav_url( 'booking' ) ); ?>"><?php echo esc_html( __t( 'nav_booking' ) ); ?></a>
      <?php if ( function_exists( 'drb_language_dropdown_html' ) ) : ?><span class="drb-native-header__lang"><?php echo drb_language_dropdown_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php endif; ?>
      <details class="drb-native-menu">
        <summary aria-label="<?php echo esc_attr( __t( 'nav_primary_label' ) ); ?>">☰</summary>
        <div class="drb-native-menu__panel">
          <a href="<?php echo esc_url( $drb_home ); ?>"><?php echo esc_html( __t( 'nav_home' ) ); ?></a>
          <a href="<?php echo esc_url( drb_nav_url( 'about' ) ); ?>"><?php echo esc_html( __t( 'nav_about' ) ); ?></a>
          <strong><?php echo esc_html( __t( 'nav_services' ) ); ?></strong>
          <a href="<?php echo esc_url( $drb_route( '/services/' ) ); ?>"><?php echo esc_html( __t( 'services_title' ) ); ?></a>
          <a href="<?php echo esc_url( drb_nav_url( 'gallery' ) ); ?>"><?php echo esc_html( __t( 'nav_gallery' ) ); ?></a>
          <a href="<?php echo esc_url( drb_nav_url( 'faq' ) ); ?>"><?php echo esc_html( __t( 'nav_faq' ) ); ?></a>
          <a href="<?php echo esc_url( drb_nav_url( 'articles' ) ); ?>"><?php echo esc_html( __t( 'nav_blog' ) ); ?></a>
          <a href="<?php echo esc_url( drb_nav_url( 'contact' ) ); ?>"><?php echo esc_html( __t( 'nav_contact' ) ); ?></a>
          <a href="<?php echo esc_url( drb_nav_url( 'booking' ) ); ?>"><?php echo esc_html( __t( 'nav_booking' ) ); ?></a>
        </div>
      </details>
    </div>
  </div>
</header>
<?php endif; ?>
