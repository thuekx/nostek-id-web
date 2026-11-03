<?php
/**
 * Keeps the slug an agent chose when its post or page is published later.
 *
 * WordPress drops the slug a user without publish rights sends for a
 * Pending Review post (wp_insert_post: "Don't allow contributors to set the
 * post slug for pending review posts"), so on publish the slug is rebuilt
 * from the title. Nostek Core stores the requested slug in the post meta
 * nostek_slug and applies it when the post is first published, so the
 * publisher can keep sending exactly {"status":"publish"}.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'nostek_core_register_slug_meta' );
function nostek_core_register_slug_meta() {
	foreach ( array( 'post', 'page' ) as $type ) {
		register_post_meta(
			$type,
			'nostek_slug',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_title',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		add_action( "rest_after_insert_{$type}", 'nostek_core_remember_slug', 10, 2 );
	}
}

/**
 * A slug sent with an unpublished post that WordPress did not keep goes to
 * nostek_slug (agents do not have to set the meta themselves).
 *
 * @param WP_Post         $post    Saved post.
 * @param WP_REST_Request $request REST request.
 */
function nostek_core_remember_slug( $post, $request ) {
	$slug = $request->get_param( 'slug' );
	if ( ! is_string( $slug ) || '' === $slug || in_array( $post->post_status, array( 'publish', 'future', 'private' ), true ) ) {
		return;
	}
	$slug = sanitize_title( $slug );
	if ( '' !== $slug && $post->post_name !== $slug ) {
		update_post_meta( $post->ID, 'nostek_slug', $slug );
	}
}

add_filter( 'wp_insert_post_data', 'nostek_core_apply_slug', 10, 2 );
/**
 * On the first publish, use nostek_slug unless the publisher sent a slug.
 *
 * @param array $data    Sanitized post data about to be saved.
 * @param array $postarr Raw post data (wp_update_post merges the stored post).
 * @return array
 */
function nostek_core_apply_slug( $data, $postarr ) {
	if ( empty( $postarr['ID'] ) || ! in_array( $data['post_status'], array( 'publish', 'future' ), true ) || ! empty( $postarr['post_name'] ) ) {
		return $data;
	}
	$old = get_post( (int) $postarr['ID'] );
	if ( ! $old || in_array( $old->post_status, array( 'publish', 'future', 'private' ), true ) ) {
		return $data;
	}
	$want = (string) get_post_meta( $old->ID, 'nostek_slug', true );
	if ( '' === $want ) {
		return $data;
	}
	$data['post_name'] = wp_unique_post_slug( $want, $old->ID, $data['post_status'], $data['post_type'], (int) $data['post_parent'] );
	return $data;
}
