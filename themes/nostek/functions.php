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
	add_editor_style( 'assets/css/retro.css' );
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
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style(
			'nostek-shop',
			get_template_directory_uri() . '/assets/css/shop.css',
			array( 'nostek-motion' ),
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
			'ruled' => __( 'Ruled', 'nostek' ),
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
