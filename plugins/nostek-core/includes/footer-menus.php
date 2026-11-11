<?php
/**
 * Footer menus the owner can edit in Appearance > Editor > Navigation.
 *
 * A Navigation block in a theme file cannot point to a menu by name, only by
 * post id, and ids differ between staging and production. So the footer's
 * Navigation blocks carry a class "nk-menu-<slug>" and this filter points
 * them, at render time (pre_render_block), to the published wp_navigation menu with that slug
 * (created by tools/setup_site.py from site/pages.json "menus"). Without such
 * a menu the links written in the theme file are shown, so the footer never
 * comes out empty. Editing the menu changes only its items; the footer layout
 * and headings stay in the theme.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Id of the published wp_navigation menu named by a "nk-menu-<slug>" class.
 *
 * @param array $attrs core/navigation block attributes.
 * @return int 0 when the block has no such class or the menu does not exist.
 */
function nostek_core_menu_ref( $attrs ) {
	static $cache = array();
	if ( ! empty( $attrs['ref'] ) ) {
		return 0;
	}
	if ( ! preg_match( '/(?:^|\s)nk-menu-([a-z0-9-]+)(?:\s|$)/', (string) ( $attrs['className'] ?? '' ), $m ) ) {
		return 0;
	}
	if ( ! isset( $cache[ $m[1] ] ) ) {
		$ids             = get_posts(
			array(
				'post_type'        => 'wp_navigation',
				'name'             => $m[1],
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);
		$cache[ $m[1] ] = $ids ? (int) $ids[0] : 0;
	}
	return $cache[ $m[1] ];
}

add_filter( 'pre_render_block', 'nostek_core_footer_menu_render', 10, 3 );
/**
 * Renders a footer Navigation block from its wp_navigation menu.
 *
 * pre_render_block, not render_block_data: for a block nested in a template
 * part, core builds the block's attributes before render_block_data runs, so
 * a ref added there is ignored.
 *
 * @param string|null   $pre    Content from an earlier filter, or null.
 * @param array         $parsed Parsed block.
 * @param WP_Block|null $parent Parent block.
 * @return string|null
 */
function nostek_core_footer_menu_render( $pre, $parsed, $parent = null ) {
	if ( null !== $pre || 'core/navigation' !== ( $parsed['blockName'] ?? '' ) ) {
		return $pre;
	}
	$ref = nostek_core_menu_ref( $parsed['attrs'] ?? array() );
	if ( ! $ref ) {
		return $pre;
	}
	$parsed['attrs']['ref'] = $ref;
	$parsed['innerBlocks']  = array();
	$parsed['innerHTML']    = '';
	$parsed['innerContent'] = array();
	$context                = $parent instanceof WP_Block ? $parent->context : array();
	return ( new WP_Block( $parsed, $context ) )->render();
}
