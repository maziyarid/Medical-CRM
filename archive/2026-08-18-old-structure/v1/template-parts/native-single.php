<?php defined( 'ABSPATH' ) || exit; while ( have_posts() ) : the_post(); $drb_hero = has_post_thumbnail() ? wp_get_attachment_image_url( get_post_thumbnail_id(), 'drb-article-hero' ) : ''; ?>
<main id="root" class="min-h-screen bg-[#F7F8F6] pb-16">
    <article>
        <header class="drb-native-article-hero<?php echo $drb_hero ? ' has-image' : ''; ?>"<?php echo $drb_hero ? ' style="background-image:url(' . esc_url( $drb_hero ) . ')"' : ''; ?>><div class="drb-native-article-hero__overlay"></div><div class="max-w-4xl mx-auto px-4 sm:px-6 drb-native-article-hero__content"><a href="<?php echo esc_url( get_post_type_archive_link( 'post' ) ?: home_url( '/blog/' ) ); ?>" class="text-white/75 text-sm"><?php echo esc_html( drb_translate_phrase( 'بازگشت به مقالات' ) ); ?></a><h1 class="text-3xl sm:text-5xl font-black leading-[1.55] mt-5 mb-4"><?php the_title(); ?></h1><div class="text-white/70 text-sm"><?php echo esc_html( get_the_date() ); ?> · <?php the_category( '، ' ); ?></div></div></header>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 pt-10 sm:pt-14">
            <?php if ( has_excerpt() ) : ?><p class="speakable text-lg sm:text-xl leading-9 text-[#3F443F] bg-[#E4F0E4] border-r-4 border-[#28722C] rounded-2xl p-5 sm:p-6 mb-8"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
            <div class="legacy-content"><?php the_content(); ?></div>
        </div>
    </article>
</main>
<?php endwhile; ?>
