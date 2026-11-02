<?php
/**
 * GET /wp-json/nostek/v1/menus: the site's menus, read only, for agents.
 *
 * The block theme keeps its menus as Navigation blocks inside the header and
 * footer template parts (or in a wp_navigation post when edited in the Site
 * Editor), so core's /wp/v2/menus does not see them, and it needs
 * edit_theme_options anyway. This route reads the template parts as the site
 * renders them and returns every Navigation block with its items.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'nostek_core_register_menus_route' );
function nostek_core_register_menus_route() {
	register_rest_route(
		'nostek/v1',
		'/menus',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'nostek_core_rest_menus',
			'permission_callback' => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}

/**
 * @return WP_REST_Response
 */
function nostek_core_rest_menus() {
	$menus = array();
	foreach ( array( 'header', 'footer' ) as $part ) {
		$template = get_block_template( get_stylesheet() . '//' . $part, 'wp_template_part' );
		if ( ! $template || '' === $template->content ) {
			continue;
		}
		$index = 0;
		foreach ( nostek_core_find_navigation( parse_blocks( $template->content ), 0 ) as $nav ) {
			++$index;
			$next    = 0;
			$menus[] = array(
				'location' => $part . '-' . $index,
				'part'     => $part,
				'class'    => (string) ( $nav['attrs']['className'] ?? '' ),
				'source'   => $template->source, // "theme" = file in git, "custom" = edited in the Site Editor.
				'items'    => nostek_core_menu_items( nostek_core_navigation_children( $nav ), 0, $next ),
			);
		}
	}
	return rest_ensure_response( $menus );
}

/**
 * All core/navigation blocks, also inside groups and theme patterns.
 *
 * @param array[] $blocks Parsed blocks.
 * @param int     $depth  Pattern nesting guard.
 * @return array[]
 */
function nostek_core_find_navigation( $blocks, $depth ) {
	$found = array();
	foreach ( $blocks as $block ) {
		if ( 'core/navigation' === $block['blockName'] ) {
			$found[] = $block;
			continue;
		}
		if ( 'core/pattern' === $block['blockName'] && $depth < 3 ) {
			$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( $block['attrs']['slug'] ?? '' );
			if ( $pattern ) {
				$found = array_merge( $found, nostek_core_find_navigation( parse_blocks( $pattern['content'] ), $depth + 1 ) );
			}
			continue;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$found = array_merge( $found, nostek_core_find_navigation( $block['innerBlocks'], $depth ) );
		}
	}
	return $found;
}

/**
 * Inner items of a Navigation block, or of the wp_navigation post it points to.
 *
 * @param array $nav Parsed core/navigation block.
 * @return array[]
 */
function nostek_core_navigation_children( $nav ) {
	if ( ! empty( $nav['attrs']['ref'] ) ) {
		$post = get_post( (int) $nav['attrs']['ref'] );
		if ( $post && 'wp_navigation' === $post->post_type && 'publish' === $post->post_status ) {
			return parse_blocks( $post->post_content );
		}
		return array();
	}
	return $nav['innerBlocks'];
}

/**
 * @param array[] $blocks Navigation inner blocks.
 * @param int     $parent Item id of the parent (0 = top level).
 * @param int     $next   Last item id used in this menu.
 * @return array[] Flat list: id, parent, title, url, object (type + slug when the link points to site content).
 */
function nostek_core_menu_items( $blocks, $parent, &$next ) {
	$items = array();
	foreach ( $blocks as $block ) {
		if ( ! in_array( $block['blockName'], array( 'core/navigation-link', 'core/navigation-submenu' ), true ) ) {
			continue;
		}
		$attrs   = $block['attrs'];
		$id      = ++$next;
		$url     = (string) ( $attrs['url'] ?? '' );
		$items[] = array(
			'id'          => $id,
			'parent'      => $parent,
			'title'       => wp_strip_all_tags( (string) ( $attrs['label'] ?? '' ) ),
			'url'         => $url,
			'description' => (string) ( $attrs['description'] ?? '' ),
			'object'      => nostek_core_menu_object( $url, $attrs ),
		);
		if ( ! empty( $block['innerBlocks'] ) ) {
			$items = array_merge( $items, nostek_core_menu_items( $block['innerBlocks'], $id, $next ) );
		}
	}
	return $items;
}

/**
 * What a menu link points to: {type, slug} of a page, post or term, or null
 * for an external or unknown URL.
 *
 * @param string $url   Link URL (often relative, e.g. /category/news/).
 * @param array  $attrs Block attributes (kind/type/id when picked in the editor).
 * @return array{type: string, slug: string}|null
 */
function nostek_core_menu_object( $url, $attrs ) {
	if ( ! empty( $attrs['id'] ) && ! empty( $attrs['kind'] ) ) {
		if ( 'post-type' === $attrs['kind'] ) {
			$post = get_post( (int) $attrs['id'] );
			return $post ? array( 'type' => $post->post_type, 'slug' => $post->post_name ) : null;
		}
		if ( 'taxonomy' === $attrs['kind'] ) {
			$term = get_term( (int) $attrs['id'] );
			return $term instanceof WP_Term ? array( 'type' => $term->taxonomy, 'slug' => $term->slug ) : null;
		}
	}
	if ( '' === $url ) {
		return null;
	}
	$abs  = 0 === strpos( $url, '/' ) ? home_url( $url ) : $url;
	$host = wp_parse_url( $abs, PHP_URL_HOST );
	if ( $host && wp_parse_url( home_url(), PHP_URL_HOST ) !== $host ) {
		return null;
	}

	$post_id = url_to_postid( $abs );
	if ( $post_id ) {
		$post = get_post( $post_id );
		return array( 'type' => $post->post_type, 'slug' => $post->post_name );
	}

	$path     = trim( (string) wp_parse_url( $abs, PHP_URL_PATH ), '/' );
	$segments = '' === $path ? array() : explode( '/', $path );
	if ( count( $segments ) >= 2 ) {
		$bases = array(
			get_option( 'category_base' ) ? get_option( 'category_base' ) : 'category' => 'category',
			get_option( 'tag_base' ) ? get_option( 'tag_base' ) : 'tag'                 => 'post_tag',
			'topics'                                                                     => 'topic',
			'product-category'                                                           => 'product_cat',
		);
		if ( isset( $bases[ $segments[0] ] ) && taxonomy_exists( $bases[ $segments[0] ] ) ) {
			$term = get_term_by( 'slug', end( $segments ), $bases[ $segments[0] ] );
			if ( $term ) {
				return array( 'type' => $term->taxonomy, 'slug' => $term->slug );
			}
		}
	}
	if ( 1 === count( $segments ) && (int) get_option( 'page_for_posts' ) ) {
		$journal = get_post( (int) get_option( 'page_for_posts' ) );
		if ( $journal && $journal->post_name === $segments[0] ) {
			return array( 'type' => 'page', 'slug' => $journal->post_name );
		}
	}
	return null;
}
