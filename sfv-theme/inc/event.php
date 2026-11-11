<?php
/**
 * Event settings: the one place the event's date, times and place are entered.
 *
 * Editors fill in a short form under Pages → Event settings (date and time
 * pickers instead of free text). The values drive both the "Event details"
 * section people read (the sfv/event-details block) and the Event data Google
 * and AI assistants read (inc/structured-data.php), so they can never disagree.
 *
 * @package SFV_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SFV_EVENT_OPTION = 'sfv_event';

/**
 * The event is in New Jersey, whatever the site's own timezone setting says.
 */
function sfv_event_timezone() {
	return new DateTimeZone( 'America/New_York' );
}

function sfv_event_defaults() {
	return array(
		'date'          => '2026-08-22',
		'checkin_start' => '07:30',
		'checkin_end'   => '11:00',
		'start'         => '09:00',
		'end'           => '15:00',
		'place'         => 'Long Branch NJ Beachfront',
		'landmark'      => 'Google Rooney’s restaurant for landmark',
		'locality'      => 'Long Branch',
		'region'        => 'NJ',
		'cost'          => '0',
		'surfer_limit'  => '100',
		// '1' = the Register page takes sign-ups (until the event ends).
		'registration_open' => '1',
	);
}

function sfv_event() {
	$saved = get_option( SFV_EVENT_OPTION, array() );

	return wp_parse_args( is_array( $saved ) ? $saved : array(), sfv_event_defaults() );
}

/**
 * A DateTimeImmutable for the event date at $time (HH:MM), or null.
 */
function sfv_event_datetime( $time ) {
	$event = sfv_event();
	$value = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $event['date'] . ' ' . $time, sfv_event_timezone() );

	return $value ? $value : null;
}

function sfv_event_time_label( $time ) {
	$value = sfv_event_datetime( $time );

	return $value ? $value->format( 'g:i A' ) : '';
}

function sfv_event_date_label() {
	$value = sfv_event_datetime( '00:00' );

	return $value ? $value->format( 'F j, Y' ) : '';
}

/**
 * Whether the Register page takes sign-ups: the "Registration is open" box in
 * Event settings is ticked and the event hasn't ended yet. So the form closes
 * by itself after the event, and opens again once a new date is entered (and
 * the box is ticked).
 */
function sfv_registration_is_open() {
	$event = sfv_event();
	$end   = sfv_event_datetime( $event['end'] ? $event['end'] : '23:59' );

	if ( '1' !== (string) $event['registration_open'] ) {
		return false;
	}

	return ! $end || $end >= new DateTimeImmutable( 'now', sfv_event_timezone() );
}

/**
 * Tells the sfv-registration plugin which event people are signing up for,
 * whether sign-ups are open, the surfer limit and where Donate goes.
 */
function sfv_registration_event_for_plugin( $defaults ) {
	$event = sfv_event();

	return array_merge(
		(array) $defaults,
		array(
			'date'         => $event['date'],
			'label'        => sfv_event_date_label(),
			'open'         => sfv_registration_is_open(),
			'surfer_limit' => (int) $event['surfer_limit'],
			'donate_url'   => SFV_DONATE_URL,
		)
	);
}
add_filter( 'sfv_registration_event', 'sfv_registration_event_for_plugin' );

function sfv_register_event_block() {
	register_block_type( get_theme_file_path( 'blocks/event-details' ) );
}
add_action( 'init', 'sfv_register_event_block' );

// ---------------------------------------------------------------------------
// Pages → Event settings
// ---------------------------------------------------------------------------

function sfv_event_admin_menu() {
	add_pages_page(
		__( 'Event settings', 'sfv-theme' ),
		__( 'Event settings', 'sfv-theme' ),
		'edit_pages',
		'sfv-event-settings',
		'sfv_event_render_admin_page'
	);
}
add_action( 'admin_menu', 'sfv_event_admin_menu' );

function sfv_event_save() {
	if ( ! isset( $_POST['sfv_event_nonce'] ) ) {
		return;
	}

	check_admin_referer( 'sfv_event_save', 'sfv_event_nonce' );

	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'sfv-theme' ) );
	}

	$in    = wp_unslash( $_POST );
	$event = sfv_event();
	$date  = isset( $in['sfv_date'] ) ? sanitize_text_field( $in['sfv_date'] ) : '';

	if ( DateTimeImmutable::createFromFormat( '!Y-m-d', $date ) ) {
		$event['date'] = $date;
	}

	foreach ( array( 'checkin_start', 'checkin_end', 'start', 'end' ) as $key ) {
		$value = isset( $in[ 'sfv_' . $key ] ) ? sanitize_text_field( $in[ 'sfv_' . $key ] ) : '';
		if ( preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $value ) ) {
			$event[ $key ] = $value;
		}
	}

	foreach ( array( 'place', 'landmark', 'locality', 'region' ) as $key ) {
		$event[ $key ] = isset( $in[ 'sfv_' . $key ] ) ? sanitize_text_field( $in[ 'sfv_' . $key ] ) : '';
	}

	foreach ( array( 'cost', 'surfer_limit' ) as $key ) {
		$event[ $key ] = isset( $in[ 'sfv_' . $key ] ) && '' !== trim( $in[ 'sfv_' . $key ] ) ? (string) absint( $in[ 'sfv_' . $key ] ) : '';
	}

	$event['registration_open'] = empty( $in['sfv_registration_open'] ) ? '' : '1';

	update_option( SFV_EVENT_OPTION, $event );

	wp_safe_redirect( add_query_arg( array( 'page' => 'sfv-event-settings', 'updated' => '1' ), admin_url( 'edit.php?post_type=page' ) ) );
	exit;
}
add_action( 'admin_init', 'sfv_event_save' );

function sfv_event_field( $key, $label, $type, $value, $help = '' ) {
	$id = 'sfv-event-' . $key;
	?>
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td>
			<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" name="sfv_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" class="<?php echo 'text' === $type ? 'regular-text' : ''; ?>"<?php echo $help ? ' aria-describedby="' . esc_attr( $id ) . '-help"' : ''; ?><?php echo 'number' === $type ? ' min="0" step="1"' : ''; ?>>
			<?php if ( $help ) : ?>
				<p class="description" id="<?php echo esc_attr( $id ); ?>-help"><?php echo esc_html( $help ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

function sfv_event_render_admin_page() {
	$event = sfv_event();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Event settings', 'sfv-theme' ); ?></h1>

		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Event settings saved. The event details on the homepage and Register page now show these.', 'sfv-theme' ); ?></p></div>
		<?php endif; ?>

		<p><?php esc_html_e( 'The date, times and place of the next event. They appear in the event details on the homepage and the Register page, and are what Google and AI assistants read, so they only need to be entered here.', 'sfv-theme' ); ?></p>

		<form method="post">
			<?php wp_nonce_field( 'sfv_event_save', 'sfv_event_nonce' ); ?>
			<table class="form-table" role="presentation">
				<?php
				sfv_event_field( 'date', __( 'Event date', 'sfv-theme' ), 'date', $event['date'] );
				sfv_event_field( 'checkin_start', __( 'Check-in opens', 'sfv-theme' ), 'time', $event['checkin_start'] );
				sfv_event_field( 'checkin_end', __( 'Check-in closes', 'sfv-theme' ), 'time', $event['checkin_end'] );
				sfv_event_field( 'start', __( 'Surfing starts', 'sfv-theme' ), 'time', $event['start'] );
				sfv_event_field( 'end', __( 'Surfing ends', 'sfv-theme' ), 'time', $event['end'] );
				sfv_event_field( 'place', __( 'Location', 'sfv-theme' ), 'text', $event['place'], __( 'As people should read it, for example "Long Branch NJ Beachfront".', 'sfv-theme' ) );
				sfv_event_field( 'landmark', __( 'Landmark note', 'sfv-theme' ), 'text', $event['landmark'], __( 'Optional. Shown under the location.', 'sfv-theme' ) );
				sfv_event_field( 'locality', __( 'Town', 'sfv-theme' ), 'text', $event['locality'], __( 'For search engines, for example "Long Branch".', 'sfv-theme' ) );
				sfv_event_field( 'region', __( 'State', 'sfv-theme' ), 'text', $event['region'] );
				sfv_event_field( 'cost', __( 'Registration fee (dollars)', 'sfv-theme' ), 'number', $event['cost'], __( '0 for free.', 'sfv-theme' ) );
				sfv_event_field( 'surfer_limit', __( 'Surfer limit', 'sfv-theme' ), 'number', $event['surfer_limit'], __( 'Leave empty for no limit.', 'sfv-theme' ) );
				?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Registration', 'sfv-theme' ); ?></th>
					<td>
						<label for="sfv-event-registration-open">
							<input type="checkbox" id="sfv-event-registration-open" name="sfv_registration_open" value="1" aria-describedby="sfv-event-registration-open-help" <?php checked( '1', (string) $event['registration_open'] ); ?>>
							<?php esc_html_e( 'Registration is open', 'sfv-theme' ); ?>
						</label>
						<p class="description" id="sfv-event-registration-open-help">
							<?php esc_html_e( 'When this is off, or once the event date has passed, the Register page shows a "registration opens soon" message instead of the form. New sign-ups are saved under the event date above.', 'sfv-theme' ); ?>
						</p>
						<p>
							<strong>
							<?php
							echo esc_html(
								sfv_registration_is_open()
									? __( 'Right now: the Register page is taking sign-ups.', 'sfv-theme' )
									: __( 'Right now: registration is closed.', 'sfv-theme' )
							);
							?>
							</strong>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save event settings', 'sfv-theme' ) ); ?>
		</form>
	</div>
	<?php
}
