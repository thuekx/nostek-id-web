<?php
/**
 * Reading-page section menu (WEB-40).
 *
 * nostek/page-nav lists the pages of the section the visitor is in: the
 * top-level page and its direct children (Legal > Terms, Privacy, ...;
 * Support > Payment, Shipping, ...). The current page is marked. Prints
 * nothing on a page with no parent and no children, so a lone page keeps
 * a clean rail. Same box style as the table of contents, so it folds on
 * phones like the TOC does.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Top-level page of the section and its published children.
 *
 * @param int $page_id Page being viewed.
 * @return array{0: WP_Post|null, 1: WP_Post[]}
 */
function nostek_core_page_section( $page_id ) {
	$ancestors = get_post_ancestors( $page_id );
	$root_id   = $ancestors ? (int) end( $ancestors ) : (int) $page_id;
	$root      = get_post( $root_id );
	if ( ! $root || 'page' !== $root->post_type ) {
		return array( null, array() );
	}
	$children = get_pages(
		array(
			'parent'      => $root_id,
			'sort_column' => 'menu_order,post_title',
			'post_status' => 'publish',
		)
	);
	return array( $root, is_array( $children ) ? $children : array() );
}

function nostek_core_render_page_nav() {
	if ( ! is_page() ) {
		return '';
	}
	$current         = (int) get_queried_object_id();
	list( $root, $kids ) = nostek_core_page_section( $current );
	if ( ! $root || ! $kids ) {
		return '';
	}
	$item = static function ( $page, $class ) use ( $current ) {
		$is_current = (int) $page->ID === $current;
		return sprintf(
			'<li class="nk-toc__item %s"><a href="%s"%s>%s</a></li>',
			esc_attr( $class ),
			esc_url( get_permalink( $page ) ),
			$is_current ? ' class="is-current" aria-current="page"' : '',
			esc_html( get_the_title( $page ) )
		);
	};
	$list = $item( $root, 'nk-pagenav__root' );
	foreach ( $kids as $kid ) {
		$list .= $item( $kid, 'nk-pagenav__item' );
	}
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'nk-toc nk-pagenav' ) );
	return sprintf(
		'<nav %s aria-label="%s"><details open><summary>%s</summary><ul>%s</ul></details></nav>',
		$wrapper,
		esc_attr__( 'Halaman di bagian ini', 'nostek-core' ),
		esc_html__( 'Di bagian ini', 'nostek-core' ),
		$list
	);
}

add_action( 'init', 'nostek_core_register_page_blocks' );
function nostek_core_register_page_blocks() {
	register_block_type(
		'nostek/page-nav',
		array(
			'api_version'     => 3,
			'title'           => __( 'Nostek Section Pages', 'nostek-core' ),
			'category'        => 'theme',
			'editor_script'   => 'nostek-core-blocks',
			'render_callback' => 'nostek_core_render_page_nav',
			'supports'        => array( 'html' => false ),
		)
	);
}
