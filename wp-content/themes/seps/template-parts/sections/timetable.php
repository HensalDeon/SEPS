<?php
/**
 * Section: Timetable (e.g. "A Day at Sunrise").
 *
 * Days, each with rows of time slots. Slots are entered one per line as
 * "Name | Time". The last slot of a day stretches to fill its row (in the
 * design: Student Dispersal).
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading.
 *     @type string $intro   Intro (large text).
 *     @type array  $days    Repeater: label, rows (slots textarea).
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_days = array();
foreach ( (array) ( $args['days'] ?? array() ) as $seps_day ) {
	$seps_rows = array();

	foreach ( (array) ( $seps_day['rows'] ?? array() ) as $seps_row ) {
		$seps_slots = array();

		foreach ( preg_split( '/\R/', (string) ( $seps_row['slots'] ?? '' ) ) as $seps_line ) {
			$seps_parts = array_map( 'trim', explode( '|', $seps_line, 2 ) );

			if ( '' !== $seps_parts[0] ) {
				$seps_slots[] = array(
					'name' => $seps_parts[0],
					'time' => $seps_parts[1] ?? '',
				);
			}
		}

		if ( $seps_slots ) {
			$seps_rows[] = $seps_slots;
		}
	}

	if ( $seps_rows ) {
		$seps_days[] = array(
			'label' => trim( (string) ( $seps_day['label'] ?? '' ) ),
			'rows'  => $seps_rows,
		);
	}
}

if ( ! $seps_days ) {
	return;
}

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$seps_id      = wp_unique_id( 'seps-timetable-' );
?>
<section class="timetable"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<?php echo seps_decor( 'hourglass-brand', 'timetable__decor' ); ?>

	<div class="container">
		<?php if ( $seps_heading || $seps_intro ) : ?>
			<header class="timetable__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $seps_intro ) : ?>
					<p class="lead lead--regular"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<div class="timetable__days">
			<?php foreach ( $seps_days as $seps_day ) : ?>
				<div class="timetable__day">
					<?php if ( $seps_day['label'] ) : ?>
						<h3 class="timetable__day-label"><?php echo esc_html( $seps_day['label'] ); ?></h3>
					<?php endif; ?>

					<?php foreach ( $seps_day['rows'] as $seps_row_index => $seps_slots ) : ?>
						<?php $seps_last_row = count( $seps_day['rows'] ) - 1 === $seps_row_index; ?>
						<ul class="timetable__row" role="list">
							<?php foreach ( $seps_slots as $seps_slot_index => $seps_slot ) : ?>
								<?php $seps_wide = $seps_last_row && count( $seps_slots ) - 1 === $seps_slot_index; ?>
								<li class="timetable__slot<?php echo $seps_wide ? ' timetable__slot--wide' : ''; ?>">
									<strong><?php echo esc_html( $seps_slot['name'] ); ?></strong>
									<?php if ( $seps_slot['time'] ) : ?>
										<span><?php echo esc_html( $seps_slot['time'] ); ?></span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
