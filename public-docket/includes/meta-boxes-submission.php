<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hb_add_submission_meta_boxes() {
	add_meta_box(
		'hb_submission_review',
		__( 'HearBack: Review submission', 'public-docket' ),
		'hb_render_submission_review_box',
		'hb_submission',
		'normal',
		'high'
	);
	add_meta_box(
		'hb_submission_source',
		__( 'HearBack: Submitted by', 'public-docket' ),
		'hb_render_submission_source_box',
		'hb_submission',
		'side',
		'default'
	);
	// Hide the default "Publish" box's confusing draft/publish concept
	// for this internal-only post type isn't necessary to remove; we
	// just don't rely on post_status for anything in this plugin.
}
add_action( 'add_meta_boxes', 'hb_add_submission_meta_boxes' );

function hb_render_submission_review_box( $post ) {
	wp_nonce_field( 'hb_save_submission', 'hb_submission_nonce' );

	$decision_id = hb_get_decision_id_for( $post->ID );
	$theme_id    = (int) get_post_meta( $post->ID, '_hb_theme_id', true );
	$consent     = (bool) get_post_meta( $post->ID, '_hb_consent', true );
	$featured    = (bool) get_post_meta( $post->ID, '_hb_featured', true );

	$themes = array();
	if ( $decision_id ) {
		$themes = get_posts(
			array(
				'post_type'      => 'hb_theme',
				'posts_per_page' => -1,
				'meta_key'       => '_hb_decision_id',
				'meta_value'     => $decision_id,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
	}
	?>
	<p>
		<strong><?php esc_html_e( 'Comment', 'public-docket' ); ?></strong><br />
		<?php echo wpautop( esc_html( $post->post_content ) ); // phpcs:ignore -- plain text, escaped above. ?>
	</p>
	<p>
		<label for="hb_theme_id"><strong><?php esc_html_e( 'Assign to theme', 'public-docket' ); ?></strong></label><br />
		<?php if ( empty( $themes ) ) : ?>
			<em><?php esc_html_e( 'No themes exist yet for this decision. Create one under Themes first.', 'public-docket' ); ?></em>
		<?php else : ?>
			<select id="hb_theme_id" name="hb_theme_id">
				<option value=""><?php esc_html_e( '— Unassigned —', 'public-docket' ); ?></option>
				<?php foreach ( $themes as $theme ) : ?>
					<option value="<?php echo esc_attr( $theme->ID ); ?>" <?php selected( $theme_id, $theme->ID ); ?>>
						<?php echo esc_html( $theme->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>
	</p>
	<p>
		<label>
			<input type="checkbox" disabled <?php checked( $consent ); ?> />
			<?php esc_html_e( 'Author consented to a public, anonymized quote', 'public-docket' ); ?>
		</label><br />
		<small><?php esc_html_e( 'Set by the resident at submission time — not editable here.', 'public-docket' ); ?></small>
	</p>
	<p>
		<label>
			<input type="checkbox" id="hb_featured" name="hb_featured" value="1" <?php checked( $featured ); ?> <?php disabled( ! $consent ); ?> />
			<strong><?php esc_html_e( 'Feature as a public quote', 'public-docket' ); ?></strong>
		</label><br />
		<small>
			<?php
			echo $consent
				? esc_html__( 'Shown (anonymized, with neighborhood if given) in "What we heard" once the synthesis is published.', 'public-docket' )
				: esc_html__( 'Disabled: this author did not consent to a public quote.', 'public-docket' );
			?>
		</small>
	</p>
	<?php
}

function hb_render_submission_source_box( $post ) {
	$name         = get_post_meta( $post->ID, '_hb_name', true );
	$email        = get_post_meta( $post->ID, '_hb_email', true );
	$neighborhood = get_post_meta( $post->ID, '_hb_neighborhood', true );
	?>
	<p><strong><?php esc_html_e( 'Name', 'public-docket' ); ?>:</strong> <?php echo esc_html( $name ? $name : __( '(not given)', 'public-docket' ) ); ?></p>
	<p><strong><?php esc_html_e( 'Email', 'public-docket' ); ?>:</strong> <?php echo esc_html( $email ? $email : __( '(not given)', 'public-docket' ) ); ?></p>
	<p><strong><?php esc_html_e( 'Neighborhood', 'public-docket' ); ?>:</strong> <?php echo esc_html( $neighborhood ? $neighborhood : __( '(not given)', 'public-docket' ) ); ?></p>
	<p><small><?php esc_html_e( 'The email address above is only ever shown here, to logged-in admins - it is never rendered on any public page.', 'public-docket' ); ?></small></p>
	<?php
}

function hb_save_submission_meta( $post_id ) {
	if ( ! isset( $_POST['hb_submission_nonce'] ) || ! wp_verify_nonce( $_POST['hb_submission_nonce'], 'hb_save_submission' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['hb_theme_id'] ) ) {
		$theme_id = absint( $_POST['hb_theme_id'] );
		if ( $theme_id ) {
			update_post_meta( $post_id, '_hb_theme_id', $theme_id );
		} else {
			delete_post_meta( $post_id, '_hb_theme_id' );
		}
	}

	// A submission can only be featured if it has consent - enforced
	// here server-side, not just by disabling the checkbox in JS.
	$consent = (bool) get_post_meta( $post_id, '_hb_consent', true );
	$featured = $consent && ! empty( $_POST['hb_featured'] );
	update_post_meta( $post_id, '_hb_featured', $featured ? 1 : 0 );
}
add_action( 'save_post_hb_submission', 'hb_save_submission_meta' );
