/* Editor side of the Nostek content elements (includes/elements.php). No build step: plain wp globals. */
( function ( wp ) {
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var be = wp.blockEditor;
	var c = wp.components;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var createBlock = wp.blocks.createBlock;
	var SSR = wp.serverSideRender;

	var COLORS = [
		{ label: 'Teal', value: 'teal' },
		{ label: 'Merah', value: 'red' },
		{ label: 'Mustard', value: 'mustard' },
		{ label: 'Teal gelap', value: 'teal-dark' },
		{ label: 'Biru', value: 'blue' },
		{ label: 'Hijau', value: 'green' },
		{ label: 'Tinta', value: 'ink' },
	];

	function useChildCount( clientId ) {
		return useSelect( function ( select ) {
			return select( 'core/block-editor' ).getBlockCount( clientId );
		}, [ clientId ] );
	}

	/* ---------- Slider ---------- */
	function newSlide( n ) {
		return createBlock(
			'core/cover',
			{ overlayColor: 'teal-dark', dimRatio: 100, minHeight: 460, contentPosition: 'center left', isDark: true },
			[
				createBlock( 'core/paragraph', { content: 'Slide ' + n, className: 'is-style-kicker', textColor: 'cream' } ),
				createBlock( 'core/heading', { content: 'Judul slide', className: 'is-style-title-3d', textColor: 'cream' } ),
				createBlock( 'core/paragraph', { content: 'Teks singkat. Klik ikon gambar di toolbar Cover untuk memasang foto latar.', textColor: 'cream' } ),
			]
		);
	}

	wp.blocks.registerBlockType( 'nostek/slider', {
		apiVersion: 3,
		icon: 'slides',
		keywords: [ 'slider', 'carousel', 'hero', 'banner', 'slide' ],
		edit: function ( props ) {
			var a = props.attributes;
			var count = useChildCount( props.clientId );
			var insertBlock = useDispatch( 'core/block-editor' ).insertBlock;
			function add() {
				insertBlock( newSlide( count + 1 ), count, props.clientId );
			}
			var blockProps = be.useBlockProps( { className: 'nk-ed-slider' } );
			var inner = be.useInnerBlocksProps(
				{ className: 'nk-ed-slider__track' },
				{
					allowedBlocks: [ 'core/cover' ],
					template: [ [ 'core/cover', { overlayColor: 'teal-dark', dimRatio: 100, minHeight: 460, contentPosition: 'center left', isDark: true }, [ [ 'core/heading', { content: 'Slide pertama', className: 'is-style-title-3d', textColor: 'cream' } ] ] ], [ 'core/cover', { overlayColor: 'red', dimRatio: 100, minHeight: 460, contentPosition: 'center left', isDark: true }, [ [ 'core/heading', { content: 'Slide kedua', className: 'is-style-title-3d', textColor: 'cream' } ] ] ] ],
					orientation: 'horizontal',
					renderAppender: false,
				}
			);
			return el(
				Fragment,
				null,
				el( be.BlockControls, { group: 'block' }, el( c.ToolbarButton, { icon: 'plus', label: 'Tambah slide', showTooltip: true, onClick: add } ) ),
				el(
					be.InspectorControls,
					null,
					el(
						c.PanelBody,
						{ title: 'Slider' },
						el( c.ToggleControl, { label: 'Ganti slide otomatis', checked: !! a.autoplay, onChange: function ( v ) { props.setAttributes( { autoplay: v } ); } } ),
						a.autoplay ? el( c.RangeControl, { label: 'Jeda antar slide (detik)', min: 2, max: 30, step: 0.5, value: a.interval, onChange: function ( v ) { props.setAttributes( { interval: v } ); } } ) : null,
						el( c.TextControl, { label: 'Nama slider (untuk pembaca layar)', value: a.label, onChange: function ( v ) { props.setAttributes( { label: v } ); } } ),
						el( 'p', { className: 'nk-ed-help' }, 'Setiap slide adalah blok Cover: ganti gambar lewat toolbar Cover, ketik langsung teksnya. Geser urutan dengan panah kiri/kanan di toolbar slide, hapus lewat menu ⋮ slide.' )
					)
				),
				el(
					'div',
					blockProps,
					el(
						'div',
						{ className: 'nk-ed-slider__head' },
						el( 'strong', null, 'Nostek Slider' ),
						el( 'span', null, count + ' slide · ' + ( a.autoplay ? 'otomatis tiap ' + a.interval + ' detik' : 'manual' ) ),
						el( c.Button, { variant: 'primary', icon: 'plus', onClick: add }, 'Tambah slide' )
					),
					el( 'div', inner )
				)
			);
		},
		save: function () {
			return el( be.InnerBlocks.Content );
		},
	} );

	/* ---------- Tabs ---------- */
	wp.blocks.registerBlockType( 'nostek/tabs', {
		apiVersion: 3,
		icon: 'index-card',
		keywords: [ 'tabs', 'tab', 'map' ],
		edit: function ( props ) {
			var count = useChildCount( props.clientId );
			var insertBlock = useDispatch( 'core/block-editor' ).insertBlock;
			function add() {
				insertBlock( createBlock( 'nostek/tab', { title: 'Tab ' + ( count + 1 ) }, [ createBlock( 'core/paragraph', { content: 'Isi tab.' } ) ] ), count, props.clientId );
			}
			var inner = be.useInnerBlocksProps(
				{ className: 'nk-ed-tabs__list' },
				{
					allowedBlocks: [ 'nostek/tab' ],
					template: [ [ 'nostek/tab', { title: 'Ringkasan' }, [ [ 'core/paragraph', { content: 'Isi tab pertama.' } ] ] ], [ 'nostek/tab', { title: 'Detail' }, [ [ 'core/paragraph', { content: 'Isi tab kedua.' } ] ] ] ],
					renderAppender: false,
				}
			);
			return el(
				Fragment,
				null,
				el( be.BlockControls, { group: 'block' }, el( c.ToolbarButton, { icon: 'plus', label: 'Tambah tab', showTooltip: true, onClick: add } ) ),
				el(
					'div',
					be.useBlockProps( { className: 'nk-ed-tabs' } ),
					el( 'div', { className: 'nk-ed-slider__head' }, el( 'strong', null, 'Nostek Tabs' ), el( 'span', null, count + ' tab · pengunjung melihat satu tab sekaligus' ), el( c.Button, { variant: 'secondary', icon: 'plus', onClick: add }, 'Tambah tab' ) ),
					el( 'div', inner )
				)
			);
		},
		save: function () {
			return el( be.InnerBlocks.Content );
		},
	} );

	wp.blocks.registerBlockType( 'nostek/tab', {
		apiVersion: 3,
		icon: 'excerpt-view',
		edit: function ( props ) {
			var inner = be.useInnerBlocksProps( { className: 'nk-ed-tab__body' }, { template: [ [ 'core/paragraph', { placeholder: 'Isi tab…' } ] ], templateLock: false } );
			return el(
				'div',
				be.useBlockProps( { className: 'nk-ed-tab' } ),
				el( be.RichText, { tagName: 'div', className: 'nk-ed-tab__title', value: props.attributes.title, allowedFormats: [], placeholder: 'Judul tab', onChange: function ( v ) { props.setAttributes( { title: v } ); } } ),
				el( 'div', inner )
			);
		},
		save: function () {
			return el( be.InnerBlocks.Content );
		},
	} );

	/* ---------- Progress bar ---------- */
	wp.blocks.registerBlockType( 'nostek/progress', {
		apiVersion: 3,
		icon: 'chart-bar',
		keywords: [ 'progress', 'bar', 'persen', 'skill' ],
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			return el(
				'div',
				be.useBlockProps(),
				el( SSR, { block: 'nostek/progress', attributes: a } ),
				props.isSelected
					? el(
						'div',
						{ className: 'nk-ed-row' },
						el( c.TextControl, { label: 'Label', value: a.label, onChange: function ( v ) { set( { label: v } ); } } ),
						el( c.RangeControl, { label: 'Persen', min: 0, max: 100, value: a.value, onChange: function ( v ) { set( { value: v } ); } } ),
						el( c.SelectControl, { label: 'Warna', value: a.color, options: COLORS, onChange: function ( v ) { set( { color: v } ); } } )
					)
					: null
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------- Donut chart ---------- */
	wp.blocks.registerBlockType( 'nostek/donut', {
		apiVersion: 3,
		icon: 'chart-pie',
		keywords: [ 'pie', 'donut', 'chart', 'grafik' ],
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var items = a.items || [];
			function put( i, key, v ) {
				var next = items.map( function ( it ) { return Object.assign( {}, it ); } );
				next[ i ][ key ] = 'value' === key ? parseFloat( v ) || 0 : v;
				set( { items: next } );
			}
			return el(
				'div',
				be.useBlockProps(),
				el( SSR, { block: 'nostek/donut', attributes: a } ),
				props.isSelected
					? el(
						'div',
						{ className: 'nk-ed-rows' },
						el( c.TextControl, { label: 'Judul di tengah chart', value: a.title, onChange: function ( v ) { set( { title: v } ); } } ),
						items.map( function ( it, i ) {
							return el(
								'div',
								{ className: 'nk-ed-row', key: i },
								el( c.TextControl, { label: 'Label ' + ( i + 1 ), value: it.label, onChange: function ( v ) { put( i, 'label', v ); } } ),
								el( c.TextControl, { label: 'Angka', type: 'number', value: String( it.value ), onChange: function ( v ) { put( i, 'value', v ); } } ),
								el( c.Button, { isDestructive: true, variant: 'tertiary', icon: 'trash', label: 'Hapus baris', onClick: function () { set( { items: items.filter( function ( x, j ) { return j !== i; } ) } ); } } )
							);
						} ),
						el( c.Button, { variant: 'secondary', icon: 'plus', onClick: function () { set( { items: items.concat( [ { label: 'Baru', value: 10 } ] ) } ); } }, 'Tambah baris' ),
						el( c.TextControl, { label: 'Catatan di bawah chart (opsional)', value: a.note, onChange: function ( v ) { set( { note: v } ); } } )
					)
					: null
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
