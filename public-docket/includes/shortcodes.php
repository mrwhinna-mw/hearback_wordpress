<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [public_docket] - drop this on any page to list every docket item.
 * Also used automatically for the hb_decision archive page.
 */
add_shortcode( 'public_docket', 'hb_render_cabinet' );

/**
 * [public_docket_item id="123"] - embed one item's full page (context,
 * timeline, synthesis, outcome, form) outside its own permalink, e.g.
 * featured on a homepage.
 */
add_shortcode(
	'public_docket_item',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts );
		$id   = absint( $atts['id'] );
		if ( ! $id || 'hb_decision' !== get_post_type( $id ) ) {
			return '';
		}
		return hb_render_decision( $id );
	}
);
