<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Finds an existing docket item for the same real-world matter.
 * Public Docket stores a case/license number in _hb_external_reference
 * precisely so a second upload mentioning "BZA# 21475" updates nothing
 * and creates nothing, instead of silently duplicating the item.
 */
function di_find_existing( $external_reference ) {
	if ( '' === trim( (string) $external_reference ) ) {
		return 0;
	}

	$found = get_posts(
		array(
			'post_type'        => 'hb_decision',
			'post_status'      => array( 'publish', 'pending', 'draft', 'future', 'private' ),
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => false,
			'meta_query'       => array(
				array(
					'key'     => '_hb_external_reference',
					'value'   => $external_reference,
					'compare' => '=',
				),
			),
		)
	);

	return empty( $found ) ? 0 : (int) $found[0];
}

function di_compose_content( $item ) {
	$parts = array();

	if ( ! empty( $item['context'] ) ) {
		$parts[] = $item['context'];
	}
	if ( ! empty( $item['address'] ) ) {
		$parts[] = sprintf( __( 'Location: %s', 'docket-ingest' ), $item['address'] );
	}
	if ( ! empty( $item['recommendation'] ) ) {
		$parts[] = sprintf( __( 'Committee recommendation: %s', 'docket-ingest' ), $item['recommendation'] );
	}

	return implode( "\n\n", $parts );
}

/**
 * Creates one pending docket item. Pending, never published: Public
 * Docket's Workspace already lists pending items, so approval happens
 * there, by a human, exactly as it does for a hand-typed item.
 *
 * @return int|WP_Error New post ID.
 */
function di_create_item( $item, $source_url = '', $source_file = '' ) {
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'hb_decision',
			'post_status'  => 'pending',
			'post_title'   => $item['title'],
			'post_content' => di_compose_content( $item ),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	update_post_meta( $post_id, '_hb_decision_question', $item['question'] );
	update_post_meta( $post_id, '_hb_comments_enabled', 1 );
	update_post_meta( $post_id, '_hb_source', 'scraped' );

	if ( ! empty( $item['external_reference'] ) ) {
		update_post_meta( $post_id, '_hb_external_reference', $item['external_reference'] );
	}
	if ( ! empty( $source_url ) ) {
		update_post_meta( $post_id, '_hb_source_url', esc_url_raw( $source_url ) );
	}

	// Our own meta, kept under a di_ prefix so Public Docket's schema
	// stays untouched. The quote is what makes the approval gate
	// meaningful - a reviewer can verify a case number against the
	// document instead of taking the model's word for it.
	if ( ! empty( $item['source_quote'] ) ) {
		update_post_meta( $post_id, '_di_source_quote', $item['source_quote'] );
	}
	if ( ! empty( $source_file ) ) {
		update_post_meta( $post_id, '_di_source_file', sanitize_text_field( $source_file ) );
	}
	update_post_meta( $post_id, '_di_ingested_at', current_time( 'mysql' ) );

	return $post_id;
}

/**
 * @return array {created: int[], skipped: array[], errors: string[]}
 */
function di_create_items( $items, $source_url = '', $source_file = '' ) {
	$created = array();
	$skipped = array();
	$errors  = array();

	foreach ( $items as $item ) {
		$existing = di_find_existing( $item['external_reference'] );
		if ( $existing ) {
			$skipped[] = array(
				'title'    => $item['title'],
				'ref'      => $item['external_reference'],
				'existing' => $existing,
			);
			continue;
		}

		$result = di_create_item( $item, $source_url, $source_file );
		if ( is_wp_error( $result ) ) {
			$errors[] = $result->get_error_message();
			continue;
		}
		$created[] = $result;
	}

	return compact( 'created', 'skipped', 'errors' );
}
