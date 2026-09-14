<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles POSTs from the public comment form (see render.php for the
 * form markup). Registered for both logged-in and logged-out visitors,
 * since residents never have accounts.
 */
function hb_handle_submission() {
	$decision_id = isset( $_POST['hb_decision_id'] ) ? absint( $_POST['hb_decision_id'] ) : 0;
	$redirect    = wp_get_referer() ? wp_get_referer() : home_url( '/' );

	if ( ! $decision_id || 'hb_decision' !== get_post_type( $decision_id ) ) {
		wp_safe_redirect( add_query_arg( 'hb_error', 'invalid', $redirect ) );
		exit;
	}

	if ( ! isset( $_POST['hb_submission_nonce_field'] )
		|| ! wp_verify_nonce( $_POST['hb_submission_nonce_field'], 'hb_submit_' . $decision_id ) ) {
		wp_safe_redirect( add_query_arg( 'hb_error', 'security', $redirect ) );
		exit;
	}

	// Honeypot: a real visitor never fills this hidden field in. If it's
	// filled, pretend success so a bot doesn't learn to look elsewhere.
	if ( ! empty( $_POST['hb_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'hb_submitted', '1', $redirect ) );
		exit;
	}

	$comment = isset( $_POST['hb_comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hb_comment'] ) ) : '';
	if ( '' === trim( $comment ) ) {
		wp_safe_redirect( add_query_arg( 'hb_error', 'empty', $redirect ) );
		exit;
	}

	$status = hb_get_decision_status( $decision_id );
	if ( 'open' !== $status ) {
		wp_safe_redirect( add_query_arg( 'hb_error', 'closed', $redirect ) );
		exit;
	}

	$name         = isset( $_POST['hb_name'] ) ? sanitize_text_field( wp_unslash( $_POST['hb_name'] ) ) : '';
	$email        = isset( $_POST['hb_email'] ) ? sanitize_email( wp_unslash( $_POST['hb_email'] ) ) : '';
	$neighborhood = isset( $_POST['hb_neighborhood'] ) ? sanitize_text_field( wp_unslash( $_POST['hb_neighborhood'] ) ) : '';
	$consent      = ! empty( $_POST['hb_consent'] );

	$submission_id = wp_insert_post(
		array(
			'post_type'    => 'hb_submission',
			'post_status'  => 'publish', // Internal type, not publicly queryable - just needs to exist.
			// phpcs:ignore -- excerpt only, never shown publicly.
			'post_title'   => wp_trim_words( $comment, 8 ),
			'post_content' => $comment,
		)
	);

	if ( is_wp_error( $submission_id ) || ! $submission_id ) {
		wp_safe_redirect( add_query_arg( 'hb_error', 'save_failed', $redirect ) );
		exit;
	}

	update_post_meta( $submission_id, '_hb_decision_id', $decision_id );
	update_post_meta( $submission_id, '_hb_name', $name );
	update_post_meta( $submission_id, '_hb_email', $email );
	update_post_meta( $submission_id, '_hb_neighborhood', $neighborhood );
	update_post_meta( $submission_id, '_hb_consent', $consent ? 1 : 0 );
	update_post_meta( $submission_id, '_hb_featured', 0 );
	update_post_meta( $submission_id, '_hb_source', 'form' );

	/**
	 * Fires after a new public submission is saved. An add-on could use
	 * this to notify the decision's response owner by email.
	 */
	do_action( 'hb_submission_created', $submission_id, $decision_id );

	wp_safe_redirect( add_query_arg( 'hb_submitted', '1', $redirect ) );
	exit;
}
add_action( 'admin_post_hb_submit', 'hb_handle_submission' );
add_action( 'admin_post_nopriv_hb_submit', 'hb_handle_submission' );
