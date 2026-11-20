<?php
/**
 * Section: Hero.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $title  Heading (line breaks preserved).
 *     @type string $text   Intro paragraph.
 *     @type array  $button SCF link.
 *     @type int    $image  Attachment ID.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_title = trim( (string) ( $args['title'] ?? '' ) );

if ( '' === $seps_title ) {
	return;
}

$seps_text  = trim( (string) ( $args['text'] ?? '' ) );
$seps_image = seps_image(
	$args['image'] ?? 0,
	'1536x1536',
	array(
		'class'         => 'hero__image',
		'sizes'         => '(min-width: 1024px) 48vw, 100vw',
		'loading'       => 'eager',
		'fetchpriority' => 'high',
		'decoding'      => 'async',
	)
);
?>
<section class="hero" aria-labelledby="hero-title">
	<div class="hero__panel">
		<div class="hero__content">
			<h1 id="hero-title" class="hero__title"><?php echo seps_multiline( $seps_title ); ?></h1>

			<?php if ( $seps_text ) : ?>
				<p class="hero__text"><?php echo esc_html( $seps_text ); ?></p>
			<?php endif; ?>

			<?php echo seps_button( $args['button'] ?? null, 'accent' ); ?>
		</div>
	</div>

	<?php if ( $seps_image ) : ?>
		<div class="hero__media">
			<?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
			<?php echo seps_decor( 'flower-yellow', 'hero__decor' ); ?>
		</div>
	<?php endif; ?>
</section>
