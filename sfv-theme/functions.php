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

/** The PayPal donate page linked from the header, footer and Donate buttons. */
const SFV_DONATE_URL = 'https://www.paypal.com/ncp/payment/YZ22T3386PE3J';

/*
 * Code is owned by Git, so nobody edits theme or plugin files in wp-admin
 * (Appearance → Theme File Editor, Plugins → Plugin File Editor). WordPress
 * checks this constant when it decides who may edit files, so defining it here
 * works like the wp-config.php line, which EasyWP only lets us change over SFTP.
 * A define in wp-config.php, if one is ever added, wins.
 */
/*
 * Never "update" this theme from WordPress.org. If someone publishes a public
 * theme with the folder name sfv-theme, WordPress would otherwise offer (and,
 * with auto-updates on, install) it over ours. That is how the old site was
 * wiped in Sept 2026. Updates come only from Git.
 */
function sfv_theme_no_wporg_updates( $transient ) {
	if ( is_object( $transient ) ) {
		foreach ( array( 'response', 'no_update' ) as $key ) {
			if ( isset( $transient->{$key}['sfv-theme'] ) ) {
				unset( $transient->{$key}['sfv-theme'] );
			}
		}
	}

	return $transient;
}
add_filter( 'site_transient_update_themes', 'sfv_theme_no_wporg_updates' );

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
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
 * Site icon (browser tab, bookmarks, phone home screen, Google results) from the
 * brand's eye mark on deep teal, unless someone sets a Site Icon in Settings →
 * General, which wins. Files: assets/images/site-icon.svg (rounded tile, any
 * size), site-icon-32/48/192.png, apple-touch-icon.png (180, full square for
 * iOS to round itself) and site-icon.png (512).
 */
function sfv_theme_site_icon() {
	if ( has_site_icon() ) {
		return;
	}

	$icons = array(
		array( 'icon', 'image/svg+xml', 'any', 'site-icon.svg' ),
		array( 'icon', 'image/png', '32x32', 'site-icon-32.png' ),
		array( 'icon', 'image/png', '48x48', 'site-icon-48.png' ),
		array( 'icon', 'image/png', '192x192', 'site-icon-192.png' ),
		array( 'apple-touch-icon', '', '180x180', 'apple-touch-icon.png' ),
	);

	foreach ( $icons as list( $rel, $type, $sizes, $file ) ) {
		printf(
			'<link rel="%s"%s sizes="%s" href="%s">' . "\n",
			esc_attr( $rel ),
			$type ? ' type="' . esc_attr( $type ) . '"' : '',
			esc_attr( $sizes ),
			esc_url( get_theme_file_uri( 'assets/images/' . $file ) )
		);
	}
}
add_action( 'wp_head', 'sfv_theme_site_icon' );
add_action( 'admin_head', 'sfv_theme_site_icon' );
add_action( 'login_head', 'sfv_theme_site_icon' );

/**
 * Some browsers and crawlers ask for /favicon.ico directly; WordPress answers
 * with an empty response when no Site Icon is set. Send them the 48px icon.
 */
function sfv_theme_favicon_ico() {
	if ( has_site_icon() ) {
		return;
	}

	wp_safe_redirect( get_theme_file_uri( 'assets/images/site-icon-48.png' ), 301 );
	exit;
}
add_action( 'do_faviconico', 'sfv_theme_favicon_ico' );
