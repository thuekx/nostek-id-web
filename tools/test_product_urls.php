<?php
/**
 * Offline test for WEB-41 shop URLs (nostek-core includes/product-urls.php).
 * Run: php tools/test_product_urls.php  -> "RESULT product-urls PASS n/n"
 * A real WordPress + WooCommerce run: bash tools/test_product_urls_wp.sh
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'OBJECT', 'OBJECT' );
$GLOBALS['F']        = array();
$GLOBALS['terms']    = array(); // slug => WP_Term
$GLOBALS['products'] = array(); // slug => status
function add_filter( $hook, $cb, $prio = 10, $n = 1 ) { $GLOBALS['F'][ $hook ] = $cb; }
function add_action( $hook, $cb, $prio = 10, $n = 1 ) { $GLOBALS['F'][ $hook ] = $cb; }
function apply( $hook, ...$args ) { return call_user_func_array( $GLOBALS['F'][ $hook ], $args ); }
function wc_get_permalink_structure() { return array( 'product_rewrite_slug' => '/product' ); }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function home_url( $p = '' ) { return 'https://staging.nostek.id' . $p; }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function is_wp_error( $x ) { return false; }
function _truncate_post_slug( $s, $l ) { return substr( $s, 0, $l ); }
class WP_Term { public $term_id; public $slug; public $parent; public $path; }
function term( $id, $slug, $parent, $path ) {
	$t = new WP_Term(); $t->term_id = $id; $t->slug = $slug; $t->parent = $parent; $t->path = $path;
	$GLOBALS['terms'][ $slug ] = $t;
}
function get_term_by( $f, $v, $tax ) { return $GLOBALS['terms'][ $v ] ?? false; }
function get_term_link( $t ) { return home_url( '/product/' . $t->path . '/' ); }
function get_page_by_path( $p, $o, $type ) {
	return isset( $GLOBALS['products'][ $p ] ) ? (object) array( 'post_name' => $p, 'post_status' => $GLOBALS['products'][ $p ] ) : null;
}
class FakeDb {
	public $posts = 'wp_posts';
	public function prepare( $q, ...$a ) { return $a; }
	public function get_var( $a ) { return isset( $GLOBALS['products'][ $a[0] ] ) ? 1 : null; }
}
$GLOBALS['wpdb'] = new FakeDb();

$ok = 0; $n = 0;
function check( $name, $cond ) { global $ok, $n; $n++; if ( $cond ) { $ok++; echo "ok   $name\n"; } else { echo "FAIL $name\n"; } }

require __DIR__ . '/../plugins/nostek-core/includes/product-urls.php';

term( 76, 'nostek-digital', 0, 'nostek-digital' );
term( 77, 'e-book', 76, 'nostek-digital/e-book' );
term( 71, 'vintage-fashion', 0, 'vintage-fashion' );
$GLOBALS['products'] = array( 'kaos-retro' => 'publish', 'draft-item' => 'draft' );

$args = apply( 'woocommerce_taxonomy_args_product_cat', array( 'rewrite' => array( 'slug' => 'product-category' ) ) );
check( 'category base is product', 'product' === $args['rewrite']['slug'] && true === $args['rewrite']['hierarchical'] );

$r = apply( 'request', array( 'product_cat' => 'kaos-retro' ) );
check( '/product/kaos-retro/ becomes the product', 'kaos-retro' === ( $r['product'] ?? '' ) && 'product' === $r['post_type'] && ! isset( $r['product_cat'] ) );
$r = apply( 'request', array( 'product_cat' => 'kaos-retro', 'feed' => 'feed' ) );
check( 'product feed kept', 'kaos-retro' === ( $r['product'] ?? '' ) && 'feed' === $r['feed'] );
$r = apply( 'request', array( 'product_cat' => 'kaos-retro/comment-page-2' ) );
check( 'product review page', 'kaos-retro' === ( $r['product'] ?? '' ) && '2' === $r['cpage'] );
check( 'top-level category stays a category', array( 'product_cat' => 'nostek-digital' ) === apply( 'request', array( 'product_cat' => 'nostek-digital' ) ) );
check( 'sub-category path stays a category', array( 'product_cat' => 'nostek-digital/e-book' ) === apply( 'request', array( 'product_cat' => 'nostek-digital/e-book' ) ) );
check( 'unknown slug left alone (404)', array( 'product_cat' => 'tidak-ada' ) === apply( 'request', array( 'product_cat' => 'tidak-ada' ) ) );
check( 'draft product not exposed', array( 'product_cat' => 'draft-item' ) === apply( 'request', array( 'product_cat' => 'draft-item' ) ) );
check( 'other requests untouched', array( 'pagename' => 'about' ) === apply( 'request', array( 'pagename' => 'about' ) ) );

check( 'product slug equal to a category gets -2', 'vintage-fashion-2' === apply( 'wp_unique_post_slug', 'vintage-fashion', 5, 'publish', 'product' ) );
$GLOBALS['products']['nostek-digital-2'] = 'publish';
check( 'suffix skips taken product slugs', 'nostek-digital-3' === apply( 'wp_unique_post_slug', 'nostek-digital', 5, 'publish', 'product' ) );
check( 'free product slug unchanged', 'radio-lawas' === apply( 'wp_unique_post_slug', 'radio-lawas', 5, 'publish', 'product' ) );
check( 'posts and pages untouched', 'vintage-fashion' === apply( 'wp_unique_post_slug', 'vintage-fashion', 5, 'publish', 'post' ) );
$d = apply( 'wp_insert_term_data', array( 'name' => 'Kaos Retro', 'slug' => 'kaos-retro' ), 'product_cat' );
check( 'category slug equal to a product gets -2', 'kaos-retro-2' === $d['slug'] );
$d = apply( 'wp_insert_term_data', array( 'name' => 'Kaos Retro', 'slug' => 'kaos-retro' ), 'post_tag' );
check( 'other taxonomies untouched', 'kaos-retro' === $d['slug'] );

$t = 'nostek_core_product_category_target';
check( 'old category URL -> /product/', 'https://staging.nostek.id/product/nostek-digital/' === $t( '/product-category/nostek-digital/' ) );
check( 'old flat sub-category URL -> full path', 'https://staging.nostek.id/product/nostek-digital/e-book/' === $t( '/product-category/e-book/' ) );
check( 'old page 2 + query kept', 'https://staging.nostek.id/product/nostek-digital/e-book/page/2/?orderby=price' === $t( '/product-category/nostek-digital/e-book/page/2/?orderby=price' ) );
check( 'old feed kept', 'https://staging.nostek.id/product/vintage-fashion/feed/' === $t( '/product-category/vintage-fashion/feed/' ) );
check( 'unknown old category -> no redirect', null === $t( '/product-category/tidak-ada/' ) );
check( 'other paths -> no redirect', null === $t( '/category/news/' ) && null === $t( '/product/kaos-retro/' ) );

echo ( $ok === $n ? 'RESULT product-urls PASS' : 'RESULT product-urls FAIL' ) . " $ok/$n\n";
exit( $ok === $n ? 0 : 1 );
