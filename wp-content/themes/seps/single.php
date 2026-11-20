<?php
/**
 * Single post template.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'container page-shell entry' ); ?>>
		<header class="page-shell__header">
			<p class="entry__meta"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
			<h1 class="section-title"><?php the_title(); ?></h1>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="entry__media">
				<?php the_post_thumbnail( 'seps-wide', array( 'sizes' => '(min-width: 1280px) 1200px, 100vw' ) ); ?>
			</div>
		<?php endif; ?>

		<div class="entry__content">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
