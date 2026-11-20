<?php
/**
 * Section: Latest News & Media Corner.
 *
 * Uses core Posts: the newest post is featured, the next three are listed.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading  Heading.
 *     @type int    $category Optional category term ID.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_query_args = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 4,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);

if ( ! empty( $args['category'] ) ) {
	$seps_query_args['cat'] = absint( $args['category'] );
}

$seps_news = new WP_Query( $seps_query_args );

if ( ! $seps_news->have_posts() ) {
	return;
}

$seps_heading   = trim( (string) ( $args['heading'] ?? '' ) );
$seps_read_more = __( 'Read More', 'seps' );
?>
<section class="news"<?php echo $seps_heading ? ' aria-labelledby="news-title"' : ''; ?>>
	<?php echo seps_decor( 'hourglass-navy', 'news__decor' ); ?>

	<div class="container">
		<?php if ( $seps_heading ) : ?>
			<h2 id="news-title" class="section-title news__title"><?php echo esc_html( $seps_heading ); ?></h2>
		<?php endif; ?>

		<div class="news__grid">
			<?php
			while ( $seps_news->have_posts() ) :
				$seps_news->the_post();
				$seps_featured = 0 === $seps_news->current_post;

				if ( 1 === $seps_news->current_post ) {
					echo '<div class="news__list">';
				}
				?>
				<article <?php post_class( 'news-card' . ( $seps_featured ? ' news-card--featured' : ' news-card--compact' ) ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<a class="news-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
							<?php
							the_post_thumbnail(
								$seps_featured ? 'seps-wide' : 'seps-thumb',
								array(
									'class' => 'news-card__image',
									'sizes' => $seps_featured ? '(min-width: 1280px) 785px, 100vw' : '(min-width: 1280px) 308px, (min-width: 640px) 40vw, 100vw',
								)
							);
							?>
						</a>
					<?php endif; ?>

					<div class="news-card__body">
						<h3 class="news-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p class="news-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php
						echo seps_button(
							array(
								'url'   => get_permalink(),
								'title' => $seps_read_more,
							),
							'soft',
							'',
							get_the_title()
						);
						?>
					</div>
				</article>
				<?php
			endwhile;

			if ( $seps_news->post_count > 1 ) {
				echo '</div>';
			}

			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
