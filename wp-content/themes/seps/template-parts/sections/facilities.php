<?php
/**
 * Section: Facilities ("Spaces That Inspire Learning"). Used by the front
 * page and, as a page section, by Resources.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading.
 *     @type string $intro   Intro text.
 *     @type array  $button  SCF link.
 *     @type array  $items   Repeater: image, title, tags (one per line).
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_items = array_filter( (array) ( $args['items'] ?? array() ), static fn( $row ): bool => ! empty( $row['title'] ) );

if ( ! $seps_items ) {
	return;
}

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$seps_id      = wp_unique_id( 'seps-facilities-' );
?>
<section class="facilities"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<?php echo seps_decor( 'sun-lilac', 'facilities__decor' ); ?>

	<div class="container">
		<header class="facilities__header">
			<?php if ( $seps_heading ) : ?>
				<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $seps_intro ) : ?>
				<p class="facilities__intro"><?php echo esc_html( $seps_intro ); ?></p>
			<?php endif; ?>
			<?php echo seps_button( $args['button'] ?? null ); ?>
		</header>
	</div>

	<ul class="container container--wide facilities__grid" role="list">
		<?php foreach ( $seps_items as $seps_item ) : ?>
			<?php $seps_tags = array_filter( array_map( 'trim', preg_split( '/\R/', (string) ( $seps_item['tags'] ?? '' ) ) ) ); ?>
			<li class="facility">
				<div class="facility__media">
					<?php
					echo seps_image(
						$seps_item['image'] ?? 0,
						'seps-card',
						array(
							'class' => 'facility__image',
							'sizes' => '(min-width: 1280px) 30vw, (min-width: 640px) 48vw, 100vw',
						)
					);
					?>
				</div>
				<div class="facility__caption">
					<h3 class="facility__title"><?php echo esc_html( $seps_item['title'] ); ?></h3>
					<?php if ( $seps_tags ) : ?>
						<ul class="facility__tags" role="list">
							<?php foreach ( $seps_tags as $seps_tag ) : ?>
								<li><?php echo esc_html( $seps_tag ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
