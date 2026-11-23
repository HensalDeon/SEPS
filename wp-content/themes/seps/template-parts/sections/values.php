<?php
/**
 * Section: Values & Guidelines (e.g. "Core Values & Code of Conduct").
 *
 * A guidelines panel (bold label + text points, optional button) followed by
 * an exclusive accordion of values, each with a list of points.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading      Heading.
 *     @type string $intro        Intro text.
 *     @type string $panel_title  Guidelines panel title.
 *     @type array  $panel_items  Repeater: label, text.
 *     @type array  $panel_button SCF link.
 *     @type array  $accordion    Repeater: title, subtitle, points (one per line).
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_heading     = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro       = trim( (string) ( $args['intro'] ?? '' ) );
$seps_panel_title = trim( (string) ( $args['panel_title'] ?? '' ) );
$seps_points      = array_filter( (array) ( $args['panel_items'] ?? array() ), static fn( $row ): bool => ! empty( $row['label'] ) || ! empty( $row['text'] ) );
$seps_button      = seps_button( $args['panel_button'] ?? null );
$seps_values      = array_filter( (array) ( $args['accordion'] ?? array() ), static fn( $row ): bool => ! empty( $row['title'] ) );

if ( ! $seps_points && ! $seps_values ) {
	return;
}

$seps_id    = wp_unique_id( 'seps-values-' );
$seps_group = wp_unique_id( 'seps-values-accordion-' );
?>
<section class="values"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<div class="container">
		<?php if ( $seps_heading || $seps_intro ) : ?>
			<header class="values__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title section-title--accent"><?php echo esc_html( $seps_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $seps_intro ) : ?>
					<p class="values__intro"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<div class="values__body">
			<?php if ( $seps_points || $seps_button ) : ?>
				<div class="guidelines">
					<?php if ( $seps_panel_title ) : ?>
						<h3 class="guidelines__title"><?php echo esc_html( $seps_panel_title ); ?></h3>
					<?php endif; ?>

					<?php if ( $seps_points ) : ?>
						<ul class="guidelines__list" role="list">
							<?php foreach ( $seps_points as $seps_point ) : ?>
								<li class="guidelines__item">
									<?php echo seps_icon( 'point', 24, 'guidelines__bullet' ); ?>
									<p>
										<?php if ( ! empty( $seps_point['label'] ) ) : ?>
											<strong><?php echo esc_html( $seps_point['label'] ); ?></strong><?php echo empty( $seps_point['text'] ) ? '' : ': '; ?>
										<?php endif; ?>
										<?php echo esc_html( (string) ( $seps_point['text'] ?? '' ) ); ?>
									</p>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php echo $seps_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
				</div>
			<?php endif; ?>

			<?php if ( $seps_values ) : ?>
				<div class="values__accordion">
					<?php foreach ( array_values( $seps_values ) as $seps_index => $seps_value ) : ?>
						<?php $seps_items = array_filter( array_map( 'trim', preg_split( '/\R/', (string) ( $seps_value['points'] ?? '' ) ) ) ); ?>
						<details class="accordion-card" name="<?php echo esc_attr( $seps_group ); ?>" <?php echo 0 === $seps_index ? 'open' : ''; ?>>
							<summary class="accordion-card__summary">
								<span class="accordion-card__title">
									<strong><?php echo esc_html( $seps_value['title'] ); ?></strong>
									<?php if ( ! empty( $seps_value['subtitle'] ) ) : ?>
										<span> - <?php echo esc_html( $seps_value['subtitle'] ); ?></span>
									<?php endif; ?>
								</span>
								<?php echo seps_icon( 'chevron-circle-dark', 20, 'accordion-card__icon', 20.667 ); ?>
							</summary>

							<?php if ( $seps_items ) : ?>
								<ul class="accordion-card__list" role="list">
									<?php foreach ( $seps_items as $seps_item ) : ?>
										<li>
											<?php echo seps_icon( 'point-muted', 20, 'accordion-card__bullet' ); ?>
											<span><?php echo esc_html( $seps_item ); ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</details>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
