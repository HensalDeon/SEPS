<?php
/**
 * Section: Cards (carousel or grid), e.g. on Student Life: "Student
 * Council" (cards on a navy panel), "Education, National Identity &
 * Activities" (with a circle photo on a lilac shape), "Celebrating Our
 * Achievements" (navy) and "Media Gallery" (grid).
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $background   'white' | 'lilac' | 'navy'.
 *     @type string $heading      Heading (line breaks preserved).
 *     @type string $intro        Intro (HTML).
 *     @type string $signature    Sign-off; the first line is bold.
 *     @type array  $button       SCF link.
 *     @type string $display      'carousel' | 'grid'.
 *     @type string $columns      '3' | '4' (cards per row on wide screens).
 *     @type string $image_height 'small' | 'medium' | 'large'.
 *     @type bool   $panel        Show the cards on a navy panel.
 *     @type string $panel_title  Panel title.
 *     @type array  $cards        Repeater: image, title, text, link.
 *     @type int    $image        Side photo (circle on a lilac shape).
 *     @type string $decor        'none' | 'sun'.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_cards   = array_filter( (array) ( $args['cards'] ?? array() ), static fn( $card ): bool => ! empty( $card['title'] ) );

if ( '' === $seps_heading && ! $seps_cards ) {
	return;
}

$seps_background  = in_array( $args['background'] ?? '', array( 'white', 'lilac', 'navy' ), true ) ? $args['background'] : 'lilac';
$seps_columns     = '4' === (string) ( $args['columns'] ?? '3' ) ? 4 : 3;
$seps_height      = in_array( $args['image_height'] ?? '', array( 'small', 'medium', 'large' ), true ) ? $args['image_height'] : 'large';
$seps_carousel    = 'grid' !== ( $args['display'] ?? 'carousel' );
$seps_panel       = ! empty( $args['panel'] );
$seps_panel_title = $seps_panel ? trim( (string) ( $args['panel_title'] ?? '' ) ) : '';
$seps_intro       = trim( (string) ( $args['intro'] ?? '' ) );
$seps_signature   = array_values( array_filter( array_map( 'trim', explode( "\n", (string) ( $args['signature'] ?? '' ) ) ) ) );
$seps_on_dark     = $seps_panel || 'navy' === $seps_background;
$seps_id          = wp_unique_id( 'seps-card-list-' );
$seps_side_image  = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'card-list__photo',
		'sizes' => '(min-width: 1280px) 463px, 320px',
	)
);
$seps_card_sizes  = 4 === $seps_columns
	? '(min-width: 1280px) 340px, (min-width: 768px) 45vw, 100vw'
	: '(min-width: 1280px) 524px, (min-width: 768px) 45vw, 100vw';

$seps_classes = array(
	'card-list',
	'card-list--' . $seps_background,
	'card-list--photo-' . $seps_height,
);
if ( $seps_side_image ) {
	$seps_classes[] = 'card-list--has-side';
}
if ( $seps_panel_title ) {
	$seps_classes[] = 'card-list--spacious';
}

ob_start();
foreach ( $seps_cards as $seps_card ) {
	ob_start();
	get_template_part(
		'template-parts/components/photo-card',
		null,
		array(
			'image' => $seps_card['image'] ?? 0,
			'title' => $seps_card['title'],
			'text'  => $seps_card['text'] ?? '',
			'link'  => $seps_card['link'] ?? null,
			'sizes' => $seps_card_sizes,
		)
	);
	$seps_card_html = trim( (string) ob_get_clean() );

	if ( '' !== $seps_card_html ) {
		printf(
			'<li class="%1$s"%2$s>%3$s</li>',
			$seps_carousel ? 'carousel__slide' : 'card-list__item',
			$seps_carousel ? ' data-carousel-slide' : '',
			$seps_card_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the component.
		);
	}
}
$seps_items = (string) ob_get_clean();
?>
<section class="<?php echo esc_attr( implode( ' ', $seps_classes ) ); ?>"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<?php if ( 'sun' === ( $args['decor'] ?? 'none' ) ) : ?>
		<?php echo seps_decor( 'sun-yellow', 'card-list__decor' ); ?>
	<?php endif; ?>

	<div class="container card-list__inner">
		<?php if ( $seps_heading || $seps_intro || $seps_signature || ! empty( $args['button'] ) ) : ?>
			<header class="card-list__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo seps_multiline( $seps_heading ); ?></h2>
				<?php endif; ?>

				<?php if ( $seps_intro ) : ?>
					<div class="card-list__intro rich-text"><?php echo wp_kses_post( $seps_intro ); ?></div>
				<?php endif; ?>

				<?php if ( $seps_signature ) : ?>
					<p class="card-list__signature">
						<strong><?php echo esc_html( array_shift( $seps_signature ) ); ?></strong>
						<?php foreach ( $seps_signature as $seps_line ) : ?>
							<br><?php echo esc_html( $seps_line ); ?>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>

				<?php echo seps_button( $args['button'] ?? null, 'navy' === $seps_background ? 'accent' : 'primary' ); ?>
			</header>
		<?php endif; ?>

		<?php if ( '' !== $seps_items ) : ?>
			<div class="card-list__body<?php echo $seps_panel ? ' card-list__panel' : ''; ?>">
				<?php if ( $seps_panel_title ) : ?>
					<h3 class="card-list__panel-title"><?php echo esc_html( $seps_panel_title ); ?></h3>
				<?php endif; ?>

				<?php if ( $seps_carousel ) : ?>
					<?php
					$seps_arrow = $seps_on_dark ? 'chevron-circle-white' : 'chevron-circle-brand';
					$seps_label = $seps_panel_title ? $seps_panel_title : $seps_heading;
					?>
					<div class="carousel carousel--cards carousel--max-<?php echo esc_attr( (string) $seps_columns ); ?><?php echo $seps_on_dark ? '' : ' carousel--on-light'; ?>" data-carousel>
						<div class="carousel__stage">
							<button type="button" class="carousel__arrow carousel__arrow--prev" data-carousel-prev hidden>
								<?php echo seps_icon( $seps_arrow, 30 ); ?>
								<span class="screen-reader-text"><?php esc_html_e( 'Previous', 'seps' ); ?></span>
							</button>

							<div class="carousel__viewport">
								<ul
									class="carousel__track"
									role="list"
									tabindex="0"
									aria-label="<?php echo esc_attr( wp_strip_all_tags( str_replace( "\n", ' ', $seps_label ) ) ); ?>"
									data-carousel-track
								>
									<?php echo $seps_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped components above. ?>
								</ul>
							</div>

							<button type="button" class="carousel__arrow carousel__arrow--next" data-carousel-next hidden>
								<?php echo seps_icon( $seps_arrow, 30 ); ?>
								<span class="screen-reader-text"><?php esc_html_e( 'Next', 'seps' ); ?></span>
							</button>
						</div>

						<div
							class="carousel__dots"
							role="group"
							aria-label="<?php esc_attr_e( 'Choose slide', 'seps' ); ?>"
							data-dot-label="<?php /* translators: %d: Slide number (used by the carousel script). */ esc_attr_e( 'Go to slide %d', 'seps' ); ?>"
							data-carousel-dots
							hidden
						></div>
					</div>
				<?php else : ?>
					<ul class="card-list__grid card-list__grid--<?php echo esc_attr( (string) $seps_columns ); ?>" role="list">
						<?php echo $seps_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped components above. ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $seps_side_image ) : ?>
		<div class="card-list__side">
			<?php echo seps_decor( 'blob-lilac', 'card-list__blob' ); ?>
			<?php echo $seps_side_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
		</div>
	<?php endif; ?>
</section>
