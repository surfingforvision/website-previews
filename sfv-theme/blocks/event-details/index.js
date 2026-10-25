/**
 * Editor side of sfv/event-details. Plain script using WordPress globals, so
 * the theme needs no build step. Shows the details as they will appear, with a
 * note on where to change them.
 */
( function ( blocks, blockEditor, components, element, i18n, ServerSideRender ) {
	var el = element.createElement;
	var __ = i18n.__;

	blocks.registerBlockType( 'sfv/event-details', {
		edit: function () {
			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					components.Notice,
					{ status: 'info', isDismissible: false },
					__( 'Change the date, times and place in ', 'sfv-theme' ),
					el( 'a', { href: 'edit.php?post_type=page&page=sfv-event-settings' }, __( 'Pages → Event settings', 'sfv-theme' ) ),
					'.'
				),
				el( components.Disabled, null, el( ServerSideRender, { block: 'sfv/event-details' } ) )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
