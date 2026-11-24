<?php
/**
 * Section: Photo + accordion panel, e.g. "School Rules & Regulations"
 * (Resources): a tall photo beside a lilac panel of expandable items, each
 * with a bold title, a short description and rich content.
 *
 * The first item starts open; items share a `name`, so opening one closes
 * the others (native <details>, no script).
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $background 'navy' | 'lilac' | 'white'.
 *     @type string $heading    Heading.
 *     @type string $intro      Intro (large text).
 *     @type int    $image      Photo (left).
 *     @type array  $items      Repeater: title, subtitle, content (HTML).
 *     @type string $decor      'none' | 'halves'.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_items = array_values( array_filter( (array) ( $args['items'] ?? array() ), static fn( $row ): bool => ! empty( $row['title'] ) ) );

if ( ! $seps_items ) {
	return;
}

$seps_background = in_array( $args['background'] ?? '', array( 'navy', 'lilac', 'white' ), true ) ? $args['background'] : 'navy';
$seps_heading    = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro      = trim( (string) ( $args['intro'] ?? '' ) );
$seps_id         = wp_unique_id( 'seps-accp-' );
$seps_name       = wp_unique_id( 'seps-accp-items-' );
$seps_image      = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'accp__image',
		'sizes' => '(min-width: 1024px) 594px, 100vw',
	)
);
?>
<section class="accp accp--<?php echo esc_attr( $seps_background ); ?><?php echo $seps_image ? ' accp--has-media' : ''; ?>"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<?php if ( 'halves' === ( $args['decor'] ?? 'none' ) ) : ?>
		<?php echo seps_decor( 'navy' === $seps_background ? 'halves-pair-white' : 'halves-pair-brand', 'accp__decor' ); ?>
	<?php endif; ?>

	<div class="container">
		<?php if ( $seps_heading || $seps_intro ) : ?>
			<header class="accp__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $seps_intro ) : ?>
					<p class="accp__intro"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<div class="accp__body">
			<?php if ( $seps_image ) : ?>
				<div class="accp__media"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
			<?php endif; ?>

			<div class="accp__panel">
				<?php foreach ( $seps_items as $seps_index => $seps_item ) : ?>
					<?php $seps_subtitle = trim( (string) ( $seps_item['subtitle'] ?? '' ) ); ?>
					<details class="accordion-card accp__item" name="<?php echo esc_attr( $seps_name ); ?>"<?php echo 0 === $seps_index ? ' open' : ''; ?>>
						<summary class="accordion-card__summary">
							<span>
								<strong><?php echo esc_html( $seps_item['title'] ); ?></strong><?php echo $seps_subtitle ? ' – ' . esc_html( $seps_subtitle ) : ''; ?>
							</span>
							<?php echo seps_icon( 'chevron-circle', 20, 'accordion-card__icon' ); ?>
						</summary>
						<?php if ( ! empty( $seps_item['content'] ) ) : ?>
							<div class="accp__content rich-text"><?php echo wp_kses_post( $seps_item['content'] ); ?></div>
						<?php endif; ?>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
