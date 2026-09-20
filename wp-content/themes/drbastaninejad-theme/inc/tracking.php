<?php

defined( 'ABSPATH' ) || exit;

function drb_tracking_is_sensitive_page() {
    $path = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    return is_page( array( 'booking', 'contact' ) )
        || (bool) preg_match( '#/(booking|contact)(/|\?|$)#i', $path );
}

function drb_tracking_is_private_page() {
    $path = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    return is_page( 'login' )
        || (bool) preg_match( '#/(wp-login|patient-login|login)(/|\?|$)#i', $path );
}

function drb_tracking_page_type() {
    if ( is_front_page() ) return 'home';
    if ( is_page( 'booking' ) ) return 'booking';
    if ( is_page( 'contact' ) ) return 'contact';
    if ( is_singular( 'post' ) ) return 'article';
    if ( is_search() ) return 'search';
    if ( is_archive() ) return 'archive';
    return 'page';
}

/**
 * Google Analytics + Google Tag Manager instrumentation.
 *
 * GTM is loaded on public booking/contact pages too, but the site's own
 * dataLayer contract never emits form-field values or patient identifiers.
 * Clarity remains excluded from those sensitive pages.
 */
function drb_output_tracking_tags() {
    if ( is_admin() || drb_tracking_is_private_page() ) return;

    $options = drb_get_theme_options();
    $ga_id = isset( $options['google_analytics_id'] ) ? strtoupper( (string) $options['google_analytics_id'] ) : '';
    $gtm_id = isset( $options['google_tag_manager_id'] ) ? strtoupper( (string) $options['google_tag_manager_id'] ) : '';
    $clarity_id = isset( $options['clarity_id'] ) ? (string) $options['clarity_id'] : '';
    $sensitive = drb_tracking_is_sensitive_page();
    $page_type = drb_tracking_page_type();
    $service_key = '';
    if ( is_singular() ) {
        $service_key = sanitize_key( (string) get_post_field( 'post_name', get_queried_object_id() ) );
    }
    ?>
    <script>
    window.dataLayer = window.dataLayer || [];
    window.drbTrack = window.drbTrack || function(eventName, params){
      try {
        if (!/^[a-z0-9_]{2,60}$/i.test(String(eventName || ''))) return;
        var clean = {}, input = params && typeof params === 'object' ? params : {};
        var allowed = [
          'page_path','page_type','form_type','service_key','step','result','source','channel',
          'payment_status','traffic_channel','referrer_host','link_host','link_type',
          'scroll_percent','engagement_seconds','language'
        ];
        allowed.forEach(function(k){
          if (input[k] === undefined || input[k] === null) return;
          var v = String(input[k]);
          if (v.length > 120) v = v.slice(0,120);
          clean[k] = v;
        });
        window.dataLayer.push(Object.assign({event:String(eventName)}, clean));
        if (typeof window.gtag === 'function') window.gtag('event', String(eventName), clean);
      } catch (_) {}
    };
    </script>
    <?php

    // Google Tag Manager — container supplied by the site owner.
    if ( preg_match( '/^GTM-[A-Z0-9]+$/', $gtm_id ) ) :
        ?>
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',<?php echo wp_json_encode( $gtm_id ); ?>);</script>
        <!-- End Google Tag Manager -->
        <?php
    endif;

    // Keep the direct GA4 bootstrap until GA4 is explicitly moved into GTM.
    // This avoids losing collection while the GTM workspace is being configured.
    if ( ! empty( $options['google_analytics_enabled'] ) && preg_match( '/^G-[A-Z0-9]+$/', $ga_id ) ) :
        ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>"></script>
        <script>
        function gtag(){dataLayer.push(arguments);}
        window.gtag = window.gtag || gtag;
        gtag('js', new Date());
        gtag('config', <?php echo wp_json_encode( $ga_id ); ?>, {
          allow_google_signals: false,
          allow_ad_personalization_signals: false
        });
        </script>
        <?php
    endif;

    // Clarity can replay sessions, so it remains excluded from booking/contact.
    if ( ! $sensitive && ! empty( $options['clarity_enabled'] ) && preg_match( '/^[a-z0-9]+$/', $clarity_id ) ) :
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
    ?>
    <script>
    (function(){
      var pageType=<?php echo wp_json_encode( $page_type ); ?>;
      var serviceKey=<?php echo wp_json_encode( $service_key ); ?>;
      var lang=(document.documentElement.lang||'fa').toLowerCase().slice(0,10);
      var refHost='';
      try{ refHost=document.referrer ? new URL(document.referrer).hostname.toLowerCase() : ''; }catch(_){}
      function trafficChannel(){
        if(!refHost)return 'direct';
        if(/(^|\.)(google|bing|yahoo|duckduckgo|yandex)\./i.test(refHost))return 'organic_search';
        if(/(^|\.)(instagram|facebook|t\.me|telegram|linkedin|x|twitter)\./i.test(refHost))return 'social';
        if(refHost===location.hostname.toLowerCase())return 'internal';
        return 'referral';
      }
      function base(extra){
        return Object.assign({
          page_path:location.pathname||'/',
          page_type:pageType,
          service_key:serviceKey,
          traffic_channel:trafficChannel(),
          referrer_host:refHost,
          language:lang
        },extra||{});
      }

      function pageContext(){
        if(typeof window.drbTrack==='function')window.drbTrack('page_context',base());
      }
      if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',pageContext,{once:true});
      else pageContext();

      document.addEventListener('click',function(e){
        var a=e.target&&e.target.closest?e.target.closest('a[href]'):null;
        if(!a)return;
        var href=String(a.getAttribute('href')||'');
        try{
          var u=new URL(a.href,location.href);
          if(/^tel:/i.test(href)){
            window.drbTrack('contact_channel_click',base({channel:'phone',link_type:'contact'}));
            return;
          }
          if(/wa\.me|whatsapp\.com/i.test(u.hostname)){
            window.drbTrack('contact_channel_click',base({channel:'whatsapp',link_host:u.hostname,link_type:'contact'}));
            return;
          }
          if(/instagram\.com/i.test(u.hostname)){
            window.drbTrack('social_click',base({channel:'instagram',link_host:u.hostname,link_type:'social'}));
            return;
          }
          if(/maps\.google|google\.[^/]+\/maps/i.test(a.href)){
            window.drbTrack('contact_channel_click',base({channel:'map',link_host:u.hostname,link_type:'location'}));
            return;
          }
          if(u.origin===location.origin && /\/booking\/?$/i.test(u.pathname)){
            window.drbTrack('booking_cta_click',base({source:'website',link_type:'booking'}));
            return;
          }
          if(u.origin!==location.origin){
            window.drbTrack('outbound_link_click',base({link_host:u.hostname,link_type:'outbound'}));
            return;
          }
          if(/\.(pdf|docx?|xlsx?|zip)(?:$|[?#])/i.test(u.pathname)){
            window.drbTrack('file_download',base({link_type:'download'}));
          }
        }catch(_){}
      },true);

      var seen={};
      function reportScroll(){
        var doc=document.documentElement;
        var max=Math.max(1,doc.scrollHeight-window.innerHeight);
        var pct=Math.round((window.scrollY||doc.scrollTop||0)/max*100);
        [25,50,75,90].forEach(function(mark){
          if(pct>=mark&&!seen[mark]){
            seen[mark]=1;
            window.drbTrack('scroll_depth',base({scroll_percent:String(mark)}));
          }
        });
      }
      var scrollTimer=0;
      window.addEventListener('scroll',function(){
        if(scrollTimer)return;
        scrollTimer=window.setTimeout(function(){scrollTimer=0;reportScroll();},250);
      },{passive:true});

      [30,60,120].forEach(function(sec){
        window.setTimeout(function(){
          if(document.visibilityState==='visible'){
            window.drbTrack('engaged_visit',base({engagement_seconds:String(sec)}));
          }
        },sec*1000);
      });
    })();
    </script>
    <?php
}
add_action( 'wp_head', 'drb_output_tracking_tags', 2 );

function drb_output_gtm_noscript() {
    if ( is_admin() || drb_tracking_is_private_page() ) return;
    $options = drb_get_theme_options();
    $gtm_id = isset( $options['google_tag_manager_id'] ) ? strtoupper( (string) $options['google_tag_manager_id'] ) : '';
    if ( ! preg_match( '/^GTM-[A-Z0-9]+$/', $gtm_id ) ) return;
    ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm_id ); ?>"
    height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php
}
add_action( 'wp_body_open', 'drb_output_gtm_noscript', 1 );