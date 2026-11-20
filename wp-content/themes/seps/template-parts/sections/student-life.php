<?php
/**
 * Section: Student Life ("Discover Life at SEPS").
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading    Heading (line breaks preserved).
 *     @type string $text       Text.
 *     @type array  $button     SCF link.
 *     @type int    $image      Attachment ID.
 *     @type string $stat_value Highlighted statistic, e.g. "75%+".
 *     @type string $stat_text  Statistic description.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );

if ( '' === $seps_heading ) {
	return;
}

$seps_text       = trim( (string) ( $args['text'] ?? '' ) );
$seps_stat_value = trim( (string) ( $args['stat_value'] ?? '' ) );
$seps_stat_text  = trim( (string) ( $args['stat_text'] ?? '' ) );
$seps_image      = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'student-life__image',
		'sizes' => '(min-width: 1280px) 598px, (min-width: 768px) 50vw, 100vw',
	)
);
?>
<section class="student-life" aria-labelledby="student-life-title">
	<div class="container student-life__inner">
		<div class="student-life__content">
			<?php echo seps_decor( 'hourglass-navy', 'student-life__decor' ); ?>
			<h2 id="student-life-title" class="section-title"><?php echo seps_multiline( $seps_heading ); ?></h2>
			<?php if ( $seps_text ) : ?>
				<p class="student-life__text"><?php echo esc_html( $seps_text ); ?></p>
			<?php endif; ?>
			<?php echo seps_button( $args['button'] ?? null ); ?>
		</div>

		<?php if ( $seps_image ) : ?>
			<div class="student-life__media"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
		<?php endif; ?>

		<?php if ( $seps_stat_value ) : ?>
			<div class="student-life__stat">
				<p class="student-life__stat-value"><?php echo esc_html( $seps_stat_value ); ?></p>
				<?php if ( $seps_stat_text ) : ?>
					<p class="student-life__stat-text"><?php echo esc_html( $seps_stat_text ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
