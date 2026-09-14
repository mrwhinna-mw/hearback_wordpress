<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The /decisions/ archive lists every decision once, so it needs its
 * own template rather than a per-post the_content filter (which would
 * run once per post in the loop and render the cabinet N times).
 */
function hb_load_archive_template( $template ) {
	if ( is_post_type_archive( 'hb_decision' ) ) {
		return HB_PATH . 'templates/archive-hb_decision.php';
	}
	return $template;
}
add_filter( 'template_include', 'hb_load_archive_template' );
