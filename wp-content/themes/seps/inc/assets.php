<?php
/**
 * Front-end asset loading.
 *
 * Compiled files live in assets/dist (see package.json). Versions use file
 * modification time so browsers pick up every rebuild without manual bumps.
 *
 * @package SEPS
 */

namespace SEPS\Theme\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Cache-busting version for a theme file.
 *
 * @param string $relative_path Path relative to the theme root.
 */
function version( string $relative_path ): string {
	$file = SEPS_THEME_DIR . '/' . ltrim( $relative_path, '/' );

	return file_exists( $file ) ? (string) filemtime( $file ) : SEPS_THEME_VERSION;
}

/**
 * Enqueue theme styles and scripts.
 */
function enqueue(): void {
	wp_enqueue_style(
		'seps-main',
		SEPS_THEME_URI . '/assets/dist/css/main.css',
		array(),
		version( 'assets/dist/css/main.css' )
	);

	wp_enqueue_script(
		'seps-main',
		SEPS_THEME_URI . '/assets/dist/js/main.js',
		array(),
		version( 'assets/dist/js/main.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue' );

/**
 * Preload the primary (latin) font file to avoid a late text swap.
 */
function preload_font(): void {
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( SEPS_THEME_URI . '/assets/fonts/outfit-latin-variable.woff2' )
	);
}
add_action( 'wp_head', __NAMESPACE__ . '\\preload_font', 1 );

/**
 * Drop block-library CSS on the front page: it is composed entirely of theme
 * templates and SCF data, so those styles would be dead weight there.
 */
function dequeue_block_styles(): void {
	if ( ! is_front_page() ) {
		return;
	}

	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\dequeue_block_styles', 100 );
