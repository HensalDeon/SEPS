<?php
/**
 * Section: Notice + Checklist (e.g. "Withdrawals").
 *
 * A notice callout (white), then a checklist card with a button beside a
 * circular photo.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading      Heading.
 *     @type string $notice_icon  Notice icon.
 *     @type string $notice_title Notice title.
 *     @type string $notice_text  Notice text (HTML).
 *     @type int    $notice_image Notice photo.
 *     @type string $list_title   Checklist title.
 *     @type string $list_intro   Checklist intro (HTML).
 *     @type string $list         Checklist (HTML bulleted list).
 *     @type array  $button       SCF link.
 *     @type int    $image        Circular photo.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_list    = trim( (string) ( $args['list'] ?? '' ) );

if ( '' === $seps_heading && '' === $seps_list ) {
	return;
}

$seps_list_title = trim( (string) ( $args['list_title'] ?? '' ) );
$seps_list_intro = trim( (string) ( $args['list_intro'] ?? '' ) );
$seps_id         = wp_unique_id( 'seps-checklist-' );
$seps_image      = seps_image(
	$args['image'] ?? 0,
	'seps-square',
	array(
		'class' => 'checklist__image',
		'sizes' => '(min-width: 1024px) 436px, 80vw',
	)
);
?>
<section class="checklist"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<div class="container">
		<?php if ( $seps_heading ) : ?>
			<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title checklist__heading"><?php echo esc_html( $seps_heading ); ?></h2>
		<?php endif; ?>

		<?php
		get_template_part(
			'template-parts/components/callout',
			null,
			array(
				'icon'    => (string) ( $args['notice_icon'] ?? '' ),
				'title'   => (string) ( $args['notice_title'] ?? '' ),
				'html'    => (string) ( $args['notice_text'] ?? '' ),
				'image'   => $args['notice_image'] ?? 0,
				'variant' => 'light',
			)
		);
		?>

		<div class="checklist__body<?php echo $seps_image ? ' checklist__body--has-image' : ''; ?>">
			<div class="checklist__main">
				<?php if ( $seps_list_title ) : ?>
					<h3 class="checklist__title"><?php echo esc_html( $seps_list_title ); ?></h3>
				<?php endif; ?>
				<?php if ( $seps_list_intro ) : ?>
					<div class="checklist__intro rich-text"><?php echo wp_kses_post( $seps_list_intro ); ?></div>
				<?php endif; ?>
				<?php if ( $seps_list ) : ?>
					<div class="checklist__list rich-text"><?php echo wp_kses_post( $seps_list ); ?></div>
				<?php endif; ?>
				<?php echo seps_button( $args['button'] ?? null ); ?>
			</div>

			<?php if ( $seps_image ) : ?>
				<div class="checklist__media">
					<?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
					<?php echo seps_decor( 'flower-grey', 'checklist__decor' ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
