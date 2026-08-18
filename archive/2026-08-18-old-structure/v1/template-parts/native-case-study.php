<?php
defined( 'ABSPATH' ) || exit;
while ( have_posts() ) : the_post();
    $drb_case_gallery = function_exists( 'drb_case_gallery_meta' ) ? drb_case_gallery_meta( get_the_ID() ) : array();
    $drb_case_label = get_post_meta( get_the_ID(), '_drb_case_label', true ) ?: get_the_title();
    $drb_case_procedure = get_post_meta( get_the_ID(), '_drb_case_procedure', true );
    $drb_case_interval = get_post_meta( get_the_ID(), '_drb_case_interval', true );
    $drb_case_verified = (bool) get_post_meta( get_the_ID(), '_drb_consent_verified', true );
?>
<main id="root" class="drb-case-preview">
    <header class="drb-case-preview__hero"><div><a href="<?php echo esc_url( function_exists( 'drb_nav_url' ) ? drb_nav_url( 'gallery' ) : home_url( '/gallery/' ) ); ?>"><?php echo esc_html( drb_translate_phrase( 'بازگشت به گالری' ) ); ?></a><span><?php echo esc_html( drb_translate_phrase( 'پیش‌نمایش پرونده چندنما' ) ); ?></span><h1><?php echo esc_html( $drb_case_label ); ?></h1><p><?php echo esc_html( implode( ' · ', array_filter( array( $drb_case_procedure, $drb_case_interval ) ) ) ); ?></p></div></header>
    <section class="drb-case-preview__content">
        <?php if ( is_preview() ) : ?><div class="drb-case-preview__notice"><strong><?php echo esc_html( drb_translate_phrase( 'پیش‌نمایش برای تأیید پزشک' ) ); ?></strong><span><?php echo esc_html( drb_translate_phrase( 'عنوان نماها، فاصله زمانی و نوع جراحی را بررسی کنید. این صفحه تا انتشار عمومی در نتایج جستجو نمایش داده نمی‌شود.' ) ); ?></span></div><?php endif; ?>
        <?php if ( ! $drb_case_verified ) : ?><div class="drb-case-preview__notice drb-case-preview__notice--warning"><strong><?php echo esc_html( drb_translate_phrase( 'رضایت انتشار هنوز تأیید نشده است' ) ); ?></strong><span><?php echo esc_html( drb_translate_phrase( 'این پیش‌نمایش فقط برای کاربران مجاز است؛ پیش از انتشار، رضایت‌نامه را ثبت کنید.' ) ); ?></span></div><?php endif; ?>
        <div class="drb-case-preview__summary"><div><strong><?php echo esc_html( count( $drb_case_gallery ) ); ?></strong><span><?php echo esc_html( drb_translate_phrase( 'نمای ثبت‌شده' ) ); ?></span></div><div><strong><?php echo esc_html( $drb_case_procedure ?: drb_translate_phrase( 'نیازمند تأیید' ) ); ?></strong><span><?php echo esc_html( drb_translate_phrase( 'نوع جراحی' ) ); ?></span></div><div><strong><?php echo esc_html( $drb_case_interval ?: drb_translate_phrase( 'نیازمند تأیید' ) ); ?></strong><span><?php echo esc_html( drb_translate_phrase( 'زمان ثبت نتیجه' ) ); ?></span></div></div>
        <?php if ( $drb_case_gallery ) : ?><div class="drb-case-preview__grid"><?php foreach ( $drb_case_gallery as $drb_case_image ) : $drb_case_image_id = absint( $drb_case_image['id'] ?? 0 ); $drb_case_image_url = wp_get_attachment_image_url( $drb_case_image_id, 'full' ); if ( ! $drb_case_image_url ) continue; ?><figure><a href="<?php echo esc_url( $drb_case_image_url ); ?>" target="_blank" rel="noopener"><?php echo wp_get_attachment_image( $drb_case_image_id, 'drb-case-cover', false, array( 'loading' => 'lazy' ) ); ?></a><figcaption><strong><?php echo esc_html( $drb_case_image['label'] ?: get_the_title( $drb_case_image_id ) ); ?></strong><span><?php echo esc_html( $drb_case_image['interval'] ?: $drb_case_interval ); ?></span></figcaption></figure><?php endforeach; ?></div><?php else : ?><div class="drb-gallery-empty"><strong><?php echo esc_html( drb_translate_phrase( 'هنوز تصویری ثبت نشده است' ) ); ?></strong><p><?php echo esc_html( drb_translate_phrase( 'از بخش «نماهای این مراجعه» تصاویر را اضافه و دوباره پیش‌نمایش را باز کنید.' ) ); ?></p></div><?php endif; ?>
    </section>
</main>
<?php endwhile; ?>
