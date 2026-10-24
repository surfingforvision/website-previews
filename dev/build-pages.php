<?php
/**
 * Rebuilds the site content from the theme's patterns ('update' mode of
 * sfv-theme/inc/content-builder.php): shared sections, Home, Register, FAQ,
 * Photos, the Pattern library page, alt text and the homepage setting.
 *
 * Local:   npx wp-env run cli wp eval-file wp-content/sfv-dev/build-pages.php
 * Preview: run by the PR preview blueprint (.github/playground/) in WordPress
 *          Playground, where production media doesn't exist, so photos fall
 *          back to the theme's sample images.
 *
 * On the live site, use Tools → Set up site content instead: it only adds what
 * is missing and never overwrites the team's edits.
 *
 * @package SFV_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'sfv_build_site_content' ) ) {
	throw new RuntimeException( 'sfv-theme must be the active theme.' );
}

foreach ( sfv_build_site_content( 'update' ) as $sfv_line ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( $sfv_line );
	} else {
		echo esc_html( $sfv_line ) . "\n";
	}
}
