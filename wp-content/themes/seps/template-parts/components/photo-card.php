<?php
/**
 * Component: Photo card (photo, title, optional text and link).
 *
 * A link with text renders as a "View"-style button; a link without text
 * makes the whole card clickable (stretched link on the title).
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type int    $image Attachment ID.
 *     @type string $title Title.
 *     @type string $text  Text (plain, line breaks preserved).
 *     @type array  $link  SCF link.
 *     @type string $sizes Image sizes attribute.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_title = trim( (string) ( $args['title'] ?? '' ) );

if ( '' === $seps_title ) {
	return;
}

$seps_text  = trim( (string) ( $args['text'] ?? '' ) );
$seps_link  = seps_link_data( $args['link'] ?? null );
$seps_whole = $seps_link && '' === trim( (string) ( $args['link']['title'] ?? '' ) );
$seps_image = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'photo-card__image',
		'sizes' => (string) ( $args['sizes'] ?? '(min-width: 768px) 33vw, 100vw' ),
	)
);
?>
<article class="photo-card<?php echo $seps_whole ? ' photo-card--linked' : ''; ?>">
	<?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>

	<div class="photo-card__body">
		<h3 class="photo-card__title">
			<?php if ( $seps_whole ) : ?>
				<a class="photo-card__link" <?php echo seps_link_attributes( $seps_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped attributes. ?>><?php echo esc_html( $seps_title ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $seps_title ); ?>
			<?php endif; ?>
		</h3>

		<?php if ( $seps_text ) : ?>
			<p class="photo-card__text"><?php echo seps_multiline( $seps_text ); ?></p>
		<?php endif; ?>

		<?php if ( ! $seps_whole ) : ?>
			<?php echo seps_button( $args['link'] ?? null, 'primary', 'photo-card__button', $seps_title ); ?>
		<?php endif; ?>
	</div>
</article>
