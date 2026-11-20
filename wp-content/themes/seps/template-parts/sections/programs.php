<?php
/**
 * Section: Programs.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading (line breaks preserved).
 *     @type string $intro   Intro text.
 *     @type array  $items   Repeater: image, title, text, button, label.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_items = array_filter( (array) ( $args['items'] ?? array() ), static fn( $row ): bool => ! empty( $row['title'] ) );

if ( ! $seps_items ) {
	return;
}

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro   = trim( (string) ( $args['intro'] ?? '' ) );
?>
<section class="programs"<?php echo $seps_heading ? ' aria-labelledby="programs-title"' : ''; ?>>
	<div class="container">
		<?php if ( $seps_heading || $seps_intro ) : ?>
			<header class="programs__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="programs-title" class="section-title section-title--accent programs__title"><?php echo seps_multiline( $seps_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $seps_intro ) : ?>
					<p class="programs__intro"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<ul class="programs__grid" role="list">
			<?php foreach ( $seps_items as $seps_item ) : ?>
				<li>
					<article class="program-card">
						<div class="program-card__media">
							<?php
							echo seps_image(
								$seps_item['image'] ?? 0,
								'seps-card',
								array(
									'class' => 'program-card__image',
									'sizes' => '(min-width: 1280px) 31vw, (min-width: 768px) 48vw, 100vw',
								)
							);
							?>
						</div>

						<div class="program-card__body">
							<h3 class="program-card__title"><?php echo esc_html( $seps_item['title'] ); ?></h3>

							<?php if ( ! empty( $seps_item['text'] ) ) : ?>
								<p class="program-card__text"><?php echo esc_html( $seps_item['text'] ); ?></p>
							<?php endif; ?>

							<div class="program-card__footer">
								<?php echo seps_button( $seps_item['button'] ?? null, 'primary', '', (string) $seps_item['title'] ); ?>
								<?php if ( ! empty( $seps_item['label'] ) ) : ?>
									<span class="dot-label"><?php echo esc_html( $seps_item['label'] ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					</article>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
