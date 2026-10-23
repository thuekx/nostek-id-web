<?php
/**
 * Nostek theme setup.
 *
 * Design tokens live in theme.json. This file only loads the retro layer
 * (textures, 3D title, ribbon, frames) and registers the block styles that
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
}

add_action( 'wp_enqueue_scripts', 'nostek_enqueue_styles' );
function nostek_enqueue_styles() {
	wp_enqueue_style(
		'nostek-retro',
		get_template_directory_uri() . '/assets/css/retro.css',
		array(),
		NOSTEK_THEME_VERSION
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
