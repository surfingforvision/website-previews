/**
 * Editor side of sfv/homepage-season. Plain script using WordPress globals, so
 * the theme needs no build step.
 *
 * Shows the current season as it will appear, with a note on where to switch
 * it and where to edit its words. The content itself is a synced pattern.
 */
( function ( blocks, blockEditor, components, element, i18n, ServerSideRender ) {
	var el = element.createElement;
	var __ = i18n.__;
	var seasonsUrl = 'edit.php?post_type=page&page=sfv-homepage-season';

	blocks.registerBlockType( 'sfv/homepage-season', {
		edit: function ( props ) {
			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					components.Notice,
					{ status: 'info', isDismissible: false },
					__( 'This section shows the current homepage season. To switch season or edit its words and photos, go to ', 'sfv-theme' ),
					el( 'a', { href: seasonsUrl }, __( 'Pages → Homepage season', 'sfv-theme' ) ),
					'.'
				),
				el(
					components.Disabled,
					null,
					el( ServerSideRender, { block: 'sfv/homepage-season', attributes: props.attributes } )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
