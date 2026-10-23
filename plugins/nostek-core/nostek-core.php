<?php
/**
 * Plugin Name: Nostek Core
 * Description: Site structure for nostek.id that must survive a theme change: Topics taxonomy, breadcrumb and Topics list blocks.
 * Version: 0.1.1
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: Nostek ID
 * License: GPL-2.0-or-later
 * Text Domain: nostek-core
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NOSTEK_CORE_VERSION', '0.1.1' );

add_action( 'init', 'nostek_core_register_topics' );
/**
 * Topics = subject hubs (AI, Cybersecurity, ...). Journal sections stay as
 * native categories (News, Insights, Guides & Tutorials, Reviews, Founder's Note).
 */
function nostek_core_register_topics() {
	register_taxonomy(
		'topic',
		array( 'post' ),
		array(
			'labels'            => array(
				'name'              => __( 'Topics', 'nostek-core' ),
				'singular_name'     => __( 'Topic', 'nostek-core' ),
				'all_items'         => __( 'All Topics', 'nostek-core' ),
				'edit_item'         => __( 'Edit Topic', 'nostek-core' ),
				'add_new_item'      => __( 'Add New Topic', 'nostek-core' ),
				'search_items'      => __( 'Search Topics', 'nostek-core' ),
				'menu_name'         => __( 'Topics', 'nostek-core' ),
				// Hierarchical taxonomies fall back to category wording otherwise.
				'view_item'         => __( 'View Topic', 'nostek-core' ),
				'update_item'       => __( 'Update Topic', 'nostek-core' ),
				'new_item_name'     => __( 'New Topic Name', 'nostek-core' ),
				'parent_item'       => __( 'Parent Topic', 'nostek-core' ),
				'parent_item_colon' => __( 'Parent Topic:', 'nostek-core' ),
				'not_found'         => __( 'No topics found.', 'nostek-core' ),
				'no_terms'          => __( 'No topics', 'nostek-core' ),
				'back_to_items'     => __( '&larr; Go to Topics', 'nostek-core' ),
				'item_link'         => __( 'Topic Link', 'nostek-core' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => 'topics',
				'with_front' => false,
			),
			// Contributors may pick Topics but only editors create or rename them.
			'capabilities'      => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			),
		)
	);
}

add_action( 'init', 'nostek_core_register_roles' );
/**
 * "Nostek Agent": for virtual-team writers. Can write, upload images and pick
 * Topics, but cannot publish, so every article lands as Pending Review.
 * To let a trusted agent publish, change that user's role to Author.
 */
function nostek_core_register_roles() {
	if ( get_role( 'nostek_agent' ) ) {
		return;
	}
	add_role(
		'nostek_agent',
		__( 'Nostek Agent', 'nostek-core' ),
		array(
			'read'         => true,
			'edit_posts'   => true,
			'delete_posts' => true,
			'upload_files' => true,
		)
	);
}

add_action( 'init', 'nostek_core_register_source_id' );
/**
 * Stable id an agent gives each article, so re-sending the same article
 * updates it instead of creating a duplicate. WordPress drops the slug for
 * users who cannot publish, so the slug cannot serve as that id.
 */
function nostek_core_register_source_id() {
	register_post_meta(
		'post',
		'nostek_source_id',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}

add_filter( 'rest_post_query', 'nostek_core_rest_filter_source_id', 10, 2 );
/**
 * Allows GET /wp/v2/posts?nostek_source_id=<id> for users who can edit posts.
 *
 * @param array           $args    WP_Query arguments.
 * @param WP_REST_Request $request REST request.
 * @return array
 */
function nostek_core_rest_filter_source_id( $args, $request ) {
	$source_id = $request->get_param( 'nostek_source_id' );
	if ( is_string( $source_id ) && '' !== $source_id && current_user_can( 'edit_posts' ) ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'   => 'nostek_source_id',
				'value' => sanitize_text_field( $source_id ),
			),
		);
	}
	return $args;
}

register_activation_hook( __FILE__, 'nostek_core_activate' );
function nostek_core_activate() {
	nostek_core_register_topics();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

add_action( 'init', 'nostek_core_register_blocks' );
function nostek_core_register_blocks() {
	wp_register_script(
		'nostek-core-blocks',
		plugins_url( 'assets/blocks.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor' ),
		NOSTEK_CORE_VERSION,
		true
	);

	register_block_type(
		'nostek/breadcrumb',
		array(
			'api_version'     => 3,
			'title'           => __( 'Nostek Breadcrumb', 'nostek-core' ),
			'category'        => 'theme',
			'editor_script'   => 'nostek-core-blocks',
			'render_callback' => 'nostek_core_render_breadcrumb',
			'supports'        => array( 'html' => false ),
		)
	);

	register_block_type(
		'nostek/topics-list',
		array(
			'api_version'     => 3,
			'title'           => __( 'Nostek Topics List', 'nostek-core' ),
			'category'        => 'theme',
			'editor_script'   => 'nostek-core-blocks',
			'render_callback' => 'nostek_core_render_topics_list',
			'supports'        => array(
				'html'  => false,
				'align' => array( 'wide', 'full' ),
			),
		)
	);
}

/**
 * Breadcrumb trail as an array of [label, url|null], last item = current page.
 *
 * @return array<int, array{0: string, 1: string|null}>
 */
function nostek_core_breadcrumb_trail() {
	$trail = array( array( __( 'Home', 'nostek-core' ), home_url( '/' ) ) );

	$posts_page = (int) get_option( 'page_for_posts' );
	$journal    = $posts_page ? array( get_the_title( $posts_page ), get_permalink( $posts_page ) ) : null;

	if ( is_home() ) {
		$trail[] = array( $journal ? $journal[0] : __( 'Journal', 'nostek-core' ), null );
	} elseif ( is_singular( 'post' ) ) {
		if ( $journal ) {
			$trail[] = $journal;
		}
		$cats = get_the_category();
		if ( $cats ) {
			$trail[] = array( $cats[0]->name, get_category_link( $cats[0] ) );
		}
		// The post title is already the page heading; the ribbon stops at the section.
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && 'topic' === $term->taxonomy ) {
			$trail[] = array( __( 'Topics', 'nostek-core' ), null );
		} elseif ( $journal ) {
			$trail[] = $journal;
		}
		$trail[] = array( single_term_title( '', false ), null );
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $ancestor ) {
			$trail[] = array( get_the_title( $ancestor ), get_permalink( $ancestor ) );
		}
		$trail[] = array( get_the_title(), null );
	} elseif ( is_search() ) {
		$trail[] = array( __( 'Search', 'nostek-core' ), null );
	} elseif ( is_author() ) {
		$trail[] = array( get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) ), null );
	} elseif ( is_archive() ) {
		$trail[] = array( wp_strip_all_tags( get_the_archive_title() ), null );
	}

	return $trail;
}

function nostek_core_render_breadcrumb() {
	$trail = nostek_core_breadcrumb_trail();
	$parts = array();
	foreach ( $trail as $item ) {
		$parts[] = $item[1]
			? sprintf( '<a href="%s">%s</a>', esc_url( $item[1] ), esc_html( $item[0] ) )
			: sprintf( '<span aria-current="page">%s</span>', esc_html( $item[0] ) );
	}

	$wrapper = get_block_wrapper_attributes( array( 'class' => 'is-style-ribbon nostek-breadcrumb' ) );
	return sprintf(
		'<nav %s aria-label="%s">%s</nav>',
		$wrapper,
		esc_attr__( 'Breadcrumb', 'nostek-core' ),
		implode( '<span class="nostek-breadcrumb__sep" aria-hidden="true"> &#9656; </span>', $parts )
	);
}

function nostek_core_render_topics_list() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'topic',
			'hide_empty' => false,
			'parent'     => 0,
		)
	);
	if ( is_wp_error( $terms ) || ! $terms ) {
		return '';
	}

	$items = '';
	foreach ( $terms as $term ) {
		$items .= sprintf(
			'<li class="nostek-topics__item"><a href="%s"><span class="nostek-topics__name">%s</span></a>%s</li>',
			esc_url( get_term_link( $term ) ),
			esc_html( $term->name ),
			$term->description ? '<p class="nostek-topics__desc">' . esc_html( $term->description ) . '</p>' : ''
		);
	}

	$wrapper = get_block_wrapper_attributes( array( 'class' => 'nostek-topics' ) );
	return sprintf( '<ul %s>%s</ul>', $wrapper, $items );
}

add_action( 'wp_enqueue_scripts', 'nostek_core_styles' );
add_action( 'enqueue_block_editor_assets', 'nostek_core_styles' );
function nostek_core_styles() {
	wp_enqueue_style( 'nostek-core', plugins_url( 'assets/nostek-core.css', __FILE__ ), array(), NOSTEK_CORE_VERSION );
}
