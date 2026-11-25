<?php
/**
 * Trash rules for the two virtual-team roles (roles v3, WEB-45).
 *
 * - Nostek Publisher may move any author's draft / pending / auto-draft post
 *   or page to the Trash (Nostek Agent: only its own, as before).
 * - A published, scheduled or private item never goes to the Trash through
 *   these roles; taking one down stays "back to draft".
 * - These roles never delete permanently (REST `force=true`, emptying the
 *   Trash, wp_delete_post): the Trash is the only way, so a person can
 *   always restore. Restoring from the Trash stays allowed.
 * - Every trash and restore by these roles is written to post meta
 *   `_nostek_trashed_by` / `_nostek_untrashed_by` and to the PHP error log.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NOSTEK_CORE_GUARDED_ROLES     = array( 'nostek_publisher', 'nostek_agent' );
const NOSTEK_CORE_TRASHABLE_STATUS  = array( 'draft', 'pending', 'auto-draft' );

/**
 * True when the user holds one of the virtual-team roles and nothing that
 * already allows more (an editor or administrator keeps core behaviour).
 *
 * @param int $user_id User id.
 */
function nostek_core_is_guarded_user( $user_id ) {
	$user = get_userdata( (int) $user_id );
	if ( ! $user ) {
		return false;
	}
	$roles = (array) $user->roles;
	if ( ! array_intersect( $roles, NOSTEK_CORE_GUARDED_ROLES ) ) {
		return false;
	}
	return ! array_intersect( $roles, array( 'administrator', 'editor' ) );
}

add_filter( 'map_meta_cap', 'nostek_core_trash_meta_cap', 10, 4 );
/**
 * delete_post / delete_page for a guarded user: live items are never
 * allowed; drafts, pending and items already in the Trash (restore) keep the
 * normal mapping (own items: delete_posts, others: delete_others_posts).
 *
 * @param string[] $caps    Primitive caps core mapped.
 * @param string   $cap     Meta cap asked for.
 * @param int      $user_id User id.
 * @param array    $args    [0] = post id.
 * @return string[]
 */
function nostek_core_trash_meta_cap( $caps, $cap, $user_id, $args ) {
	if ( 'delete_post' !== $cap && 'delete_page' !== $cap ) {
		return $caps;
	}
	if ( empty( $args[0] ) || ! nostek_core_is_guarded_user( $user_id ) ) {
		return $caps;
	}
	$post = get_post( (int) $args[0] );
	if ( ! $post ) {
		return $caps;
	}
	if ( 'trash' === $post->post_status || in_array( $post->post_status, NOSTEK_CORE_TRASHABLE_STATUS, true ) ) {
		return $caps;
	}
	return array( 'do_not_allow' );
}

add_filter( 'pre_delete_post', 'nostek_core_block_permanent_delete', 10, 3 );
/**
 * No permanent delete by a guarded user, whatever the route.
 *
 * @param WP_Post|false|null $delete       Short-circuit value.
 * @param WP_Post            $post         Post.
 * @param bool               $force_delete Force flag.
 * @return WP_Post|false|null
 */
function nostek_core_block_permanent_delete( $delete, $post, $force_delete ) {
	if ( null !== $delete || ! is_user_logged_in() || ! nostek_core_is_guarded_user( get_current_user_id() ) ) {
		return $delete;
	}
	if ( $post instanceof WP_Post && in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
		error_log( sprintf( 'nostek-core: REFUSE permanent delete post=%d type=%s user=%d', $post->ID, $post->post_type, get_current_user_id() ) );
		return false;
	}
	return $delete;
}

add_filter( 'pre_trash_post', 'nostek_core_block_live_trash', 10, 3 );
/**
 * Second fence behind map_meta_cap: a live item is never trashed by a
 * guarded user, even through code that skips the capability check.
 *
 * @param bool|null $trash           Short-circuit value.
 * @param WP_Post   $post            Post.
 * @param string    $previous_status Status before the trash.
 * @return bool|null
 */
function nostek_core_block_live_trash( $trash, $post, $previous_status = '' ) {
	if ( null !== $trash || ! is_user_logged_in() || ! nostek_core_is_guarded_user( get_current_user_id() ) ) {
		return $trash;
	}
	$status = $previous_status ? $previous_status : $post->post_status;
	if ( in_array( $post->post_type, array( 'post', 'page' ), true ) && ! in_array( $status, NOSTEK_CORE_TRASHABLE_STATUS, true ) ) {
		return false;
	}
	return $trash;
}

add_filter( 'rest_pre_dispatch', 'nostek_core_rest_refuse_force', 10, 3 );
/**
 * REST DELETE …?force=true on a post or page by a guarded user = 403 before
 * core runs (otherwise core answers 500 "cannot be deleted").
 *
 * @param mixed           $result  Short-circuit value.
 * @param WP_REST_Server  $server  Server.
 * @param WP_REST_Request $request Request.
 * @return mixed
 */
function nostek_core_rest_refuse_force( $result, $server, $request ) {
	if ( null !== $result || 'DELETE' !== $request->get_method() ) {
		return $result;
	}
	if ( ! preg_match( '#^/wp/v2/(posts|pages)/\d+$#', $request->get_route() ) ) {
		return $result;
	}
	if ( ! rest_sanitize_boolean( $request->get_param( 'force' ) ) || ! nostek_core_is_guarded_user( get_current_user_id() ) ) {
		return $result;
	}
	return new WP_Error(
		'nostek_no_permanent_delete',
		__( 'Agents move items to the Trash only; a person deletes permanently.', 'nostek-core' ),
		array( 'status' => 403 )
	);
}

/**
 * Audit record: post meta + one log line.
 *
 * @param int    $post_id Post id.
 * @param string $key     Meta key.
 * @param string $action  trash | untrash.
 * @param string $old     Status before the action.
 */
function nostek_core_trash_audit( $post_id, $key, $action, $old ) {
	$user_id = get_current_user_id();
	if ( ! $user_id || ! nostek_core_is_guarded_user( $user_id ) ) {
		return;
	}
	$user = get_userdata( $user_id );
	$rec  = array(
		'post'       => (int) $post_id,
		'type'       => get_post_type( $post_id ),
		'old_status' => (string) $old,
		'user'       => $user ? $user->user_login : (string) $user_id,
		'time'       => wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'Asia/Jakarta' ) ) . ' WIB',
	);
	update_post_meta( $post_id, $key, wp_slash( wp_json_encode( $rec ) ) );
	error_log( sprintf( 'nostek-core: %s post=%d type=%s old_status=%s user=%s time=%s', $action, $rec['post'], $rec['type'], $rec['old_status'], $rec['user'], $rec['time'] ) );
}

add_action( 'trashed_post', 'nostek_core_log_trashed' );
/**
 * @param int $post_id Post id.
 */
function nostek_core_log_trashed( $post_id ) {
	nostek_core_trash_audit( $post_id, '_nostek_trashed_by', 'trash', (string) get_post_meta( $post_id, '_wp_trash_meta_status', true ) );
}

add_action( 'untrash_post', 'nostek_core_log_untrashed' );
/**
 * Runs before core restores the item, so the old status is still "trash".
 *
 * @param int $post_id Post id.
 */
function nostek_core_log_untrashed( $post_id ) {
	nostek_core_trash_audit( $post_id, '_nostek_untrashed_by', 'untrash', 'trash' );
}
