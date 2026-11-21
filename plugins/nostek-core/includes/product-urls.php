<?php
/**
 * Shop URLs under one base: /product/.
 *
 * WEB-41: product category archives move from /product-category/<path>/ to
 * /product/<path>/ (sub-categories /product/<parent>/<child>/), while single
 * products stay at /product/<slug>/ as before (existing product links keep
 * working). WooCommerce cannot do this alone: with both bases set to
 * "product" the category rule is generated first and every single product
 * becomes a 404. Nostek Core therefore:
 *
 * 1. sets the product category base to "product" in code (both sites, no
 *    per-site setting; Settings > Permalinks shows a note);
 * 2. resolves /product/<slug>/ to the product when no top-level category has
 *    that slug (also its feed, embed and review page URLs);
 * 3. keeps product slugs and category slugs apart, so one URL never means
 *    both (a clash gets the suffix -2, -3, ...);
 * 4. sends old /product-category/... URLs, and a sub-category reached by its
 *    slug alone (/product/e-book/), to the one real address with a 301.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NOSTEK_CORE_PRODUCT_BASE = 'product';

/** Bump when the rewrite rules of this file change; rules are flushed once per version. */
const NOSTEK_CORE_PRODUCT_URLS_VERSION = 1;

add_filter( 'woocommerce_taxonomy_args_product_cat', 'nostek_core_product_cat_args' );
/**
 * @param array $args register_taxonomy() arguments of product_cat.
 * @return array
 */
function nostek_core_product_cat_args( $args ) {
	$args['rewrite'] = array(
		'slug'         => NOSTEK_CORE_PRODUCT_BASE,
		'with_front'   => false,
		'hierarchical' => true,
	);
	return $args;
}

/** @return bool True when single products also live under /product/ (WooCommerce default). */
function nostek_core_products_share_base() {
	if ( ! function_exists( 'wc_get_permalink_structure' ) ) {
		return false;
	}
	$structure = wc_get_permalink_structure();
	return NOSTEK_CORE_PRODUCT_BASE === trim( (string) ( $structure['product_rewrite_slug'] ?? '' ), '/' );
}

/**
 * @param string $slug Post name.
 * @param int    $skip Product id to ignore (the product being saved).
 * @return bool True when a product (any status except auto-draft) uses this slug.
 */
function nostek_core_product_slug_taken( $slug, $skip = 0 ) {
	global $wpdb;
	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM $wpdb->posts WHERE post_type = 'product' AND post_name = %s AND ID <> %d AND post_status <> 'auto-draft' LIMIT 1",
			$slug,
			(int) $skip
		)
	);
}

/**
 * @param string $slug Term slug.
 * @param int    $skip Term id to ignore (the term being saved).
 * @return bool True when a product category uses this slug.
 */
function nostek_core_product_cat_slug_taken( $slug, $skip = 0 ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	return $term && (int) $term->term_id !== (int) $skip;
}

add_filter( 'request', 'nostek_core_product_request' );
/**
 * The category rule (product/(.+?)/?$) is matched before the product rule, so
 * /product/<slug>/ arrives here as a category. Hand it to the product when
 * no top-level category has that slug and a published product does.
 *
 * @param array $vars Parsed query vars.
 * @return array
 */
function nostek_core_product_request( $vars ) {
	if ( empty( $vars['product_cat'] ) || ! is_string( $vars['product_cat'] ) || isset( $vars['product'] ) || ! nostek_core_products_share_base() ) {
		return $vars;
	}
	$path  = trim( $vars['product_cat'], '/' );
	$extra = array();
	if ( preg_match( '#^([^/]+)/comment-page-([0-9]{1,})$#', $path, $m ) ) {
		$path  = $m[1];
		$extra = array( 'cpage' => $m[2] );
	} elseif ( preg_match( '#^([^/]+)/([0-9]+)$#', $path, $m ) ) {
		$path  = $m[1];
		$extra = array( 'page' => $m[2] );
	}
	if ( '' === $path || false !== strpos( $path, '/' ) ) {
		return $vars;
	}
	$term = get_term_by( 'slug', $path, 'product_cat' );
	if ( $term && 0 === (int) $term->parent ) {
		return $vars;
	}
	$product = get_page_by_path( $path, OBJECT, 'product' );
	if ( ! $product || 'publish' !== $product->post_status ) {
		// Not a product either: a sub-category reached by its slug alone, or a 404.
		return $vars;
	}
	unset( $vars['product_cat'] );
	$vars['product']   = $path;
	$vars['post_type'] = 'product';
	$vars['name']      = $path;
	return array_merge( $vars, $extra );
}

add_filter( 'wp_unique_post_slug', 'nostek_core_product_unique_slug', 10, 4 );
/**
 * A product never takes the slug of a product category (/product/<slug>/
 * would mean both).
 *
 * @param string $slug        Slug WordPress picked.
 * @param int    $post_id     Post id.
 * @param string $post_status Post status.
 * @param string $post_type   Post type.
 * @return string
 */
function nostek_core_product_unique_slug( $slug, $post_id, $post_status, $post_type ) {
	if ( 'product' !== $post_type || '' === (string) $slug || ! nostek_core_product_cat_slug_taken( $slug ) ) {
		return $slug;
	}
	$n = 2;
	do {
		$try = _truncate_post_slug( $slug, 200 - ( strlen( (string) $n ) + 1 ) ) . '-' . $n++;
	} while ( nostek_core_product_cat_slug_taken( $try ) || nostek_core_product_slug_taken( $try, $post_id ) );
	return $try;
}

add_filter( 'wp_insert_term_data', 'nostek_core_product_cat_unique_slug', 10, 2 );
add_filter( 'wp_update_term_data', 'nostek_core_product_cat_unique_slug_update', 10, 3 );
/**
 * A product category never takes the slug of a product.
 *
 * @param array  $data     Term data (name, slug, term_group).
 * @param string $taxonomy Taxonomy.
 * @param int    $term_id  Term id on update, 0 on insert.
 * @return array
 */
function nostek_core_product_cat_unique_slug( $data, $taxonomy, $term_id = 0 ) {
	if ( 'product_cat' !== $taxonomy || empty( $data['slug'] ) || ! nostek_core_product_slug_taken( $data['slug'] ) ) {
		return $data;
	}
	$base = $data['slug'];
	$n    = 2;
	do {
		$try = $base . '-' . $n++;
	} while ( nostek_core_product_slug_taken( $try ) || nostek_core_product_cat_slug_taken( $try, $term_id ) );
	$data['slug'] = $try;
	return $data;
}

/**
 * @param array  $data     Term data.
 * @param int    $term_id  Term id.
 * @param string $taxonomy Taxonomy.
 * @return array
 */
function nostek_core_product_cat_unique_slug_update( $data, $term_id, $taxonomy ) {
	return nostek_core_product_cat_unique_slug( $data, $taxonomy, $term_id );
}

add_action( 'template_redirect', 'nostek_core_product_category_redirect', 5 );
/** Old /product-category/<path>/[page/N/|feed/] URLs move to /product/<path>/ with a 301. */
function nostek_core_product_category_redirect() {
	// An old category feed URL is caught by the page feed rule, so it is no 404.
	if ( ! is_404() && ! is_feed() ) {
		return;
	}
	$target = nostek_core_product_category_target( (string) ( $_SERVER['REQUEST_URI'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( null === $target ) {
		return;
	}
	wp_safe_redirect( $target, 301 );
	exit;
}

/**
 * @param string $uri Request URI (path and query).
 * @return string|null New absolute URL, or null when the URI is not an old category URL.
 */
function nostek_core_product_category_target( $uri ) {
	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	$home = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( '' !== $home && 0 === strpos( $path, $home . '/' ) ) {
		$path = substr( $path, strlen( $home ) + 1 );
	}
	if ( ! preg_match( '#^product-category/((?:[a-z0-9_%-]+/)*?[a-z0-9_%-]+)(/page/[0-9]+|/feed(?:/[a-z0-9]+)?)?$#i', $path, $m ) ) {
		return null;
	}
	$suffix = isset( $m[2] ) ? trim( $m[2], '/' ) . '/' : '';
	$slugs  = explode( '/', strtolower( $m[1] ) );
	$term   = get_term_by( 'slug', end( $slugs ), 'product_cat' );
	if ( ! $term ) {
		return null;
	}
	$link = get_term_link( $term );
	if ( is_wp_error( $link ) ) {
		return null;
	}
	$query = (string) wp_parse_url( $uri, PHP_URL_QUERY );
	return trailingslashit( $link ) . $suffix . ( '' !== $query ? '?' . $query : '' );
}

add_action( 'template_redirect', 'nostek_core_product_cat_canonical', 6 );
/**
 * WordPress shows a sub-category for any path that ends in its slug
 * (/product/e-book/, /product/x/e-book/). Send those to the one real
 * address, /product/<parent>/<child>/, so each archive has a single URL.
 */
function nostek_core_product_cat_canonical() {
	if ( ! is_tax( 'product_cat' ) || is_feed() ) {
		return;
	}
	$term = get_queried_object();
	$link = $term instanceof WP_Term ? get_term_link( $term ) : '';
	if ( ! is_string( $link ) || '' === $link ) {
		return;
	}
	$uri  = (string) ( $_SERVER['REQUEST_URI'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	$page = '';
	if ( preg_match( '#^(.*)/(page/[0-9]+)$#', $path, $m ) ) {
		$path = $m[1];
		$page = $m[2] . '/';
	}
	$want = trim( (string) wp_parse_url( $link, PHP_URL_PATH ), '/' );
	if ( rawurldecode( $path ) === rawurldecode( $want ) ) {
		return;
	}
	$query = (string) wp_parse_url( $uri, PHP_URL_QUERY );
	wp_safe_redirect( trailingslashit( $link ) . $page . ( '' !== $query ? '?' . $query : '' ), 301 );
	exit;
}

add_action( 'init', 'nostek_core_product_urls_flush', 99 );
/** Rebuild the rewrite rules once after this file's rules change (WooCommerce registers its types at init 5). */
function nostek_core_product_urls_flush() {
	if ( (int) get_option( 'nostek_core_product_urls_version' ) === NOSTEK_CORE_PRODUCT_URLS_VERSION || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'nostek_core_product_urls_version', NOSTEK_CORE_PRODUCT_URLS_VERSION );
}

add_action( 'load-options-permalink.php', 'nostek_core_product_urls_notice_hook' );
function nostek_core_product_urls_notice_hook() {
	add_action( 'admin_notices', 'nostek_core_product_urls_notice' );
}

/** Settings > Permalinks: the category base field of WooCommerce has no effect. */
function nostek_core_product_urls_notice() {
	echo '<div class="notice notice-info"><p>' . esc_html__( 'Nostek Core: product categories always use /product/<category>/ and products /product/<product>/. The WooCommerce "Product category base" field below is ignored; keep the product base at the default "product".', 'nostek-core' ) . '</p></div>';
}
