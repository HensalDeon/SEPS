<?php
/**
 * Section: Documents (e.g. "CBSE Mandatory Public Disclosure").
 *
 * A navy panel of preview cards; each card opens its document (PDF) in a new
 * tab. Up to three cards show as a row; more become a carousel with arrows
 * and dots (the carousel script hides them while every card fits).
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading.
 *     @type string $intro   Intro text.
 *     @type array  $items   Repeater: title, image (preview photo), file (document URL).
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_items = array();
foreach ( (array) ( $args['items'] ?? array() ) as $seps_item ) {
	$seps_title = trim( (string) ( $seps_item['title'] ?? '' ) );
	$seps_url   = trim( (string) ( $seps_item['file'] ?? '' ) );

	// A card is a link to its document: without one there is nothing to open.
	if ( '' !== $seps_title && '' !== $seps_url ) {
		$seps_items[] = array(
			'title' => $seps_title,
			'image' => absint( $seps_item['image'] ?? 0 ),
			'url'   => $seps_url,
		);
	}
}

if ( ! $seps_items ) {
	return;
}

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$seps_id      = wp_unique_id( 'seps-documents-' );
?>
<section class="documents"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<div class="container">
		<?php if ( $seps_heading || $seps_intro ) : ?>
			<header class="documents__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $seps_intro ) : ?>
					<p class="documents__intro"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<div class="documents__panel">
			<div class="carousel carousel--cards carousel--max-3" data-carousel>
				<div class="carousel__stage">
					<button type="button" class="carousel__arrow carousel__arrow--prev" data-carousel-prev hidden>
						<?php echo seps_icon( 'chevron-circle-white', 30 ); ?>
						<span class="screen-reader-text"><?php esc_html_e( 'Previous', 'seps' ); ?></span>
					</button>

					<div class="carousel__viewport">
						<ul
							class="carousel__track"
							role="list"
							tabindex="0"
							aria-label="<?php echo esc_attr( $seps_heading ? $seps_heading : __( 'Documents', 'seps' ) ); ?>"
							data-carousel-track
						>
							<?php foreach ( $seps_items as $seps_item ) : ?>
								<li class="carousel__slide" data-carousel-slide>
									<a class="document-card" href="<?php echo esc_url( $seps_item['url'] ); ?>" target="_blank" rel="noopener noreferrer">
										<span class="document-card__media">
											<?php
											echo seps_image(
												$seps_item['image'],
												'seps-card',
												array(
													'class' => 'document-card__image',
													'alt'   => '',
													'sizes' => '(min-width: 1280px) 460px, (min-width: 768px) 45vw, 100vw',
												)
											);
											?>
										</span>
										<span class="document-card__title">
											<?php echo esc_html( $seps_item['title'] ); ?>
											<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'seps' ); ?></span>
										</span>
										<?php echo seps_icon( 'fullscreen', 45, 'document-card__icon' ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>

					<button type="button" class="carousel__arrow carousel__arrow--next" data-carousel-next hidden>
						<?php echo seps_icon( 'chevron-circle-white', 30 ); ?>
						<span class="screen-reader-text"><?php esc_html_e( 'Next', 'seps' ); ?></span>
					</button>
				</div>

				<div
					class="carousel__dots"
					role="group"
					aria-label="<?php esc_attr_e( 'Choose slide', 'seps' ); ?>"
					data-dot-label="<?php /* translators: %d: Slide number (used by the carousel script). */ esc_attr_e( 'Go to slide %d', 'seps' ); ?>"
					data-count-label="<?php /* translators: 1: Cards in view, e.g. "4–6", 2: Number of cards (used by the carousel script). */ esc_attr_e( '%1$s of %2$s', 'seps' ); ?>"
					data-carousel-dots
					hidden
				></div>
			</div>
		</div>
	</div>
</section>
