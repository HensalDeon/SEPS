<?php
/**
 * Component: Post card (featured image, title, excerpt, "Read More").
 *
 * Used inside a loop (the current global post), e.g. the Latest posts
 * section and the News & Events list.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading_tag Title element: 'h3' (default) or 'h2'.
 *     @type string $sizes       Image sizes attribute.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_tag   = 'h2' === ( $args['heading_tag'] ?? 'h3' ) ? 'h2' : 'h3';
$seps_title = get_the_title();
?>
<article <?php post_class( 'post-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="post-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php
			the_post_thumbnail(
				'seps-thumb',
				array(
					'class' => 'post-card__image',
					'sizes' => (string) ( $args['sizes'] ?? '(min-width: 1024px) 365px, (min-width: 768px) 45vw, 100vw' ),
					'alt'   => '',
				)
			);
			?>
		</a>
	<?php endif; ?>

	<div class="post-card__body">
		<<?php echo esc_html( $seps_tag ); ?> class="post-card__title">
			<a href="<?php the_permalink(); ?>"><?php echo esc_html( $seps_title ); ?></a>
		</<?php echo esc_html( $seps_tag ); ?>>

		<p class="post-card__excerpt"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>

		<a class="btn btn--soft post-card__more" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'Read More', 'seps' ); ?><span class="screen-reader-text">: <?php echo esc_html( $seps_title ); ?></span>
		</a>
	</div>
</article>
