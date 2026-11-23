<?php
/**
 * Section: Info Cards (e.g. "Fee Structure", "Uniform").
 *
 * A grid of white cards (icon, title, rich text, optional details table),
 * half or full width, beside a photo — rounded on the right, or a circle on
 * the left with a sun decoration.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $background  'navy' | 'lilac' | 'white'.
 *     @type bool   $corner      Rounded top-right corner.
 *     @type string $heading     Heading.
 *     @type string $intro       Intro (HTML).
 *     @type array  $button      SCF link.
 *     @type string $icon_style  'above' | 'inline'.
 *     @type array  $cards       Repeater: icon, title, width, text (HTML), rows (label, value).
 *     @type int    $image       Attachment ID.
 *     @type string $image_style 'right' | 'circle'.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_cards = array_filter( (array) ( $args['cards'] ?? array() ), static fn( $row ): bool => ! empty( $row['title'] ) );

if ( ! $seps_cards ) {
	return;
}

$seps_background = in_array( $args['background'] ?? '', array( 'navy', 'lilac', 'white' ), true ) ? $args['background'] : 'navy';
$seps_circle     = 'circle' === ( $args['image_style'] ?? 'right' );
$seps_inline     = 'inline' === ( $args['icon_style'] ?? 'above' );
$seps_heading    = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro      = trim( (string) ( $args['intro'] ?? '' ) );
$seps_id         = wp_unique_id( 'seps-info-cards-' );
$seps_image      = seps_image(
	$args['image'] ?? 0,
	$seps_circle ? 'seps-square' : 'large',
	array(
		'class' => 'info-cards__image',
		'sizes' => $seps_circle ? '(min-width: 1024px) 418px, 80vw' : '(min-width: 1024px) 384px, 100vw',
	)
);

$seps_classes = array( 'info-cards', 'info-cards--' . $seps_background );
if ( ! empty( $args['compact'] ) ) {
	$seps_classes[] = 'info-cards--compact';
}
if ( ! empty( $args['corner'] ) ) {
	$seps_classes[] = 'info-cards--corner';
}
if ( $seps_image ) {
	$seps_classes[] = $seps_circle ? 'info-cards--image-circle' : 'info-cards--image-right';
}
?>
<section class="<?php echo esc_attr( implode( ' ', $seps_classes ) ); ?>"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<div class="container">
		<?php if ( $seps_heading || $seps_intro ) : ?>
			<header class="info-cards__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $seps_intro ) : ?>
					<div class="info-cards__intro rich-text"><?php echo wp_kses_post( $seps_intro ); ?></div>
				<?php endif; ?>
				<?php echo seps_button( $args['button'] ?? null, 'accent' ); ?>
			</header>
		<?php endif; ?>

		<div class="info-cards__body">
			<?php if ( $seps_image ) : ?>
				<div class="info-cards__media">
					<?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
					<?php if ( $seps_circle ) : ?>
						<?php echo seps_decor( 'sun-yellow', 'info-cards__decor' ); ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<ul class="info-cards__grid" role="list">
				<?php foreach ( $seps_cards as $seps_card ) : ?>
					<?php
					$seps_rows = array_filter( (array) ( $seps_card['rows'] ?? array() ), static fn( $row ): bool => ! empty( $row['label'] ) || ! empty( $row['value'] ) );
					$seps_icon = seps_content_icon( (string) ( $seps_card['icon'] ?? '' ), 'info-box__icon' );
					?>
					<li class="info-box<?php echo 'full' === ( $seps_card['width'] ?? 'half' ) ? ' info-box--full' : ''; ?><?php echo $seps_inline ? ' info-box--inline' : ''; ?>">
						<div class="info-box__head">
							<?php echo $seps_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
							<h3 class="info-box__title"><?php echo esc_html( $seps_card['title'] ); ?></h3>
						</div>

						<?php if ( ! empty( $seps_card['text'] ) ) : ?>
							<div class="info-box__text rich-text"><?php echo wp_kses_post( $seps_card['text'] ); ?></div>
						<?php endif; ?>

						<?php if ( $seps_rows ) : ?>
							<dl class="info-box__rows">
								<?php foreach ( $seps_rows as $seps_row ) : ?>
									<div>
										<dt><?php echo esc_html( (string) ( $seps_row['label'] ?? '' ) ); ?></dt>
										<dd><?php echo esc_html( (string) ( $seps_row['value'] ?? '' ) ); ?></dd>
									</div>
								<?php endforeach; ?>
							</dl>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
