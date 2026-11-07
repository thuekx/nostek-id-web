<?php
/**
 * Offline test for the WEB-M2 media URL filter (nostek-core includes/media.php).
 * Run: php tools/test_media_urls.php  -> "RESULT media-urls PASS n/n"
 */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['hooks'] = array();
function add_filter( $hook, $cb, $prio = 10 ) { $GLOBALS['hooks'][ $hook ] = array( $cb, $prio ); }
function wp_image_editor_supports( $a ) { return true; }
function apply( $hook, $v ) { return call_user_func( $GLOBALS['hooks'][ $hook ][0], $v ); }

$ok = 0; $n = 0;
function check( $name, $cond ) { global $ok, $n; $n++; if ( $cond ) { $ok++; echo "ok   $name\n"; } else { echo "FAIL $name\n"; } }

require __DIR__ . '/../plugins/nostek-core/includes/media.php';
// Constants not defined yet: the filter must change nothing (static cache is filled on first call, so test this in a child).
$child = shell_exec( escapeshellarg( PHP_BINARY ) . ' -r ' . escapeshellarg(
	'define("ABSPATH","/");function add_filter(){} function wp_image_editor_supports(){return true;}'
	. 'require "' . __DIR__ . '/../plugins/nostek-core/includes/media.php";'
	. 'echo nostek_core_media_clean_url("https://media.nostek.id/media.nostek.id/a.webp");'
) );
check( 'no offloader constants -> unchanged', 'https://media.nostek.id/media.nostek.id/a.webp' === $child );

define( 'ADVMO_MINIO_DOMAIN', 'https://media.nostek.id/' );
define( 'ADVMO_MINIO_BUCKET', 'media.nostek.id' );
$bad  = 'https://media.nostek.id/media.nostek.id/staging/2026/11/07193154/597064.webp';
$good = 'https://media.nostek.id/staging/2026/11/07193154/597064.webp';
check( 'attachment url cleaned', $good === apply( 'wp_get_attachment_url', $bad ) );
check( 'image src array cleaned, size kept', array( $good, 1600, 900, false ) === apply( 'wp_get_attachment_image_src', array( $bad, 1600, 900, false ) ) );
$srcset = apply( 'wp_calculate_image_srcset', array( 800 => array( 'url' => $bad, 'descriptor' => 'w', 'value' => 800 ) ) );
check( 'srcset cleaned', $good === $srcset[800]['url'] && 800 === $srcset[800]['value'] );
$js = apply( 'wp_prepare_attachment_for_js', array( 'url' => $bad, 'sizes' => array( 'full' => array( 'url' => $bad ) ), 'id' => 7 ) );
check( 'media library data cleaned', $good === $js['url'] && $good === $js['sizes']['full']['url'] && 7 === $js['id'] );
check( 'content cleaned, other text kept', '<img src="' . $good . '"> media.nostek.id is ok' === apply( 'the_content', '<img src="' . $bad . '"> media.nostek.id is ok' ) );
check( 'clean URL stays as it is', $good === apply( 'render_block', $good ) );
check( 'other hosts untouched', 'https://example.com/media.nostek.id/x.png' === apply( 'the_content', 'https://example.com/media.nostek.id/x.png' ) );
check( 'non-string values untouched', false === apply( 'wp_get_attachment_image_src', false ) );
check( 'runs last (PHP_INT_MAX)', PHP_INT_MAX === $GLOBALS['hooks']['the_content'][1] );
echo 'RESULT media-urls ' . ( $ok === $n ? 'PASS' : 'FAIL' ) . " $ok/$n\n";
exit( $ok === $n ? 0 : 1 );
