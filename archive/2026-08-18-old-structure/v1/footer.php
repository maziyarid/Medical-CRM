<?php if ( function_exists( 'drb_use_native_template' ) && drb_use_native_template() ) :
$drb_footer_options = drb_get_theme_options();
$drb_lang = function_exists( 'drb_detect_lang' ) ? drb_detect_lang() : 'fa';
$drb_t = static function ( $source ) use ( $drb_lang ) { return function_exists( 'drb_translate_phrase' ) ? drb_translate_phrase( $source, $drb_lang ) : $source; };
$drb_route = static function ( $path ) use ( $drb_lang ) { return function_exists( 'drb_route_url' ) ? drb_route_url( $path, $drb_lang ) : home_url( $path ); };
$drb_doctor = 'fa' === $drb_lang ? $drb_footer_options['doctor_name'] : __t( 'site_name' );
$drb_title = 'fa' === $drb_lang ? $drb_footer_options['doctor_title'] : __t( 'site_tagline' );
$drb_hours = 'fa' === $drb_lang ? $drb_footer_options['clinic_hours'] : $drb_t( $drb_footer_options['clinic_hours'] );
$drb_address = 'fa' === $drb_lang ? $drb_footer_options['address'] : $drb_t( $drb_footer_options['address'] );
?>
<footer class="drb-native-footer" role="contentinfo">
    <div class="drb-native-footer__inner">
        <div class="drb-native-footer__socials">
            <?php
            $drb_socials = array(
                'instagram' => 'Instagram', 'youtube' => 'YouTube', 'aparat' => 'Aparat', 'telegram' => 'Telegram',
            );
            foreach ( $drb_socials as $drb_social_key => $drb_social_label ) :
                if ( empty( $drb_footer_options[ $drb_social_key ] ) ) continue;
                $label = 'fa' === $drb_lang ? array( 'instagram'=>'اینستاگرام','youtube'=>'یوتیوب','aparat'=>'آپارات','telegram'=>'تلگرام' )[ $drb_social_key ] : $drb_social_label;
                ?><a href="<?php echo esc_url( $drb_footer_options[ $drb_social_key ] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $label ); ?></a><?php
            endforeach;
            ?>
        </div>
        <div class="drb-native-footer__grid">
            <section>
                <div class="drb-native-footer__brand"><img src="<?php echo esc_url( DRB_THEME_URI . '/assets/dist6/logo-white.svg' ); ?>" alt="<?php echo esc_attr( $drb_doctor ); ?>" width="36" height="36"><strong><?php echo esc_html( $drb_doctor ); ?></strong></div>
                <p><?php echo esc_html( $drb_title ); ?><br><?php echo esc_html( 'fa' === $drb_lang ? 'تهران، ایران' : __t( 'footer_address' ) ); ?></p>
                <div class="drb-native-footer__badges"><img src="<?php echo esc_url( DRB_THEME_URI . '/assets/dist6/images/enamad.webp' ); ?>" alt="<?php echo esc_attr( 'fa' === $drb_lang ? 'نماد اعتماد' : 'Trust seal' ); ?>" width="48" height="48"><img src="<?php echo esc_url( DRB_THEME_URI . '/assets/dist6/images/namad-n1.webp' ); ?>" alt="<?php echo esc_attr( 'fa' === $drb_lang ? 'نشان انجمن' : 'Association badge' ); ?>" width="48" height="48"></div>
            </section>
            <nav aria-label="<?php echo esc_attr( $drb_t( 'لینک‌های سریع' ) ); ?>"><h2><?php echo esc_html( $drb_t( 'لینک‌های سریع' ) ); ?></h2><a href="<?php echo esc_url( drb_nav_url( 'about' ) ); ?>"><?php echo esc_html( __t( 'nav_about' ) ); ?></a><a href="<?php echo esc_url( drb_nav_url( 'gallery' ) ); ?>"><?php echo esc_html( __t( 'nav_gallery' ) ); ?></a><a href="<?php echo esc_url( drb_nav_url( 'articles' ) ); ?>"><?php echo esc_html( __t( 'nav_blog' ) ); ?></a><a href="<?php echo esc_url( drb_nav_url( 'faq' ) ); ?>"><?php echo esc_html( __t( 'nav_faq' ) ); ?></a><a href="<?php echo esc_url( drb_nav_url( 'booking' ) ); ?>"><?php echo esc_html( __t( 'nav_booking' ) ); ?></a></nav>
            <nav aria-label="<?php echo esc_attr( __t( 'nav_services' ) ); ?>"><h2><?php echo esc_html( __t( 'nav_services' ) ); ?></h2><a href="<?php echo esc_url( $drb_route( '/services/rhinoplasty-primary/' ) ); ?>"><?php echo esc_html( __t( 'svc_rhinoplasty_primary' ) ); ?></a><a href="<?php echo esc_url( $drb_route( '/services/rhinoplasty-revision/' ) ); ?>"><?php echo esc_html( __t( 'svc_rhinoplasty_revision' ) ); ?></a><a href="<?php echo esc_url( $drb_route( '/services/rhinoplasty-bony/' ) ); ?>"><?php echo esc_html( __t( 'svc_rhinoplasty_bony' ) ); ?></a><a href="<?php echo esc_url( $drb_route( '/services/septoplasty/' ) ); ?>"><?php echo esc_html( __t( 'svc_septoplasty' ) ); ?></a><a href="<?php echo esc_url( $drb_route( '/services/' ) ); ?>"><?php echo esc_html( __t( 'cta_view_all' ) ); ?></a></nav>
            <section><h2><?php echo esc_html( __t( 'contact_title' ) ); ?></h2><small><?php echo esc_html( $drb_t( 'تلفن کلینیک' ) ); ?></small><?php foreach ( array_slice( array_values( array_filter( preg_split( '/\R+/', $drb_footer_options['phones'] ) ) ), 0, 2 ) as $drb_phone ) : $display = function_exists( 'drb_format_iran_phone' ) ? drb_format_iran_phone( trim( $drb_phone ), $drb_lang ) : trim( $drb_phone ); $href = function_exists( 'drb_format_iran_phone' ) ? drb_format_iran_phone( trim( $drb_phone ), $drb_lang, true ) : 'tel:' . preg_replace( '/\D+/', '', $drb_phone ); ?><a class="drb-native-footer__phone drb-phone-intl" href="<?php echo esc_url( $href ); ?>"><?php echo esc_html( $display ); ?></a><?php endforeach; ?><small><?php echo esc_html( $drb_t( 'ساعت پذیرش' ) ); ?></small><p><?php echo esc_html( $drb_hours ); ?></p><small><?php echo esc_html( $drb_t( 'آدرس' ) ); ?></small><p><?php echo esc_html( $drb_address ); ?></p><div class="drb-native-map-links" aria-label="<?php echo esc_attr( $drb_t( 'مسیریاب‌ها' ) ); ?>"><?php foreach ( array( array( 'Google Maps', 'https://maps.google.com/?q=35.758881,51.413824', 'GoogleMap.webp' ), array( 'Neshan', 'https://nshn.ir/gNbNgYZt_Rxg', 'Neshan.webp' ), array( 'Balad', 'https://balad.ir/p/3cuwGGif58f8hT', 'Balad.webp' ), array( 'Waze', 'https://waze.com/ul/htnke3vwqs', 'Waze.webp' ) ) as $drb_map ) : ?><a href="<?php echo esc_url( $drb_map[1] ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( $drb_map[0] ); ?>"><img src="<?php echo esc_url( DRB_THEME_URI . '/assets/dist6/images/Icons/' . $drb_map[2] ); ?>" alt="<?php echo esc_attr( $drb_map[0] ); ?>" width="20" height="20"></a><?php endforeach; ?></div></section>
        </div>
        <nav class="drb-native-footer__legal" aria-label="<?php echo esc_attr( 'fa' === $drb_lang ? 'پیوندهای حقوقی' : 'Legal links' ); ?>"><a href="<?php echo esc_url( $drb_route( '/legal/privacy/' ) ); ?>"><?php echo esc_html( __t( 'privacy' ) ); ?></a><a href="<?php echo esc_url( $drb_route( '/legal/terms/' ) ); ?>"><?php echo esc_html( __t( 'terms' ) ); ?></a><a href="<?php echo esc_url( $drb_route( '/legal/cancellation/' ) ); ?>"><?php echo esc_html( __t( 'cancellation' ) ); ?></a><a href="<?php echo esc_url( $drb_route( '/sitemap/' ) ); ?>"><?php echo esc_html( __t( 'sitemap' ) ); ?></a></nav>
        <div class="drb-native-footer__bottom"><span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( $drb_doctor ); ?> — <?php echo esc_html( __t( 'footer_rights' ) ); ?>.</span><span><?php echo esc_html( __t( 'footer_designed' ) ); ?> <a href="https://maziyarid.com" target="_blank" rel="noopener">MAZ</a></span></div>
    </div>
</footer>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
