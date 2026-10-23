<?php
/**
 * Who can see and manage registrations.
 *
 * Registrations hold personal details, so they are never public. Editors (the
 * team) can view and export them; deleting entries and sending emails stay
 * with Administrators.
 *
 * @package SFV_Registration
 */

namespace SFV\Registration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VIEW_CAP   = 'sfv_view_registrations';
const MANAGE_CAP = 'sfv_manage_registrations';

/**
 * Bump when the role → capability map below changes.
 */
const CAPS_VERSION = '1';

function capability_map(): array {
	return array(
		'administrator' => array( VIEW_CAP, MANAGE_CAP ),
		'editor'        => array( VIEW_CAP ),
	);
}

function add_capabilities(): void {
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
