<?php
/**
 * Section: Latest posts, e.g. "Love for the UAE" on News & Events.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading  Heading.
 *     @type string $intro    Intro (HTML).
 *     @type array  $button   SCF link.
 *     @type int    $category Optional category term ID.
 *     @type int    $count    Number of posts (default 3).
 *     @type int    $image    Side photo (circle on a navy shape, left).
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_query_args = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => min( 12, max( 1, absint( $args['count'] ?? 3 ) ) ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);

if ( ! empty( $args['category'] ) ) {
	$seps_query_args['cat'] = absint( $args['category'] );
}

$seps_posts   = new WP_Query( $seps_query_args );
$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );

if ( ! $seps_posts->have_posts() && '' === $seps_heading ) {
	return;
}

$seps_intro = trim( (string) ( $args['intro'] ?? '' ) );
$seps_id    = wp_unique_id( 'seps-posts-' );
$seps_image = seps_image(
	$args['image'] ?? 0,
	'seps-square',
	array(
		'class' => 'posts__photo',
		'sizes' => '(min-width: 1280px) 416px, 320px',
	)
);
?>
<section class="posts<?php echo $seps_image ? ' posts--has-side' : ''; ?>"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<div class="container posts__inner">
		<div class="posts__content">
			<?php if ( $seps_heading || $seps_intro || ! empty( $args['button'] ) ) : ?>
				<header class="posts__header">
					<?php if ( $seps_heading ) : ?>
						<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
					<?php endif; ?>

					<?php if ( $seps_intro ) : ?>
						<div class="posts__intro rich-text"><?php echo wp_kses_post( $seps_intro ); ?></div>
					<?php endif; ?>

					<?php echo seps_button( $args['button'] ?? null, 'primary' ); ?>
				</header>
			<?php endif; ?>

			<?php if ( $seps_posts->have_posts() ) : ?>
				<ul class="post-grid" role="list">
					<?php
					while ( $seps_posts->have_posts() ) :
						$seps_posts->the_post();
						?>
						<li class="post-grid__item"><?php get_template_part( 'template-parts/components/post-card' ); ?></li>
					<?php endwhile; ?>
				</ul>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $seps_image ) : ?>
		<div class="posts__side">
			<?php echo seps_decor( 'drop-navy', 'posts__shape' ); ?>
			<?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
			<?php echo seps_decor( 'flower-yellow', 'posts__flower posts__flower--1' ); ?>
			<?php echo seps_decor( 'flower-yellow', 'posts__flower posts__flower--2' ); ?>
		</div>
	<?php endif; ?>
</section>
