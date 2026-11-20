<?php
/**
 * Section: Highlights strip.
 *
 * Static, evenly spaced row on wide screens; a CSS marquee on narrower ones
 * (the cloned list is aria-hidden so screen readers hear each item once).
 * Motion is disabled for users who prefer reduced motion.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type array $items Repeater: label.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_labels = array_values(
	array_filter(
		array_map( static fn( $row ): string => trim( (string) ( $row['label'] ?? '' ) ), (array) ( $args['items'] ?? array() ) )
	)
);

if ( ! $seps_labels ) {
	return;
}

$seps_render_list = static function ( array $labels, bool $is_clone ): void {
	printf(
		'<ul class="highlights__list" role="list"%s>',
		$is_clone ? ' aria-hidden="true" data-clone' : ''
	);
	foreach ( $labels as $label ) {
		printf( '<li class="highlights__item">%s</li>', esc_html( $label ) );
	}
	echo '</ul>';
};
?>
<section class="highlights" aria-label="<?php esc_attr_e( 'School highlights', 'seps' ); ?>">
	<div class="highlights__track">
		<?php
		$seps_render_list( $seps_labels, false );
		$seps_render_list( $seps_labels, true );
		?>
	</div>
</section>
