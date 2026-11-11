<?php
/**
 * Who can see and manage registrations.
 *
 * Registrations hold personal details, so they are never public.
 * - Editors (the team) can view and export them.
 * - The Registration lead role (an Editor who runs registration) can also send
 *   the confirmation and reminder emails.
 * - Administrators can do all of that, delete entries and change email settings.
 *
 * @package SFV_Registration
 */

namespace SFV\Registration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VIEW_CAP   = 'sfv_view_registrations';
const SEND_CAP   = 'sfv_send_registration_emails';
const MANAGE_CAP = 'sfv_manage_registrations';

const LEAD_ROLE = 'sfv_registration_lead';

/**
 * Bump when the role → capability map below changes.
 */
const CAPS_VERSION = '2';

function capability_map(): array {
	return array(
		'administrator' => array( VIEW_CAP, SEND_CAP, MANAGE_CAP ),
		LEAD_ROLE       => array( VIEW_CAP, SEND_CAP ),
		'editor'        => array( VIEW_CAP ),
	);
}

/**
 * The Registration lead is an Editor plus the registration email capability, so
 * the person running registration doesn't need to be an Administrator.
 */
function add_lead_role(): void {
	$editor = get_role( 'editor' );

	if ( ! $editor ) {
		return;
	}

	remove_role( LEAD_ROLE );
	add_role( LEAD_ROLE, __( 'Registration lead', 'sfv-registration' ), $editor->capabilities );
}

function add_capabilities(): void {
	add_lead_role();

	foreach ( capability_map() as $role_name => $caps ) {
		$role = get_role( $role_name );

		if ( ! $role ) {
			continue;
		}

		foreach ( $caps as $cap ) {
			$role->add_cap( $cap );
		}
	}

	update_option( 'sfv_registration_caps_version', CAPS_VERSION );
}

/**
 * Activation hooks don't run when files are replaced over SFTP, so also sync
 * once per capability-map version.
 */
function maybe_sync_capabilities(): void {
	if ( get_option( 'sfv_registration_caps_version' ) !== CAPS_VERSION ) {
		add_capabilities();
	}
}
add_action( 'init', __NAMESPACE__ . '\maybe_sync_capabilities' );
