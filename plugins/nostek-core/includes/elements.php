<?php
/**
 * Content element blocks: Slider, Tabs, Progress bar, Donut chart.
 *
 * Editors and agents insert them like any block (inserter, patterns in the
 * "Nostek – Elemen" category, or block markup over the REST API). All are
 * rendered on the server from their attributes and inner blocks, so a theme
 * change never leaves broken saved HTML behind. The look lives in the theme
 * (assets/css/elements.css); this file only builds the markup.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'nostek_core_register_elements' );
function nostek_core_register_elements() {
	wp_register_script(
		'nostek-core-elements-editor',
		plugins_url( 'assets/elements-editor.js', NOSTEK_CORE_FILE ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'wp-server-side-render' ),
		NOSTEK_CORE_VERSION,
		true
	);
	wp_register_style(
		'nostek-core-elements-editor',
		plugins_url( 'assets/elements-editor.css', NOSTEK_CORE_FILE ),
		array(),
		NOSTEK_CORE_VERSION
	);
	wp_register_script(
		'nostek-core-elements',
		plugins_url( 'assets/elements.js', NOSTEK_CORE_FILE ),
		array(),
		NOSTEK_CORE_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	$common = array(
		'api_version'   => 3,
		'category'      => 'nostek',
		'editor_script' => 'nostek-core-elements-editor',
		'editor_style'  => 'nostek-core-elements-editor',
	);

	register_block_type(
		'nostek/slider',
		$common + array(
			'title'           => __( 'Nostek Slider', 'nostek-core' ),
			'description'     => __( 'Slides that change by themselves. Each slide is a Cover block: image, text and button.', 'nostek-core' ),
			'attributes'      => array(
				'autoplay' => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'interval' => array(
					'type'    => 'number',
					'default' => 6.5,
				),
				'label'    => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'supports'        => array(
				'html'   => false,
				'align'   => array( 'wide', 'full' ),
				'anchor'  => true,
				'spacing' => array(
					'margin'  => true,
					'padding' => true,
				),
			),
			'render_callback' => 'nostek_core_render_slider',
		)
	);

	register_block_type(
		'nostek/tabs',
		$common + array(
			'title'           => __( 'Nostek Tabs', 'nostek-core' ),
			'description'     => __( 'Content in tabs. Each tab has a title and any blocks inside.', 'nostek-core' ),
			'supports'        => array(
				'html'  => false,
				'align' => array( 'wide' ),
			),
			'view_script'     => 'nostek-core-elements',
			'render_callback' => 'nostek_core_render_tabs',
		)
	);

	register_block_type(
		'nostek/tab',
		$common + array(
			'title'           => __( 'Tab', 'nostek-core' ),
			'parent'          => array( 'nostek/tabs' ),
			'attributes'      => array(
				'title' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'supports'        => array(
				'html'     => false,
				'reusable' => false,
			),
			'render_callback' => 'nostek_core_render_tab',
		)
	);

	register_block_type(
		'nostek/progress',
		$common + array(
			'title'           => __( 'Nostek Progress Bar', 'nostek-core' ),
			'description'     => __( 'A striped bar with a label and a percentage.', 'nostek-core' ),
			'attributes'      => array(
				'label' => array(
					'type'    => 'string',
					'default' => 'Progress',
				),
				'value' => array(
					'type'    => 'number',
					'default' => 75,
				),
				'color' => array(
					'type'    => 'string',
					'default' => 'teal',
				),
			),
			'supports'        => array( 'html' => false ),
			'render_callback' => 'nostek_core_render_progress',
		)
	);

	register_block_type(
		'nostek/donut',
		$common + array(
			'title'           => __( 'Nostek Donut Chart', 'nostek-core' ),
			'description'     => __( 'A pie/donut chart from a short list of labels and numbers.', 'nostek-core' ),
			'attributes'      => array(
				'title' => array(
					'type'    => 'string',
					'default' => '',
				),
				'items' => array(
					'type'    => 'array',
					'default' => array(
						array(
							'label' => 'A',
							'value' => 60,
						),
						array(
							'label' => 'B',
							'value' => 40,
						),
					),
					'items'   => array(
						'type'       => 'object',
						'properties' => array(
							'label' => array( 'type' => 'string' ),
							'value' => array( 'type' => 'number' ),
						),
					),
				),
				'note'  => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'supports'        => array(
				'html'  => false,
				'align' => array( 'wide' ),
			),
			'render_callback' => 'nostek_core_render_donut',
		)
	);
}

add_filter( 'block_categories_all', 'nostek_core_block_category' );
/**
 * @param array[] $categories Block categories.
 * @return array[]
 */
function nostek_core_block_category( $categories ) {
	foreach ( $categories as $cat ) {
		if ( 'nostek' === $cat['slug'] ) {
			return $categories;
		}
	}
	array_unshift(
		$categories,
		array(
			'slug'  => 'nostek',
			'title' => __( 'Nostek', 'nostek-core' ),
		)
	);
	return $categories;
}

/** Palette names a block may use, in chart order. */
function nostek_core_element_colors() {
	return array( 'teal', 'red', 'mustard', 'teal-dark', 'blue', 'green', 'red-dark', 'ink' );
}

/**
 * @param array    $attrs   Block attributes.
 * @param string   $content Rendered slides (Cover blocks).
 * @return string
 */
function nostek_core_render_slider( $attrs, $content ) {
	$interval = max( 2, min( 30, (float) ( $attrs['interval'] ?? 6.5 ) ) );
	$wrapper  = get_block_wrapper_attributes(
		array(
			'class'         => 'is-style-slider nk-hero',
			'data-interval' => (string) (int) round( $interval * 1000 ),
			'data-autoplay' => empty( $attrs['autoplay'] ) ? '0' : '1',
			'aria-label'    => '' !== (string) ( $attrs['label'] ?? '' ) ? (string) $attrs['label'] : null,
		)
	);
	return '<div ' . $wrapper . '>' . $content . '</div>';
}

/**
 * Tab buttons from the inner Tab blocks, panels from their content.
 *
 * @param array    $attrs   Block attributes.
 * @param string   $content Rendered inner blocks (unused: rebuilt per tab).
 * @param WP_Block $block   Block instance.
 * @return string
 */
function nostek_core_render_tabs( $attrs, $content, $block ) {
	static $n = 0;
	++$n;
	$buttons = '';
	$panels  = '';
	$i       = 0;
	foreach ( $block->inner_blocks as $tab ) {
		if ( 'nostek/tab' !== $tab->name ) {
			continue;
		}
		$id    = 'nk-tabs-' . $n . '-' . $i;
		$title = trim( (string) ( $tab->attributes['title'] ?? '' ) );
		if ( '' === $title ) {
			/* translators: %d: tab number */
			$title = sprintf( __( 'Tab %d', 'nostek-core' ), $i + 1 );
		}
		$on       = 0 === $i;
		$buttons .= sprintf(
			'<button type="button" role="tab" class="nk-tabs__tab%1$s" id="%2$s-t" aria-controls="%2$s" aria-selected="%3$s"%4$s>%5$s</button>',
			$on ? ' is-on' : '',
			esc_attr( $id ),
			$on ? 'true' : 'false',
			$on ? '' : ' tabindex="-1"',
			esc_html( $title )
		);
		$panels  .= sprintf(
			'<div role="tabpanel" class="nk-tabs__panel" id="%1$s" aria-labelledby="%1$s-t"%2$s>%3$s</div>',
			esc_attr( $id ),
			$on ? '' : ' hidden',
			$tab->render()
		);
		++$i;
	}
	if ( ! $i ) {
		return '';
	}
	return '<div ' . get_block_wrapper_attributes( array( 'class' => 'nk-tabs' ) ) . '><div class="nk-tabs__bar" role="tablist">' . $buttons . '</div>' . $panels . '</div>';
}

/**
 * @param array  $attrs   Block attributes.
 * @param string $content Inner blocks.
 * @return string
 */
function nostek_core_render_tab( $attrs, $content ) {
	return $content;
}

/**
 * @param array $attrs Block attributes.
 * @return string
 */
function nostek_core_render_progress( $attrs ) {
	$value = max( 0, min( 100, (int) round( (float) ( $attrs['value'] ?? 0 ) ) ) );
	$color = in_array( $attrs['color'] ?? '', nostek_core_element_colors(), true ) ? $attrs['color'] : 'teal';
	$label = (string) ( $attrs['label'] ?? '' );
	return sprintf(
		'<div %1$s><div class="nk-pbar__head"><span>%2$s</span><span>%3$d%%</span></div><div class="nk-pbar__track" role="progressbar" aria-label="%2$s" aria-valuemin="0" aria-valuemax="100" aria-valuenow="%3$d"><div class="nk-pbar__fill" style="width:%3$d%%;--nk-c:var(--wp--preset--color--%4$s)"></div></div></div>',
		get_block_wrapper_attributes( array( 'class' => 'nk-pbar' ) ),
		esc_html( $label ),
		$value,
		esc_attr( $color )
	);
}

/**
 * Conic-gradient donut with a legend; shares are computed from the values.
 *
 * @param array $attrs Block attributes.
 * @return string
 */
function nostek_core_render_donut( $attrs ) {
	$items = array();
	foreach ( (array) ( $attrs['items'] ?? array() ) as $item ) {
		$value = (float) ( $item['value'] ?? 0 );
		if ( $value > 0 ) {
			$items[] = array( (string) ( $item['label'] ?? '' ), $value );
		}
	}
	if ( ! $items ) {
		return '';
	}
	$total  = array_sum( array_column( $items, 1 ) );
	$colors = nostek_core_element_colors();
	$stops  = array();
	$legend = '';
	$at     = 0.0;
	foreach ( $items as $i => $item ) {
		$color   = 'var(--wp--preset--color--' . $colors[ $i % count( $colors ) ] . ')';
		$share   = $item[1] / $total * 100;
		$stops[] = sprintf( '%s %.2f%% %.2f%%', $color, $at, $at + $share );
		$at     += $share;
		$legend .= sprintf(
			'<li><i style="background:%1$s"></i><span>%2$s</span><b>%3$s%%</b></li>',
			esc_attr( $color ),
			esc_html( $item[0] ),
			esc_html( (string) round( $share ) )
		);
	}
	$title = (string) ( $attrs['title'] ?? '' );
	$note  = (string) ( $attrs['note'] ?? '' );
	return sprintf(
		'<figure %1$s><div class="nk-donut__ring" style="background:conic-gradient(%2$s)" role="img" aria-label="%3$s"><span>%4$s</span></div><ul class="nk-donut__legend">%5$s</ul>%6$s</figure>',
		get_block_wrapper_attributes( array( 'class' => 'nk-donut' ) ),
		esc_attr( implode( ', ', $stops ) ),
		esc_attr( $title ),
		esc_html( $title ),
		$legend,
		'' !== $note ? '<figcaption>' . esc_html( $note ) . '</figcaption>' : ''
	);
}
