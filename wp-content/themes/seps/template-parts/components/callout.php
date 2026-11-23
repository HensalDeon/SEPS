<?php
/**
 * Component: Callout — a panel with a thick brand left border, an icon +
 * title, text and an optional photo on the right.
 *
 * Used by the Highlights & Focus Areas section (lilac) and the Checklist
 * section (white).
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $icon    Content icon name.
 *     @type string $title   Title.
 *     @type string $html    Text as HTML (sanitised here with wp_kses_post).
 *     @type int    $image   Attachment ID.
 *     @type string $variant 'lilac' (default) or 'light'.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_title = trim( (string) ( $args['title'] ?? '' ) );
$seps_html  = trim( (string) ( $args['html'] ?? '' ) );

if ( '' === $seps_title && '' === $seps_html ) {
	return;
}

$seps_image = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'callout__image',
		'sizes' => '(min-width: 1024px) 615px, 100vw',
	)
);

$seps_classes = 'callout';
if ( 'light' === ( $args['variant'] ?? 'lilac' ) ) {
	$seps_classes .= ' callout--light';
}
if ( $seps_image ) {
	$seps_classes .= ' callout--has-image';
}
?>
<div class="<?php echo esc_attr( $seps_classes ); ?>">
	<div class="callout__body">
		<?php if ( $seps_title ) : ?>
			<h3 class="callout__title">
				<?php echo seps_content_icon( (string) ( $args['icon'] ?? '' ), 'callout__icon' ); ?>
				<?php echo esc_html( $seps_title ); ?>
			</h3>
		<?php endif; ?>
		<?php if ( $seps_html ) : ?>
			<div class="callout__text"><?php echo wp_kses_post( $seps_html ); ?></div>
		<?php endif; ?>
	</div>
	<?php if ( $seps_image ) : ?>
		<div class="callout__media"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
	<?php endif; ?>
</div>
