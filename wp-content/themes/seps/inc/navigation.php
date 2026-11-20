<?php
/**
 * Navigation menu enhancements.
 *
 * Instead of a full custom Walker we hook the one filter we need to inject
 * accessible sub-menu toggles into the primary menu. (Core already outputs
 * aria-current on the active link.)
 *
 * @package SEPS
 */

namespace SEPS\Theme\Navigation;

use stdClass;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Append a disclosure button after parent items in the primary menu.
 *
 * @param string   $item_output Rendered item HTML.
 * @param WP_Post  $item        Menu item.
 * @param int      $depth       Depth.
 * @param stdClass $args        wp_nav_menu() arguments.
 */
function submenu_toggle( string $item_output, WP_Post $item, int $depth, stdClass $args ): string {
	if ( 'primary' !== ( $args->theme_location ?? '' ) ) {
		return $item_output;
	}

	if ( ! in_array( 'menu-item-has-children', (array) $item->classes, true ) ) {
		return $item_output;
	}

	$label = sprintf(
		/* translators: %s: Parent menu item title. */
		__( 'Show submenu for %s', 'seps' ),
		wp_strip_all_tags( $item->title )
	);

	$button = sprintf(
		'<button type="button" class="site-nav__toggle" aria-expanded="false" aria-label="%1$s" data-submenu-toggle>' .
		'<svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' .
		'</button>',
		esc_attr( $label )
	);

	return $item_output . $button;
}
add_filter( 'walker_nav_menu_start_el', __NAMESPACE__ . '\\submenu_toggle', 10, 4 );
