<?php
/**
 * Surfing for Vision email sending.
 *
 * Registration confirmation emails plus one-off blasts to all registrants.
 * Everything goes through wp_mail(), so delivery is handled by whichever
 * mail plugin is configured (Resend via an SMTP plugin in production,
 * Local's Mailpit catcher in development).
 *
 * Ported from legacy/koinonia/inc/sfv-emails.php. Changes: the admin screen is a
 * submenu of Registrations, gated by MANAGE_CAP (Administrators).
 *
 * @package SFV_Registration
 */

namespace SFV\Registration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SFV_EMAIL_SETTINGS_OPTION = 'sfv_email_settings';
const SFV_EMAIL_QUEUE_OPTION    = 'sfv_email_queue';
const SFV_EMAIL_QUEUE_HOOK      = 'sfv_email_queue_batch';
const SFV_EMAIL_BATCH_SIZE      = 15;

add_action( 'sfv_registration_created', __NAMESPACE__ . '\sfv_email_on_registration', 10, 2 );
add_action( SFV_EMAIL_QUEUE_HOOK, __NAMESPACE__ . '\sfv_email_queue_run' );
add_action( 'admin_menu', __NAMESPACE__ . '\sfv_email_admin_menu' );
add_action( 'admin_init', __NAMESPACE__ . '\sfv_email_admin_actions' );

// ---------------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------------

function sfv_email_get_settings() {
	$defaults = array(
		'enabled'              => '',
		'from_name'            => 'Surfing for Vision',
		'from_email'           => 'registration@surfingforvision.com',
		'reply_to'             => '',
		'confirmation_subject' => '',
		'confirmation_message' => '',
	);

	$saved = get_option( SFV_EMAIL_SETTINGS_OPTION, array() );

	return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

function sfv_email_sending_enabled() {
	$settings = sfv_email_get_settings();

	return '1' === $settings['enabled'];
}

function sfv_email_headers() {
	$settings = sfv_email_get_settings();
	$headers  = array( 'Content-Type: text/html; charset=UTF-8' );

	if ( ! empty( $settings['from_email'] ) && is_email( $settings['from_email'] ) ) {
		$headers[] = sprintf( 'From: %s <%s>', $settings['from_name'], $settings['from_email'] );
	}

	if ( ! empty( $settings['reply_to'] ) && is_email( $settings['reply_to'] ) ) {
		$headers[] = 'Reply-To: ' . $settings['reply_to'];
	}

	return $headers;
}

// ---------------------------------------------------------------------------
// Message content
// ---------------------------------------------------------------------------

function sfv_email_default_confirmation_subject() {
	return 'You\'re officially registered for Surfing for Vision 2026!';
}

function sfv_email_default_confirmation_message() {
	return "Hello!\n\n"
		. "You have officially registered for Surfing for Vision 2026!\n\n"
		. "Saturday, August 22, is going to be filled with surfing, stoke, sun, sand, and even some door prizes.\n\n"
		. "We can’t promise that the swell will be firing, but we can promise tons of fun and memories to last a lifetime!\n\n"
		. "Keep an eye out for emails from the Surfing for Vision team. More to come – thank you!\n\n"
		. ':))';
}

function sfv_email_confirmation_subject() {
	$settings = sfv_email_get_settings();
	$subject  = trim( $settings['confirmation_subject'] );

	return '' !== $subject ? $subject : sfv_email_default_confirmation_subject();
}

function sfv_email_confirmation_message() {
	$settings = sfv_email_get_settings();
	$message  = trim( $settings['confirmation_message'] );

	return '' !== $message ? $message : sfv_email_default_confirmation_message();
}

function sfv_email_confirmation_body_html() {
	return sfv_email_paragraphs( sfv_email_confirmation_message() );
}

/**
 * Converts plain text (blank lines = paragraph breaks) to paragraphs with
 * inline margins, since email clients ignore stylesheets.
 */
function sfv_email_paragraphs( $text ) {
	return str_replace( '<p>', '<p style="margin:0 0 16px;">', wpautop( $text ) );
}

/**
 * Wraps message paragraphs in the shared branded email shell.
 *
 * Inline styles and table layout only, for email client compatibility.
 */
function sfv_email_wrap_html( $body_html ) {
	ob_start();
	?>
<div style="background-color:#eef7dc;padding:24px 12px;">
	<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" width="100%" style="max-width:560px;margin:0 auto;">
		<tr>
			<td style="background-color:#003f47;border-radius:12px 12px 0 0;padding:28px 32px;text-align:center;">
				<span style="font-family:Georgia,'Times New Roman',serif;font-size:26px;line-height:1.2;color:#c9ff55;">Surfing for Vision</span>
			</td>
		</tr>
		<tr>
			<td style="background-color:#ffffff;border-radius:0 0 12px 12px;padding:32px;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:1.6;color:#061c22;">
				<?php echo $body_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from trusted/kses-sanitized content. ?>
			</td>
		</tr>
		<tr>
			<td style="padding:20px 32px;text-align:center;font-family:Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#00786f;">
				Surfing for Vision is a 501(c)(3) nonprofit organization dedicated to providing a fun, safe surfing experience for the blind community.
			</td>
		</tr>
	</table>
</div>
	<?php
	return ob_get_clean();
}

/**
 * wp_mail() applies the wp_mail_from/wp_mail_from_name filters *after* parsing
 * the From header, so a host-level filter (EasyWP sets one) silently wins over
 * the header. Force our sender at a late priority for the duration of our own
 * sends only, leaving other site mail (password resets, etc.) alone.
 */
function sfv_email_force_sender( $enable ) {
	if ( $enable ) {
		add_filter( 'wp_mail_from', __NAMESPACE__ . '\sfv_email_filter_from', PHP_INT_MAX );
		add_filter( 'wp_mail_from_name', __NAMESPACE__ . '\sfv_email_filter_from_name', PHP_INT_MAX );
		return;
	}

	remove_filter( 'wp_mail_from', __NAMESPACE__ . '\sfv_email_filter_from', PHP_INT_MAX );
	remove_filter( 'wp_mail_from_name', __NAMESPACE__ . '\sfv_email_filter_from_name', PHP_INT_MAX );
}

function sfv_email_filter_from( $from_email ) {
	$settings = sfv_email_get_settings();

	return ! empty( $settings['from_email'] ) && is_email( $settings['from_email'] )
		? $settings['from_email']
		: $from_email;
}

function sfv_email_filter_from_name( $from_name ) {
	$settings = sfv_email_get_settings();

	return '' !== trim( $settings['from_name'] ) ? $settings['from_name'] : $from_name;
}

/**
 * The sender WordPress hands to the mail layer once every wp_mail_from* filter
 * has run. Surfaced in the admin page so a host-level override is visible.
 */
function sfv_email_effective_sender() {
	$settings = sfv_email_get_settings();

	sfv_email_force_sender( true );
	$email = apply_filters( 'wp_mail_from', $settings['from_email'] );
	$name  = apply_filters( 'wp_mail_from_name', $settings['from_name'] );
	sfv_email_force_sender( false );

	return sprintf( '%s <%s>', $name, $email );
}

function sfv_email_send( $to, $subject, $body_html ) {
	sfv_email_force_sender( true );

	$sent = wp_mail( $to, $subject, sfv_email_wrap_html( $body_html ), sfv_email_headers() );

	sfv_email_force_sender( false );

	return $sent;
}

// ---------------------------------------------------------------------------
// Confirmation on new registration
// ---------------------------------------------------------------------------

function sfv_email_on_registration( $registration_id, $values ) {
	if ( ! sfv_email_sending_enabled() ) {
		return;
	}

	$sent = sfv_email_send( $values['email'], sfv_email_confirmation_subject(), sfv_email_confirmation_body_html() );

	if ( $sent ) {
		sfv_email_mark_confirmed( array( (int) $registration_id ) );
	}
}

function sfv_email_mark_confirmed( $ids ) {
	global $wpdb;

	$ids = array_filter( array_map( 'absint', $ids ) );

	if ( empty( $ids ) ) {
		return;
	}

	$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query( $wpdb->prepare(
		'UPDATE ' . sfv_register_table_name() . " SET confirmation_sent = %s WHERE id IN ({$placeholders})",
		array_merge( array( current_time( 'mysql' ) ), $ids )
	) );
}

// ---------------------------------------------------------------------------
// Batched send queue (used for the catch-up send and blasts)
// ---------------------------------------------------------------------------

function sfv_email_get_queue() {
	$queue = get_option( SFV_EMAIL_QUEUE_OPTION );

	return is_array( $queue ) ? $queue : null;
}

/**
 * Starts a queued send. Recipients are [ [ 'email' => ..., 'ids' => [...] ], ... ].
 * The first batch goes out immediately; the rest run via WP-Cron.
 */
function sfv_email_queue_start( $type, $subject, $body_html, $recipients ) {
	if ( sfv_email_get_queue() ) {
		return false;
	}

	update_option(
		SFV_EMAIL_QUEUE_OPTION,
		array(
			'type'       => $type,
			'subject'    => $subject,
			'body'       => $body_html,
			'recipients' => array_values( $recipients ),
			'total'      => count( $recipients ),
			'sent'       => 0,
			'failed'     => 0,
			'started_at' => time(),
		),
		false
	);

	sfv_email_queue_run();

	return true;
}

function sfv_email_queue_cancel() {
	delete_option( SFV_EMAIL_QUEUE_OPTION );
	wp_clear_scheduled_hook( SFV_EMAIL_QUEUE_HOOK );
}

function sfv_email_queue_run() {
	$queue = sfv_email_get_queue();

	if ( ! $queue ) {
		return;
	}

	$batch = array_splice( $queue['recipients'], 0, SFV_EMAIL_BATCH_SIZE );

	foreach ( $batch as $recipient ) {
		$sent = sfv_email_send( $recipient['email'], $queue['subject'], $queue['body'] );

		if ( $sent ) {
			$queue['sent']++;

			if ( 'confirmation' === $queue['type'] && ! empty( $recipient['ids'] ) ) {
				sfv_email_mark_confirmed( $recipient['ids'] );
			}
		} else {
			$queue['failed']++;
		}

		// Stay under Resend's default rate limit of 2 requests/second.
		usleep( 500000 );
	}

	if ( empty( $queue['recipients'] ) ) {
		update_option(
			'sfv_email_last_send',
			array(
				'type'        => $queue['type'],
				'subject'     => $queue['subject'],
				'sent'        => $queue['sent'],
				'failed'      => $queue['failed'],
				'finished_at' => time(),
			),
			false
		);
		sfv_email_queue_cancel();
		return;
	}

	update_option( SFV_EMAIL_QUEUE_OPTION, $queue, false );

	if ( ! wp_next_scheduled( SFV_EMAIL_QUEUE_HOOK ) ) {
		wp_schedule_single_event( time() + 60, SFV_EMAIL_QUEUE_HOOK );
	}
}

// ---------------------------------------------------------------------------
// Recipient queries
// ---------------------------------------------------------------------------

/**
 * Registrations that never received a confirmation, deduped by email address
 * (one email per address, all matching rows marked once it sends).
 */
function sfv_email_get_unconfirmed_recipients() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$rows = $wpdb->get_results( 'SELECT email, GROUP_CONCAT(id) AS ids FROM ' . sfv_register_table_name() . ' WHERE confirmation_sent IS NULL GROUP BY email' );

	$recipients = array();
	foreach ( $rows as $row ) {
		if ( ! is_email( $row->email ) ) {
			continue;
		}
		$recipients[] = array(
			'email' => $row->email,
			'ids'   => array_map( 'absint', explode( ',', $row->ids ) ),
		);
	}

	return $recipients;
}

function sfv_email_get_all_recipients() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$emails = $wpdb->get_col( 'SELECT DISTINCT email FROM ' . sfv_register_table_name() );

	$recipients = array();
	foreach ( $emails as $email ) {
		if ( ! is_email( $email ) ) {
			continue;
		}
		$recipients[] = array(
			'email' => $email,
			'ids'   => array(),
		);
	}

	return $recipients;
}

// ---------------------------------------------------------------------------
// Admin page
// ---------------------------------------------------------------------------

function sfv_email_admin_menu() {
	add_submenu_page(
		'sfv-registrations',
		__( 'Surfing for Vision Emails', 'sfv-registration' ),
		__( 'Emails', 'sfv-registration' ),
		MANAGE_CAP,
		'sfv-emails',
		__NAMESPACE__ . '\sfv_email_render_admin_page'
	);
}

function sfv_email_admin_actions() {
	if ( ! isset( $_GET['page'] ) || 'sfv-emails' !== $_GET['page'] || empty( $_POST['sfv_email_action'] ) ) {
		return;
	}

	if ( ! current_user_can( MANAGE_CAP ) ) {
		wp_die( esc_html__( 'Not allowed.', 'sfv-registration' ) );
	}

	$action = sanitize_key( wp_unslash( $_POST['sfv_email_action'] ) );
	$notice = '';
	$count  = 0;

	if ( 'save_settings' === $action ) {
		check_admin_referer( 'sfv_email_save_settings' );

		update_option(
			SFV_EMAIL_SETTINGS_OPTION,
			array(
				'enabled'              => empty( $_POST['enabled'] ) ? '' : '1',
				'from_name'            => isset( $_POST['from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['from_name'] ) ) : '',
				'from_email'           => isset( $_POST['from_email'] ) ? sanitize_email( wp_unslash( $_POST['from_email'] ) ) : '',
				'reply_to'             => isset( $_POST['reply_to'] ) ? sanitize_email( wp_unslash( $_POST['reply_to'] ) ) : '',
				'confirmation_subject' => isset( $_POST['confirmation_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmation_subject'] ) ) : '',
				'confirmation_message' => isset( $_POST['confirmation_message'] ) ? wp_kses_post( wp_unslash( $_POST['confirmation_message'] ) ) : '',
			)
		);

		$notice = 'settings_saved';
	}

	if ( 'send_test' === $action ) {
		check_admin_referer( 'sfv_email_send_test' );

		$to = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : '';

		if ( $to && is_email( $to ) ) {
			$sent   = sfv_email_send( $to, sfv_email_confirmation_subject(), sfv_email_confirmation_body_html() );
			$notice = $sent ? 'test_sent' : 'test_failed';
		} else {
			$notice = 'test_failed';
		}
	}

	if ( 'send_confirmations' === $action ) {
		check_admin_referer( 'sfv_email_send_confirmations' );

		$recipients = sfv_email_get_unconfirmed_recipients();
		$count      = count( $recipients );

		if ( 0 === $count ) {
			$notice = 'no_recipients';
		} elseif ( sfv_email_queue_start( 'confirmation', sfv_email_confirmation_subject(), sfv_email_confirmation_body_html(), $recipients ) ) {
			$notice = 'queued';
		} else {
			$notice = 'queue_busy';
		}
	}

	if ( 'send_blast' === $action ) {
		check_admin_referer( 'sfv_email_send_blast' );

		$subject = isset( $_POST['blast_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['blast_subject'] ) ) : '';
		$message = isset( $_POST['blast_message'] ) ? wp_kses_post( wp_unslash( $_POST['blast_message'] ) ) : '';

		if ( '' === $subject || '' === trim( wp_strip_all_tags( $message ) ) ) {
			$notice = 'blast_missing';
		} else {
			$recipients = sfv_email_get_all_recipients();
			$count      = count( $recipients );

			if ( 0 === $count ) {
				$notice = 'no_recipients';
			} elseif ( sfv_email_queue_start( 'blast', $subject, sfv_email_paragraphs( $message ), $recipients ) ) {
				$notice = 'queued';
			} else {
				$notice = 'queue_busy';
			}
		}
	}

	if ( 'cancel_queue' === $action ) {
		check_admin_referer( 'sfv_email_cancel_queue' );
		sfv_email_queue_cancel();
		$notice = 'queue_cancelled';
	}

	wp_safe_redirect( add_query_arg(
		array(
			'page'       => 'sfv-emails',
			'sfv_notice' => $notice,
			'sfv_count'  => $count,
		),
		admin_url( 'admin.php' )
	) );
	exit;
}

function sfv_email_admin_notice() {
	if ( empty( $_GET['sfv_notice'] ) ) {
		return;
	}

	$notice = sanitize_key( wp_unslash( $_GET['sfv_notice'] ) );
	$count  = isset( $_GET['sfv_count'] ) ? absint( $_GET['sfv_count'] ) : 0;

	$messages = array(
		'settings_saved'  => array( 'success', __( 'Email settings saved.', 'sfv-registration' ) ),
		'test_sent'       => array( 'success', __( 'Test email sent.', 'sfv-registration' ) ),
		'test_failed'     => array( 'error', __( 'Test email could not be sent. Check the address and mail configuration.', 'sfv-registration' ) ),
		/* translators: %d: number of recipients */
		'queued'          => array( 'success', sprintf( __( 'Send started for %d recipient(s). The first batch has gone out; remaining batches send about once per minute.', 'sfv-registration' ), $count ) ),
		'no_recipients'   => array( 'warning', __( 'No matching recipients found.', 'sfv-registration' ) ),
		'queue_busy'      => array( 'error', __( 'A send is already in progress. Wait for it to finish or cancel it first.', 'sfv-registration' ) ),
		'blast_missing'   => array( 'error', __( 'Enter both a subject and a message.', 'sfv-registration' ) ),
		'queue_cancelled' => array( 'success', __( 'Send cancelled.', 'sfv-registration' ) ),
	);

	if ( ! isset( $messages[ $notice ] ) ) {
		return;
	}

	printf(
		'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
		esc_attr( $messages[ $notice ][0] ),
		esc_html( $messages[ $notice ][1] )
	);
}

function sfv_email_render_admin_page() {
	$settings    = sfv_email_get_settings();
	$queue       = sfv_email_get_queue();
	$last_send   = get_option( 'sfv_email_last_send' );
	$enabled     = sfv_email_sending_enabled();
	$unconfirmed = sfv_email_get_unconfirmed_recipients();
	$all         = sfv_email_get_all_recipients();
	$effective   = sfv_email_effective_sender();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Surfing for Vision Emails', 'sfv-registration' ); ?></h1>

		<?php sfv_email_admin_notice(); ?>

		<?php if ( ! $enabled ) : ?>
			<div class="notice notice-warning">
				<p><strong><?php esc_html_e( 'Sending is disabled.', 'sfv-registration' ); ?></strong>
				<?php esc_html_e( 'New registrations will not receive confirmation emails and mass sends are locked. Enable sending below once the Resend account is connected (via an SMTP plugin) and the domain is verified.', 'sfv-registration' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $queue ) : ?>
			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Send in progress:', 'sfv-registration' ); ?></strong>
					<?php
					printf(
						/* translators: 1: sent count, 2: total count, 3: failed count */
						esc_html__( '%1$d of %2$d sent (%3$d failed). Batches go out about once per minute.', 'sfv-registration' ),
						(int) $queue['sent'],
						(int) $queue['total'],
						(int) $queue['failed']
					);
					?>
					<a href="<?php echo esc_url( add_query_arg( 'page', 'sfv-emails', admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Refresh', 'sfv-registration' ); ?></a>
				</p>
				<form method="post">
					<?php wp_nonce_field( 'sfv_email_cancel_queue' ); ?>
					<input type="hidden" name="sfv_email_action" value="cancel_queue">
					<p><button type="submit" class="button"><?php esc_html_e( 'Cancel remaining sends', 'sfv-registration' ); ?></button></p>
				</form>
			</div>
		<?php elseif ( $last_send ) : ?>
			<div class="notice notice-info">
				<p>
					<?php
					printf(
						/* translators: 1: subject, 2: sent count, 3: failed count, 4: date */
						esc_html__( 'Last send: "%1$s" — %2$d sent, %3$d failed (%4$s).', 'sfv-registration' ),
						esc_html( $last_send['subject'] ),
						(int) $last_send['sent'],
						(int) $last_send['failed'],
						esc_html( wp_date( 'M j, Y g:i a', $last_send['finished_at'] ) )
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Settings', 'sfv-registration' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'sfv_email_save_settings' ); ?>
			<input type="hidden" name="sfv_email_action" value="save_settings">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Sending enabled', 'sfv-registration' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enabled" value="1" <?php checked( $enabled ); ?>>
							<?php esc_html_e( 'Send confirmation emails to new registrations and allow mass sends', 'sfv-registration' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sfv-from-name"><?php esc_html_e( 'From name', 'sfv-registration' ); ?></label></th>
					<td><input type="text" class="regular-text" id="sfv-from-name" name="from_name" value="<?php echo esc_attr( $settings['from_name'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="sfv-from-email"><?php esc_html_e( 'From address', 'sfv-registration' ); ?></label></th>
					<td>
						<input type="email" class="regular-text" id="sfv-from-email" name="from_email" value="<?php echo esc_attr( $settings['from_email'] ); ?>">
						<p class="description"><?php esc_html_e( 'Must be on a domain verified with the email provider (surfingforvision.com).', 'sfv-registration' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sfv-reply-to"><?php esc_html_e( 'Reply-To address', 'sfv-registration' ); ?></label></th>
					<td>
						<input type="email" class="regular-text" id="sfv-reply-to" name="reply_to" value="<?php echo esc_attr( $settings['reply_to'] ); ?>">
						<p class="description"><?php esc_html_e( 'Optional. Set this to a mailbox the team reads (for example info@) so replies are seen.', 'sfv-registration' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sfv-confirmation-subject"><?php esc_html_e( 'Confirmation subject', 'sfv-registration' ); ?></label></th>
					<td><input type="text" class="large-text" id="sfv-confirmation-subject" name="confirmation_subject" value="<?php echo esc_attr( sfv_email_confirmation_subject() ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="sfv-confirmation-message"><?php esc_html_e( 'Confirmation message', 'sfv-registration' ); ?></label></th>
					<td>
						<textarea class="large-text" id="sfv-confirmation-message" name="confirmation_message" rows="12"><?php echo esc_textarea( sfv_email_confirmation_message() ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Sent to new registrations, the catch-up send, and test emails. Blank lines become new paragraphs; the branded design is added automatically. Clear a field and save to restore the original wording.', 'sfv-registration' ); ?></p>
					</td>
				</tr>
			</table>
			<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'sfv-registration' ); ?></button></p>
		</form>

		<p class="description">
			<?php
			printf(
				/* translators: %s: the sender address WordPress will use */
				esc_html__( 'WordPress will hand outgoing mail to the SMTP plugin as %s. If delivered emails show a different sender than this, the override is coming from the SMTP plugin itself (check its "force from name/email" options), not from WordPress.', 'sfv-registration' ),
				'<code>' . esc_html( $effective ) . '</code>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inline.
			);
			?>
		</p>

		<hr>

		<h2><?php esc_html_e( 'Send a test email', 'sfv-registration' ); ?></h2>
		<p><?php esc_html_e( 'Sends the registration confirmation email to one address so you can check formatting and delivery. Works even while sending is disabled.', 'sfv-registration' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'sfv_email_send_test' ); ?>
			<input type="hidden" name="sfv_email_action" value="send_test">
			<p>
				<input type="email" class="regular-text" name="test_email" placeholder="you@example.com" required>
				<button type="submit" class="button"><?php esc_html_e( 'Send test', 'sfv-registration' ); ?></button>
			</p>
		</form>

		<hr>

		<h2><?php esc_html_e( 'Send missed confirmation emails', 'sfv-registration' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: %d: number of email addresses */
				esc_html__( '%d email address(es) registered before confirmations were set up and have not received one. Each address gets one confirmation email, even if it was used for multiple registrations.', 'sfv-registration' ),
				count( $unconfirmed )
			);
			?>
		</p>
		<form method="post" onsubmit="return confirm('<?php echo esc_js( sprintf( __( 'Send the confirmation email to %d address(es) now?', 'sfv-registration' ), count( $unconfirmed ) ) ); ?>');">
			<?php wp_nonce_field( 'sfv_email_send_confirmations' ); ?>
			<input type="hidden" name="sfv_email_action" value="send_confirmations">
			<p>
				<button type="submit" class="button button-primary" <?php disabled( ! $enabled || empty( $unconfirmed ) || $queue ); ?>>
					<?php esc_html_e( 'Send confirmations now', 'sfv-registration' ); ?>
				</button>
			</p>
		</form>

		<hr>

		<h2><?php esc_html_e( 'Email all registrants', 'sfv-registration' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: %d: number of email addresses */
				esc_html__( 'Sends a one-off email (for example, event details right before the big day) to all %d registered address(es). Duplicate addresses receive one copy. The message is wrapped in the Surfing for Vision email design automatically.', 'sfv-registration' ),
				count( $all )
			);
			?>
		</p>
		<form method="post" onsubmit="return confirm('<?php echo esc_js( sprintf( __( 'Send this email to all %d address(es)? This cannot be undone.', 'sfv-registration' ), count( $all ) ) ); ?>');">
			<?php wp_nonce_field( 'sfv_email_send_blast' ); ?>
			<input type="hidden" name="sfv_email_action" value="send_blast">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sfv-blast-subject"><?php esc_html_e( 'Subject', 'sfv-registration' ); ?></label></th>
					<td><input type="text" class="large-text" id="sfv-blast-subject" name="blast_subject"></td>
				</tr>
				<tr>
					<th scope="row"><label for="sfv-blast-message"><?php esc_html_e( 'Message', 'sfv-registration' ); ?></label></th>
					<td>
						<textarea class="large-text" id="sfv-blast-message" name="blast_message" rows="10"></textarea>
						<p class="description"><?php esc_html_e( 'Plain text. Blank lines become new paragraphs.', 'sfv-registration' ); ?></p>
					</td>
				</tr>
			</table>
			<p>
				<button type="submit" class="button button-primary" <?php disabled( ! $enabled || empty( $all ) || $queue ); ?>>
					<?php esc_html_e( 'Send to all registrants', 'sfv-registration' ); ?>
				</button>
			</p>
		</form>
	</div>
	<?php
}
