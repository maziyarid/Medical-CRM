<?php

defined( 'ABSPATH' ) || exit;

/** Output the administrator-configured analytics tags once in the document head. */
function drb_output_tracking_tags() {
    if ( is_admin() ) return;
    $options = drb_get_theme_options();
    $ga_id = isset( $options['google_analytics_id'] ) ? (string) $options['google_analytics_id'] : '';
    $clarity_id = isset( $options['clarity_id'] ) ? (string) $options['clarity_id'] : '';

    if ( ! empty( $options['google_analytics_enabled'] ) && preg_match( '/^G-[A-Z0-9]+$/', $ga_id ) ) :
        ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', <?php echo wp_json_encode( $ga_id ); ?>);
        </script>
        <?php
    endif;

    if ( ! empty( $options['clarity_enabled'] ) && preg_match( '/^[a-z0-9]+$/', $clarity_id ) ) :
        ?>
        <script>
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src='https://www.clarity.ms/tag/'+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, 'clarity', 'script', <?php echo wp_json_encode( $clarity_id ); ?>);
        </script>
        <?php
    endif;
}
add_action( 'wp_head', 'drb_output_tracking_tags', 2 );
