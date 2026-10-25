<?php
// Hand-written (no build step): the WordPress scripts index.js expects.
return array(
	'dependencies' => array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
	'version'      => (string) filemtime( __DIR__ . '/index.js' ),
);
