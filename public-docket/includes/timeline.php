<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Derives the 4-stage public timeline from timestamps, the same way the
 * original HearBack pilot did: the stage isn't its own stored field,
 * it's computed from which dates are set. Only meaningful for items
 * that collect public comments - see hb_get_decision_status() for the
 * simpler 2-stage version used when they don't.
 *
 * Returns an array:
 *   'stages'  => the 4 stage labels, in order
 *   'current' => 1-based index of the current stage
 */
function hb_get_timeline( $decision_id ) {
	$stages = array(
		__( 'Open for input', 'hearback-cabinet' ),
		__( 'Synthesis published', 'hearback-cabinet' ),
		__( 'Board reviewing', 'hearback-cabinet' ),
		__( 'Outcome', 'hearback-cabinet' ),
	);

	$comment_open_at = get_post_meta( $decision_id, '_hb_comment_open_at', true );
	$synthesis_at    = get_post_meta( $decision_id, '_hb_synthesis_published_at', true );
	$response_at     = get_post_meta( $decision_id, '_hb_response_published_at', true );
	$now             = current_time( 'timestamp' );

	$current = 1;

	if ( $comment_open_at && strtotime( $comment_open_at ) <= $now ) {
		$current = 1; // Open for input.
	}
	if ( $synthesis_at ) {
		$current = 2; // Synthesis published.
		$current = 3; // Board reviewing is implied once synthesis is out.
	}
	if ( $response_at ) {
		$current = 4; // Outcome.
	}

	return array(
		'stages'  => $stages,
		'current' => $current,
	);
}

/**
 * A short machine-readable status, useful for filtering the docket
 * archive and for anything (like a future add-on) reading over REST.
 * Items with public comments turned off skip the 4-stage timeline
 * entirely and use a simpler two-state status instead, since "open for
 * input" and "board reviewing" don't apply to a purely informational
 * item.
 */
function hb_get_decision_status( $decision_id ) {
	if ( ! hb_comments_enabled( $decision_id ) ) {
		$response_at = get_post_meta( $decision_id, '_hb_response_published_at', true );
		return $response_at ? 'answered' : 'posted';
	}

	$timeline = hb_get_timeline( $decision_id );
	$map      = array(
		1 => 'open',
		2 => 'synthesis_published',
		3 => 'reviewing',
		4 => 'answered',
	);
	return $map[ $timeline['current'] ];
}

/**
 * Centralized display labels for every possible status, shared by the
 * public cabinet, the admin list columns, and the Workspace page so
 * the wording only has to be changed in one place.
 */
function hb_get_status_labels() {
	return array(
		'posted'               => __( 'Posted', 'hearback-cabinet' ),
		'open'                 => __( 'Open for input', 'hearback-cabinet' ),
		'synthesis_published'  => __( 'Synthesis published', 'hearback-cabinet' ),
		'reviewing'            => __( 'Board reviewing', 'hearback-cabinet' ),
		'answered'             => __( 'Answered', 'hearback-cabinet' ),
	);
}
