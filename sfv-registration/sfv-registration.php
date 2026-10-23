<?php
/**
 * Plugin Name: Surfing for Vision Registration
 * Description: Event registration form block, the registrations table and admin
 *              screens, and confirmation emails. Lives in a plugin so a theme switch
 *              or update can never take sign-ups offline.
 * Version:     0.2.0
 * Requires at least: 7.0
 * Requires PHP: 8.0
 * Author:      Surfing for Vision
 * Text Domain: sfv-registration
 *
 * Ported from legacy/koinonia/inc/sfv-register.php and sfv-emails.php. Function
 * names are kept so the two can be diffed, but they live in this namespace so
 * they never collide with koinonia's global copies.
 *
 * While koinonia still carries its own registration code, this plugin stays
 * dormant (capabilities only) and shows admins a notice. It takes over by itself
 * as soon as the active theme no longer defines that code, so it can be installed
 * and activated on production ahead of the theme cutover.
 *
 * @package SFV_Registration
 */

namespace SFV\Registration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '0.2.0';
const FILE    = __FILE__;

require_once __DIR__ . '/includes/capabilities.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\add_capabilities' );
add_action( 'after_setup_theme', __NAMESPACE__ . '\boot', 20 );

/**
 * True while the koinonia theme's own registration code is loaded.
 */
function legacy_theme_active(): bool {
	return function_exists( '\sfv_register_table_name' );
}

function boot(): void {
	if ( legacy_theme_active() ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\legacy_notice' );
		return;
	}

	require_once __DIR__ . '/includes/registrations.php';
	require_once __DIR__ . '/includes/emails.php';
	require_once __DIR__ . '/includes/form-block.php';
}

function legacy_notice(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div class="notice notice-info"><p>'
		. esc_html__( 'Surfing for Vision Registration is installed but waiting: the current theme still handles registrations. It takes over automatically when the theme is switched.', 'sfv-registration' )
		. '</p></div>';
}
