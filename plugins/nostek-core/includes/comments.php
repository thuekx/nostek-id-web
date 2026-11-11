<?php
/**
 * Comments for signed-in readers only (WEB-34, owner decision D,
 * 2026-11-11): readers sign in with Google, Facebook or X through the
 * Nextend Social Login plugin, which creates a Subscriber account with a
 * verified email. This file keeps the rest closed:
 *
 * - the plain wp-login.php register form is shut, so "Anyone can
 *   register" (needed by the social plugin) does not open a bot door;
 * - Subscribers never see the dashboard or the admin bar;
 * - the login prompt above the comment form is in Indonesian.
 *
 * @package NostekCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'login_form_register', 'nostek_core_close_register_form' );
/**
 * The classic username + email sign-up page sends people to the login page.
 * Social sign-in creates accounts through its own callback, not this form.
 */
function nostek_core_close_register_form() {
	wp_safe_redirect( wp_login_url() );
	exit;
}

add_filter( 'register', '__return_empty_string' );

add_filter( 'show_admin_bar', 'nostek_core_admin_bar_for_staff' );
function nostek_core_admin_bar_for_staff( $show ) {
	return $show && current_user_can( 'edit_posts' );
}

add_action( 'admin_init', 'nostek_core_keep_readers_out_of_admin' );
function nostek_core_keep_readers_out_of_admin() {
	if ( wp_doing_ajax() || current_user_can( 'edit_posts' ) || ! is_user_logged_in() ) {
		return;
	}
	// profile.php stays reachable so a reader can delete or change their own details.
	if ( isset( $GLOBALS['pagenow'] ) && 'profile.php' === $GLOBALS['pagenow'] ) {
		return;
	}
	wp_safe_redirect( home_url( '/' ) );
	exit;
}

add_filter( 'comment_form_defaults', 'nostek_core_comment_form_text' );
function nostek_core_comment_form_text( $defaults ) {
	$defaults['title_reply']         = __( 'Tulis komentar', 'nostek-core' );
	$defaults['label_submit']        = __( 'Kirim komentar', 'nostek-core' );
	$defaults['comment_notes_after'] = '<p class="nk-comment-note">' . esc_html__( 'Komentar pertama ditinjau dulu sebelum tampil. Tetap sopan dan sesuai topik.', 'nostek-core' ) . '</p>';
	$defaults['must_log_in']         = '<p class="must-log-in nk-comment-login">' . esc_html__( 'Masuk dengan Google, Facebook atau X untuk berkomentar. Email Anda tidak ditampilkan.', 'nostek-core' ) . '</p>';
	return $defaults;
}
