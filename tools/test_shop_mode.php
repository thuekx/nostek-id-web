<?php
/**
 * Offline test for WEB-35 shop mode (nostek-core includes/shop-mode.php).
 * Run: php tools/test_shop_mode.php  -> "RESULT shop-mode PASS n/n"
 */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['F']    = array();
$GLOBALS['opt']  = array();
$GLOBALS['page'] = '';
$GLOBALS['wc']   = false;
function add_filter( $hook, $cb, $prio = 10, $n = 1 ) { $GLOBALS['F'][ $hook ] = $cb; }
function apply( $hook, ...$args ) { return call_user_func_array( $GLOBALS['F'][ $hook ], $args ); }
function nostek_core_setting( $key ) { return $GLOBALS['opt'][ $key ] ?? ''; }
function is_admin() { return false; }
function is_woocommerce() { return $GLOBALS['wc']; }
function is_cart() { return false; }
function is_checkout() { return false; }
function is_account_page() { return false; }
function is_page() { return '' !== $GLOBALS['page']; }
function get_queried_object_id() { return 1; }
function get_page_uri( $p ) { return $GLOBALS['page']; }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function home_url( $p = '' ) { return 'https://staging.nostek.id' . $p; }
function __( $t ) { return $t; }
class WP_Block {
	public $context = array();
	private $b;
	public function __construct( $b, $c = array() ) { $this->b = $b; $this->context = $c; }
	public function render() { return '<li><a href="' . $this->b['attrs']['url'] . '">' . $this->b['attrs']['label'] . '</a><span>' . $this->b['attrs']['description'] . '</span></li>'; }
}

$ok = 0; $n = 0;
function check( $name, $cond ) { global $ok, $n; $n++; if ( $cond ) { $ok++; echo "ok   $name\n"; } else { echo "FAIL $name\n"; } }

require __DIR__ . '/../plugins/nostek-core/includes/shop-mode.php';

foreach ( array( 'market', 'sell', 'sell/titip-jual', 'support/payment', 'support/order-tracking', 'legal/dispute-policy', 'shop', 'cart', 'checkout', 'my-account/orders' ) as $p ) {
	check( "shop path: $p", nostek_core_shop_path( $p ) );
}
foreach ( array( 'journal', 'topics', 'services', 'support', 'support/request', 'legal/terms', 'privacy-policy', 'about', 'seller', 'marketing' ) as $p ) {
	check( "not a shop path: $p", ! nostek_core_shop_path( $p ) );
}
check( 'closed by default', nostek_core_shop_closed() );
check( 'nothing purchasable while closed', false === apply( 'woocommerce_is_purchasable', true ) );
check( 'mini cart skipped before render (no drawer in footer)', '' === apply( 'pre_render_block', null, array( 'blockName' => 'woocommerce/mini-cart' ) ) );
check( 'other blocks render normally', null === apply( 'pre_render_block', null, array( 'blockName' => 'core/paragraph' ) ) );
check( 'mini cart hidden', '' === apply( 'render_block', '<div>cart</div>', array( 'blockName' => 'woocommerce/mini-cart' ) ) );
check( 'footer Payment link hidden', '' === apply( 'render_block', '<li>Payment</li>', array( 'blockName' => 'core/navigation-link', 'attrs' => array( 'url' => '/support/payment/' ) ) ) );
check( 'footer Request Support kept', '<li>R</li>' === apply( 'render_block', '<li>R</li>', array( 'blockName' => 'core/navigation-link', 'attrs' => array( 'url' => '/support/request/' ) ) ) );
check( 'Market link itself kept', '<li>M</li>' === apply( 'render_block', '<li>M</li>', array( 'blockName' => 'core/navigation-link', 'attrs' => array( 'url' => '/market/' ) ) ) );
check( 'external link kept', '<li>X</li>' === apply( 'render_block', '<li>X</li>', array( 'blockName' => 'core/navigation-link', 'attrs' => array( 'url' => 'https://example.com/shop/' ) ) ) );
$out = apply( 'render_block', '<li>mega</li>', array( 'blockName' => 'core/navigation-submenu', 'attrs' => array( 'label' => 'Market' ) ) );
check( 'Market submenu becomes one link with Segera hadir', false !== strpos( $out, 'href="/market/"' ) && false !== strpos( $out, 'Segera hadir' ) && false === strpos( $out, 'mega' ) );
$GLOBALS['page'] = 'sell/titip-jual';
check( 'shop page gets coming-soon template first', array( 'coming-soon', 'page' ) === apply( 'page_template_hierarchy', array( 'page' ) ) );
check( 'shop page noindex', ! empty( apply( 'wp_robots', array() )['noindex'] ) );
check( 'sitemap drops products', array( 'post' => 1 ) === apply( 'wp_sitemaps_post_types', array( 'post' => 1, 'product' => 1 ) ) );

// Open shop: everything back to normal (fresh process: the request result is cached per request).
$GLOBALS['opt']['shop_open'] = '1';
check( 'open shop: purchasable unchanged', true === apply( 'woocommerce_is_purchasable', true ) );
check( 'open shop: mini cart renders', null === apply( 'pre_render_block', null, array( 'blockName' => 'woocommerce/mini-cart' ) ) );
check( 'open shop: mini cart shown', '<div>cart</div>' === apply( 'render_block', '<div>cart</div>', array( 'blockName' => 'woocommerce/mini-cart' ) ) );
check( 'open shop: products stay in sitemap', isset( apply( 'wp_sitemaps_post_types', array( 'product' => 1 ) )['product'] ) );
echo 'RESULT shop-mode ' . ( $ok === $n ? 'PASS' : 'FAIL' ) . " $ok/$n\n";
exit( $ok === $n ? 0 : 1 );
