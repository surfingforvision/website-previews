<?php
/**
 * Registers the sfv/registration-form block.
 *
 * The block holds only what must not drift: the release agreement, the fields
 * and the bot checks. Everything around it on the Register page (event date,
 * times, intro copy) is ordinary page content that editors change.
 *
 * @package SFV_Registration
 */

namespace SFV\Registration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function register_form_block(): void {
	register_block_type( dirname( FILE ) . '/blocks/registration-form' );
}
add_action( 'init', __NAMESPACE__ . '\register_form_block' );
