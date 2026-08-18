<?php
defined( 'ABSPATH' ) || exit;
$key = drb_current_localized_key();
$page = drb_localized_page( $key );
$services = drb_localized_collection( 'services' );
$faqs = drb_localized_collection( 'faqs' );
$gallery = drb_localized_collection( 'gallery_labels' );
if ( ! $page ) return;
?>
<main id="primary" class="drb-localized-page" data-page-key="<?php echo esc_attr( $key ); ?>">
  <header class="drb-localized-hero">
    <div class="drb-localized-shell">
      <span><?php echo esc_html( __t( 'site_tagline' ) ); ?></span>
      <h1><?php echo esc_html( $page['title'] ); ?></h1>
      <p><?php echo esc_html( $page['seo_description'] ); ?></p>
      <div class="drb-localized-actions"><a class="is-primary" href="<?php echo esc_url( drb_nav_url( 'booking' ) ); ?>"><?php echo esc_html( __t( 'cta_book' ) ); ?></a><a href="<?php echo esc_url( drb_nav_url( 'contact' ) ); ?>"><?php echo esc_html( __t( 'cta_contact' ) ); ?></a></div>
    </div>
  </header>
  <div class="drb-localized-shell drb-localized-body">
    <article class="drb-localized-content"><?php echo wp_kses_post( drb_localized_content_html( $page['content_html'] ) ); ?></article>

    <?php if ( 'home' === $key && $services ) : ?>
      <section id="services" class="drb-localized-section"><h2><?php echo esc_html( __t( 'nav_services' ) ); ?></h2><div class="drb-localized-grid">
        <?php foreach ( $services as $service ) : ?><article class="drb-localized-card"><h3><?php echo esc_html( $service['title'] ); ?></h3><strong><?php echo esc_html( $service['subtitle'] ); ?></strong><p><?php echo esc_html( $service['description'] ); ?></p></article><?php endforeach; ?>
      </div></section>
    <?php endif; ?>

    <?php if ( 'faq' === $key && $faqs ) : ?>
      <section class="drb-localized-section"><div class="drb-localized-faqs">
        <?php foreach ( $faqs as $faq ) : ?><details><summary><?php echo esc_html( $faq['question'] ); ?></summary><div><?php echo wp_kses_post( $faq['answer_html'] ); ?></div></details><?php endforeach; ?>
      </div></section>
    <?php endif; ?>

    <?php if ( 'gallery' === $key && $gallery ) : ?>
      <section class="drb-localized-section"><div class="drb-localized-grid">
        <?php foreach ( $gallery as $case ) : ?><article class="drb-localized-card"><h3><?php echo esc_html( $case['label'] ); ?></h3><p><?php echo esc_html( $case['procedure'] ); ?></p><small><?php echo esc_html( $case['interval'] ); ?></small></article><?php endforeach; ?>
      </div></section>
    <?php endif; ?>

    <?php if ( in_array( $key, array( 'booking', 'contact' ), true ) ) : $appointment = 'booking' === $key; ?>
      <section class="drb-localized-section drb-localized-form-wrap">
        <h2><?php echo esc_html( $appointment ? __t( 'booking_title' ) : __t( 'nav_contact' ) ); ?></h2>
        <form class="drb-localized-form" data-endpoint="<?php echo esc_attr( $appointment ? 'appointment' : 'contact' ); ?>">
          <input type="text" name="website" tabindex="-1" autocomplete="off" class="drb-honeypot" aria-hidden="true">
          <label><span><?php echo esc_html( __t( 'form_name' ) ); ?></span><input name="name" required minlength="2" autocomplete="name"></label>
          <label><span><?php echo esc_html( __t( 'form_mobile' ) ); ?></span><input name="mobile" required inputmode="tel" autocomplete="tel" placeholder="+49 170 1234567"></label>
          <label><span><?php echo esc_html( __t( 'form_email' ) ); ?></span><input name="email" type="email" required autocomplete="email"></label>
          <?php if ( $appointment ) : ?>
            <label><span><?php echo esc_html( __t( 'form_procedure' ) ); ?></span><select name="procedure" required><option value="">—</option><?php foreach ( $services as $service ) : $source_title = 'rhinoplasty-revision' === $service['slug'] ? 'ترمیمی' : $service['slug']; ?><option value="<?php echo esc_attr( $source_title ); ?>"><?php echo esc_html( $service['title'] ); ?></option><?php endforeach; ?></select></label>
            <label><span><?php echo esc_html( __t( 'eligibility_age' ) ); ?></span><input name="age" type="number" min="18" max="45" required></label>
            <label><span><?php echo esc_html( __t( 'eligibility_revision' ) ); ?></span><input name="previousSurgeryMonths" type="number" min="0"></label>
            <label class="drb-localized-check"><input name="eligibilityConfirmed" type="checkbox" value="1" required><span><?php echo esc_html( drb_form_t( 'تأیید شرایط پذیرش الزامی است.' ) ); ?></span></label>
          <?php endif; ?>
          <label class="is-wide"><span><?php echo esc_html( __t( 'form_message' ) ); ?></span><textarea name="message" rows="5"></textarea></label>
          <button type="submit"><?php echo esc_html( __t( 'form_submit' ) ); ?></button><p class="drb-localized-form__status" role="status" aria-live="polite"></p>
        </form>
      </section>
    <?php endif; ?>
  </div>
</main>
<script>window.__DRB_LOCALIZED_FORMS__=<?php echo wp_json_encode( drb_localized_forms_map(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;</script>
