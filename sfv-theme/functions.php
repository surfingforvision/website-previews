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
require_once get_theme_file_path( 'inc/editor-access.php' );

/**
 * Loads the theme stylesheet on the front end and in the editor.
 */
function sfv_theme_setup() {
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'sfv_theme_setup' );

function sfv_theme_styles() {
	wp_enqueue_style(
		'sfv-theme',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'sfv_theme_styles' );

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
 * Schema.org NGO data for the organisation. Added (with the legal name,
 * address and EIN in the footer) to pass Google for Nonprofits verification,
 * so it must stay accurate.
 */
function sfv_theme_ngo_json_ld() {
	if ( ! is_front_page() ) {
		return;
	}

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'NGO',
		'@id'             => home_url( '/#organization' ),
		'name'            => 'Surfing for Vision',
		'legalName'       => 'Surfing for Vision a NJ Nonprofit Corporation',
		'url'             => home_url( '/' ),
		'logo'            => get_theme_file_uri( 'assets/images/logo-stacked.png' ),
		'email'           => 'info@surfingforvision.com',
		'taxID'           => '39-3423583',
		'nonprofitStatus' => 'Nonprofit501c3',
		// Region only: the registered street address is a volunteer's home.
		'address'         => array(
			'@type'          => 'PostalAddress',
			'addressRegion'  => 'NJ',
			'addressCountry' => 'US',
		),
		'sameAs'          => array( 'https://www.instagram.com/surfingforvision/' ),
	);

	echo '<script type="application/ld+json">'
		. wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. '</script>' . "\n";
}
add_action( 'wp_head', 'sfv_theme_ngo_json_ld' );

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
