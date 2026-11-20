<?php
/**
 * Plugin Name:       SEPS Core
 * Description:       Content model for the SEPS website: Testimonials and FAQs post types, their fields, and the `wp seps seed` demo-content command. Kept separate from the theme so content survives a redesign.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  secure-custom-fields
 * Author:            Eighty Six Media
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       seps-core
 *
 * @package SEPS\Core
 */

defined( 'ABSPATH' ) || exit;

define( 'SEPS_CORE_VERSION', '1.0.0' );
define( 'SEPS_CORE_DIR', plugin_dir_path( __FILE__ ) );

require_once SEPS_CORE_DIR . 'includes/post-types.php';
require_once SEPS_CORE_DIR . 'includes/fields.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once SEPS_CORE_DIR . 'includes/cli/class-seed-command.php';
	WP_CLI::add_command( 'seps seed', SEPS\Core\CLI\Seed_Command::class );
}
