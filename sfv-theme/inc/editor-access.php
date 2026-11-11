<?php
/**
 * Gives Editors a way into the synced patterns (Event details, Sponsors, the
 * homepage seasons). WordPress only links them from Appearance, which Editors
 * can't open, though they are allowed to edit them.
 *
 * @package SFV_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sfv_shared_sections_menu() {
	add_pages_page(
		__( 'Shared sections', 'sfv-theme' ),
		__( 'Shared sections', 'sfv-theme' ),
		'edit_posts',
		'edit.php?post_type=wp_block'
	);
}
add_action( 'admin_menu', 'sfv_shared_sections_menu' );
