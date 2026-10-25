<?php
/**
 * Surfing for Vision theme setup.
 *
 * Presentation only. Registration, emails and anything that must survive a
 * theme switch belong in the sfv-registration plugin, not here.
 *
 * @package SFV_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_theme_file_path( 'inc/seasons.php' );
require_once get_theme_file_path( 'inc/event.php' );
require_once get_theme_file_path( 'inc/structured-data.php' );
require_once get_theme_file_path( 'inc/editor-access.php' );
require_once get_theme_file_path( 'inc/footer-links.php' );
require_once get_theme_file_path( 'inc/content-builder.php' );
require_once get_theme_file_path( 'inc/setup-content.php' );

/**
 * Loads the theme stylesheet on the front end and in the editor.
 */
function sfv_theme_setup() {
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'sfv_theme_setup' );

function sfv_theme_styles() {
	// Version by file time, so a deploy that changes the CSS also changes its URL
	// and nobody keeps a stale cached copy.
	wp_enqueue_style(
		'sfv-theme',
		get_stylesheet_uri(),
		array(),
		(string) filemtime( get_stylesheet_directory() . '/style.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'sfv_theme_styles' );

/**
 * Prints aria-current="page" when $url is the page being viewed, so screen
 * readers announce "current page" in the header and footer menus. Links to
 * sections of a page (with #) never count.
 */
function sfv_current_attr( $url ) {
	if ( str_contains( $url, '#' ) || empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	$current = wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
	$target  = wp_parse_url( $url, PHP_URL_PATH );

	if ( $current && $target && untrailingslashit( $current ) === untrailingslashit( $target ) ) {
		echo ' aria-current="page"';
	}
}

/**
 * Groups the theme's section patterns together in the inserter.
 */
function sfv_theme_pattern_categories() {
	register_block_pattern_category(
		'sfv-sections',
		array(
			'label'       => __( 'Surfing for Vision', 'sfv-theme' ),
			'description' => __( 'Sections and full pages for the Surfing for Vision site.', 'sfv-theme' ),
		)
	);
}
add_action( 'init', 'sfv_theme_pattern_categories' );

/**
 * Named styles editors can pick from the block sidebar instead of the
 * patterns hard-coding one-off CSS classes.
 */
function sfv_theme_block_styles() {
	register_block_style(
		'core/group',
		array(
			'name'  => 'sfv-card',
			'label' => __( 'Card', 'sfv-theme' ),
		)
	);
	register_block_style(
		'core/image',
		array(
			'name'  => 'sfv-framed',
			'label' => __( 'Framed', 'sfv-theme' ),
		)
	);
	register_block_style(
		'core/paragraph',
		array(
			'name'  => 'sfv-kicker',
			'label' => __( 'Section label', 'sfv-theme' ),
		)
	);
}
add_action( 'init', 'sfv_theme_block_styles' );

/**
 * Favicon from the theme when no Site Icon is set in Settings.
 */
function sfv_theme_site_icon() {
	if ( has_site_icon() ) {
		return;
	}

	printf( '<link rel="icon" type="image/png" href="%s">' . "\n", esc_url( get_theme_file_uri( 'assets/images/site-icon.png' ) ) );
}
add_action( 'wp_head', 'sfv_theme_site_icon' );
