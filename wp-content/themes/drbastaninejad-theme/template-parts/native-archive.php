<?php defined( 'ABSPATH' ) || exit; ?>
<main id="root" class="min-h-screen bg-[#F7F8F6]">
    <header class="archive__hero"><div class="max-w-[1200px] mx-auto px-4 sm:px-6"><p class="text-[#9ED6A1] font-bold mb-2"><?php echo esc_html( drb_translate_phrase( 'آموزش و راهنمایی' ) ); ?></p><h1 class="text-3xl sm:text-5xl font-black"><?php echo is_home() ? esc_html( drb_translate_phrase( 'مقالات تخصصی' ) ) : esc_html( get_the_archive_title() ); ?></h1><?php if ( ! is_home() ) the_archive_description( '<div class="text-white/70 mt-3">', '</div>' ); ?></div></header>
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6 py-12">
        <?php if ( have_posts() ) : ?><div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6"><?php while ( have_posts() ) : the_post(); ?>
            <article <?php post_class( 'bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden shadow-sm' ); ?>>
                <?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'drb-card', array( 'class' => 'w-full aspect-[16/9] object-cover', 'loading' => 'lazy', 'width' => 720, 'height' => 405 ) ); ?></a><?php endif; ?>
                <div class="p-5"><p class="text-xs text-[#28722C] font-bold mb-2"><?php echo esc_html( get_the_date() ); ?></p><h2 class="text-lg font-black leading-8 mb-3"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p class="text-sm text-[#545B64] leading-7"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p><a class="inline-flex mt-4 text-[#28722C] font-bold text-sm" href="<?php the_permalink(); ?>"><?php echo esc_html( drb_translate_phrase( 'مطالعه مقاله' ) ); ?></a></div>
            </article>
        <?php endwhile; ?></div><div class="mt-10"><?php the_posts_pagination(); ?></div><?php else : ?><p><?php echo esc_html( drb_translate_phrase( 'محتوایی یافت نشد.' ) ); ?></p><?php endif; ?>
    </div>
</main>
