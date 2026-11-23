<?php
/**
 * Section: Photo + stacked cards, e.g. "Teaching & Academic Roles" (Careers):
 * a tall photo on the left and horizontal cards (thumbnail, title, text,
 * button) stacked on the right.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $background 'white' | 'lilac'.
 *     @type string $heading    Heading.
 *     @type string $intro      Intro text.
 *     @type int    $image      Tall photo (left).
 *     @type array  $cards      Repeater: image, title, text, link.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_cards   = array_filter( (array) ( $args['cards'] ?? array() ), static fn( $card ): bool => ! empty( $card['title'] ) );

if ( '' === $seps_heading && ! $seps_cards ) {
	return;
}

$seps_background = 'lilac' === ( $args['background'] ?? 'white' ) ? 'lilac' : 'white';
$seps_intro      = trim( (string) ( $args['intro'] ?? '' ) );
$seps_id         = wp_unique_id( 'seps-stacked-' );
$seps_image      = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'stacked__image',
		'sizes' => '(min-width: 1024px) 414px, 100vw',
	)
);
?>
<section class="stacked stacked--<?php echo esc_attr( $seps_background ); ?><?php echo $seps_image ? ' stacked--has-media' : ''; ?>"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<div class="container">
		<?php if ( $seps_heading || $seps_intro ) : ?>
			<header class="stacked__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $seps_intro ) : ?>
					<p class="stacked__intro"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<div class="stacked__body">
			<?php if ( $seps_image ) : ?>
				<div class="stacked__media"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
			<?php endif; ?>

			<?php if ( $seps_cards ) : ?>
				<ul class="stacked__list" role="list">
					<?php foreach ( $seps_cards as $seps_card ) : ?>
						<?php
						$seps_thumb = seps_image(
							$seps_card['image'] ?? 0,
							'medium_large',
							array(
								'class' => 'stacked__thumb',
								'sizes' => '(min-width: 768px) 204px, 100vw',
							)
						);
						?>
						<li class="stacked__card">
							<?php echo $seps_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
							<div class="stacked__content">
								<h3 class="stacked__title"><?php echo esc_html( $seps_card['title'] ); ?></h3>
								<?php if ( ! empty( $seps_card['text'] ) ) : ?>
									<p class="stacked__text"><?php echo esc_html( $seps_card['text'] ); ?></p>
								<?php endif; ?>
								<?php echo seps_button( $seps_card['link'] ?? null, 'primary' ); ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
