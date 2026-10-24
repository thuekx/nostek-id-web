<?php
/**
 * Image output for good PageSpeed scores. Core already adds width/height,
 * loading="lazy" and fetchpriority="high" on the first large image; this
 * file only makes the resized copies WebP when the server can write WebP.
 * The uploaded original is kept as it is, so agents should upload WebP.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'image_editor_output_format', 'nostek_core_webp_sizes' );
/**
 * @param array<string, string> $formats Source mime => output mime.
 * @return array<string, string>
 */
function nostek_core_webp_sizes( $formats ) {
	if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		$formats['image/jpeg'] = 'image/webp';
		$formats['image/png']  = 'image/webp';
	}
	return $formats;
}
