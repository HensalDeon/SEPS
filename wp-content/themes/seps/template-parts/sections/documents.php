<?php
/**
 * Section: Documents (e.g. "CBSE Mandatory Public Disclosure").
 *
 * Preview cards that open the document (or the full preview image) in a new
 * tab.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading.
 *     @type string $intro   Intro text.
 *     @type array  $items   Repeater: title, image (preview), file (URL).
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_items = array();
foreach ( (array) ( $args['items'] ?? array() ) as $seps_item ) {
	$seps_title = trim( (string) ( $seps_item['title'] ?? '' ) );
	$seps_image = absint( $seps_item['image'] ?? 0 );
	$seps_url   = (string) ( $seps_item['file'] ?? '' );

	if ( '' === $seps_url && $seps_image ) {
		$seps_url = (string) wp_get_attachment_url( $seps_image );
	}

	if ( '' !== $seps_title && '' !== $seps_url ) {
		$seps_items[] = compact( 'seps_title', 'seps_image', 'seps_url' );
	}
}

if ( ! $seps_items ) {
	return;
}

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$seps_id      = wp_unique_id( 'seps-documents-' );
?>
<section class="documents"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<div class="container">
		<?php if ( $seps_heading || $seps_intro ) : ?>
			<header class="documents__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $seps_intro ) : ?>
					<p class="documents__intro"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<div class="documents__panel">
			<ul class="documents__grid" role="list">
				<?php foreach ( $seps_items as $seps_item ) : ?>
					<li>
						<a class="document-card" href="<?php echo esc_url( $seps_item['seps_url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<span class="document-card__media">
								<?php
								echo seps_image(
									$seps_item['seps_image'],
									'seps-card',
									array(
										'class' => 'document-card__image',
										'alt'   => '',
										'sizes' => '(min-width: 1280px) 503px, (min-width: 768px) 33vw, 100vw',
									)
								);
								?>
							</span>
							<span class="document-card__title">
								<?php echo esc_html( $seps_item['seps_title'] ); ?>
								<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'seps' ); ?></span>
							</span>
							<?php echo seps_icon( 'fullscreen', 45, 'document-card__icon' ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
