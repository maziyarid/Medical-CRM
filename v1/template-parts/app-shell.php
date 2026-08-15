<?php
defined( 'ABSPATH' ) || exit;
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
<noscript><div style="max-width:800px;margin:40px auto;padding:20px" dir="<?php echo esc_attr( drb_is_rtl() ? 'rtl' : 'ltr' ); ?>"><?php echo esc_html( drb_translate_phrase( 'برای استفاده کامل از سایت، JavaScript مرورگر را فعال کنید.' ) ); ?></div></noscript>

