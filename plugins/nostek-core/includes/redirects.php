<?php
/**
 * /journal/<category>/ sends readers to the category archive.
 *
 * Journal categories live at /category/<slug>/. Readers (and old links)
 * guess /journal/<slug>/ because the menu sits under Journal, which was a
 * 404. When nothing else matched and the slug is a category, redirect.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'nostek_core_journal_category_redirect' );
function nostek_core_journal_category_redirect() {
	if ( ! is_404() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
	$home = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( '' !== $home && 0 === strpos( $path, $home . '/' ) ) {
		$path = substr( $path, strlen( $home ) + 1 );
	}
	if ( ! preg_match( '#^journal/([a-z0-9-]+)$#', $path, $m ) ) {
		return;
	}
	$term = get_term_by( 'slug', $m[1], 'category' );
	if ( ! $term ) {
		return;
	}
	$link = get_term_link( $term );
	if ( is_wp_error( $link ) ) {
		return;
	}
	wp_safe_redirect( $link, 301 );
	exit;
}
