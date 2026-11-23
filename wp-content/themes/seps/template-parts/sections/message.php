<?php
/**
 * Section: Message with photo and pull quote (e.g. Chairman's message).
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading.
 *     @type string $message Message (already wpautop-formatted by SCF).
 *     @type string $name    Name.
 *     @type string $role    Designation.
 *     @type int    $image   Attachment ID.
 *     @type string $quote   Pull quote.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_message = trim( (string) ( $args['message'] ?? '' ) );

if ( '' === $seps_message ) {
	return;
}

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_name    = trim( (string) ( $args['name'] ?? '' ) );
$seps_role    = trim( (string) ( $args['role'] ?? '' ) );
$seps_quote   = trim( (string) ( $args['quote'] ?? '' ) );
$seps_id      = wp_unique_id( 'seps-message-' );
$seps_image   = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'message__image',
		'sizes' => '(min-width: 1024px) 488px, 100vw',
	)
);

// Two overlapping marks form one quotation glyph, as in the design (33×44 each).
$seps_glyph = seps_icon( 'quote-mark', 33, '', 44 );
$seps_open  = '<span class="quote-mark" aria-hidden="true">' . $seps_glyph . $seps_glyph . '</span>';
$seps_close = '<span class="quote-mark quote-mark--close" aria-hidden="true">' . $seps_glyph . $seps_glyph . '</span>';
?>
<section class="message<?php echo $seps_quote ? ' message--has-quote' : ''; ?>"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<?php echo seps_decor( 'flower-brand', 'message__decor' ); ?>

	<div class="container message__inner">
		<div class="message__content">
			<?php if ( $seps_heading ) : ?>
				<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
			<?php endif; ?>

			<div class="message__body"><?php echo wp_kses_post( $seps_message ); ?></div>

			<?php if ( $seps_name || $seps_role ) : ?>
				<p class="message__signature">
					<?php if ( $seps_name ) : ?>
						<strong><?php echo esc_html( $seps_name ); ?></strong>
					<?php endif; ?>
					<?php if ( $seps_role ) : ?>
						<span><?php echo esc_html( $seps_role ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>

		<?php if ( $seps_image ) : ?>
			<div class="message__media"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
		<?php endif; ?>

		<?php if ( $seps_quote ) : ?>
			<blockquote class="message__quote">
				<p>
					<?php
					echo $seps_open; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from seps_icon().
					echo esc_html( $seps_quote );
					echo $seps_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from seps_icon().
					?>
				</p>
			</blockquote>
		<?php endif; ?>
	</div>
</section>
