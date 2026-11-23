<?php
/**
 * Section: Map ("Our Location", Contact page) — heading, intro, button and
 * an embedded Google Map.
 *
 * Only Google Maps embed addresses are rendered, so the field cannot be used
 * to frame arbitrary sites.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading   Heading.
 *     @type string $intro     Intro text.
 *     @type array  $button    SCF link (e.g. "View Location").
 *     @type string $embed_url Google Maps embed URL.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_embed   = esc_url_raw( (string) ( $args['embed_url'] ?? '' ), array( 'https' ) );
$seps_parts   = wp_parse_url( $seps_embed );
$seps_valid   = $seps_parts
	&& in_array( $seps_parts['host'] ?? '', array( 'www.google.com', 'maps.google.com' ), true )
	&& str_starts_with( (string) ( $seps_parts['path'] ?? '' ), '/maps' );

if ( '' === $seps_heading && ! $seps_valid ) {
	return;
}

$seps_intro = trim( (string) ( $args['intro'] ?? '' ) );
$seps_id    = wp_unique_id( 'seps-map-' );
?>
<section class="map"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<div class="container map__inner">
		<?php if ( $seps_heading || $seps_intro || ! empty( $args['button'] ) ) : ?>
			<header class="map__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $seps_intro ) : ?>
					<p class="map__intro"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
				<?php echo seps_button( $args['button'] ?? null, 'primary' ); ?>
			</header>
		<?php endif; ?>

		<?php if ( $seps_valid ) : ?>
			<div class="map__frame">
				<iframe
					class="map__embed"
					src="<?php echo esc_url( $seps_embed ); ?>"
					title="<?php echo esc_attr( $seps_heading ? $seps_heading : __( 'Map', 'seps' ) ); ?>"
					loading="lazy"
					referrerpolicy="no-referrer-when-downgrade"
					allowfullscreen
				></iframe>
			</div>
		<?php endif; ?>
	</div>
</section>
