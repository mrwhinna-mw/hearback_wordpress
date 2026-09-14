<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---- Decisions list: show computed status + submission count ---- */

add_filter(
	'manage_hb_decision_posts_columns',
	function ( $columns ) {
		$columns['hb_status']  = __( 'Status', 'hearback-cabinet' );
		$columns['hb_count']   = __( 'Submissions', 'hearback-cabinet' );
		return $columns;
	}
);
add_action(
	'manage_hb_decision_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'hb_status' === $column ) {
			$labels = hb_get_status_labels();
			echo esc_html( $labels[ hb_get_decision_status( $post_id ) ] );
		}
		if ( 'hb_count' === $column ) {
			$count = count(
				get_posts(
					array(
						'post_type'      => 'hb_submission',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'meta_key'       => '_hb_decision_id',
						'meta_value'     => $post_id,
					)
				)
			);
			echo esc_html( $count );
		}
	},
	10,
	2
);

/* ---- Submissions list: show which decision, theme, and featured status ---- */

add_filter(
	'manage_hb_submission_posts_columns',
	function ( $columns ) {
		unset( $columns['title'] );
		$columns['hb_comment']  = __( 'Comment', 'hearback-cabinet' );
		$columns['hb_decision'] = __( 'Decision', 'hearback-cabinet' );
		$columns['hb_theme']    = __( 'Theme', 'hearback-cabinet' );
		$columns['hb_featured'] = __( 'Featured', 'hearback-cabinet' );
		return $columns;
	}
);
add_action(
	'manage_hb_submission_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'hb_comment' === $column ) {
			$post = get_post( $post_id );
			echo esc_html( wp_trim_words( $post->post_content, 12 ) );
		}
		if ( 'hb_decision' === $column ) {
			$decision_id = hb_get_decision_id_for( $post_id );
			echo $decision_id ? esc_html( get_the_title( $decision_id ) ) : '—';
		}
		if ( 'hb_theme' === $column ) {
			$theme_id = (int) get_post_meta( $post_id, '_hb_theme_id', true );
			echo $theme_id ? esc_html( get_the_title( $theme_id ) ) : esc_html__( 'Unassigned', 'hearback-cabinet' );
		}
		if ( 'hb_featured' === $column ) {
			echo get_post_meta( $post_id, '_hb_featured', true ) ? '★' : '';
		}
	},
	10,
	2
);

/* ---- Themes list: show which decision it belongs to ---- */

add_filter(
	'manage_hb_theme_posts_columns',
	function ( $columns ) {
		$columns['hb_decision'] = __( 'Decision', 'hearback-cabinet' );
		return $columns;
	}
);
add_action(
	'manage_hb_theme_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'hb_decision' === $column ) {
			$decision_id = hb_get_decision_id_for( $post_id );
			echo $decision_id ? esc_html( get_the_title( $decision_id ) ) : '—';
		}
	},
	10,
	2
);
