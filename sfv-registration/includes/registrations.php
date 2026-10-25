<?php
/**
 * Registration storage, submission handling, form markup and the admin screen.
 *
 * Ported from legacy/koinonia/inc/sfv-register.php. Changes from the original:
 * - No virtual /register/ route. The form is a block placed on a real page, so
 *   the copy around it is editable and the page title is correct.
 * - Admin screen moved from Tools to its own Registrations menu, gated by
 *   capabilities so Editors can view and export (see capabilities.php).
 *
 * @package SFV_Registration
 */

namespace SFV\Registration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SFV_REGISTER_DB_VERSION = '1.1.0';

/**
 * Shortest time (in seconds) a genuine human could plausibly take to read the
 * release and fill in the form. Submissions faster than this are treated as
 * automated. Kept conservative so slow-but-real users are never blocked.
 */
const SFV_REGISTER_MIN_FILL_SECONDS = 3;

add_action( 'init', __NAMESPACE__ . '\sfv_register_maybe_create_table' );
add_action( 'template_redirect', __NAMESPACE__ . '\sfv_register_template_redirect' );
add_action( 'admin_menu', __NAMESPACE__ . '\sfv_register_admin_menu' );
add_action( 'admin_init', __NAMESPACE__ . '\sfv_register_admin_actions' );

function sfv_register_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'sfv_registrations';
}

function sfv_register_maybe_create_table() {
	if ( get_option( 'sfv_register_db_version' ) === SFV_REGISTER_DB_VERSION ) {
		return;
	}

	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table_name      = sfv_register_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		created_at datetime NOT NULL,
		first_name varchar(80) NOT NULL,
		last_name varchar(80) NOT NULL,
		registration_type varchar(24) NOT NULL,
		email varchar(190) NOT NULL,
		phone varchar(20) NOT NULL,
		age tinyint unsigned NOT NULL,
		ip_address varchar(45) NOT NULL DEFAULT '',
		user_agent text NULL,
		confirmation_sent datetime NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY email (email),
		KEY created_at (created_at)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'sfv_register_db_version', SFV_REGISTER_DB_VERSION );
}

/**
 * The submission result for this request, read by the form block.
 */
function sfv_register_result( $set = null ) {
	static $result = null;

	if ( null !== $set ) {
		$result = $set;
	}

	return $result ?? array(
		'success' => false,
		'errors'  => array(),
		'values'  => sfv_register_default_values(),
	);
}

/**
 * On pages that contain the form: never cache (the form carries a nonce and a
 * signed timestamp), and handle a submission before any output.
 */
function sfv_register_template_redirect() {
	if ( ! is_singular() || ! has_block( 'sfv/registration-form', get_queried_object() ) ) {
		return;
	}

	nocache_headers();
	sfv_register_result( sfv_register_handle_submission() );
}

function sfv_register_handle_submission() {
	$result = array(
		'success' => false,
		'errors'  => array(),
		'values'  => sfv_register_default_values(),
	);

	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['sfv_register_submit'] ) ) {
		return $result;
	}

	$values = array(
		'first_name'        => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
		'last_name'         => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
		'registration_type' => isset( $_POST['registration_type'] ) ? sanitize_key( wp_unslash( $_POST['registration_type'] ) ) : '',
		'email'             => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'phone'             => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
		'age'               => isset( $_POST['age'] ) ? sanitize_text_field( wp_unslash( $_POST['age'] ) ) : '',
		'release_ack'       => isset( $_POST['release_ack'] ) ? '1' : '',
	);

	$result['values'] = $values;

	if ( empty( $_POST['sfv_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sfv_register_nonce'] ) ), 'sfv_register_submit' ) ) {
		$result['errors']['form'] = __( 'Please refresh the page and try submitting the form again.', 'sfv-registration' );
		return $result;
	}

	// Bot protection (honeypot + timing trap). Runs after the nonce check so
	// only same-origin submissions reach it. On a hit we stop without saving
	// and show a neutral message that gives automated tools nothing to learn
	// from. Genuine users never see the honeypot field or trip the timer.
	if ( sfv_register_looks_like_bot() ) {
		$result['errors']['form'] = __( 'Your submission could not be processed. Please refresh the page and try again.', 'sfv-registration' );
		return $result;
	}

	$errors = sfv_register_validate_values( $values );

	if ( ! empty( $errors ) ) {
		$result['errors'] = $errors;
		return $result;
	}

	global $wpdb;

	$inserted = $wpdb->insert(
		sfv_register_table_name(),
		array(
			'created_at'        => current_time( 'mysql' ),
			'first_name'        => $values['first_name'],
			'last_name'         => $values['last_name'],
			'registration_type' => $values['registration_type'],
			'email'             => $values['email'],
			'phone'             => sfv_register_normalize_phone( $values['phone'] ),
			'age'               => (int) $values['age'],
			'ip_address'        => sfv_register_get_ip_address(),
			'user_agent'        => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 500 ) : '',
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
	);

	if ( false === $inserted ) {
		$result['errors']['form'] = __( 'We could not save your registration. Please try again.', 'sfv-registration' );
		return $result;
	}

	do_action( 'sfv_registration_created', (int) $wpdb->insert_id, $values );

	$result['success'] = true;
	$result['values']  = sfv_register_default_values();

	return $result;
}

function sfv_register_default_values() {
	return array(
		'first_name'        => '',
		'last_name'         => '',
		'registration_type' => '',
		'email'             => '',
		'phone'             => '',
		'age'               => '',
		'release_ack'       => '',
	);
}

/**
 * Decides whether a submission looks automated rather than blocking outright,
 * using two low-friction, fully accessible signals:
 *
 *   1. Honeypot — a field hidden from sight and assistive tech that humans
 *      never fill but naive form-filling bots do.
 *   2. Timing trap — a signed page-render timestamp; a submission that arrives
 *      faster than any human could complete the form is treated as a bot.
 *
 * Neither adds a visible challenge, so blind and low-vision registrants (this
 * event's core audience) are unaffected.
 */
function sfv_register_looks_like_bot() {
	// Honeypot: any content here means an automated filler populated a field a
	// real user can neither see nor tab to.
	if ( ! empty( $_POST['sfv_hp_website'] ) ) {
		return true;
	}

	$ts  = isset( $_POST['sfv_register_ts'] ) ? absint( $_POST['sfv_register_ts'] ) : 0;
	$sig = isset( $_POST['sfv_register_ts_sig'] ) ? sanitize_text_field( wp_unslash( $_POST['sfv_register_ts_sig'] ) ) : '';

	// A missing or forged timestamp means the form markup was not the one we
	// served (scripted post), so treat it as automated.
	if ( ! $ts || ! hash_equals( sfv_register_ts_signature( $ts ), $sig ) ) {
		return true;
	}

	// Submitted implausibly soon after the page was rendered.
	if ( ( time() - $ts ) < SFV_REGISTER_MIN_FILL_SECONDS ) {
		return true;
	}

	return false;
}

/**
 * Signs the render timestamp with a site secret so it cannot be forged or
 * back-dated by a client to defeat the timing trap.
 */
function sfv_register_ts_signature( $ts ) {
	return hash_hmac( 'sha256', 'sfv_register_ts|' . (int) $ts, wp_salt( 'nonce' ) );
}

/**
 * One message per field, saying what to do and giving an example. Used by the
 * server-side check, the in-browser check and the error summary.
 */
function sfv_register_messages() {
	return array(
		'first_name'        => __( 'Enter a valid first name.', 'sfv-registration' ),
		'last_name'         => __( 'Enter a valid last name.', 'sfv-registration' ),
		'registration_type' => __( 'Choose one registration type.', 'sfv-registration' ),
		'email'             => __( 'Enter a valid email address.', 'sfv-registration' ),
		'phone'             => __( 'Enter a valid US phone number.', 'sfv-registration' ),
		'age'               => __( 'Enter a valid age.', 'sfv-registration' ),
		'release_ack'       => __( 'You must acknowledge the event release to submit.', 'sfv-registration' ),
	);
}

function sfv_register_validate_values( $values ) {
	$messages = sfv_register_messages();
	$errors   = array();

	if ( ! sfv_register_is_valid_name( $values['first_name'] ) ) {
		$errors['first_name'] = $messages['first_name'];
	}

	if ( ! sfv_register_is_valid_name( $values['last_name'] ) ) {
		$errors['last_name'] = $messages['last_name'];
	}

	if ( ! in_array( $values['registration_type'], array( 'surfer', 'guest', 'volunteer' ), true ) ) {
		$errors['registration_type'] = $messages['registration_type'];
	}

	if ( empty( $values['email'] ) || ! is_email( $values['email'] ) ) {
		$errors['email'] = $messages['email'];
	}

	if ( ! sfv_register_is_valid_us_phone( $values['phone'] ) ) {
		$errors['phone'] = $messages['phone'];
	}

	if ( '' === $values['age'] || false === filter_var( $values['age'], FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1, 'max_range' => 120 ) ) ) ) {
		$errors['age'] = $messages['age'];
	}

	if ( '1' !== $values['release_ack'] ) {
		$errors['release_ack'] = $messages['release_ack'];
	}

	return $errors;
}

/**
 * Letters (any language), spaces, hyphens, periods and apostrophes, including
 * the curly ’ phones type, from 1 to 60 characters with at least one letter.
 */
function sfv_register_is_valid_name( $name ) {
	$name = trim( preg_replace( '/\s+/u', ' ', $name ) );

	if ( '' === $name || mb_strlen( $name ) > 60 ) {
		return false;
	}

	return 1 === preg_match( "/^[\p{L}\p{M}'’. -]+$/u", $name ) && 1 === preg_match( '/\p{L}/u', $name );
}

function sfv_register_is_valid_us_phone( $phone ) {
	$digits = preg_replace( '/\D+/', '', $phone );

	if ( 11 === strlen( $digits ) && '1' === $digits[0] ) {
		$digits = substr( $digits, 1 );
	}

	if ( 10 !== strlen( $digits ) ) {
		return false;
	}

	return '0' !== $digits[0] && '1' !== $digits[0] && '0' !== $digits[3] && '1' !== $digits[3];
}

function sfv_register_normalize_phone( $phone ) {
	$digits = preg_replace( '/\D+/', '', $phone );

	if ( 11 === strlen( $digits ) && '1' === $digits[0] ) {
		$digits = substr( $digits, 1 );
	}

	return sprintf( '(%s) %s-%s', substr( $digits, 0, 3 ), substr( $digits, 3, 3 ), substr( $digits, 6 ) );
}

function sfv_register_get_ip_address() {
	if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
		return '';
	}

	return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
}

// ---------------------------------------------------------------------------
// Form markup (rendered by the sfv/registration-form block)
//
// Accessibility pattern (after GOV.UK's tested form guidance):
// - Errors are listed in one summary at the top of the form, each linking to
//   its field; keyboard focus moves to the summary (view.js) and the page title
//   starts with "Error:". Fields show the same message inline, tied to the input.
// - After a successful registration focus moves to the thank-you heading and
//   the page title says the registration is complete.
// - Hints are visible text tied to the input, not placeholders.
// ---------------------------------------------------------------------------

add_filter( 'document_title_parts', __NAMESPACE__ . '\sfv_register_title_parts' );
add_filter( 'wpseo_title', __NAMESPACE__ . '\sfv_register_wpseo_title' );

/**
 * 'error', 'success' or '' for the current request's submission.
 */
function sfv_register_state() {
	$result = sfv_register_result();

	if ( ! empty( $result['success'] ) ) {
		return 'success';
	}

	return empty( $result['errors'] ) ? '' : 'error';
}

function sfv_register_title_parts( $parts ) {
	$state = sfv_register_state();

	if ( 'success' === $state ) {
		$parts['title'] = __( 'Registration complete', 'sfv-registration' );
	} elseif ( 'error' === $state ) {
		/* translators: %s: page title */
		$parts['title'] = sprintf( __( 'Error: %s', 'sfv-registration' ), $parts['title'] );
	}

	return $parts;
}

function sfv_register_wpseo_title( $title ) {
	$state = sfv_register_state();

	if ( 'success' === $state ) {
		/* translators: %s: page title */
		return sprintf( __( 'Registration complete – %s', 'sfv-registration' ), $title );
	}

	/* translators: %s: page title */
	return 'error' === $state ? sprintf( __( 'Error: %s', 'sfv-registration' ), $title ) : $title;
}

/**
 * The element an error summary link should move to.
 */
function sfv_register_field_target( $name ) {
	$targets = array(
		'registration_type' => 'sfv-registration-surfer',
		'release_ack'       => 'sfv-release-ack',
	);

	return isset( $targets[ $name ] ) ? $targets[ $name ] : 'sfv-' . str_replace( '_', '-', $name );
}

function sfv_register_render_error_summary( $errors ) {
	if ( empty( $errors ) ) {
		return;
	}
	?>
	<div class="sfv-error-summary" id="sfv-error-summary" role="region" tabindex="-1" aria-labelledby="sfv-error-summary-title">
		<h3 id="sfv-error-summary-title"><?php esc_html_e( 'There is a problem', 'sfv-registration' ); ?></h3>
		<ul>
			<?php if ( ! empty( $errors['form'] ) ) : ?>
				<li><?php echo esc_html( $errors['form'] ); ?></li>
			<?php endif; ?>
			<?php foreach ( array_keys( sfv_register_messages() ) as $name ) : ?>
				<?php if ( ! empty( $errors[ $name ] ) ) : ?>
					<li><a href="#<?php echo esc_attr( sfv_register_field_target( $name ) ); ?>"><?php echo esc_html( $errors[ $name ] ); ?></a></li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

function sfv_register_render_form( $result ) {
	$values = $result['values'];
	$errors = $result['errors'];
	?>
	<?php
	// The wording below matches the live site. Propose changes in
	// docs/accessibility.md rather than making them. (The empty span keeps
	// WordPress from turning the legend's "..." into an ellipsis character.)
	?>
	<div class="sfv-form-section">
		<div class="sfv-form-shell">
			<h2 id="sfv-form-title">Surfing For Vision Registration</h2>
			<div class="sfv-release">
				<h3 id="sfv-release-title">SURFING FOR VISION RELEASE AND HOLD HARMLESS AGREEMENT</h3>
				<p>I understand that my participation and/or involvement in a Surfing for Vision event, including but not limited to traveling to and from the location and participating in the activity, event, program, etc. carries with it the potential for certain risks, some of which may not be reasonably foreseeable.</p>
				<p>I further acknowledge that these risks could cause me, or others around me, harm, including, but not limited to, bodily injury, damage to property, emotional distress, or death.</p>
				<p>I am willing to travel to and from and participate in the Surfing for Vision activity, event, program, etc., at my own risk knowing that there may be foreseeable and unforeseeable risks involved in travel and participating in a visual experience discussed with me in advance.</p>
				<p>I also agree that my image in photos and video maybe used for media and for the purposes of promoting the Foundation and the cause. I am also willing to participate in media interviews if requested.</p>
				<p>I agree that by registering online it constitutes my agreement to this entire release and that I am agreeing to release, indemnify, and hold harmless Surfing for Vision, as well as all their/its employees, agents, representatives, successors, volunteers, the city of Long Branch etc. from all losses, claims, theft, demands, liabilities, causes of action, or expenses, known or unknown, arising out of my uncovered expense or claim based on my participation in, attendance at a Surfing for Vision visit.</p>
			</div>

			<form class="sfv-registration-form" id="register-form" method="post" action="<?php echo esc_url( get_permalink() . '#register-form' ); ?>" data-validation-active="<?php echo empty( $errors ) ? '0' : '1'; ?>" data-sfv-validation="<?php echo esc_attr( wp_json_encode( sfv_register_validation_data() ) ); ?>" novalidate>
				<?php sfv_register_render_error_summary( $errors ); ?>
				<?php wp_nonce_field( 'sfv_register_submit', 'sfv_register_nonce' ); ?>
				<?php $sfv_register_ts = time(); ?>
				<input type="hidden" name="sfv_register_ts" value="<?php echo esc_attr( $sfv_register_ts ); ?>">
				<input type="hidden" name="sfv_register_ts_sig" value="<?php echo esc_attr( sfv_register_ts_signature( $sfv_register_ts ) ); ?>">
				<?php // Honeypot: hidden from sight (off-screen) and from assistive tech (aria-hidden + tabindex -1). Only bots fill it. ?>
				<div class="sfv-hp-field" aria-hidden="true" style="position:absolute!important;left:-9999px!important;top:auto;width:1px;height:1px;overflow:hidden;">
					<label for="sfv-hp-website">Leave this field blank</label>
					<input type="text" id="sfv-hp-website" name="sfv_hp_website" value="" tabindex="-1" autocomplete="off">
				</div>
				<div class="sfv-form-grid">
					<?php sfv_register_render_input( 'first_name', 'First Name', 'text', $values, $errors, array( 'autocomplete' => 'given-name' ) ); ?>
					<?php sfv_register_render_input( 'last_name', 'Last Name', 'text', $values, $errors, array( 'autocomplete' => 'family-name' ) ); ?>

					<fieldset class="sfv-field sfv-field--full<?php echo isset( $errors['registration_type'] ) ? ' has-error' : ''; ?>"<?php echo isset( $errors['registration_type'] ) ? ' aria-describedby="' . esc_attr( sfv_register_error_id( 'registration_type' ) ) . '"' : ''; ?>>
						<legend><span class="sfv-sr-only">Required: </span><span class="sfv-required" aria-hidden="true">*</span>I am registering as a..<span></span>.</legend>
						<div class="sfv-radio-row">
							<?php sfv_register_render_radio( 'surfer', 'Surfer (Participant)', $values['registration_type'], isset( $errors['registration_type'] ) ); ?>
							<?php sfv_register_render_radio( 'guest', 'Guest (Family & Friends)', $values['registration_type'], isset( $errors['registration_type'] ) ); ?>
							<?php sfv_register_render_radio( 'volunteer', 'Volunteer', $values['registration_type'], isset( $errors['registration_type'] ) ); ?>
						</div>
						<?php sfv_register_render_error( 'registration_type', $errors ); ?>
					</fieldset>

					<?php sfv_register_render_input( 'email', 'Email Address', 'email', $values, $errors, array( 'autocomplete' => 'email', 'spellcheck' => 'false', 'autocapitalize' => 'none', 'placeholder' => 'JohnAppleseed@emailaddress.com' ) ); ?>
					<?php sfv_register_render_input( 'phone', 'Phone Number', 'tel', $values, $errors, array( 'autocomplete' => 'tel-national', 'placeholder' => '(555) 555-5555' ) ); ?>
					<?php sfv_register_render_input( 'age', 'Age of Registrant', 'text', $values, $errors, array( 'inputmode' => 'numeric', 'maxlength' => '3', 'autocomplete' => 'off', 'class' => 'sfv-input--short' ) ); ?>
				</div>
				<div class="sfv-field sfv-release-ack<?php echo isset( $errors['release_ack'] ) ? ' has-error' : ''; ?>">
					<input id="sfv-release-ack" type="checkbox" name="release_ack" value="1" required <?php checked( $values['release_ack'], '1' ); ?><?php echo isset( $errors['release_ack'] ) ? ' aria-invalid="true" aria-describedby="' . esc_attr( sfv_register_error_id( 'release_ack' ) ) . '"' : ''; ?>>
					<label for="sfv-release-ack"><span class="sfv-sr-only">Required: </span><span class="sfv-required" aria-hidden="true">*</span>This box must be checked to acknowledge that you read and agree to the event release.</label>
					<?php sfv_register_render_error( 'release_ack', $errors ); ?>
				</div>
				<button class="sfv-button sfv-submit-button" type="submit" name="sfv_register_submit" value="1">Submit registration form</button>
			</form>
		</div>
	</div>
	<?php
}

/**
 * The same checks as the server, run on submit so most mistakes are caught
 * without a page reload. Builds the same error summary and moves focus to it.
 */
/**
 * Settings for view.js (the in-browser checks), passed as data on the form.
 */
function sfv_register_validation_data() {
	$targets = array();
	foreach ( array_keys( sfv_register_messages() ) as $name ) {
		$targets[ $name ] = sfv_register_field_target( $name );
	}

	return array(
		'messages'     => sfv_register_messages(),
		'targets'      => $targets,
		'summaryTitle' => __( 'There is a problem', 'sfv-registration' ),
		'errorPrefix'  => __( 'Error:', 'sfv-registration' ),
	);
}

function sfv_register_error_id( $name ) {
	return 'sfv-' . str_replace( '_', '-', $name ) . '-error';
}

/**
 * Text input with its label, an optional visible hint ($attrs['hint']) and its
 * error, all tied to the input with aria-describedby.
 */
function sfv_register_render_input( $name, $label, $type, $values, $errors, $attrs = array() ) {
	$field_id = 'sfv-' . str_replace( '_', '-', $name );
	$hint     = isset( $attrs['hint'] ) ? $attrs['hint'] : '';
	$hint_id  = $field_id . '-hint';
	$classes  = 'sfv-field';
	unset( $attrs['hint'] );

	if ( isset( $errors[ $name ] ) ) {
		$classes .= ' has-error';
	}

	$described = array_filter( array( $hint ? $hint_id : '', isset( $errors[ $name ] ) ? sfv_register_error_id( $name ) : '' ) );

	$attrs = array_merge(
		array(
			'id'       => $field_id,
			'name'     => $name,
			'type'     => $type,
			'value'    => $values[ $name ],
			'required' => 'required',
		),
		$attrs
	);

	if ( $described ) {
		$attrs['aria-describedby'] = implode( ' ', $described );
	}

	if ( isset( $errors[ $name ] ) ) {
		$attrs['aria-invalid'] = 'true';
	}
	?>
	<div class="<?php echo esc_attr( $classes ); ?>">
		<label for="<?php echo esc_attr( $field_id ); ?>"><span class="sfv-sr-only">Required: </span><span class="sfv-required" aria-hidden="true">*</span><?php echo esc_html( $label ); ?></label>
		<?php if ( $hint ) : ?>
			<p class="sfv-field__hint" id="<?php echo esc_attr( $hint_id ); ?>"><?php echo esc_html( $hint ); ?></p>
		<?php endif; ?>
		<input <?php sfv_register_print_attrs( $attrs ); ?>>
		<?php sfv_register_render_error( $name, $errors ); ?>
	</div>
	<?php
}

function sfv_register_render_radio( $value, $label, $selected, $has_error = false ) {
	$field_id = 'sfv-registration-' . $value;
	?>
	<label class="sfv-radio" for="<?php echo esc_attr( $field_id ); ?>">
		<input id="<?php echo esc_attr( $field_id ); ?>" type="radio" name="registration_type" value="<?php echo esc_attr( $value ); ?>" required <?php checked( $selected, $value ); ?><?php echo $has_error ? ' aria-invalid="true"' : ''; ?>>
		<span><?php echo esc_html( $label ); ?></span>
	</label>
	<?php
}

function sfv_register_render_error( $name, $errors ) {
	if ( empty( $errors[ $name ] ) ) {
		return;
	}

	?><p class="sfv-form-error" id="<?php echo esc_attr( sfv_register_error_id( $name ) ); ?>"><span class="sfv-sr-only"><?php esc_html_e( 'Error:', 'sfv-registration' ); ?> </span><?php echo esc_html( $errors[ $name ] ); ?></p><?php
}

function sfv_register_print_attrs( $attrs ) {
	foreach ( $attrs as $name => $value ) {
		if ( is_bool( $value ) ) {
			if ( $value ) {
				echo ' ' . esc_attr( $name );
			}
			continue;
		}

		echo ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
	}
}

/**
 * Heading and message come from the block's settings so editors can change
 * them (for example the event year) without code. Focus moves to the heading
 * so screen reader users hear that it worked.
 */
function sfv_register_render_success( $heading, $message ) {
	?>
	<div class="sfv-register-success" id="register-form">
		<div class="sfv-register-success__inner">
			<h2 id="sfv-success-title" tabindex="-1"><?php echo esc_html( $heading ); ?></h2>
			<p><?php echo esc_html( $message ); ?></p>
			<a class="sfv-button" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to homepage</a>
		</div>
	</div>
	<?php
}

// ---------------------------------------------------------------------------
// Admin screen
// ---------------------------------------------------------------------------

function sfv_register_admin_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => 'sfv-registrations' ), $args ), admin_url( 'admin.php' ) );
}

function sfv_register_admin_menu() {
	add_menu_page(
		__( 'Surfing for Vision Registrations', 'sfv-registration' ),
		__( 'Registrations', 'sfv-registration' ),
		VIEW_CAP,
		'sfv-registrations',
		__NAMESPACE__ . '\sfv_register_render_admin_page',
		'dashicons-groups',
		26
	);
}

/**
 * Handles the CSV export and single-row delete actions before any output.
 * Hooked to admin_init so headers are still available.
 */
function sfv_register_admin_actions() {
	if ( ! isset( $_GET['page'] ) || 'sfv-registrations' !== $_GET['page'] ) {
		return;
	}

	// ---- CSV export --------------------------------------------------------
	if ( isset( $_GET['sfv_action'] ) && 'export_csv' === $_GET['sfv_action'] ) {
		check_admin_referer( 'sfv_export_csv' );
		if ( ! current_user_can( VIEW_CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'sfv-registration' ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( 'SELECT * FROM ' . sfv_register_table_name() . ' ORDER BY created_at ASC', ARRAY_A );

		$filename = 'sfv-registrations-' . gmdate( 'Y-m-d' ) . '.csv';
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'ID', 'Date', 'First Name', 'Last Name', 'Type', 'Email', 'Phone', 'Age' ) );
		foreach ( $rows as $row ) {
			fputcsv( $out, array(
				$row['id'],
				$row['created_at'],
				$row['first_name'],
				$row['last_name'],
				$row['registration_type'],
				$row['email'],
				$row['phone'],
				$row['age'],
			) );
		}
		fclose( $out );
		exit;
	}

	// ---- Delete single row -------------------------------------------------
	if ( isset( $_GET['sfv_action'] ) && 'delete' === $_GET['sfv_action'] ) {
		$row_id = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;
		check_admin_referer( 'sfv_delete_' . $row_id );
		if ( ! current_user_can( MANAGE_CAP ) || ! $row_id ) {
			wp_die( esc_html__( 'Not allowed.', 'sfv-registration' ) );
		}

		global $wpdb;
		$wpdb->delete( sfv_register_table_name(), array( 'id' => $row_id ), array( '%d' ) );

		wp_safe_redirect( sfv_register_admin_url( array( 'deleted' => '1' ) ) );
		exit;
	}
}

function sfv_register_render_admin_page() {
	global $wpdb;

	$can_delete = current_user_can( MANAGE_CAP );
	$table      = sfv_register_table_name();
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$rows       = $wpdb->get_results( 'SELECT * FROM ' . $table . ' ORDER BY created_at DESC' );
	$total      = count( $rows );
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$surfer_count = (int) $wpdb->get_var( $wpdb->prepare(
		'SELECT COUNT(*) FROM ' . $table . ' WHERE registration_type = %s',
		'surfer'
	) );

	$export_url = wp_nonce_url( sfv_register_admin_url( array( 'sfv_action' => 'export_csv' ) ), 'sfv_export_csv' );
	?>
	<div class="wrap">
		<h1 style="display:flex;align-items:center;gap:16px;">
			<?php esc_html_e( 'Surfing for Vision Registrations', 'sfv-registration' ); ?>
			<a href="<?php echo esc_url( $export_url ); ?>" class="button button-primary">
				<?php esc_html_e( 'Export CSV', 'sfv-registration' ); ?>
			</a>
		</h1>

		<?php if ( isset( $_GET['deleted'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Entry deleted.', 'sfv-registration' ); ?></p></div>
		<?php endif; ?>

		<p>
			<strong><?php echo esc_html( $total ); ?></strong> <?php esc_html_e( 'total registrations', 'sfv-registration' ); ?> &mdash;
			<strong><?php echo esc_html( $surfer_count ); ?> / 100</strong> <?php esc_html_e( 'surfer slots filled', 'sfv-registration' ); ?>
		</p>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'sfv-registration' ); ?></th>
					<th><?php esc_html_e( 'Name', 'sfv-registration' ); ?></th>
					<th><?php esc_html_e( 'Type', 'sfv-registration' ); ?></th>
					<th><?php esc_html_e( 'Email', 'sfv-registration' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'sfv-registration' ); ?></th>
					<th><?php esc_html_e( 'Age', 'sfv-registration' ); ?></th>
					<th><?php esc_html_e( 'Emailed', 'sfv-registration' ); ?></th>
					<?php if ( $can_delete ) : ?>
						<th><?php esc_html_e( 'Delete', 'sfv-registration' ); ?></th>
					<?php endif; ?>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="<?php echo $can_delete ? 8 : 7; ?>"><?php esc_html_e( 'No registrations yet.', 'sfv-registration' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row->created_at ); ?></td>
							<td><?php echo esc_html( $row->first_name . ' ' . $row->last_name ); ?></td>
							<td><?php echo esc_html( ucfirst( $row->registration_type ) ); ?></td>
							<td><a href="mailto:<?php echo esc_attr( $row->email ); ?>"><?php echo esc_html( $row->email ); ?></a></td>
							<td><?php echo esc_html( $row->phone ); ?></td>
							<td><?php echo esc_html( $row->age ); ?></td>
							<td><?php echo empty( $row->confirmation_sent ) ? '&mdash;' : '<span style="color:#00786f;" title="' . esc_attr( $row->confirmation_sent ) . '">&#10003;</span>'; ?></td>
							<?php if ( $can_delete ) : ?>
								<td>
									<a href="<?php echo esc_url( wp_nonce_url( sfv_register_admin_url( array( 'sfv_action' => 'delete', 'entry_id' => $row->id ) ), 'sfv_delete_' . $row->id ) ); ?>"
									   class="button button-small"
									   style="color:#b32d2e;"
									   onclick="return confirm('<?php esc_attr_e( 'Delete this entry? This cannot be undone.', 'sfv-registration' ); ?>')">
										<?php esc_html_e( 'Delete', 'sfv-registration' ); ?>
									</a>
								</td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
