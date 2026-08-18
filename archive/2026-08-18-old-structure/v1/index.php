<?php
/**
 * The main template file.
 * This is the most generic template and the required fallback
 * for a standalone (classic) WordPress theme.
 *
 * @package DrBastaninejad
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="primary" class="site-main">
<?php
if ( have_posts() ) :

	while ( have_posts() ) :
		the_post();

		// Use a template part if you have one, otherwise fall back inline.
		if ( locate_template( 'template-parts/content.php' ) ) {
			get_template_part( 'template-parts/content', get_post_type() );
		} else {
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</header>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</article>
			<?php
		}

	endwhile;

	the_posts_pagination();

else :
	?>
	<p><?php esc_html_e( 'Nothing found.', 'drbastaninejad-theme' ); ?></p>
	<?php
endif;
?>
</main>

<?php
get_footer();
