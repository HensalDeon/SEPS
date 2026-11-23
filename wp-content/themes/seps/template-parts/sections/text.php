<?php
/**
 * Section: Text (rich text, e.g. policies and simple pages).
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading.
 *     @type string $content WYSIWYG content (formatted by SCF).
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_content = trim( (string) ( $args['content'] ?? '' ) );

if ( '' === $seps_content ) {
	return;
}

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
?>
<section class="text-section">
	<div class="container">
		<?php if ( $seps_heading ) : ?>
			<h2 class="section-title text-section__title"><?php echo esc_html( $seps_heading ); ?></h2>
		<?php endif; ?>
		<div class="entry__content"><?php echo wp_kses_post( $seps_content ); ?></div>
	</div>
</section>
