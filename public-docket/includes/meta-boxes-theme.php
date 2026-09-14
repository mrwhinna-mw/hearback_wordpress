<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hb_add_theme_meta_box() {
	add_meta_box(
		'hb_theme_decision',
		__( 'HearBack: Belongs to decision', 'hearback-cabinet' ),
		'hb_render_theme_meta_box',
		'hb_theme',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'hb_add_theme_meta_box' );

function hb_render_theme_meta_box( $post ) {
	wp_nonce_field( 'hb_save_theme', 'hb_theme_nonce' );
	$current = hb_get_decision_id_for( $post->ID );

	$decisions = get_posts(
		array(
			'post_type'      => 'hb_decision',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	?>
	<p>
		<select name="hb_decision_id" class="widefat">
			<option value=""><?php esc_html_e( '— Select a decision —', 'hearback-cabinet' ); ?></option>
			<?php foreach ( $decisions as $decision ) : ?>
				<option value="<?php echo esc_attr( $decision->ID ); ?>" <?php selected( $current, $decision->ID ); ?>>
					<?php echo esc_html( $decision->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<small><?php esc_html_e( 'Use the Order field below to control display order within this decision.', 'hearback-cabinet' ); ?></small>
	</p>
	<?php
}

function hb_save_theme_meta( $post_id ) {
	if ( ! isset( $_POST['hb_theme_nonce'] ) || ! wp_verify_nonce( $_POST['hb_theme_nonce'], 'hb_save_theme' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['hb_decision_id'] ) ) {
		update_post_meta( $post_id, '_hb_decision_id', absint( $_POST['hb_decision_id'] ) );
	}
}
add_action( 'save_post_hb_theme', 'hb_save_theme_meta' );
