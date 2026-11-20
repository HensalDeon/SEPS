<?php
/**
 * Fallback template (blog index, archives, search).
 *
 * Inner-page designs arrive in later phases; this keeps every route usable
 * with the theme's typography and layout in the meantime.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="container page-shell">
	<header class="page-shell__header">
		<h1 class="section-title">
			<?php
			if ( is_home() && ! is_front_page() ) {
				single_post_title();
			} elseif ( is_search() ) {
				/* translators: %s: Search query. */
				printf( esc_html__( 'Search results for “%s”', 'seps' ), esc_html( get_search_query() ) );
			} elseif ( is_archive() ) {
				echo wp_kses_post( get_the_archive_title() );
			} else {
				esc_html_e( 'Latest News', 'seps' );
			}
			?>
		</h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="post-list">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', 'excerpt' );
			endwhile;
			?>
		</div>
		<?php
		the_posts_pagination(
			array(
				'mid_size'  => 1,
				'prev_text' => esc_html__( 'Previous', 'seps' ),
				'next_text' => esc_html__( 'Next', 'seps' ),
			)
		);
		?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found.', 'seps' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
