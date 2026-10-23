<?php
/**
 * Front-end markup for sfv/homepage-season: the current season's synced
 * pattern, rendered as if it were placed here directly.
 *
 * @package SFV_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sfv_block_id = sfv_season_block_id( sfv_current_season() );

if ( ! $sfv_block_id ) {
	return;
}

// core/block guards against a synced pattern rendering itself.
echo render_block(
	array(
		'blockName'    => 'core/block',
		'attrs'        => array( 'ref' => $sfv_block_id ),
		'innerBlocks'  => array(),
		'innerHTML'    => '',
		'innerContent' => array(),
	)
);
