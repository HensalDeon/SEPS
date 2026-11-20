<?php
/**
 * Theme supports, menus, image sizes and small core tweaks.
 *
 * @package SEPS
 */

namespace SEPS\Theme\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Register theme features.
 */
function setup(): void {
	load_theme_textdomain( 'seps', SEPS_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'               => 160,
			'width'                => 176,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => false,
		)
	);

	register_nav_menus(
		array(
			'primary'        => __( 'Header: Primary navigation', 'seps' ),
			'utility'        => __( 'Header: Top bar links', 'seps' ),
			'footer_campus'  => __( 'Footer: Column 1 (Explore Our Campus)', 'seps' ),
			'footer_quick'   => __( 'Footer: Column 2 (Quick Links)', 'seps' ),
			'footer_portals' => __( 'Footer: Column 3 (SEPS Portals)', 'seps' ),
			'legal'          => __( 'Footer: Legal links', 'seps' ),
		)
	);

	// Hard-cropped sizes matching the design's aspect ratios (2x for retina).
	add_image_size( 'seps-card', 1054, 670, true );     // Programs, facilities.
	add_image_size( 'seps-wide', 1570, 810, true );     // Featured news.
	add_image_size( 'seps-thumb', 616, 360, true );     // News list.
	add_image_size( 'seps-square', 1000, 1000, true );  // Circular crops.
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );

/**
 * Content width for embeds and media.
 */
function content_width(): void {
	$GLOBALS['content_width'] = 1200; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\\content_width', 0 );

/**
 * Make custom sizes selectable in the media modal.
 *
 * @param array<string,string> $sizes Size names keyed by slug.
 * @return array<string,string>
 */
function media_size_names( array $sizes ): array {
	return array_merge(
		$sizes,
		array(
			'seps-card'   => __( 'Card (landscape)', 'seps' ),
			'seps-wide'   => __( 'Wide', 'seps' ),
			'seps-square' => __( 'Square', 'seps' ),
		)
	);
}
add_filter( 'image_size_names_choose', __NAMESPACE__ . '\\media_size_names' );

/**
 * Remove the emoji polyfill: the design relies on native emoji (highlights strip)
 * and the script is render-blocking weight we don't need.
 */
function disable_emoji(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', __NAMESPACE__ . '\\disable_emoji' );

/**
 * Shorter excerpts for news cards (auto-generated excerpts only).
 */
function excerpt_length(): int {
	return 24;
}
add_filter( 'excerpt_length', __NAMESPACE__ . '\\excerpt_length' );

/**
 * Plain ellipsis instead of core's "[…]".
 */
function excerpt_more(): string {
	return '&hellip;';
}
add_filter( 'excerpt_more', __NAMESPACE__ . '\\excerpt_more' );
