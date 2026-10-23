/**
 * Editor side of sfv/registration-form. Plain script using WordPress globals,
 * so the plugin needs no build step.
 *
 * In the editor the block shows a short summary instead of the live form; the
 * thank-you heading and message are editable in the block sidebar.
 */
( function ( blocks, blockEditor, components, element, i18n ) {
	var el = element.createElement;
	var __ = i18n.__;

	blocks.registerBlockType( 'sfv/registration-form', {
		edit: function ( props ) {
			var attrs = props.attributes;

			return el(
				'div',
				blockEditor.useBlockProps( { className: 'sfv-registration-placeholder' } ),
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'After someone registers', 'sfv-registration' ) },
						el( components.TextControl, {
							label: __( 'Thank-you heading', 'sfv-registration' ),
							value: attrs.successHeading,
							onChange: function ( value ) {
								props.setAttributes( { successHeading: value } );
							},
							__nextHasNoMarginBottom: true,
						} ),
						el( components.TextareaControl, {
							label: __( 'Thank-you message', 'sfv-registration' ),
							value: attrs.successMessage,
							onChange: function ( value ) {
								props.setAttributes( { successMessage: value } );
							},
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el( 'strong', null, __( 'Registration form', 'sfv-registration' ) ),
				el(
					'p',
					null,
					__( 'Visitors see the release agreement and the sign-up form here. The form and release wording are managed in code; the thank-you message is editable in the sidebar.', 'sfv-registration' )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n );
