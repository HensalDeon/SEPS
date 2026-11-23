<?php
/**
 * Enquiries: the Contact page form.
 *
 * Submissions post to admin-post.php. Each one is stored as a private
 * "Enquiry" (so nothing is lost if email fails) and emailed to the school.
 * Without JavaScript the handler redirects back with ?enquiry=<status>
 * (post/redirect/get); the theme's script sends the form with
 * "Accept: application/json" and gets a JSON reply instead.
 *
 * No nonce: the form is public and pages are served from WP Engine's page
 * cache, where a stale nonce would reject genuine enquiries. Spam and abuse
 * are handled by a honeypot field, a same-site origin check, a per-IP rate
 * limit and strict validation.
 *
 * @package SEPS\Core
 */

namespace SEPS\Core\Enquiries;

defined( 'ABSPATH' ) || exit;

const POST_TYPE  = 'seps_enquiry';
const ACTION     = 'seps_enquiry';
const HONEYPOT   = 'seps_website';
const RATE_LIMIT = 5;                       // Submissions…
const RATE_TTL   = 15 * MINUTE_IN_SECONDS;  // …per IP per window.

/**
 * Form fields: name => [ label, max length, required ].
 *
 * @return array<string,array{0:string,1:int,2:bool}>
 */
function fields(): array {
	return array(
		'name'         => array( __( 'Parent’s name', 'seps-core' ), 100, true ),
		'phone_code'   => array( __( 'Country code', 'seps-core' ), 5, false ),
		'phone'        => array( __( 'Phone number', 'seps-core' ), 20, true ),
		'email'        => array( __( 'Email address', 'seps-core' ), 100, true ),
		'subject'      => array( __( 'Subject', 'seps-core' ), 100, true ),
		'contact_time' => array( __( 'Preferred contact time', 'seps-core' ), 50, false ),
		'message'      => array( __( 'Message', 'seps-core' ), 5000, true ),
	);
}

/**
 * User-facing message for a submission status.
 *
 * @param string $status 'sent' | 'invalid' | 'limited' | 'error'.
 */
function message( string $status ): string {
	return array(
		'sent'    => __( 'Thank you! Your enquiry has been sent. Our team will get back to you as soon as possible.', 'seps-core' ),
		'invalid' => __( 'Please check the highlighted fields and try again.', 'seps-core' ),
		'limited' => __( 'You have sent several enquiries in a short time. Please try again a little later.', 'seps-core' ),
		'error'   => __( 'Sorry, something went wrong and your enquiry was not sent. Please try again, or email us directly.', 'seps-core' ),
	)[ $status ] ?? '';
}

/**
 * Register the (admin-only) Enquiry post type.
 */
function register(): void {
	register_post_type(
		POST_TYPE,
		array(
			'labels'              => array(
				'name'               => __( 'Enquiries', 'seps-core' ),
				'singular_name'      => __( 'Enquiry', 'seps-core' ),
				'edit_item'          => __( 'Enquiry', 'seps-core' ),
				'search_items'       => __( 'Search enquiries', 'seps-core' ),
				'not_found'          => __( 'No enquiries yet.', 'seps-core' ),
				'not_found_in_trash' => __( 'No enquiries found in Trash.', 'seps-core' ),
				'menu_name'          => __( 'Enquiries', 'seps-core' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'capability_type'     => 'post',
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ), // Created by the form only.
			'map_meta_cap'        => true,
			'supports'            => array( 'title' ),
			'menu_icon'           => 'dashicons-email-alt',
			'menu_position'       => 20,
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register' );

/**
 * Handle a submission (admin-post.php, logged in or not).
 */
function handle(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public, page-cached form; see the file header.
	$status = process( wp_unslash( $_POST ), client_ip() );

	$wants_json = str_contains( (string) ( $_SERVER['HTTP_ACCEPT'] ?? '' ), 'application/json' );

	if ( $wants_json ) {
		wp_send_json(
			array(
				'success' => 'sent' === $status['status'],
				'status'  => $status['status'],
				'message' => message( $status['status'] ),
				'errors'  => $status['errors'],
			),
			'error' === $status['status'] ? 500 : 200
		);
	}

	$back = wp_validate_redirect( (string) wp_get_referer(), home_url( '/' ) );
	$back = remove_query_arg( 'enquiry', $back );

	wp_safe_redirect( add_query_arg( 'enquiry', $status['status'], strtok( $back, '#' ) ) . '#enquiry-form', 303 );
	exit;
}
add_action( 'admin_post_nopriv_' . ACTION, __NAMESPACE__ . '\\handle' );
add_action( 'admin_post_' . ACTION, __NAMESPACE__ . '\\handle' );

/**
 * Validate, store and email a submission.
 *
 * @param array<string,mixed> $input Raw (unslashed) POST data.
 * @param string              $ip    Client IP (for rate limiting only; not stored).
 * @return array{status:string,errors:string[]}
 */
function process( array $input, string $ip ): array {
	// Bots fill every field; people never see this one. Pretend success.
	if ( '' !== trim( (string) ( $input[ HONEYPOT ] ?? '' ) ) ) {
		return array(
			'status' => 'sent',
			'errors' => array(),
		);
	}

	if ( ! same_site_request() ) {
		return array(
			'status' => 'error',
			'errors' => array(),
		);
	}

	$data   = array();
	$errors = array();
	$raw    = is_array( $input['enquiry'] ?? null ) ? $input['enquiry'] : array();

	foreach ( fields() as $name => [ , $max, $required ] ) {
		$value = 'message' === $name
			? sanitize_textarea_field( (string) ( $raw[ $name ] ?? '' ) )
			: sanitize_text_field( (string) ( $raw[ $name ] ?? '' ) );
		$value = trim( $value );

		if ( ( $required && '' === $value ) || mb_strlen( $value ) > $max ) {
			$errors[] = $name;
		}

		$data[ $name ] = $value;
	}

	if ( '' !== $data['email'] && ! is_email( $data['email'] ) ) {
		$errors[] = 'email';
	}
	if ( '' !== $data['phone'] && ! preg_match( '/^[0-9 ()\-]{6,20}$/', $data['phone'] ) ) {
		$errors[] = 'phone';
	}
	if ( '' !== $data['phone_code'] && ! preg_match( '/^\+?[0-9]{1,4}$/', $data['phone_code'] ) ) {
		$errors[] = 'phone_code';
	}

	if ( $errors ) {
		return array(
			'status' => 'invalid',
			'errors' => array_values( array_unique( $errors ) ),
		);
	}

	$key   = 'seps_enquiry_' . md5( $ip );
	$count = (int) get_transient( $key );
	if ( $count >= RATE_LIMIT ) {
		return array(
			'status' => 'limited',
			'errors' => array(),
		);
	}
	set_transient( $key, $count + 1, RATE_TTL );

	$id = wp_insert_post(
		array(
			'post_type'    => POST_TYPE,
			'post_status'  => 'private',
			/* translators: 1: Sender name, 2: Subject. */
			'post_title'   => sprintf( __( '%1$s – %2$s', 'seps-core' ), $data['name'], $data['subject'] ),
			'post_content' => $data['message'],
		),
		true
	);

	if ( is_wp_error( $id ) ) {
		return array(
			'status' => 'error',
			'errors' => array(),
		);
	}

	foreach ( array( 'email', 'phone_code', 'phone', 'subject', 'contact_time' ) as $name ) {
		update_post_meta( $id, '_seps_' . $name, $data[ $name ] );
	}

	update_post_meta( $id, '_seps_mailed', send_email( $data ) ? 1 : 0 );

	return array(
		'status' => 'sent',
		'errors' => array(),
	);
}

/**
 * Email the enquiry to the school.
 *
 * @param array<string,string> $data Validated fields.
 */
function send_email( array $data ): bool {
	/**
	 * Filters the address enquiries are sent to (the theme uses the school email from Site Settings).
	 *
	 * @param string $recipient Default: the site admin email.
	 */
	$to = (string) apply_filters( 'seps_enquiry_recipient', get_option( 'admin_email' ) );

	if ( ! is_email( $to ) ) {
		return false;
	}

	$lines = array();
	foreach ( fields() as $name => [ $label ] ) {
		if ( 'phone_code' === $name || '' === $data[ $name ] ) {
			continue;
		}
		$value   = 'phone' === $name ? trim( $data['phone_code'] . ' ' . $data['phone'] ) : $data[ $name ];
		$lines[] = 'message' === $name ? "\n{$label}:\n{$value}" : "{$label}: {$value}";
	}

	return wp_mail(
		$to,
		/* translators: 1: Subject, 2: Sender name. */
		sprintf( __( 'New enquiry: %1$s (%2$s)', 'seps-core' ), $data['subject'], $data['name'] ),
		implode( "\n", $lines ),
		array( sprintf( 'Reply-To: %s <%s>', str_replace( array( '<', '>', '"', "\r", "\n" ), '', $data['name'] ), $data['email'] ) )
	);
}

/**
 * Reject cross-site posts: when the browser sends an Origin header it must
 * be this site.
 */
function same_site_request(): bool {
	$origin = (string) ( $_SERVER['HTTP_ORIGIN'] ?? '' );

	if ( '' === $origin ) {
		return true;
	}

	return wp_parse_url( $origin, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST );
}

/**
 * Client IP for rate limiting. REMOTE_ADDR only: forwarded headers can be
 * spoofed (WP Engine already sets REMOTE_ADDR to the visitor's address).
 */
function client_ip(): string {
	return (string) filter_var( $_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP );
}

/*
 * -----------------------------------------------------------------------------
 * Admin: list columns and a read-only details box.
 * -----------------------------------------------------------------------------
 */

/**
 * List table columns.
 *
 * @param array<string,string> $columns Columns.
 * @return array<string,string>
 */
function columns( array $columns ): array {
	return array(
		'cb'                 => $columns['cb'] ?? '',
		'title'              => __( 'Enquiry', 'seps-core' ),
		'seps_email'         => __( 'Email', 'seps-core' ),
		'seps_phone'         => __( 'Phone', 'seps-core' ),
		'seps_contact_time'  => __( 'Contact time', 'seps-core' ),
		'date'               => __( 'Received', 'seps-core' ),
	);
}
add_filter( 'manage_' . POST_TYPE . '_posts_columns', __NAMESPACE__ . '\\columns' );

/**
 * Render a list table cell.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function column( string $column, int $post_id ): void {
	if ( 'seps_phone' === $column ) {
		echo esc_html( trim( get_post_meta( $post_id, '_seps_phone_code', true ) . ' ' . get_post_meta( $post_id, '_seps_phone', true ) ) );
	} elseif ( str_starts_with( $column, 'seps_' ) ) {
		echo esc_html( (string) get_post_meta( $post_id, '_' . $column, true ) );
	}
}
add_action( 'manage_' . POST_TYPE . '_posts_custom_column', __NAMESPACE__ . '\\column', 10, 2 );

/**
 * Details box on the enquiry screen.
 */
function meta_boxes(): void {
	add_meta_box( 'seps-enquiry', __( 'Details', 'seps-core' ), __NAMESPACE__ . '\\details_box', POST_TYPE, 'normal', 'high' );
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\meta_boxes' );

/**
 * Render the details box.
 *
 * @param \WP_Post $post Enquiry.
 */
function details_box( \WP_Post $post ): void {
	$email = (string) get_post_meta( $post->ID, '_seps_email', true );
	$phone = trim( get_post_meta( $post->ID, '_seps_phone_code', true ) . ' ' . get_post_meta( $post->ID, '_seps_phone', true ) );
	$rows  = array(
		__( 'Email', 'seps-core' )        => $email ? sprintf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( $email ), esc_html( $email ) ) : '',
		__( 'Phone', 'seps-core' )        => $phone ? sprintf( '<a href="tel:%1$s">%2$s</a>', esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ), esc_html( $phone ) ) : '',
		__( 'Subject', 'seps-core' )      => esc_html( (string) get_post_meta( $post->ID, '_seps_subject', true ) ),
		__( 'Contact time', 'seps-core' ) => esc_html( (string) get_post_meta( $post->ID, '_seps_contact_time', true ) ),
		__( 'Emailed', 'seps-core' )      => get_post_meta( $post->ID, '_seps_mailed', true ) ? esc_html__( 'Yes', 'seps-core' ) : esc_html__( 'No (check the site’s email settings)', 'seps-core' ),
	);

	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( $rows as $label => $value ) {
		printf( '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>', esc_html( $label ), $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
	}
	printf(
		'<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
		esc_html__( 'Message', 'seps-core' ),
		nl2br( esc_html( $post->post_content ) )
	);
	echo '</tbody></table>';
}

/*
 * -----------------------------------------------------------------------------
 * Privacy: export and erase a person's enquiries by email
 * (Tools → Export / Erase Personal Data).
 * -----------------------------------------------------------------------------
 */

/**
 * Enquiry IDs sent from an email address.
 *
 * @param string $email Email address.
 * @return int[]
 */
function ids_for_email( string $email ): array {
	return get_posts(
		array(
			'post_type'      => POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_seps_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Admin privacy tools only.
			'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Admin privacy tools only.
		)
	);
}

/**
 * Register the exporter.
 *
 * @param array<string,array<string,mixed>> $exporters Exporters.
 * @return array<string,array<string,mixed>>
 */
function register_exporter( array $exporters ): array {
	$exporters[ POST_TYPE ] = array(
		'exporter_friendly_name' => __( 'Enquiries', 'seps-core' ),
		'callback'               => static function ( string $email ): array {
			$items = array();
			foreach ( ids_for_email( $email ) as $id ) {
				$items[] = array(
					'group_id'    => POST_TYPE,
					'group_label' => __( 'Enquiries', 'seps-core' ),
					'item_id'     => POST_TYPE . '-' . $id,
					'data'        => array(
						array(
							'name'  => __( 'Enquiry', 'seps-core' ),
							'value' => get_the_title( $id ),
						),
						array(
							'name'  => __( 'Message', 'seps-core' ),
							'value' => get_post_field( 'post_content', $id ),
						),
						array(
							'name'  => __( 'Received', 'seps-core' ),
							'value' => get_the_date( '', $id ),
						),
					),
				);
			}

			return array(
				'data' => $items,
				'done' => true,
			);
		},
	);

	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', __NAMESPACE__ . '\\register_exporter' );

/**
 * Register the eraser.
 *
 * @param array<string,array<string,mixed>> $erasers Erasers.
 * @return array<string,array<string,mixed>>
 */
function register_eraser( array $erasers ): array {
	$erasers[ POST_TYPE ] = array(
		'eraser_friendly_name' => __( 'Enquiries', 'seps-core' ),
		'callback'             => static function ( string $email ): array {
			$removed = false;
			foreach ( ids_for_email( $email ) as $id ) {
				$removed = (bool) wp_delete_post( $id, true ) || $removed;
			}

			return array(
				'items_removed'  => $removed,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		},
	);

	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', __NAMESPACE__ . '\\register_eraser' );
