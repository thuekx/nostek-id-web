<?php
/**
 * WEB-35 shop mode (owner 2026-11-12): the shop is not open yet. Until the
 * owner ticks "Shop is open" in Settings → Nostek, every shop page shows the
 * theme template "coming-soon" (noindex), nothing can be bought, the header
 * Market menu is one plain link, the mini cart and the footer links to order
 * help and marketplace policies are hidden, and shop URLs leave the sitemap.
 * Page content stays in WordPress untouched, so opening the shop is one tick.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Page paths (get_page_uri) that belong to the shop. A trailing "/" also covers child pages. */
const NOSTEK_CORE_SHOP_PAGES = array(
	'market',
	'sell',
	'sell/',
	'support/payment',
	'support/shipping',
	'support/warranty',
	'support/returns',
	'support/order-tracking',
	'legal/marketplace-policy',
	'legal/seller-agreement',
	'legal/authenticity-policy',
	'legal/prohibited-items',
	'legal/dispute-policy',
);

/** @return bool True while the shop is closed (the default). */
function nostek_core_shop_closed() {
	return '1' !== nostek_core_setting( 'shop_open' );
}

/**
 * @param string $path Page path without slashes at the ends, e.g. "sell/titip-jual".
 * @return bool
 */
function nostek_core_shop_path( $path ) {
	$path = trim( strtolower( (string) $path ), '/' );
	foreach ( NOSTEK_CORE_SHOP_PAGES as $item ) {
		if ( '/' === substr( $item, -1 ) ? 0 === strpos( $path, $item ) : $path === $item ) {
			return true;
		}
	}
	// Shop URLs written in menus and patterns: /shop/…, /cart/, /checkout/, /my-account/.
	return (bool) preg_match( '#^(shop|cart|checkout|my-account|product|product-category|product-tag)(/|$)#', $path );
}

/** @return bool True when the current front-end request is a shop page and the shop is closed. */
function nostek_core_shop_request() {
	static $hit = null;
	if ( null !== $hit ) {
		return $hit;
	}
	if ( is_admin() || ! nostek_core_shop_closed() ) {
		return $hit = false;
	}
	if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
		return $hit = true;
	}
	if ( is_page() ) {
		return $hit = nostek_core_shop_path( get_page_uri( get_queried_object_id() ) );
	}
	return $hit = false;
}

foreach ( array( 'index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'home', 'frontpage', 'page', 'search', 'single', 'singular', 'attachment' ) as $nostek_core_type ) {
	add_filter( "{$nostek_core_type}_template_hierarchy", 'nostek_core_shop_template', PHP_INT_MAX );
}
unset( $nostek_core_type );
/**
 * Puts the theme template "coming-soon" first for shop pages.
 *
 * @param string[] $templates Template slugs from core, theme and WooCommerce.
 * @return string[]
 */
function nostek_core_shop_template( $templates ) {
	if ( nostek_core_shop_request() ) {
		array_unshift( $templates, 'coming-soon' );
	}
	return $templates;
}

add_filter( 'wp_robots', 'nostek_core_shop_robots', PHP_INT_MAX );
/**
 * @param array $robots Robots directives.
 * @return array
 */
function nostek_core_shop_robots( $robots ) {
	if ( nostek_core_shop_request() ) {
		$robots['noindex'] = true;
		unset( $robots['max-image-preview'] );
	}
	return $robots;
}

add_filter( 'woocommerce_is_purchasable', 'nostek_core_shop_purchasable', PHP_INT_MAX );
add_filter( 'woocommerce_variation_is_purchasable', 'nostek_core_shop_purchasable', PHP_INT_MAX );
/**
 * Nothing goes into a cart while the shop is closed (also blocks the Store API).
 *
 * @param bool $purchasable WooCommerce decision.
 * @return bool
 */
function nostek_core_shop_purchasable( $purchasable ) {
	return nostek_core_shop_closed() ? false : $purchasable;
}

add_filter( 'render_block', 'nostek_core_shop_blocks', PHP_INT_MAX, 3 );
/**
 * Header and footer while the shop is closed: no mini cart, the Market
 * submenu becomes one link to /market/, links to shop help pages are hidden.
 * render_block (not pre_render_block): Navigation renders its links itself,
 * and only render_block runs for them, also for menus loaded by ref.
 *
 * @param string        $content  Rendered block.
 * @param array         $parsed   Parsed block.
 * @param WP_Block|null $instance Block instance.
 * @return string
 */
function nostek_core_shop_blocks( $content, $parsed, $instance = null ) {
	if ( is_admin() || ! nostek_core_shop_closed() ) {
		return $content;
	}
	$name  = $parsed['blockName'] ?? '';
	$attrs = $parsed['attrs'] ?? array();
	if ( 'woocommerce/mini-cart' === $name ) {
		return '';
	}
	if ( 'core/navigation-link' === $name && nostek_core_shop_url( (string) ( $attrs['url'] ?? '' ) ) ) {
		return '';
	}
	if ( 'core/navigation-submenu' === $name && 'market' === strtolower( trim( (string) ( $attrs['label'] ?? '' ) ) ) ) {
		$link    = array(
			'blockName'    => 'core/navigation-link',
			'attrs'        => array(
				'label'       => (string) $attrs['label'],
				'url'         => '/market/',
				'kind'        => 'custom',
				'description' => __( 'Segera hadir', 'nostek-core' ),
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);
		$context = $instance instanceof WP_Block ? $instance->context : array();
		return ( new WP_Block( $link, $context ) )->render();
	}
	return $content;
}

/**
 * Links hidden from menus while the shop is closed: shop help and policy
 * pages. /market/ itself stays (it shows "coming soon").
 *
 * @param string $url Link URL as written in the menu.
 * @return bool
 */
function nostek_core_shop_url( $url ) {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	if ( '' !== $host && wp_parse_url( home_url(), PHP_URL_HOST ) !== $host ) {
		return false;
	}
	$path = trim( $path, '/' );
	return '' !== $path && 'market' !== $path && nostek_core_shop_path( $path );
}

add_filter( 'wp_sitemaps_post_types', 'nostek_core_shop_sitemap_types', PHP_INT_MAX );
/**
 * @param array $types Post types in the sitemap.
 * @return array
 */
function nostek_core_shop_sitemap_types( $types ) {
	if ( nostek_core_shop_closed() ) {
		unset( $types['product'] );
	}
	return $types;
}

add_filter( 'wp_sitemaps_taxonomies', 'nostek_core_shop_sitemap_taxonomies', PHP_INT_MAX );
/**
 * @param array $taxonomies Taxonomies in the sitemap.
 * @return array
 */
function nostek_core_shop_sitemap_taxonomies( $taxonomies ) {
	if ( nostek_core_shop_closed() ) {
		unset( $taxonomies['product_cat'], $taxonomies['product_tag'] );
	}
	return $taxonomies;
}

add_filter( 'wp_sitemaps_posts_query_args', 'nostek_core_shop_sitemap_pages', PHP_INT_MAX, 2 );
/**
 * Leaves shop pages out of the page sitemap while the shop is closed.
 *
 * @param array  $args      WP_Query arguments.
 * @param string $post_type Post type.
 * @return array
 */
function nostek_core_shop_sitemap_pages( $args, $post_type ) {
	if ( 'page' !== $post_type || ! nostek_core_shop_closed() ) {
		return $args;
	}
	$ids = array();
	foreach ( get_pages( array( 'post_status' => 'publish' ) ) as $page ) {
		if ( nostek_core_shop_path( get_page_uri( $page ) ) ) {
			$ids[] = (int) $page->ID;
		}
	}
	foreach ( array( 'shop', 'cart', 'checkout', 'myaccount' ) as $wc_page ) {
		$id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( $wc_page ) : 0;
		if ( $id > 0 ) {
			$ids[] = $id;
		}
	}
	$args['post__not_in'] = array_values( array_unique( array_merge( $args['post__not_in'] ?? array(), $ids ) ) );
	return $args;
}
