<?php
/**
 * Section: Text + Image (e.g. "Transport", "Registrations").
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $background 'white' | 'lilac' | 'navy'.
 *     @type string $heading    Heading.
 *     @type string $content    Text (HTML).
 *     @type array  $buttons    Repeater: link, style ('accent'|'primary').
 *     @type int    $image      Attachment ID.
 *     @type string $image_size     'narrow' (fills the text height) | 'wide' (landscape) | 'large' (half width).
 *     @type string $image_position 'right' (default) | 'left'.
 *     @type string $decor      'none' | 'flower' | 'halves'.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_content = trim( (string) ( $args['content'] ?? '' ) );

if ( '' === $seps_heading && '' === $seps_content ) {
	return;
}

$seps_background = in_array( $args['background'] ?? '', array( 'white', 'lilac', 'navy' ), true ) ? $args['background'] : 'white';
$seps_size       = in_array( $args['image_size'] ?? '', array( 'narrow', 'wide', 'large' ), true ) ? $args['image_size'] : 'narrow';
$seps_decor      = array(
	'flower' => 'flower-brand',
	'halves' => 'halves-lilac',
)[ $args['decor'] ?? 'none' ] ?? '';
$seps_id         = wp_unique_id( 'seps-text-image-' );
$seps_image      = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'text-image__image',
		'sizes' => array(
			'narrow' => '(min-width: 1024px) 384px, 100vw',
			'wide'   => '(min-width: 1024px) 593px, 100vw',
			'large'  => '(min-width: 1024px) 50vw, 100vw',
		)[ $seps_size ],
	)
);

$seps_classes = array( 'text-image', 'text-image--' . $seps_background );
if ( $seps_image ) {
	$seps_classes[] = 'text-image--' . $seps_size;

	if ( 'left' === ( $args['image_position'] ?? 'right' ) ) {
		$seps_classes[] = 'text-image--image-left';
	}
}
?>
<section class="<?php echo esc_attr( implode( ' ', $seps_classes ) ); ?>"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<?php if ( $seps_decor ) : ?>
		<?php echo seps_decor( $seps_decor, 'text-image__decor' ); ?>
	<?php endif; ?>

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
			<div class="text-image__media"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
		<?php endif; ?>
	</div>
</section>
