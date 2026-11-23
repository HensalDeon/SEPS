<?php
/**
 * Section: Badge Accordion (e.g. "Courses Offered" + "Senior Secondary Streams").
 *
 * One or more groups of expandable items, each with a badge and title. The
 * optional image sits beside the first group. The first item of each group
 * starts open; items in a group are mutually exclusive.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading.
 *     @type string $intro   Intro text.
 *     @type int    $image   Attachment ID (beside the first group).
 *     @type array  $groups  Repeater: heading, items (badge, title, content).
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_groups = array();
foreach ( (array) ( $args['groups'] ?? array() ) as $seps_group ) {
	$seps_items = array_values( array_filter( (array) ( $seps_group['items'] ?? array() ), static fn( $row ): bool => ! empty( $row['title'] ) ) );

	if ( $seps_items ) {
		$seps_groups[] = array(
			'heading' => trim( (string) ( $seps_group['heading'] ?? '' ) ),
			'items'   => $seps_items,
		);
	}
}

if ( ! $seps_groups ) {
	return;
}

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$seps_id      = wp_unique_id( 'seps-badges-' );
$seps_image   = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'badge-accordion__image',
		'sizes' => '(min-width: 1024px) 518px, 100vw',
	)
);
?>
<section class="badge-accordion"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<div class="container badge-accordion__inner">
		<?php if ( $seps_heading || $seps_intro ) : ?>
			<header class="badge-accordion__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $seps_intro ) : ?>
					<p class="badge-accordion__intro"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<?php foreach ( $seps_groups as $seps_index => $seps_group ) : ?>
			<?php
			$seps_name      = wp_unique_id( 'seps-badges-group-' );
			$seps_has_image = 0 === $seps_index && $seps_image;
			?>
			<?php if ( $seps_group['heading'] ) : ?>
				<h2 class="section-title badge-accordion__group-title"><?php echo esc_html( $seps_group['heading'] ); ?></h2>
			<?php endif; ?>

			<div class="badge-accordion__group<?php echo $seps_has_image ? ' badge-accordion__group--has-image' : ''; ?>">
				<div class="badge-accordion__items">
					<?php foreach ( $seps_group['items'] as $seps_item_index => $seps_item ) : ?>
						<details class="accordion-card accordion-card--badge" name="<?php echo esc_attr( $seps_name ); ?>" <?php echo 0 === $seps_item_index ? 'open' : ''; ?>>
							<summary class="accordion-card__summary">
								<span class="accordion-card__title">
									<?php if ( ! empty( $seps_item['badge'] ) ) : ?>
										<span class="badge"><?php echo esc_html( $seps_item['badge'] ); ?></span>
									<?php endif; ?>
									<span><?php echo esc_html( $seps_item['title'] ); ?></span>
								</span>
								<?php echo seps_icon( 'chevron-circle', 20, 'accordion-card__icon' ); ?>
							</summary>
							<?php if ( ! empty( $seps_item['content'] ) ) : ?>
								<p class="accordion-card__content"><?php echo esc_html( $seps_item['content'] ); ?></p>
							<?php endif; ?>
						</details>
					<?php endforeach; ?>
				</div>

				<?php if ( $seps_has_image ) : ?>
					<div class="badge-accordion__media"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
