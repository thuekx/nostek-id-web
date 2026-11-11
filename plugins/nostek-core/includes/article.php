<?php
/**
 * Single article rails (WEB-33): heading anchors, table of contents,
 * related articles and share links. Lives in the plugin so the internal
 * links and anchors survive a theme change.
 *
 * - Every h2/h3 in a post gets a stable id (kept when the editor set one).
 * - nostek/toc lists those headings; hidden when there are fewer than two.
 * - nostek/related lists posts that share a Topic, then the category.
 * - nostek/share prints plain share links: no third-party script, no
 *   cookie, nothing to put behind the consent banner.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add ids to h2/h3 headings in an HTML string.
 *
 * @param string $html Post HTML (raw block markup or rendered content).
 * @return array{0: string, 1: array<int, array{level: int, text: string, id: string}>}
 */
function nostek_core_heading_anchors( $html ) {
	$items = array();
	$used  = array();
	$out   = preg_replace_callback(
		'#<h([23])(\s[^>]*)?>(.*?)</h\1>#is',
		static function ( $m ) use ( &$items, &$used ) {
			$attrs = isset( $m[2] ) ? $m[2] : '';
			$text  = trim( html_entity_decode( wp_strip_all_tags( $m[3] ), ENT_QUOTES, 'UTF-8' ) );
			if ( '' === $text ) {
				return $m[0];
			}
			if ( preg_match( '/\sid=(["\'])([^"\']+)\1/i', $attrs, $id_match ) ) {
				$id = $id_match[2];
			} else {
				$base = sanitize_title( $text );
				$base = '' !== $base ? $base : 'bagian';
				$id   = $base;
				$n    = 2;
				while ( isset( $used[ $id ] ) ) {
					$id = $base . '-' . $n++;
				}
				$attrs .= ' id="' . esc_attr( $id ) . '"';
			}
			$used[ $id ] = true;
			$items[]     = array(
				'level' => (int) $m[1],
				'text'  => $text,
				'id'    => $id,
			);
			return '<h' . $m[1] . $attrs . '>' . $m[3] . '</h' . $m[1] . '>';
		},
		(string) $html
	);
	return array( null === $out ? (string) $html : $out, $items );
}

add_filter( 'the_content', 'nostek_core_content_anchors', 20 );
/**
 * Anchors on the article being viewed, so TOC links always land.
 *
 * @param string $content Rendered content.
 * @return string
 */
function nostek_core_content_anchors( $content ) {
	if ( ! is_singular( 'post' ) || get_the_ID() !== get_queried_object_id() ) {
		return $content;
	}
	return nostek_core_heading_anchors( $content )[0];
}

/**
 * Headings of the viewed article, computed from its stored markup.
 *
 * @return array<int, array{level: int, text: string, id: string}>
 */
function nostek_core_article_headings() {
	$post = get_post( get_queried_object_id() );
	if ( ! $post || 'post' !== $post->post_type ) {
		return array();
	}
	return nostek_core_heading_anchors( $post->post_content )[1];
}

function nostek_core_render_toc() {
	$items = is_singular( 'post' ) ? nostek_core_article_headings() : array();
	if ( count( $items ) < 2 ) {
		return '';
	}
	$list = '';
	foreach ( $items as $item ) {
		$list .= sprintf(
			'<li class="nk-toc__item nk-toc__item--h%d"><a href="#%s">%s</a></li>',
			$item['level'],
			esc_attr( $item['id'] ),
			esc_html( $item['text'] )
		);
	}
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'nk-toc' ) );
	return sprintf(
		'<nav %s aria-label="%s"><details open><summary>%s</summary><ol>%s</ol></details></nav>',
		$wrapper,
		esc_attr__( 'Daftar isi', 'nostek-core' ),
		esc_html__( 'Daftar Isi', 'nostek-core' ),
		$list
	);
}

/**
 * Up to $count published posts related to $post: same Topic first, then
 * the same category, newest first. Never the post itself.
 *
 * @param WP_Post $post  Current post.
 * @param int     $count How many.
 * @return WP_Post[]
 */
function nostek_core_related_posts( $post, $count = 3 ) {
	$found = array();
	$skip  = array( $post->ID );
	$tries = array(
		array( 'topic', wp_get_post_terms( $post->ID, 'topic', array( 'fields' => 'ids' ) ) ),
		array( 'category', wp_get_post_categories( $post->ID ) ),
	);
	foreach ( $tries as $try ) {
		if ( count( $found ) >= $count || is_wp_error( $try[1] ) || ! $try[1] ) {
			continue;
		}
		$query = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => $count - count( $found ),
				'post__not_in'        => $skip,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $try[0],
						'terms'    => array_map( 'intval', $try[1] ),
					),
				),
			)
		);
		foreach ( $query->posts as $hit ) {
			$found[] = $hit;
			$skip[]  = $hit->ID;
		}
	}
	return $found;
}

function nostek_core_render_related() {
	if ( ! is_singular( 'post' ) ) {
		return '';
	}
	$posts = nostek_core_related_posts( get_queried_object() );
	if ( ! $posts ) {
		return '';
	}
	$list = '';
	foreach ( $posts as $item ) {
		$thumb = get_the_post_thumbnail(
			$item,
			'thumbnail',
			array(
				'class'   => 'nk-related__img',
				'loading' => 'lazy',
				'alt'     => '',
			)
		);
		$list .= sprintf(
			'<li class="nk-related__item"><a href="%s">%s<span class="nk-related__text"><span class="nk-related__title">%s</span><time datetime="%s">%s</time></span></a></li>',
			esc_url( get_permalink( $item ) ),
			$thumb,
			esc_html( get_the_title( $item ) ),
			esc_attr( get_the_date( 'c', $item ) ),
			esc_html( get_the_date( '', $item ) )
		);
	}
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'nk-related' ) );
	return sprintf(
		'<section %s aria-labelledby="nk-related-h"><h2 id="nk-related-h" class="nk-rail__title">%s</h2><ol>%s</ol></section>',
		$wrapper,
		esc_html__( 'Artikel Terkait', 'nostek-core' ),
		$list
	);
}

function nostek_core_render_share() {
	if ( ! is_singular() ) {
		return '';
	}
	$url   = rawurlencode( (string) wp_get_canonical_url( get_queried_object() ) );
	$title = rawurlencode( html_entity_decode( wp_strip_all_tags( get_the_title( get_queried_object() ) ), ENT_QUOTES, 'UTF-8' ) );
	$nets  = array(
		array( 'wa', 'WhatsApp', 'https://wa.me/?text=' . $title . '%20' . $url ),
		array( 'fb', 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $url ),
		array( 'x', 'X', 'https://x.com/intent/post?url=' . $url . '&text=' . $title ),
		array( 'in', 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url ),
		array( 'tg', 'Telegram', 'https://t.me/share/url?url=' . $url . '&text=' . $title ),
	);
	$links = '';
	foreach ( $nets as $net ) {
		$links .= sprintf(
			'<li><a class="nk-share__link nk-share__link--%1$s" href="%2$s" target="_blank" rel="noopener nofollow" aria-label="%3$s">%4$s</a></li>',
			esc_attr( $net[0] ),
			esc_url( $net[2] ),
			/* translators: %s: social network name. */
			esc_attr( sprintf( __( 'Bagikan ke %s', 'nostek-core' ), $net[1] ) ),
			esc_html( strtoupper( $net[0] ) )
		);
	}
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'nk-share' ) );
	return sprintf(
		'<div %s><p class="nk-rail__title">%s</p><ul>%s</ul></div>',
		$wrapper,
		esc_html__( 'Bagikan', 'nostek-core' ),
		$links
	);
}

add_action( 'init', 'nostek_core_register_article_blocks' );
function nostek_core_register_article_blocks() {
	$blocks = array(
		'nostek/toc'     => array( __( 'Nostek Table of Contents', 'nostek-core' ), 'nostek_core_render_toc' ),
		'nostek/related' => array( __( 'Nostek Related Articles', 'nostek-core' ), 'nostek_core_render_related' ),
		'nostek/share'   => array( __( 'Nostek Share Links', 'nostek-core' ), 'nostek_core_render_share' ),
	);
	foreach ( $blocks as $name => $block ) {
		register_block_type(
			$name,
			array(
				'api_version'     => 3,
				'title'           => $block[0],
				'category'        => 'theme',
				'editor_script'   => 'nostek-core-blocks',
				'render_callback' => $block[1],
				'supports'        => array( 'html' => false ),
			)
		);
	}
}
