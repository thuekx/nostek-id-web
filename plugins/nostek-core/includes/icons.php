<?php
/**
 * Social and link icons as inline SVG (WEB-33): no icon font, no
 * third-party request. Simple single-colour marks drawn for Nostek that
 * follow currentColor, so they work in light and dark mode.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Networks Nostek links to: key => [label, svg path (24x24, evenodd)].
 *
 * @return array<string, array{0: string, 1: string}>
 */
function nostek_core_icon_paths() {
	return array(
		'whatsapp'  => array( 'WhatsApp', 'M12 2.5a9.5 9.5 0 0 0-8.2 14.3L2.5 21.5l4.8-1.3A9.5 9.5 0 1 0 12 2.5Zm0 1.8a7.7 7.7 0 1 1-3.9 14.3l-.3-.2-2.8.8.8-2.7-.2-.3A7.7 7.7 0 0 1 12 4.3ZM9 7.6c-.2-.4-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.3.3-.9.9-.9 2.2s.9 2.5 1 2.7c.1.2 1.8 2.9 4.5 3.9 2.2.9 2.7.7 3.2.7.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.2-.2-.5-.3l-1.7-.8c-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1-.3-.1-1.1-.4-2-1.3-.8-.7-1.3-1.5-1.4-1.8-.2-.3 0-.4.1-.5l.4-.5.3-.5v-.5L9 7.6Z' ),
		'facebook'  => array( 'Facebook', 'M13.5 21.5v-8h2.7l.4-3.2h-3.1V8.3c0-.9.3-1.6 1.6-1.6h1.7V3.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2.2H7.3v3.2h2.8v8h3.4Z' ),
		'x'         => array( 'X', 'M3.5 3.5h5.2l3.9 5.4 4.6-5.4h2.3l-5.9 6.9 6.9 10.1h-5.2l-4.3-6.1-5.2 6.1H3.5l6.5-7.6-6.5-9.4Zm3.1 1.6 9.6 13.8h1.6L8.2 5.1H6.6Z' ),
		'linkedin'  => array( 'LinkedIn', 'M4 9h3.6v11.5H4V9Zm1.8-5.6a2.1 2.1 0 1 1 0 4.2 2.1 2.1 0 0 1 0-4.2ZM9.9 9h3.4v1.6h.1c.5-.9 1.6-1.9 3.4-1.9 3.6 0 4.3 2.4 4.3 5.5v6.3h-3.6v-5.6c0-1.3 0-3-1.9-3s-2.1 1.4-2.1 2.9v5.7H9.9V9Z' ),
		'telegram'  => array( 'Telegram', 'M21.2 3.6 2.9 10.7c-1.2.5-1.2 1.2-.2 1.5l4.7 1.5 1.8 5.5c.2.6.4.8.8.8.4 0 .6-.2.9-.4l2.2-2.2 4.6 3.4c.8.5 1.4.2 1.6-.8l3-14.2c.3-1.2-.5-1.8-1.3-1.4ZM9.4 14.1l8.4-7.6-6.7 8.6-.3 3-1.4-4Z' ),
		'instagram' => array( 'Instagram', 'M7 2.5h10A4.5 4.5 0 0 1 21.5 7v10a4.5 4.5 0 0 1-4.5 4.5H7A4.5 4.5 0 0 1 2.5 17V7A4.5 4.5 0 0 1 7 2.5Zm0 2A2.5 2.5 0 0 0 4.5 7v10A2.5 2.5 0 0 0 7 19.5h10a2.5 2.5 0 0 0 2.5-2.5V7A2.5 2.5 0 0 0 17 4.5H7Zm5 3.5a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm0 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm4.8-3.3a1.1 1.1 0 1 1 0 2.2 1.1 1.1 0 0 1 0-2.2Z' ),
		'youtube'   => array( 'YouTube', 'M21.6 7.2a2.6 2.6 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4a2.6 2.6 0 0 0-1.8 1.8A27 27 0 0 0 2 12a27 27 0 0 0 .4 4.8 2.6 2.6 0 0 0 1.8 1.8c1.6.4 7.8.4 7.8.4s6.2 0 7.8-.4a2.6 2.6 0 0 0 1.8-1.8A27 27 0 0 0 22 12a27 27 0 0 0-.4-4.8ZM10 15V9l5.2 3L10 15Z' ),
		'website'   => array( 'Website', 'M12 2.5a9.5 9.5 0 1 0 0 19 9.5 9.5 0 0 0 0-19Zm-1.2 2.1C9.7 5.9 9 7.8 8.8 10.1H4.6a7.5 7.5 0 0 1 6.2-5.5Zm2.4 0a7.5 7.5 0 0 1 6.2 5.5h-4.2c-.2-2.3-.9-4.2-2-5.5ZM12 4.9c1 1.1 1.7 3 1.9 5.2h-3.8c.2-2.2.9-4.1 1.9-5.2Zm-7.4 7.2h4.2c.2 2.3.9 4.2 2 5.5a7.5 7.5 0 0 1-6.2-5.5Zm5.5 0h3.8c-.2 2.2-.9 4.1-1.9 5.2-1-1.1-1.7-3-1.9-5.2Zm5.1 0h4.2a7.5 7.5 0 0 1-6.2 5.5c1.1-1.3 1.8-3.2 2-5.5Z' ),
		'link'      => array( 'Link', 'M10.6 7.8 13 5.4a4.2 4.2 0 0 1 5.9 5.9l-2.8 2.8a4.2 4.2 0 0 1-5.9 0l1.4-1.4c.8.8 2.2.8 3 0l2.8-2.8a2.2 2.2 0 0 0-3-3l-2.4 2.3-1.4-1.4Zm2.8 8.4L11 18.6a4.2 4.2 0 0 1-5.9-5.9l2.8-2.8a4.2 4.2 0 0 1 5.9 0l-1.4 1.4a2.2 2.2 0 0 0-3 0l-2.8 2.8a2.2 2.2 0 0 0 3 3l2.4-2.3 1.4 1.4Z' ),
	);
}

/**
 * Inline SVG for one icon, hidden from screen readers (the link carries the label).
 *
 * @param string $key Icon key.
 * @return string
 */
function nostek_core_icon( $key ) {
	$paths = nostek_core_icon_paths();
	if ( ! isset( $paths[ $key ] ) ) {
		return '';
	}
	return '<svg class="nk-icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" fill-rule="evenodd" d="' . esc_attr( $paths[ $key ][1] ) . '"/></svg>';
}
