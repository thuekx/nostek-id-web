<?php
/**
 * SEO head tags without a third-party SEO plugin: meta description, canonical
 * for non-singular views, Open Graph + Twitter card, JSON-LD, and the
 * staging noindex guard.
 *
 * If Yoast SEO or Rank Math is ever activated, the head tags here switch off
 * so the page never carries two sets; the staging guard always stays on.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True only on the live site. Set NOSTEK_ENV in wp-config.php to force it;
 * otherwise the home URL host decides, so a Softaculous clone of production
 * on staging.nostek.id is never treated as live.
 */
function nostek_core_is_production() {
	if ( defined( 'NOSTEK_ENV' ) ) {
		return 'production' === NOSTEK_ENV;
	}
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	return in_array( $host, array( 'nostek.id', 'www.nostek.id' ), true );
}

function nostek_core_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' );
}

add_action( 'init', 'nostek_core_register_meta_description' );
/**
 * Optional search snippet, written by agents over REST like nostek_source_id.
 * Empty = the excerpt is used.
 */
function nostek_core_register_meta_description() {
	foreach ( array( 'post', 'page' ) as $type ) {
		register_post_meta(
			$type,
			'nostek_meta_description',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}

add_action( 'after_setup_theme', 'nostek_core_title_tag' );
function nostek_core_title_tag() {
	add_theme_support( 'title-tag' );
}

add_filter( 'document_title_separator', 'nostek_core_title_separator' );
function nostek_core_title_separator() {
	return '|';
}

/* ---------------------------------------------------------------- staging */

add_filter( 'wp_robots', 'nostek_core_robots_meta', 99 );
/**
 * Staging (and any non-live copy) is noindex whatever "Discourage search
 * engines" says, so a clone with the box unticked still stays out of Google.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function nostek_core_robots_meta( $robots ) {
	if ( ! nostek_core_is_production() ) {
		return array(
			'noindex'  => true,
			'nofollow' => true,
		);
	}
	return $robots;
}

add_filter( 'wp_sitemaps_enabled', 'nostek_core_sitemaps_enabled' );
/**
 * @param bool $enabled Core decision (blog_public).
 * @return bool
 */
function nostek_core_sitemaps_enabled( $enabled ) {
	return $enabled && nostek_core_is_production();
}

add_action( 'send_headers', 'nostek_core_robots_header' );
/**
 * Same rule as a header, so feeds and REST answers are covered too
 * (uploaded files are served by the web server and rely on robots.txt).
 */
function nostek_core_robots_header() {
	if ( ! nostek_core_is_production() ) {
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}
}

add_filter( 'robots_txt', 'nostek_core_robots_txt', 99, 2 );
/**
 * Live: core rules plus the core sitemap. Anything else: disallow all.
 * A physical robots.txt in the web root bypasses this filter, so none may exist.
 *
 * @param string $output Robots.txt body built by core.
 * @param bool   $public Value of blog_public.
 * @return string
 */
function nostek_core_robots_txt( $output, $public ) {
	if ( ! nostek_core_is_production() ) {
		return "User-agent: *\nDisallow: /\n";
	}
	$sitemap = home_url( '/wp-sitemap.xml' );
	if ( $public && false === strpos( $output, $sitemap ) ) {
		$output .= "\nSitemap: " . esc_url_raw( $sitemap ) . "\n";
	}
	return $output;
}

/* ------------------------------------------------------------- head tags */

/**
 * Plain-text description for the current view, at most 160 characters.
 */
function nostek_core_meta_description() {
	$text = '';
	if ( is_singular() ) {
		$post = get_queried_object();
		$text = (string) get_post_meta( $post->ID, 'nostek_meta_description', true );
		if ( '' === $text && has_excerpt( $post ) ) {
			$text = $post->post_excerpt;
		}
		if ( '' === $text && ! is_front_page() ) {
			$text = wp_trim_words( excerpt_remove_blocks( strip_shortcodes( $post->post_content ) ), 30, '' );
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$text = term_description();
	} elseif ( is_home() && get_option( 'page_for_posts' ) ) {
		$text = (string) get_post_meta( (int) get_option( 'page_for_posts' ), 'nostek_meta_description', true );
	}
	if ( '' === trim( wp_strip_all_tags( $text ) ) && ( is_front_page() || is_home() ) ) {
		$text = get_bloginfo( 'description' );
	}
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) );
	if ( mb_strlen( $text ) > 160 ) {
		$text = rtrim( mb_substr( $text, 0, 157 ) ) . '…';
	}
	return $text;
}

/**
 * Canonical URL of the current view without query strings (utm_* and the
 * like stay in the address bar and in analytics, never in the canonical).
 */
function nostek_core_canonical_url() {
	if ( is_singular() ) {
		return wp_get_canonical_url( get_queried_object() );
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	$url   = '';
	if ( is_home() ) {
		$url = get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		$url  = is_wp_error( $link ) ? '' : $link;
	} elseif ( is_author() ) {
		$url = get_author_posts_url( (int) get_query_var( 'author' ) );
	}
	if ( $url && $paged > 1 ) {
		$url = get_pagenum_link( $paged, false );
		$url = strtok( $url, '?' );
	}
	return $url;
}

/**
 * Featured image of the current post, else the site icon.
 *
 * @return array{url: string, width: int, height: int, alt: string}|null
 */
function nostek_core_share_image() {
	if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
		$id  = get_post_thumbnail_id( get_queried_object_id() );
		$img = wp_get_attachment_image_src( $id, 'large' );
		if ( $img ) {
			return array(
				'url'    => $img[0],
				'width'  => (int) $img[1],
				'height' => (int) $img[2],
				'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			);
		}
	}
	$icon = get_site_icon_url( 512 );
	if ( $icon ) {
		return array(
			'url'    => $icon,
			'width'  => 512,
			'height' => 512,
			'alt'    => get_bloginfo( 'name' ),
		);
	}
	return null;
}

add_action( 'wp_head', 'nostek_core_head_tags', 2 );
function nostek_core_head_tags() {
	if ( nostek_core_seo_plugin_active() || is_404() || is_search() ) {
		return;
	}

	$tags        = array();
	$description = nostek_core_meta_description();
	$canonical   = nostek_core_canonical_url();
	$is_article  = is_singular( 'post' );
	$image       = nostek_core_share_image();
	$title       = is_front_page() ? get_bloginfo( 'name' ) : wp_get_document_title();

	if ( $description ) {
		$tags[] = array( 'name', 'description', $description );
	}
	// Core prints rel=canonical for singular views itself.
	if ( $canonical && ! is_singular() ) {
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
	}

	$tags[] = array( 'property', 'og:locale', 'id_ID' );
	$tags[] = array( 'property', 'og:site_name', get_bloginfo( 'name' ) );
	$tags[] = array( 'property', 'og:type', $is_article ? 'article' : 'website' );
	$tags[] = array( 'property', 'og:title', is_singular() && ! is_front_page() ? get_the_title( get_queried_object() ) : $title );
	if ( $description ) {
		$tags[] = array( 'property', 'og:description', $description );
	}
	if ( $canonical ) {
		$tags[] = array( 'property', 'og:url', $canonical );
	}
	if ( $image ) {
		$tags[] = array( 'property', 'og:image', $image['url'] );
		$tags[] = array( 'property', 'og:image:width', (string) $image['width'] );
		$tags[] = array( 'property', 'og:image:height', (string) $image['height'] );
		if ( $image['alt'] ) {
			$tags[] = array( 'property', 'og:image:alt', $image['alt'] );
		}
	}
	if ( $is_article ) {
		$post   = get_queried_object();
		$tags[] = array( 'property', 'article:published_time', get_post_time( 'c', true, $post ) );
		$tags[] = array( 'property', 'article:modified_time', get_post_modified_time( 'c', true, $post ) );
		$cats   = get_the_category( $post->ID );
		if ( $cats ) {
			$tags[] = array( 'property', 'article:section', $cats[0]->name );
		}
	}
	$tags[] = array( 'name', 'twitter:card', $image && $image['width'] >= 600 ? 'summary_large_image' : 'summary' );

	foreach ( $tags as $tag ) {
		printf( '<meta %s="%s" content="%s" />' . "\n", $tag[0], esc_attr( $tag[1] ), esc_attr( $tag[2] ) );
	}

	$schema = nostek_core_schema( $canonical, $description, $image );
	if ( $schema ) {
		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG )
		);
	}
}

/**
 * Organization on the front page, Article on single posts.
 *
 * @param string     $canonical   Canonical URL.
 * @param string     $description Meta description.
 * @param array|null $image       Share image.
 * @return array|null
 */
function nostek_core_schema( $canonical, $description, $image ) {
	$org = array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#organization' ),
		'name'  => nostek_core_setting( 'org_name' ) ? nostek_core_setting( 'org_name' ) : 'Nostek',
		'url'   => home_url( '/' ),
	);
	$logo = nostek_core_setting( 'logo_url' );
	if ( ! $logo && has_custom_logo() ) {
		$logo = wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' );
	}
	if ( ! $logo ) {
		$logo = get_site_icon_url( 512 );
	}
	if ( $logo ) {
		$org['logo'] = $logo;
	}
	$same_as = array_values( array_filter( array_map( 'esc_url_raw', preg_split( '/\s+/', (string) nostek_core_setting( 'same_as' ) ) ) ) );
	if ( $same_as ) {
		$org['sameAs'] = $same_as;
	}

	if ( is_front_page() ) {
		return array( '@context' => 'https://schema.org' ) + $org;
	}
	if ( ! is_singular( 'post' ) ) {
		return null;
	}

	$post    = get_queried_object();
	$article = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'Article',
		'headline'         => html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' ),
		'datePublished'    => get_post_time( 'c', true, $post ),
		'dateModified'     => get_post_modified_time( 'c', true, $post ),
		'mainEntityOfPage' => $canonical,
		'author'           => array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
		),
		'publisher'        => $org,
		'inLanguage'       => 'id-ID',
	);
	if ( $description ) {
		$article['description'] = $description;
	}
	if ( $image ) {
		$article['image'] = $image['url'];
	}
	return $article;
}
