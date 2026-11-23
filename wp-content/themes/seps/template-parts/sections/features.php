<?php
/**
 * Section: Highlights & Focus Areas (e.g. "CBSE Curriculum").
 *
 * Highlight tiles, a row of focus cards and an optional callout with photo.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading       Heading.
 *     @type string $intro         Intro (large text).
 *     @type array  $tiles         Repeater: icon, label.
 *     @type string $cards_heading Cards heading.
 *     @type array  $cards         Repeater: icon, title, text.
 *     @type string $callout_icon  Callout icon.
 *     @type string $callout_title Callout title.
 *     @type string $callout_text  Callout text.
 *     @type int    $callout_image Callout image.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_heading       = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro         = trim( (string) ( $args['intro'] ?? '' ) );
$seps_tiles         = array_filter( (array) ( $args['tiles'] ?? array() ), static fn( $row ): bool => ! empty( $row['label'] ) );
$seps_cards_heading = trim( (string) ( $args['cards_heading'] ?? '' ) );
$seps_cards         = array_filter( (array) ( $args['cards'] ?? array() ), static fn( $row ): bool => ! empty( $row['title'] ) );
$seps_callout_title = trim( (string) ( $args['callout_title'] ?? '' ) );
$seps_callout_text  = trim( (string) ( $args['callout_text'] ?? '' ) );

if ( ! $seps_tiles && ! $seps_cards && '' === $seps_callout_title ) {
	return;
}

$seps_id            = wp_unique_id( 'seps-features-' );
$seps_callout_image = seps_image(
	$args['callout_image'] ?? 0,
	'large',
	array(
		'class' => 'callout__image',
		'sizes' => '(min-width: 1024px) 615px, 100vw',
	)
);
?>
<section class="features"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<div class="container features__inner">
		<?php if ( $seps_heading || $seps_intro ) : ?>
			<header class="features__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $seps_intro ) : ?>
					<p class="lead"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<?php if ( $seps_tiles ) : ?>
			<ul class="features__tiles" role="list">
				<?php foreach ( $seps_tiles as $seps_tile ) : ?>
					<li class="feature-tile">
						<?php echo seps_content_icon( (string) ( $seps_tile['icon'] ?? '' ), 'feature-tile__icon' ); ?>
						<span class="feature-tile__label"><?php echo esc_html( $seps_tile['label'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $seps_cards ) : ?>
			<div class="features__cards-block">
				<?php if ( $seps_cards_heading ) : ?>
					<h3 class="features__cards-title"><?php echo esc_html( $seps_cards_heading ); ?></h3>
				<?php endif; ?>
				<ul class="features__cards" role="list">
					<?php foreach ( $seps_cards as $seps_card ) : ?>
						<li class="focus-card">
							<?php echo seps_content_icon( (string) ( $seps_card['icon'] ?? '' ), 'focus-card__icon' ); ?>
							<h4 class="focus-card__title"><?php echo esc_html( $seps_card['title'] ); ?></h4>
							<?php if ( ! empty( $seps_card['text'] ) ) : ?>
								<p class="focus-card__text"><?php echo esc_html( $seps_card['text'] ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( $seps_callout_title || $seps_callout_text ) : ?>
			<div class="callout<?php echo $seps_callout_image ? ' callout--has-image' : ''; ?>">
				<div class="callout__body">
					<?php if ( $seps_callout_title ) : ?>
						<h3 class="callout__title">
							<?php echo seps_content_icon( (string) ( $args['callout_icon'] ?? '' ), 'callout__icon' ); ?>
							<?php echo esc_html( $seps_callout_title ); ?>
						</h3>
					<?php endif; ?>
					<?php if ( $seps_callout_text ) : ?>
						<p class="callout__text"><?php echo esc_html( $seps_callout_text ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $seps_callout_image ) : ?>
					<div class="callout__media"><?php echo $seps_callout_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
