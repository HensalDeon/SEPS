<?php
/**
 * Section: Message from the Principal.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading.
 *     @type string $message Message (already wpautop-formatted by SCF).
 *     @type int    $image   Attachment ID.
 *     @type string $name    Name.
 *     @type string $role    Designation.
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
$seps_image   = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'principal__image',
		'sizes' => '(min-width: 1280px) 598px, (min-width: 768px) 45vw, 100vw',
	)
);
?>
<section class="principal"<?php echo $seps_heading ? ' aria-labelledby="principal-title"' : ''; ?>>
	<?php echo seps_decor( 'flower-yellow', 'principal__decor' ); ?>

	<div class="container principal__inner">
		<?php if ( $seps_image ) : ?>
			<div class="principal__media"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
		<?php endif; ?>

		<div class="principal__content">
			<?php if ( $seps_heading ) : ?>
				<h2 id="principal-title" class="section-title section-title--light"><?php echo esc_html( $seps_heading ); ?></h2>
			<?php endif; ?>

			<figure class="principal__quote">
				<blockquote class="principal__message"><?php echo wp_kses_post( $seps_message ); ?></blockquote>
				<?php if ( $seps_name || $seps_role ) : ?>
					<figcaption class="principal__signature">
						<?php if ( $seps_name ) : ?>
							<strong class="principal__name"><?php echo esc_html( $seps_name ); ?></strong>
						<?php endif; ?>
						<?php if ( $seps_role ) : ?>
							<span class="principal__role"><?php echo esc_html( $seps_role ); ?></span>
						<?php endif; ?>
					</figcaption>
				<?php endif; ?>
			</figure>
		</div>
	</div>
</section>
