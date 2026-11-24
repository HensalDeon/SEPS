<?php
/**
 * Template tags used across theme templates.
 *
 * Every function that returns markup returns it fully escaped, so templates
 * can echo the result directly. These functions are declared as
 * customAutoEscapedFunctions in phpcs.xml.dist.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

/*
 * -----------------------------------------------------------------------------
 * Data access (Secure Custom Fields)
 * -----------------------------------------------------------------------------
 */

/**
 * Whether Secure Custom Fields (or a compatible ACF API) is loaded.
 */
function seps_scf_active(): bool {
	return function_exists( 'get_field' );
}

/**
 * Safe wrapper around get_field(): the front end degrades gracefully instead
 * of fatalling when the plugin is deactivated.
 *
 * @param string          $selector Field name.
 * @param int|string|bool $post_id  Post ID, 'option', or false for the current post.
 * @return mixed
 */
function seps_field( string $selector, int|string|bool $post_id = false ): mixed {
	return seps_scf_active() ? get_field( $selector, $post_id ) : null;
}

/**
 * Read a value from the global "Site Settings" options page.
 *
 * Values are memoised per request because the header and footer ask for the
 * same options several times.
 *
 * @param string $selector Field name.
 * @return mixed
 */
function seps_option( string $selector ): mixed {
	static $cache = array();

	if ( ! array_key_exists( $selector, $cache ) ) {
		$cache[ $selector ] = seps_field( $selector, 'option' );
	}

	return $cache[ $selector ];
}

/*
 * -----------------------------------------------------------------------------
 * Assets
 * -----------------------------------------------------------------------------
 */

/**
 * Public URL for a file inside the theme's assets directory.
 *
 * @param string $path Path relative to /assets.
 */
function seps_asset_url( string $path ): string {
	return SEPS_THEME_URI . '/assets/' . ltrim( $path, '/' );
}

/**
 * Decorative SVG shapes from the design (flowers, suns, hourglass).
 *
 * Rendered as <img> with empty alt so assistive tech skips them.
 *
 * @param string $name  File name in assets/images/decor without extension.
 * @param string $class Extra class names.
 */
function seps_decor( string $name, string $class = '' ): string {
	// Intrinsic size: width, or [width, height] for non-square shapes.
	$sizes = array(
		'flower-yellow'   => 50,
		'flower-brand'    => 50,
		'flower-grey'     => 50,
		'sun-yellow'      => 100,
		'sun-lilac'       => 100,
		'hourglass-navy'  => 100,
		'hourglass-brand' => 100,
		'halves-lilac'    => array( 100, 148 ),
		'blob-lilac'      => array( 936, 674 ),
		'halves-white'    => array( 100, 148 ),
		'drop-navy'       => array( 603, 641 ),
		'halves-pair-brand' => 100,
		'halves-pair-white' => 100,
		'halves-brand'    => array( 100, 148 ),
	);

	if ( ! isset( $sizes[ $name ] ) ) {
		return '';
	}

	[ $width, $height ] = is_array( $sizes[ $name ] ) ? $sizes[ $name ] : array( $sizes[ $name ], $sizes[ $name ] );

	return sprintf(
		'<img class="%1$s" src="%2$s" width="%3$d" height="%4$d" alt="" aria-hidden="true" loading="lazy" decoding="async">',
		esc_attr( trim( 'decor decor--' . $name . ' ' . $class ) ),
		esc_url( seps_asset_url( "images/decor/{$name}.svg" ) ),
		$width,
		$height
	);
}

/**
 * Icon from assets/images/icons, rendered as a decorative <img>.
 *
 * @param string     $name   Icon file name without extension.
 * @param int        $size   Rendered width in px (and height, unless given).
 * @param string     $class  Extra class names.
 * @param float|null $height Rendered height in px for non-square icons.
 */
function seps_icon( string $name, int $size = 24, string $class = '', ?float $height = null ): string {
	$name = sanitize_key( $name );

	if ( ! file_exists( SEPS_THEME_DIR . "/assets/images/icons/{$name}.svg" ) ) {
		return '';
	}

	return sprintf(
		'<img class="%1$s" src="%2$s" width="%3$d" height="%4$s" alt="" aria-hidden="true" decoding="async">',
		esc_attr( trim( 'icon icon--' . $name . ' ' . $class ) ),
		esc_url( seps_asset_url( "images/icons/{$name}.svg" ) ),
		$size,
		esc_attr( (string) ( $height ?? $size ) )
	);
}

/**
 * Icons editors can choose for content (tiles, cards, callouts).
 *
 * @return array<string,string> Icon file name => admin label.
 */
function seps_content_icon_choices(): array {
	return array(
		'book'         => __( 'Book', 'seps' ),
		'tick'         => __( 'Tick', 'seps' ),
		'graduation'   => __( 'Graduation cap', 'seps' ),
		'globe'        => __( 'Globe', 'seps' ),
		'bulb'         => __( 'Light bulb', 'seps' ),
		'raindrop'     => __( 'Drop', 'seps' ),
		'sun'          => __( 'Sun', 'seps' ),
		'cross-circle' => __( 'Circle cross', 'seps' ),
		'home'         => __( 'Home', 'seps' ),
		'pin'          => __( 'Location pin', 'seps' ),
		'people'       => __( 'People', 'seps' ),
		'certificate'  => __( 'Certificate', 'seps' ),
		'student'      => __( 'Graduate', 'seps' ),
		'payment'      => __( 'Payment card', 'seps' ),
		'calendar'     => __( 'Calendar', 'seps' ),
		'clock'        => __( 'Clock', 'seps' ),
		'boy'          => __( 'Boy', 'seps' ),
		'girl'         => __( 'Girl', 'seps' ),
		'alert'        => __( 'Alert', 'seps' ),
		'call'         => __( 'Phone', 'seps' ),
		'heart'        => __( 'Heart', 'seps' ),
	);
}

/**
 * Content icon that takes the surrounding text colour.
 *
 * The SVG is inlined so CSS can recolour it to currentColor: one icon file
 * works on light and dark backgrounds (e.g. brand on lilac, white on navy).
 * Inlining also avoids extra requests and cross-origin (CDN) issues.
 * IDs get a per-instance suffix so repeated icons stay valid.
 *
 * @param string $name  Icon from seps_content_icon_choices().
 * @param string $class Extra class names.
 */
function seps_content_icon( string $name, string $class = '' ): string {
	static $files    = array();
	static $instance = 0;

	$name = sanitize_key( $name );

	if ( ! isset( seps_content_icon_choices()[ $name ] ) ) {
		return '';
	}

	if ( ! isset( $files[ $name ] ) ) {
		$path = SEPS_THEME_DIR . "/assets/images/icons/{$name}.svg";
		$svg  = is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local theme file.

		// Trusted theme file: drop the XML prolog and inline display style only.
		$files[ $name ] = trim( (string) preg_replace( array( '/<\?xml[^>]*\?>/', '/\sstyle="[^"]*"/' ), '', $svg, 1 ) );
	}

	if ( '' === $files[ $name ] ) {
		return '';
	}

	$suffix = '-seps' . ( ++$instance );
	$svg    = preg_replace(
		array( '/\sid="([^"]+)"/', '/url\(#([^)]+)\)/', '/href="#([^"]+)"/' ),
		array( ' id="$1' . $suffix . '"', 'url(#$1' . $suffix . ')', 'href="#$1' . $suffix . '"' ),
		$files[ $name ]
	);

	return sprintf(
		'<span class="%1$s" aria-hidden="true">%2$s</span>',
		esc_attr( trim( 'content-icon content-icon--' . $name . ' ' . $class ) ),
		$svg
	);
}

/*
 * -----------------------------------------------------------------------------
 * Content helpers
 * -----------------------------------------------------------------------------
 */

/**
 * Attachment image markup with srcset/sizes, from an SCF image (ID) value.
 *
 * @param mixed                $image Attachment ID (SCF return format "id").
 * @param string               $size  Registered image size.
 * @param array<string,string> $attr  Extra attributes (class, sizes, loading…).
 */
function seps_image( mixed $image, string $size = 'large', array $attr = array() ): string {
	$id = is_array( $image ) ? (int) ( $image['ID'] ?? $image['id'] ?? 0 ) : (int) $image;

	if ( $id <= 0 ) {
		return '';
	}

	return wp_get_attachment_image( $id, $size, false, $attr );
}

/**
 * Escape plain text and preserve editor-entered line breaks.
 *
 * Used for headings where the design has intentional breaks
 * (e.g. "Explore Our / Programs").
 *
 * @param mixed $text Raw text.
 */
function seps_multiline( mixed $text ): string {
	return nl2br( esc_html( trim( (string) $text ) ), false );
}

/**
 * Normalise an SCF link value into url/title/target, or null if unusable.
 *
 * @param mixed $link SCF link array.
 * @return array{url:string,title:string,target:string}|null
 */
function seps_link_data( mixed $link ): ?array {
	if ( ! is_array( $link ) || empty( $link['url'] ) ) {
		return null;
	}

	return array(
		'url'    => (string) $link['url'],
		'title'  => (string) ( $link['title'] ?? '' ),
		'target' => (string) ( $link['target'] ?? '' ),
	);
}

/**
 * Attributes for an anchor, including safe rel for new-tab links.
 *
 * @param array{url:string,title:string,target:string} $link Normalised link.
 */
function seps_link_attributes( array $link ): string {
	$attributes = sprintf( 'href="%s"', esc_url( $link['url'] ) );

	if ( '_blank' === $link['target'] ) {
		$attributes .= ' target="_blank" rel="noopener noreferrer"';
	}

	return $attributes;
}

/**
 * Pill button from an SCF link field.
 *
 * @param mixed  $link       SCF link array.
 * @param string $variant    Visual variant: primary | accent | soft | light.
 * @param string $class      Extra class names.
 * @param string $sr_context Visually hidden context appended to the label so
 *                           repeated labels ("Apply Now", "Read More") stay
 *                           unique for screen-reader users.
 */
function seps_button( mixed $link, string $variant = 'primary', string $class = '', string $sr_context = '' ): string {
	$link = seps_link_data( $link );

	if ( null === $link || '' === $link['title'] ) {
		return '';
	}

	$label = esc_html( $link['title'] );

	if ( '' !== $sr_context ) {
		$label .= sprintf( '<span class="screen-reader-text">: %s</span>', esc_html( $sr_context ) );
	}

	return sprintf(
		'<a class="%1$s" %2$s>%3$s</a>',
		esc_attr( trim( "btn btn--{$variant} {$class}" ) ),
		seps_link_attributes( $link ),
		$label
	);
}

/**
 * tel: URI from a human-formatted phone number.
 *
 * @param string $phone Phone number, e.g. "+971 2 552 9989".
 */
function seps_tel_uri( string $phone ): string {
	return 'tel:' . preg_replace( '/[^\d+]/', '', $phone );
}

/*
 * -----------------------------------------------------------------------------
 * Page Sections (section builder)
 * -----------------------------------------------------------------------------
 */

/**
 * Render the current page's Page Sections.
 *
 * Each row is passed to its template as $args (plus 'variant' => 'page').
 * If the page has no Page Hero, a visually hidden <h1> keeps the heading
 * outline valid.
 *
 * @param int $post_id Page ID.
 * @return int Number of sections rendered.
 */
function seps_render_page_sections( int $post_id ): int {
	$rows = seps_field( 'page_sections', $post_id );

	if ( empty( $rows ) || ! is_array( $rows ) ) {
		return 0;
	}

	$templates = SEPS\Theme\SCF\page_section_templates();
	$layouts   = wp_list_pluck( $rows, 'acf_fc_layout' );

	if ( ! in_array( 'page_hero', $layouts, true ) ) {
		printf( '<h1 class="screen-reader-text">%s</h1>', esc_html( get_the_title( $post_id ) ) );
	}

	$rendered = 0;

	foreach ( $rows as $row ) {
		$layout = (string) ( $row['acf_fc_layout'] ?? '' );

		if ( ! isset( $templates[ $layout ] ) ) {
			continue;
		}

		if ( 'page_hero' === $layout && '' === trim( (string) ( $row['title'] ?? '' ) ) ) {
			$row['title'] = get_the_title( $post_id );
		}

		// Optional Section ID so links can jump to a section (e.g. /academics/#mandatory-disclosure).
		$anchor = sanitize_title( (string) ( $row['anchor'] ?? '' ) );
		if ( '' !== $anchor ) {
			printf( '<span id="%s" class="section-anchor"></span>', esc_attr( $anchor ) );
		}

		get_template_part( 'template-parts/sections/' . $templates[ $layout ], null, array_merge( $row, array( 'variant' => 'page' ) ) );
		++$rendered;
	}

	return $rendered;
}

/*
 * -----------------------------------------------------------------------------
 * Menus
 * -----------------------------------------------------------------------------
 */

/**
 * Render a theme menu location, or nothing if no menu is assigned.
 *
 * Deliberately no fallback_cb: an unassigned location should render empty
 * rather than dumping every page into the header.
 *
 * @param string               $location Theme location slug.
 * @param string               $class    Class for the <ul>.
 * @param int                  $depth    Max depth.
 * @param array<string,string> $args     Extra wp_nav_menu() args.
 */
function seps_menu( string $location, string $class, int $depth = 1, array $args = array() ): string {
	if ( ! has_nav_menu( $location ) ) {
		return '';
	}

	return (string) wp_nav_menu(
		array_merge(
			array(
				'theme_location' => $location,
				'menu_class'     => $class,
				'container'      => false,
				'depth'          => $depth,
				'fallback_cb'    => false,
				'echo'           => false,
			),
			$args
		)
	);
}

/**
 * Menu name assigned to a location; used as the footer column heading so the
 * label is editable from Appearance → Menus.
 *
 * @param string $location Theme location slug.
 */
function seps_menu_label( string $location ): string {
	return has_nav_menu( $location ) ? esc_html( wp_get_nav_menu_name( $location ) ) : '';
}

/*
 * -----------------------------------------------------------------------------
 * Site settings helpers
 * -----------------------------------------------------------------------------
 */

/**
 * Social networks supported by the icon set.
 *
 * @return array<string,string> Network slug => label.
 */
function seps_social_networks(): array {
	return array(
		'facebook'  => __( 'Facebook', 'seps' ),
		'x'         => __( 'X (Twitter)', 'seps' ),
		'instagram' => __( 'Instagram', 'seps' ),
	);
}

/**
 * Social links list markup.
 *
 * @param string $class Class(es) for the <ul>; should include "social".
 */
function seps_social_links( string $class = 'social' ): string {
	$rows     = seps_option( 'social_links' );
	$networks = seps_social_networks();

	if ( empty( $rows ) || ! is_array( $rows ) ) {
		return '';
	}

	$items = '';

	foreach ( $rows as $row ) {
		$network = (string) ( $row['network'] ?? '' );
		$url     = (string) ( $row['url'] ?? '' );

		if ( '' === $url || ! isset( $networks[ $network ] ) ) {
			continue;
		}

		$items .= sprintf(
			'<li><a class="social__link" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s<span class="screen-reader-text">%3$s</span></a></li>',
			esc_url( $url ),
			seps_icon( $network, 18 ),
			esc_html(
				sprintf(
					/* translators: %s: Social network name. */
					__( '%s (opens in a new tab)', 'seps' ),
					$networks[ $network ]
				)
			)
		);
	}

	return '' === $items ? '' : sprintf( '<ul class="%1$s" role="list">%2$s</ul>', esc_attr( $class ), $items );
}

/**
 * Footer copyright line with the {year} token resolved.
 */
function seps_copyright(): string {
	$text = (string) seps_option( 'copyright' );

	if ( '' === $text ) {
		/* translators: 1: Current year, 2: Site name. */
		$text = sprintf( __( '© %1$s %2$s. All rights reserved.', 'seps' ), '{year}', get_bloginfo( 'name' ) );
	}

	return esc_html( str_replace( '{year}', wp_date( 'Y' ), $text ) );
}
