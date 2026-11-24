<?php
/**
 * Section: Text + Image (e.g. "Transport", "Registrations", "SEPS Chronicle",
 * "Parent Handbook").
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $background 'white' | 'lilac' | 'navy'.
 *     @type string $heading    Heading.
 *     @type string $content    Text (HTML).
 *     @type array  $buttons    Repeater: link, style ('accent'|'primary').
 *     @type int    $image      Attachment ID.
 *     @type string $image_size     'narrow' / 'fill' (fill the text height; 384 / 592px) | 'wide' (landscape)
 *                                  | 'large' (half width) | 'circle' / 'circle_small' / 'circle_large' (338 / 278 / 506px)
 *                                  | 'document' (framed page preview; its full-screen icon opens the first button's link).
 *     @type string $image_position 'right' (default) | 'left'.
 *     @type string $decor      'none' | 'flower' | 'halves' | 'flower_photo' (yellow, on the photo)
 *                              | 'sun_photo' (beside the photo) | 'halves_brand' (blue, right edge).
 *     @type bool   $corner     Rounded bottom-right corner.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_content = trim( (string) ( $args['content'] ?? '' ) );

if ( '' === $seps_heading && '' === $seps_content ) {
	return;
}

$seps_background = in_array( $args['background'] ?? '', array( 'white', 'lilac', 'navy' ), true ) ? $args['background'] : 'white';
$seps_sizes      = array(
	'narrow'       => '(min-width: 1024px) 384px, 100vw',
	'fill'         => '(min-width: 1024px) 592px, 100vw',
	'wide'         => '(min-width: 1024px) 593px, 100vw',
	'large'        => '(min-width: 1024px) 50vw, 100vw',
	'circle'       => '(min-width: 1024px) 338px, 280px',
	'circle_small' => '(min-width: 1024px) 278px, 240px',
	'circle_large' => '(min-width: 1024px) 506px, 320px',
	'document'     => '(min-width: 1024px) 379px, 80vw',
);
$seps_size       = isset( $seps_sizes[ $args['image_size'] ?? '' ] ) ? $args['image_size'] : 'narrow';
$seps_decor_key  = (string) ( $args['decor'] ?? 'none' );
$seps_decor      = array(
	'flower'       => 'flower-brand',
	'halves'       => 'halves-lilac',
	'halves_brand' => 'halves-brand',
)[ $seps_decor_key ] ?? '';
$seps_photo_dec  = array(
	'flower_photo' => 'flower-yellow',
	'sun_photo'    => 'sun-yellow',
)[ $seps_decor_key ] ?? '';
$seps_id         = wp_unique_id( 'seps-text-image-' );
$seps_image      = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'text-image__image',
		'sizes' => $seps_sizes[ $seps_size ],
	)
);

$seps_classes = array( 'text-image', 'text-image--' . $seps_background );
if ( ! empty( $args['corner'] ) ) {
	$seps_classes[] = 'text-image--corner';
}
if ( $seps_image ) {
	$seps_classes[] = 'text-image--' . str_replace( '_', '-', $seps_size );

	if ( in_array( $seps_size, array( 'narrow', 'fill' ), true ) ) {
		$seps_classes[] = 'text-image--fills';
	} elseif ( str_starts_with( $seps_size, 'circle' ) ) {
		$seps_classes[] = 'text-image--round';
	}

	if ( 'left' === ( $args['image_position'] ?? 'right' ) ) {
		$seps_classes[] = 'text-image--image-left';
	}
}
?>
<section class="<?php echo esc_attr( implode( ' ', $seps_classes ) ); ?>"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<?php if ( $seps_decor ) : ?>
		<?php echo seps_decor( $seps_decor, 'text-image__decor' ); ?>
	<?php endif; ?>

	<?php
	// Document preview: the full-screen icon opens the first button's link.
	$seps_doc_link = 'document' === $seps_size ? seps_link_data( $args['buttons'][0]['link'] ?? null ) : null;
	?>

	<div class="container text-image__inner">
		<div class="text-image__content">
			<?php if ( $seps_heading ) : ?>
				<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
			<?php endif; ?>

			<?php if ( $seps_content ) : ?>
				<div class="text-image__text rich-text"><?php echo wp_kses_post( $seps_content ); ?></div>
			<?php endif; ?>

			<?php
			$seps_buttons = '';
			foreach ( (array) ( $args['buttons'] ?? array() ) as $seps_button ) {
				$seps_buttons .= seps_button( $seps_button['link'] ?? null, 'primary' === ( $seps_button['style'] ?? 'accent' ) ? 'primary' : 'accent' );
			}
			?>
			<?php if ( $seps_buttons ) : ?>
				<div class="text-image__buttons"><?php echo $seps_buttons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from seps_button(). ?></div>
			<?php endif; ?>
		</div>

		<?php if ( $seps_image ) : ?>
			<div class="text-image__media">
				<?php if ( 'document' === $seps_size ) : ?>
					<div class="text-image__page"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
					<?php if ( $seps_doc_link ) : ?>
						<a class="text-image__zoom" <?php echo seps_link_attributes( $seps_doc_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped attributes. ?>>
							<?php echo seps_icon( 'fullscreen', 45 ); ?>
							<span class="screen-reader-text">
								<?php
								/* translators: %s: Section heading, e.g. "Parent Handbook". */
								echo esc_html( sprintf( __( 'Open %s', 'seps' ), $seps_heading ? $seps_heading : $seps_doc_link['title'] ) );
								?>
							</span>
						</a>
					<?php endif; ?>
				<?php else : ?>
					<?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
				<?php endif; ?>
				<?php if ( $seps_photo_dec ) : ?>
					<?php echo seps_decor( $seps_photo_dec, 'text-image__photo-decor text-image__photo-decor--' . $seps_photo_dec ); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
