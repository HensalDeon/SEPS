<?php
/**
 * Section: Testimonials ("Hear from our Parents").
 *
 * Testimonials are a post type (seps-core plugin) so they can be reused on
 * other pages. Editors may hand-pick them; otherwise the latest are shown.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading.
 *     @type string $intro   Intro text.
 *     @type array  $button  SCF link.
 *     @type int    $image   Circular image.
 *     @type int[]  $items   Selected testimonial IDs (optional).
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );

if ( '' === $seps_heading ) {
	return;
}

$seps_ids   = array_filter( array_map( 'absint', (array) ( $args['items'] ?? array() ) ) );
$seps_query = array(
	'post_type'              => 'seps_testimonial',
	'post_status'            => 'publish',
	'posts_per_page'         => $seps_ids ? count( $seps_ids ) : 6,
	'no_found_rows'          => true,
	'update_post_term_cache' => false,
);

if ( $seps_ids ) {
	$seps_query['post__in'] = $seps_ids;
	$seps_query['orderby']  = 'post__in';
}

$seps_testimonials = post_type_exists( 'seps_testimonial' ) ? get_posts( $seps_query ) : array();
$seps_intro        = trim( (string) ( $args['intro'] ?? '' ) );
$seps_image        = seps_image(
	$args['image'] ?? 0,
	'seps-square',
	array(
		'class' => 'testimonials__image',
		'sizes' => '(min-width: 1280px) 445px, 320px',
	)
);
?>
<section class="testimonials<?php echo $seps_image ? ' testimonials--has-media' : ''; ?>" aria-labelledby="testimonials-title">
	<div class="container testimonials__inner">
		<div class="testimonials__body">
			<header class="testimonials__header">
				<h2 id="testimonials-title" class="section-title section-title--light"><?php echo esc_html( $seps_heading ); ?></h2>
				<?php if ( $seps_intro ) : ?>
					<p class="testimonials__intro"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
				<?php echo seps_button( $args['button'] ?? null, 'accent' ); ?>
			</header>

			<?php if ( $seps_testimonials ) : ?>
				<div class="carousel" data-carousel>
					<ul
						class="carousel__track"
						role="list"
						tabindex="0"
						aria-label="<?php esc_attr_e( 'Parent testimonials', 'seps' ); ?>"
						data-carousel-track
					>
						<?php foreach ( $seps_testimonials as $seps_testimonial ) : ?>
							<?php
							$seps_quote = trim( (string) seps_field( 'quote', $seps_testimonial->ID ) );
							$seps_role  = trim( (string) seps_field( 'author_role', $seps_testimonial->ID ) );

							if ( '' === $seps_quote ) {
								continue;
							}
							?>
							<li class="carousel__slide" data-carousel-slide>
								<figure class="quote-card">
									<blockquote class="quote-card__quote">
										<p>&ldquo;<?php echo esc_html( $seps_quote ); ?>&rdquo;</p>
									</blockquote>
									<figcaption class="quote-card__author">
										<strong class="quote-card__name"><?php echo esc_html( get_the_title( $seps_testimonial ) ); ?></strong>
										<?php if ( $seps_role ) : ?>
											<span class="quote-card__role"><?php echo esc_html( $seps_role ); ?></span>
										<?php endif; ?>
									</figcaption>
								</figure>
							</li>
						<?php endforeach; ?>
					</ul>

					<div
						class="carousel__dots"
						role="group"
						aria-label="<?php esc_attr_e( 'Choose testimonial', 'seps' ); ?>"
						data-dot-label="<?php /* translators: %d: Slide number (used by the carousel script). */ esc_attr_e( 'Go to slide %d', 'seps' ); ?>"
						data-carousel-dots
						hidden
					></div>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $seps_image ) : ?>
			<div class="testimonials__media">
				<div class="testimonials__frame"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
				<?php echo seps_decor( 'flower-brand', 'testimonials__decor' ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>
