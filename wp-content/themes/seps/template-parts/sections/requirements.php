<?php
/**
 * Section: Requirements (e.g. "Admission Process").
 *
 * Heading, intro and button, then two columns: badge rows (e.g. age
 * requirements) and a bulleted checklist (e.g. documents required).
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading     Heading.
 *     @type string $intro       Intro.
 *     @type array  $button      SCF link.
 *     @type string $left_title  Left column title.
 *     @type array  $left_rows   Repeater: badge, text.
 *     @type string $right_title Right column title.
 *     @type string $right_items Checklist, one per line.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_rows  = array_filter( (array) ( $args['left_rows'] ?? array() ), static fn( $row ): bool => ! empty( $row['badge'] ) || ! empty( $row['text'] ) );
$seps_items = array_filter( array_map( 'trim', preg_split( '/\R/', (string) ( $args['right_items'] ?? '' ) ) ) );

if ( ! $seps_rows && ! $seps_items ) {
	return;
}

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$seps_id      = wp_unique_id( 'seps-requirements-' );
?>
<section class="requirements"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<div class="container">
		<header class="requirements__header">
			<?php if ( $seps_heading ) : ?>
				<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $seps_intro ) : ?>
				<p class="requirements__intro"><?php echo esc_html( $seps_intro ); ?></p>
			<?php endif; ?>
			<?php echo seps_button( $args['button'] ?? null, 'accent' ); ?>
		</header>

		<div class="requirements__grid">
			<?php if ( $seps_rows ) : ?>
				<div class="requirements__column">
					<?php if ( ! empty( $args['left_title'] ) ) : ?>
						<h3 class="requirements__title"><?php echo esc_html( $args['left_title'] ); ?></h3>
					<?php endif; ?>
					<ul class="requirements__panel requirements__rows" role="list">
						<?php foreach ( $seps_rows as $seps_row ) : ?>
							<li>
								<?php if ( ! empty( $seps_row['badge'] ) ) : ?>
									<span class="badge badge--navy"><?php echo esc_html( $seps_row['badge'] ); ?></span>
								<?php endif; ?>
								<span><?php echo esc_html( (string) ( $seps_row['text'] ?? '' ) ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $seps_items ) : ?>
				<div class="requirements__column">
					<?php if ( ! empty( $args['right_title'] ) ) : ?>
						<h3 class="requirements__title"><?php echo esc_html( $args['right_title'] ); ?></h3>
					<?php endif; ?>
					<div class="requirements__panel">
						<ul class="requirements__list">
							<?php foreach ( $seps_items as $seps_item ) : ?>
								<li><?php echo esc_html( $seps_item ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
