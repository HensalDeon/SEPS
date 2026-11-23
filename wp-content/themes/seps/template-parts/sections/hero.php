<?php
/**
 * Section: Hero.
 *
 * Used by the front page ('home' variant, 950px) and by the Page Hero
 * section on inner pages ('page' variant, 700px, no flower).
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $title   Heading (line breaks preserved).
 *     @type string $text    Intro paragraph.
 *     @type array  $button  SCF link.
 *     @type int    $image   Attachment ID.
 *     @type string $variant 'home' (default) or 'page'.
 *     @type string $decor   Page variant only: 'none' (default), 'sun' or 'flower'.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_title = trim( (string) ( $args['title'] ?? '' ) );

if ( '' === $seps_title ) {
	return;
}

$seps_is_page = 'page' === ( $args['variant'] ?? 'home' );
$seps_decor   = $seps_is_page ? (string) ( $args['decor'] ?? 'none' ) : 'flower';
$seps_decor   = array(
	'flower' => 'flower-yellow',
	'sun'    => 'sun-yellow',
)[ $seps_decor ] ?? '';
$seps_id      = wp_unique_id( 'seps-hero-' );
$seps_text    = trim( (string) ( $args['text'] ?? '' ) );
$seps_image   = seps_image(
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
<section class="hero<?php echo $seps_is_page ? ' hero--page' : ''; ?>" aria-labelledby="<?php echo esc_attr( $seps_id ); ?>">
	<div class="hero__panel">
		<div class="hero__content">
			<h1 id="<?php echo esc_attr( $seps_id ); ?>" class="hero__title"><?php echo seps_multiline( $seps_title ); ?></h1>

			<?php if ( $seps_text ) : ?>
				<p class="hero__text"><?php echo esc_html( $seps_text ); ?></p>
			<?php endif; ?>

			<?php echo seps_button( $args['button'] ?? null, 'accent' ); ?>
		</div>
	</div>

	<?php if ( $seps_image ) : ?>
		<div class="hero__media">
			<?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
			<?php if ( $seps_decor ) : ?>
				<?php echo seps_decor( $seps_decor, 'hero__decor hero__decor--' . $seps_decor ); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
