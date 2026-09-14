<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Three post types. Internal identifiers (hb_decision, the _hb_ meta
 * prefix) stay as-is even though the user-facing label is now "Docket
 * Item" rather than "Decision" - renaming the internal slugs would
 * touch every file for no visible benefit and risks breaking things
 * that already reference them.
 */
function hb_register_post_types() {

	register_post_type(
		'hb_decision',
		array(
			'labels'       => array(
				'name'          => __( 'Docket Items', 'hearback-cabinet' ),
				'singular_name' => __( 'Docket Item', 'hearback-cabinet' ),
				'add_new_item'  => __( 'Add New Docket Item', 'hearback-cabinet' ),
				'edit_item'     => __( 'Edit Docket Item', 'hearback-cabinet' ),
				'all_items'     => __( 'All Docket Items', 'hearback-cabinet' ),
				'menu_name'     => __( 'Public Docket', 'hearback-cabinet' ),
			),
			'public'       => true,
			'has_archive'  => 'docket',
			'rewrite'      => array( 'slug' => 'docket' ),
			'menu_icon'    => 'dashicons-clipboard',
			'show_in_rest' => true,
			'rest_base'    => 'hb-decisions',
			// 'editor' holds the plain-language context (the "why this
			// item exists" text from the original schema).
			'supports'     => array( 'title', 'editor' ),
		)
	);

	register_post_type(
		'hb_theme',
		array(
			'labels'       => array(
				'name'          => __( 'Themes', 'hearback-cabinet' ),
				'singular_name' => __( 'Theme', 'hearback-cabinet' ),
				'add_new_item'  => __( 'Add New Theme', 'hearback-cabinet' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'edit.php?post_type=hb_decision',
			'show_in_rest' => true,
			'rest_base'    => 'hb-themes',
			'supports'     => array( 'title', 'editor', 'page-attributes' ),
		)
	);

	register_post_type(
		'hb_submission',
		array(
			'labels'       => array(
				'name'          => __( 'Submissions', 'hearback-cabinet' ),
				'singular_name' => __( 'Submission', 'hearback-cabinet' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'edit.php?post_type=hb_decision',
			// Residents create these anonymously through the front-end
			// form, not the block editor - but REST stays on so an
			// authenticated admin-side add-on can still query them.
			// WordPress restricts read/write on a non-public post
			// type's REST endpoint to logged-in users who can edit it,
			// so this does not expose submissions publicly.
			'show_in_rest' => true,
			'rest_base'    => 'hb-submissions',
			'supports'     => array( 'title' ),
		)
	);
}
add_action( 'init', 'hb_register_post_types' );

/**
 * Registers post meta with show_in_rest, which WordPress otherwise
 * withholds by default for any underscore-prefixed key. Without this,
 * a future ingestion script (agenda/transcript scraper) would find
 * every custom field simply missing from the REST response even
 * though the post types themselves are REST-enabled.
 *
 * Only the docket-item fields are genuinely public information;
 * submission fields (name, email, neighborhood, consent, featured)
 * stay off the public REST surface entirely, matching this plugin's
 * rule that email is never exposed anywhere outside the logged-in
 * admin screens.
 */
function hb_register_meta_fields() {
	$decision_fields = array(
		'_hb_decision_question'      => 'string',
		'_hb_comment_open_at'        => 'string',
		'_hb_response_by_date'       => 'string',
		'_hb_response_owner_name'    => 'string',
		'_hb_response_owner_role'    => 'string',
		'_hb_synthesis_published_at' => 'string',
		'_hb_response_status'        => 'string',
		'_hb_response_rationale'     => 'string',
		'_hb_response_next_step'     => 'string',
		'_hb_response_published_at'  => 'string',
		'_hb_comments_enabled'       => 'boolean',
		// Automation-readiness: which real-world matter a scraped
		// record maps to, and where it came from, so an ingestion
		// script can check "does this already exist?" before creating
		// a duplicate, and an admin reviewing a pending item can see
		// its source.
		'_hb_external_reference'     => 'string',
		'_hb_source'                 => 'string',
		'_hb_source_url'             => 'string',
	);
	foreach ( $decision_fields as $key => $type ) {
		register_post_meta(
			'hb_decision',
			$key,
			array(
				'type'         => $type,
				'single'       => true,
				'show_in_rest' => true,
			)
		);
	}

	// Submission source (form vs. transcript) is useful for an
	// ingestion pipeline to record, but stays private - not
	// show_in_rest - same as the rest of a submission's fields.
	register_post_meta(
		'hb_submission',
		'_hb_source',
		array(
			'type'   => 'string',
			'single' => true,
		)
	);
}
add_action( 'init', 'hb_register_meta_fields' );

/**
 * Helper used throughout the plugin: every hb_theme / hb_submission
 * stores which decision it belongs to in '_hb_decision_id' rather than
 * post_parent, so this is the one place that relationship is read.
 */
function hb_get_decision_id_for( $post_id ) {
	return (int) get_post_meta( $post_id, '_hb_decision_id', true );
}

/**
 * Whether public comments are collected for this docket item. Absent
 * meta defaults to enabled, so items created before this option
 * existed keep working exactly as before.
 */
function hb_comments_enabled( $decision_id ) {
	$value = get_post_meta( $decision_id, '_hb_comments_enabled', true );
	return '' === $value ? true : (bool) $value;
}
