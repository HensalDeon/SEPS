<?php
/**
 * Section: Enquiry form ("We'd Love to Hear From You", Contact page).
 *
 * Submissions are handled by the seps-core plugin (SEPS\Core\Enquiries):
 * stored as private Enquiries and emailed to the school. Works without
 * JavaScript (post/redirect/get with ?enquiry=<status>); assets/src/js/
 * modules/enquiry-form.js sends it in place when JavaScript is available.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading       Heading.
 *     @type string $intro         Intro text.
 *     @type string $subjects      Subject options, one per line.
 *     @type string $contact_times Preferred contact time options, one per line.
 *     @type string $phone_code    Default country code, e.g. "+971".
 *     @type string $submit_label  Button label.
 *     @type int    $image         Photo on the right.
 * }
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'SEPS\Core\Enquiries\message' ) ) {
	if ( current_user_can( 'activate_plugins' ) ) {
		printf( '<p class="container">%s</p>', esc_html__( 'The enquiry form needs the SEPS Core plugin to be active.', 'seps' ) );
	}
	return;
}

$seps_lines   = static fn( mixed $text ): array => array_values( array_filter( array_map( 'trim', explode( "\n", (string) $text ) ) ) );
$seps_subject = $seps_lines( $args['subjects'] ?? '' );
$seps_times   = $seps_lines( $args['contact_times'] ?? '' );
$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$seps_code    = trim( (string) ( $args['phone_code'] ?? '' ) );
$seps_submit  = trim( (string) ( $args['submit_label'] ?? '' ) );
$seps_id      = wp_unique_id( 'seps-enquiry-' );
$seps_image   = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'enquiry__image',
		'sizes' => '(min-width: 1024px) 409px, 100vw',
	)
);

// Result of a no-JavaScript submission (see SEPS\Core\Enquiries\handle()).
$seps_status  = isset( $_GET['enquiry'] ) ? sanitize_key( wp_unslash( $_GET['enquiry'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
$seps_message = $seps_status ? SEPS\Core\Enquiries\message( $seps_status ) : '';

/**
 * Field wrapper: label + control.
 *
 * @param string $id      Control ID.
 * @param string $label   Label.
 * @param string $control Control HTML (pre-escaped).
 * @param string $class   Extra class.
 */
$seps_field = static function ( string $id, string $label, string $control, string $class = '' ): void {
	printf(
		'<div class="enquiry__field %1$s"><label class="enquiry__label" for="%2$s">%3$s</label>%4$s</div>',
		esc_attr( $class ),
		esc_attr( $id ),
		esc_html( $label ),
		$control // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with escaped values below.
	);
};

/**
 * <select>, optionally starting on a disabled placeholder option.
 *
 * @param string   $name        Field name.
 * @param string   $id          ID.
 * @param string[] $options     Options.
 * @param string   $placeholder Placeholder text ('' for none: the first option is selected).
 * @param bool     $required    Required.
 */
$seps_select = static function ( string $name, string $id, array $options, string $placeholder, bool $required ): string {
	$html = sprintf(
		'<select class="enquiry__control enquiry__select" id="%1$s" name="enquiry[%2$s]"%3$s>',
		esc_attr( $id ),
		esc_attr( $name ),
		$required ? ' required' : ''
	);
	if ( '' !== $placeholder ) {
		$html .= sprintf( '<option value="" disabled selected>%s</option>', esc_html( $placeholder ) );
	}
	foreach ( $options as $option ) {
		$html .= sprintf( '<option>%s</option>', esc_html( $option ) );
	}

	return $html . '</select>';
};
?>
<section class="enquiry<?php echo $seps_image ? ' enquiry--has-media' : ''; ?>"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<span id="enquiry-form" class="section-anchor"></span>

	<div class="container enquiry__inner">
		<div class="enquiry__main">
			<?php if ( $seps_heading || $seps_intro ) : ?>
				<header class="enquiry__header">
					<?php if ( $seps_heading ) : ?>
						<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
					<?php endif; ?>
					<?php if ( $seps_intro ) : ?>
						<p class="enquiry__intro"><?php echo esc_html( $seps_intro ); ?></p>
					<?php endif; ?>
				</header>
			<?php endif; ?>

			<form class="enquiry__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-enquiry-form>
				<input type="hidden" name="action" value="<?php echo esc_attr( SEPS\Core\Enquiries\ACTION ); ?>">

				<div class="enquiry__honeypot" aria-hidden="true">
					<label for="<?php echo esc_attr( $seps_id ); ?>-website"><?php esc_html_e( 'Leave this field empty', 'seps' ); ?></label>
					<input type="text" id="<?php echo esc_attr( $seps_id ); ?>-website" name="<?php echo esc_attr( SEPS\Core\Enquiries\HONEYPOT ); ?>" tabindex="-1" autocomplete="off">
				</div>

				<?php
				$seps_field(
					"{$seps_id}-name",
					__( 'Parent’s Name', 'seps' ),
					sprintf(
						'<input class="enquiry__control" type="text" id="%1$s" name="enquiry[name]" placeholder="%2$s" autocomplete="name" maxlength="100" required>',
						esc_attr( "{$seps_id}-name" ),
						esc_attr__( 'Enter full name here', 'seps' )
					)
				);
				?>

				<div class="enquiry__field">
					<label class="enquiry__label" for="<?php echo esc_attr( "{$seps_id}-phone" ); ?>"><?php esc_html_e( 'Phone Number', 'seps' ); ?></label>
					<div class="enquiry__phone">
						<input class="enquiry__control enquiry__code" type="text" name="enquiry[phone_code]" value="<?php echo esc_attr( $seps_code ); ?>" inputmode="tel" autocomplete="tel-country-code" maxlength="5" pattern="\+?[0-9]{1,4}" aria-label="<?php esc_attr_e( 'Country code', 'seps' ); ?>">
						<input class="enquiry__control" type="tel" id="<?php echo esc_attr( "{$seps_id}-phone" ); ?>" name="enquiry[phone]" placeholder="<?php esc_attr_e( 'Enter phone number here', 'seps' ); ?>" autocomplete="tel-national" maxlength="20" pattern="[0-9 ()\-]{6,20}" required>
					</div>
				</div>

				<?php
				$seps_field(
					"{$seps_id}-email",
					__( 'Email Address', 'seps' ),
					sprintf(
						'<input class="enquiry__control" type="email" id="%1$s" name="enquiry[email]" placeholder="%2$s" autocomplete="email" maxlength="100" required>',
						esc_attr( "{$seps_id}-email" ),
						esc_attr__( 'Enter email address here', 'seps' )
					),
					'enquiry__field--full'
				);

				$seps_field(
					"{$seps_id}-subject",
					__( 'Subject', 'seps' ),
					$seps_subject
						? $seps_select( 'subject', "{$seps_id}-subject", $seps_subject, __( 'What is this regarding', 'seps' ), true )
						: sprintf( '<input class="enquiry__control" type="text" id="%1$s" name="enquiry[subject]" placeholder="%2$s" maxlength="100" required>', esc_attr( "{$seps_id}-subject" ), esc_attr__( 'What is this regarding', 'seps' ) )
				);

				if ( $seps_times ) {
					$seps_field(
						"{$seps_id}-time",
						__( 'Preferred Contact Time', 'seps' ),
						$seps_select( 'contact_time', "{$seps_id}-time", $seps_times, '', false )
					);
				}

				$seps_field(
					"{$seps_id}-message",
					__( 'Message', 'seps' ),
					sprintf(
						'<textarea class="enquiry__control enquiry__textarea" id="%1$s" name="enquiry[message]" placeholder="%2$s" rows="7" maxlength="5000" required></textarea>',
						esc_attr( "{$seps_id}-message" ),
						esc_attr__( 'Tell us a bit about your enquiry...', 'seps' )
					),
					'enquiry__field--full'
				);
				?>

				<p class="enquiry__status<?php echo 'sent' === $seps_status ? ' is-success' : ( $seps_message ? ' is-error' : '' ); ?>" role="status" data-enquiry-status<?php echo $seps_message ? '' : ' hidden'; ?>><?php echo esc_html( $seps_message ); ?></p>

				<button class="btn btn--primary enquiry__submit" type="submit"><?php echo esc_html( $seps_submit ? $seps_submit : __( 'Send Enquiry', 'seps' ) ); ?></button>
			</form>
		</div>

		<?php if ( $seps_image ) : ?>
			<div class="enquiry__media">
				<?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
				<?php echo seps_decor( 'halves-pair-brand', 'enquiry__decor' ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>
