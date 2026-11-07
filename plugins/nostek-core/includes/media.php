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

/*
 * WEB-M2: images on the media server. Advanced Media Offloader (MinIO provider,
 * path style) builds URLs as <domain>/<bucket>/<key>; our bucket is named after
 * the domain, so pages would show https://media.nostek.id/media.nostek.id/….
 * The media server serves both forms (nostek-media-1.sh rev 2); this keeps the
 * visible URL short: https://media.nostek.id/<key>. Active only when the
 * offloader constants are set in wp-config.php; changes nothing otherwise.
 */

/**
 * @return array{0: string, 1: string}|array{} [ doubled prefix, clean prefix ] or empty.
 */
function nostek_core_media_url_prefixes() {
	static $prefixes = null;
	if ( null === $prefixes ) {
		$prefixes = array();
		if ( defined( 'ADVMO_MINIO_DOMAIN' ) && defined( 'ADVMO_MINIO_BUCKET' ) ) {
			$domain = rtrim( (string) ADVMO_MINIO_DOMAIN, '/' );
			$bucket = trim( (string) ADVMO_MINIO_BUCKET, '/' );
			if ( '' !== $domain && '' !== $bucket ) {
				if ( ! preg_match( '#^https?://#i', $domain ) ) {
					$domain = 'https://' . $domain;
				}
				$prefixes = array( $domain . '/' . $bucket . '/', $domain . '/' );
			}
		}
	}
	return $prefixes;
}

/**
 * Replaces the doubled media prefix in a string, or in every string of an array.
 *
 * @param mixed $value Filter value (string, array or anything else).
 * @return mixed
 */
function nostek_core_media_clean_url( $value ) {
	$prefixes = nostek_core_media_url_prefixes();
	if ( empty( $prefixes ) ) {
		return $value;
	}
	if ( is_string( $value ) ) {
		return str_replace( $prefixes[0], $prefixes[1], $value );
	}
	if ( is_array( $value ) ) {
		foreach ( $value as $key => $item ) {
			$value[ $key ] = nostek_core_media_clean_url( $item );
		}
	}
	return $value;
}

foreach ( array(
	'wp_get_attachment_url',
	'wp_get_attachment_image_src',
	'wp_calculate_image_srcset',
	'wp_get_attachment_image_attributes',
	'wp_prepare_attachment_for_js',
	'the_content',
	'render_block',
) as $nostek_core_media_hook ) {
	add_filter( $nostek_core_media_hook, 'nostek_core_media_clean_url', PHP_INT_MAX );
}
unset( $nostek_core_media_hook );
