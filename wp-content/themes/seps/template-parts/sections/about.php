<?php
/**
 * Section: About ("A Legacy Built on Education…").
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading         Heading.
 *     @type int    $image           Main circular image.
 *     @type array  $features        Repeater: icon, title, text.
 *     @type string $intro           Side panel text.
 *     @type array  $button          Side panel link.
 *     @type int    $image_secondary Side panel circular image.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_heading  = trim( (string) ( $args['heading'] ?? '' ) );
$seps_features = array_filter( (array) ( $args['features'] ?? array() ), static fn( $row ): bool => ! empty( $row['title'] ) );
$seps_intro    = trim( (string) ( $args['intro'] ?? '' ) );
$seps_button   = seps_button( $args['button'] ?? null );

$seps_image = seps_image(
	$args['image'] ?? 0,
	'seps-square',
	array(
		'class' => 'about__image',
		'sizes' => '(min-width: 1024px) 496px, 80vw',
	)
);

$seps_image_secondary = seps_image(
	$args['image_secondary'] ?? 0,
	'seps-square',
	array(
		'class' => 'about__aside-image',
		'sizes' => '(min-width: 1024px) 400px, 70vw',
	)
);

if ( '' === $seps_heading && ! $seps_features && '' === $seps_intro ) {
	return;
}
?>
<section class="about"<?php echo $seps_heading ? ' aria-labelledby="about-title"' : ''; ?>>
	<?php echo seps_decor( 'flower-brand', 'about__decor' ); ?>

	<div class="about__main">
		<div class="about__lead">
			<?php if ( $seps_image ) : ?>
				<div class="about__media"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
			<?php endif; ?>

			<?php if ( $seps_heading ) : ?>
				<h2 id="about-title" class="section-title about__title"><?php echo seps_multiline( $seps_heading ); ?></h2>
			<?php endif; ?>
		</div>

		<?php if ( $seps_features ) : ?>
			<ul class="about__features" role="list">
				<?php foreach ( $seps_features as $seps_feature ) : ?>
					<li class="feature">
						<?php echo seps_icon( (string) ( $seps_feature['icon'] ?? '' ), 50, 'feature__icon' ); ?>
						<h3 class="feature__title"><?php echo esc_html( $seps_feature['title'] ); ?></h3>
						<?php if ( ! empty( $seps_feature['text'] ) ) : ?>
							<p class="feature__text"><?php echo esc_html( $seps_feature['text'] ); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<?php if ( $seps_intro || $seps_button || $seps_image_secondary ) : ?>
		<div class="about__aside">
			<div class="about__aside-content">
				<?php if ( $seps_intro ) : ?>
					<p class="about__intro"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
				<?php echo $seps_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
			</div>

			<?php if ( $seps_image_secondary ) : ?>
				<div class="about__aside-media">
					<?php echo $seps_image_secondary; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
					<?php echo seps_decor( 'sun-yellow', 'about__aside-decor' ); ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
