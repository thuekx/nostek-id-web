/* Editor previews for Nostek Core dynamic blocks. No build step: plain wp globals. */
( function ( wp ) {
	var el = wp.element.createElement;
	var ServerSideRender = wp.serverSideRender;
	var useBlockProps = wp.blockEditor.useBlockProps;

	[ 'nostek/breadcrumb', 'nostek/topics-list' ].forEach( function ( name ) {
		wp.blocks.registerBlockType( name, {
			apiVersion: 3,
			edit: function () {
				return el( 'div', useBlockProps(), el( ServerSideRender, { block: name } ) );
			},
			save: function () {
				return null;
			},
		} );
	} );
} )( window.wp );
