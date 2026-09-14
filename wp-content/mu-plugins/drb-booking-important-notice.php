<?php
/**
 * Plugin Name: DRB Booking Important Notice
 * Description: Shows the clinic's duplicate-submission warning before the public booking form.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', static function (): void {
    if ( is_admin() ) {
        return;
    }

    $path = wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
    if ( ! is_string( $path ) || ! preg_match( '#/(?:fa/)?booking/?$#', $path ) ) {
        return;
    }

    if ( function_exists( 'drb_detect_lang' ) && 'fa' !== drb_detect_lang() ) {
        return;
    }

    $css = <<<'CSS'
.drb-booking-important-notice{
  display:flex;
  align-items:flex-start;
  gap:.85rem;
  margin:0 0 1.15rem;
  padding:1rem 1.15rem;
  border:1px solid #efb4b4;
  border-radius:14px;
  background:#fff4f4;
  color:#8f2020;
  direction:rtl;
  text-align:right;
  line-height:1.9;
  box-shadow:0 1px 0 rgba(127,29,29,.03);
}
.drb-booking-important-notice__icon{
  flex:0 0 auto;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  width:1.55rem;
  height:1.55rem;
  margin-top:.05rem;
  border-radius:50%;
  background:#b42318;
  color:#fff;
  font-weight:900;
  font-size:.95rem;
}
.drb-booking-important-notice__body{min-width:0;flex:1}
.drb-booking-important-notice strong{
  display:block;
  margin:0 0 .2rem;
  color:#8a1717;
  font-weight:900;
  font-size:.98rem;
}
.drb-booking-important-notice p{
  margin:0;
  color:#9b2c2c;
  font-size:.88rem;
  line-height:1.95;
}
@media (max-width:640px){
  .drb-booking-important-notice{padding:.9rem 1rem;gap:.7rem;border-radius:12px}
  .drb-booking-important-notice p{font-size:.84rem}
}
CSS;

    wp_register_style( 'drb-booking-important-notice', false, array(), '1.0.0' );
    wp_enqueue_style( 'drb-booking-important-notice' );
    wp_add_inline_style( 'drb-booking-important-notice', $css );

    $script = <<<'JS'
(function(){
  'use strict';
  var NOTICE_ID='drb-booking-important-notice';
  function mount(){
    if(document.getElementById(NOTICE_ID)) return true;
    var form=document.querySelector('[data-drb-minimal-booking]');
    if(!form) return false;
    var notice=document.createElement('div');
    notice.id=NOTICE_ID;
    notice.className='drb-booking-important-notice';
    notice.setAttribute('role','note');
    notice.setAttribute('aria-label','توجه مهم پیش از تکمیل فرم');
    notice.innerHTML='<span class="drb-booking-important-notice__icon" aria-hidden="true">!</span>'+
      '<div class="drb-booking-important-notice__body">'+
      '<strong>توجه مهم پیش از تکمیل فرم:</strong>'+
      '<p>اطلاعات این فرم مستقیماً در پرونده پزشکی شما در سامانه کلینیک ثبت می‌شود.<br>'+ 
      'لطفاً فرم را فقط یک‌بار تکمیل کنید و از ارسال مجدد همان اطلاعات خودداری نمایید.<br>'+ 
      'در صورتی که قبلاً فرم را تکمیل کرده‌اید، نیازی به ثبت مجدد اطلاعات نیست و سوابق شما در پرونده موجود است.</p>'+ 
      '</div>';
    form.insertBefore(notice, form.firstChild);
    return true;
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',mount,{once:true}); else mount();
  if('MutationObserver' in window){
    var obs=new MutationObserver(function(){ if(mount()) obs.disconnect(); });
    obs.observe(document.documentElement,{childList:true,subtree:true});
  }
})();
JS;

    wp_register_script( 'drb-booking-important-notice', '', array(), '1.0.0', true );
    wp_enqueue_script( 'drb-booking-important-notice' );
    wp_add_inline_script( 'drb-booking-important-notice', $script );
}, 40 );
