<?php
/**
 * Theme bootstrap.
 *
 * Keep this file thin: it only defines constants and loads modules from /inc.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

define( 'SEPS_THEME_VERSION', '1.0.0' );
define( 'SEPS_THEME_DIR', get_template_directory() );
define( 'SEPS_THEME_URI', get_template_directory_uri() );

$seps_modules = array(
	'inc/setup.php',
	'inc/assets.php',
	'inc/template-tags.php',
	'inc/integrations/contact-form-7.php',
	'inc/scf/bootstrap.php',
	'inc/scf/fields-site-settings.php',
	'inc/scf/fields-front-page.php',
	'inc/scf/fields-page-sections.php',
);

foreach ( $seps_modules as $seps_module ) {
	require_once SEPS_THEME_DIR . '/' . $seps_module;
}

unset( $seps_modules, $seps_module );
