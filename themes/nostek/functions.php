<?php
/**
 * Nostek theme setup.
 *
 * Design tokens live in theme.json. This file loads the retro layer
 * (textures, 3D title, ribbon, frames), the motion layer (sticky header,
 * mega menu, sliders, ticker, reveal) and registers the block styles that
 * editors pick from the Styles panel.
 *
 * @package Nostek
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NOSTEK_THEME_VERSION', wp_get_theme()->get( 'Version' ) );

add_action( 'after_setup_theme', 'nostek_setup' );
function nostek_setup() {
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/retro.css', 'assets/css/editorial.css' ) );
	load_theme_textdomain( 'nostek', get_template_directory() . '/languages' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}

add_action( 'wp_enqueue_scripts', 'nostek_enqueue_styles' );
function nostek_enqueue_styles() {
	wp_enqueue_style(
		'nostek-retro',
		get_template_directory_uri() . '/assets/css/retro.css',
		array(),
		NOSTEK_THEME_VERSION
	);
	wp_enqueue_style(
		'nostek-motion',
		get_template_directory_uri() . '/assets/css/motion.css',
		array( 'nostek-retro' ),
		NOSTEK_THEME_VERSION
	);
	wp_enqueue_style(
		'nostek-editorial',
		get_template_directory_uri() . '/assets/css/editorial.css',
		array( 'nostek-motion' ),
		NOSTEK_THEME_VERSION
	);
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style(
			'nostek-shop',
			get_template_directory_uri() . '/assets/css/shop.css',
			array( 'nostek-editorial' ),
			NOSTEK_THEME_VERSION
		);
	}
	wp_enqueue_script(
		'nostek-motion',
		get_template_directory_uri() . '/assets/js/motion.js',
		array(),
		NOSTEK_THEME_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}

add_action( 'init', 'nostek_register_block_styles' );
function nostek_register_block_styles() {
	$styles = array(
		'core/heading'   => array(
			'title-3d' => __( 'Retro 3D', 'nostek' ),
			'divider'  => __( 'Section divider', 'nostek' ),
		),
		'core/paragraph' => array(
			'ribbon' => __( 'Ribbon', 'nostek' ),
			'kicker' => __( 'Kicker', 'nostek' ),
		),
		'core/group'     => array(
			'frame'        => __( 'Double frame', 'nostek' ),
			'band'         => __( 'Spray band', 'nostek' ),
			'info'         => __( 'Info box', 'nostek' ),
			'info-success' => __( 'Success box', 'nostek' ),
			'info-error'   => __( 'Error box', 'nostek' ),
			'info-warning' => __( 'Warning box', 'nostek' ),
			'sunburst'     => __( 'Sunburst', 'nostek' ),
			'slider'       => __( 'Slider', 'nostek' ),
			'carousel'     => __( 'Carousel', 'nostek' ),
		),
		'core/button'    => array(
			'ruled'  => __( 'Ruled', 'nostek' ),
			'offset' => __( 'Offset', 'nostek' ),
		),
		'core/details'   => array(
			'accordion' => __( 'Accordion', 'nostek' ),
		),
		'core/image'     => array(
			'sepia' => __( 'Sepia', 'nostek' ),
		),
	);

	foreach ( $styles as $block => $variations ) {
		foreach ( $variations as $name => $label ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}

add_action( 'init', 'nostek_register_pattern_categories' );
function nostek_register_pattern_categories() {
	register_block_pattern_category( 'nostek', array( 'label' => __( 'Nostek', 'nostek' ) ) );
}

/*
 * "Baca Juga" carousel (pattern nostek/post-carousel, queryId 32): leave out
 * the article the visitor is reading.
 */
add_filter( 'query_loop_block_query_vars', 'nostek_carousel_exclude_current', 10, 2 );
function nostek_carousel_exclude_current( $query, $block ) {
	if ( 32 === (int) ( $block->context['queryId'] ?? 0 ) && is_singular() ) {
		$query['post__not_in'] = array_merge( (array) ( $query['post__not_in'] ?? array() ), array( get_queried_object_id() ) );
	}
	return $query;
}

/*
 * Dark mode: set <html data-mode> before first paint so the page never
 * flashes light. The header toggle stores the visitor's pick; without a
 * pick the system setting decides.
 */
add_action( 'wp_head', 'nostek_mode_script', 0 );
function nostek_mode_script() {
	echo "<script>(function(d){var m;try{m=localStorage.getItem('nostek-mode')}catch(e){}if(m!=='dark'&&m!=='light'){m=window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'}d.documentElement.setAttribute('data-mode',m)})(document);</script>\n";
}

/*
 * Brand icons from the theme until a Site Icon is set in Settings > General.
 */
add_action( 'wp_head', 'nostek_brand_icons', 2 );
function nostek_brand_icons() {
	if ( has_site_icon() ) {
		return;
	}
	$dir = get_template_directory_uri() . '/assets/img/brand/';
	printf( '<link rel="icon" href="%s" sizes="any">' . "\n", esc_url( $dir . 'favicon.ico' ) );
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( $dir . 'nostek-mark.svg' ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( $dir . 'apple-touch-icon.png' ) );
	echo '<meta name="theme-color" content="#2c4a42">' . "\n";
}

/*
 * Active nav item. Core only marks links to a post or page by id; the
 * header uses custom URLs, so compare the link path with the request path.
 * A submenu parent is active when one of its links is.
 */
add_filter( 'render_block_core/navigation-link', 'nostek_mark_current_link', 10, 2 );
function nostek_mark_current_link( $content, $block ) {
	$url = $block['attrs']['url'] ?? '';
	if ( '' === $url || false !== strpos( $content, 'current-menu-item' ) ) {
		return $content;
	}
	if ( nostek_url_path( $url ) !== nostek_url_path( $_SERVER['REQUEST_URI'] ?? '/' ) ) {
		return $content;
	}
	$tags = new WP_HTML_Tag_Processor( $content );
	if ( $tags->next_tag( 'li' ) ) {
		$tags->add_class( 'current-menu-item' );
		if ( $tags->next_tag( 'a' ) ) {
			$tags->set_attribute( 'aria-current', 'page' );
		}
	}
	return $tags->get_updated_html();
}

add_filter( 'render_block_core/navigation-submenu', 'nostek_mark_current_parent', 10, 2 );
function nostek_mark_current_parent( $content, $block ) {
	if ( false === strpos( $content, 'current-menu-item' ) ) {
		return $content;
	}
	$tags = new WP_HTML_Tag_Processor( $content );
	if ( $tags->next_tag( 'li' ) ) {
		$tags->add_class( 'current-menu-ancestor' );
	}
	return $tags->get_updated_html();
}

function nostek_url_path( $url ) {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	return '/' . trim( $path, '/' );
}
