<?php
/**
 * Section: Image + Text Blocks (e.g. "Our Vision / Our Mission").
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type int    $image          Attachment ID (tall).
 *     @type string $image_position 'left' (default) or 'right'.
 *     @type array  $blocks         Repeater: heading, text, cards (title, text).
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_blocks = array_filter( (array) ( $args['blocks'] ?? array() ), static fn( $row ): bool => ! empty( $row['heading'] ) );

if ( ! $seps_blocks ) {
	return;
}

$seps_image = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'media-text__image',
		'sizes' => '(min-width: 1024px) 491px, 100vw',
	)
);

$seps_classes = 'media-text';
if ( $seps_image && 'right' === ( $args['image_position'] ?? 'left' ) ) {
	$seps_classes .= ' media-text--image-right';
}
?>
<section class="<?php echo esc_attr( $seps_classes ); ?>">
	<div class="container media-text__inner<?php echo $seps_image ? '' : ' media-text__inner--text-only'; ?>">
		<?php if ( $seps_image ) : ?>
			<div class="media-text__media"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
		<?php endif; ?>

		<div class="media-text__blocks">
			<?php foreach ( $seps_blocks as $seps_block ) : ?>
				<?php $seps_cards = array_filter( (array) ( $seps_block['cards'] ?? array() ), static fn( $card ): bool => ! empty( $card['title'] ) || ! empty( $card['text'] ) ); ?>
				<div class="media-text__block">
					<h2 class="section-title"><?php echo esc_html( $seps_block['heading'] ); ?></h2>

					<?php if ( ! empty( $seps_block['text'] ) ) : ?>
						<p class="media-text__text"><?php echo esc_html( $seps_block['text'] ); ?></p>
					<?php endif; ?>

					<?php if ( $seps_cards ) : ?>
						<ul class="media-text__cards" role="list">
							<?php foreach ( $seps_cards as $seps_card ) : ?>
								<li class="info-card">
									<?php if ( ! empty( $seps_card['title'] ) ) : ?>
										<h3 class="info-card__title"><?php echo esc_html( $seps_card['title'] ); ?></h3>
									<?php endif; ?>
									<?php if ( ! empty( $seps_card['text'] ) ) : ?>
										<p class="info-card__text"><?php echo esc_html( $seps_card['text'] ); ?></p>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
