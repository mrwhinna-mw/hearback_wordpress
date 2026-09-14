<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hb_add_decision_meta_boxes() {
	add_meta_box(
		'hb_decision_details',
		__( 'Public Docket: Item details', 'hearback-cabinet' ),
		'hb_render_decision_meta_box',
		'hb_decision',
		'normal',
		'high'
	);
	add_meta_box(
		'hb_decision_response',
		__( 'Public Docket: Outcome', 'hearback-cabinet' ),
		'hb_render_response_meta_box',
		'hb_decision',
		'normal',
		'high'
	);
	add_meta_box(
		'hb_decision_automation',
		__( 'Public Docket: Source', 'hearback-cabinet' ),
		'hb_render_automation_meta_box',
		'hb_decision',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'hb_add_decision_meta_boxes' );

function hb_render_decision_meta_box( $post ) {
	wp_nonce_field( 'hb_save_decision', 'hb_decision_nonce' );

	$decision_question = get_post_meta( $post->ID, '_hb_decision_question', true );
	$comment_open_at   = get_post_meta( $post->ID, '_hb_comment_open_at', true );
	$response_by_date  = get_post_meta( $post->ID, '_hb_response_by_date', true );
	$owner_name        = get_post_meta( $post->ID, '_hb_response_owner_name', true );
	$owner_role        = get_post_meta( $post->ID, '_hb_response_owner_role', true );
	$synthesis_at      = get_post_meta( $post->ID, '_hb_synthesis_published_at', true );
	$comments_enabled  = hb_comments_enabled( $post->ID );
	?>
	<p>
		<label>
			<input type="checkbox" name="hb_comments_enabled" value="1" <?php checked( $comments_enabled ); ?> />
			<strong><?php esc_html_e( 'Collect public comments on this item', 'hearback-cabinet' ); ?></strong>
		</label><br />
		<small><?php esc_html_e( 'Turn this off for purely informational items with no feedback period - the comment form, themes, and timeline are all hidden, and only the item details and eventual outcome are shown.', 'hearback-cabinet' ); ?></small>
	</p>
	<hr />
	<p>
		<label for="hb_decision_question"><strong><?php esc_html_e( 'The question', 'hearback-cabinet' ); ?></strong></label><br />
		<small><?php esc_html_e( 'What is actually being decided or addressed. The "Context" text above (the post editor) is the plain-language background.', 'hearback-cabinet' ); ?></small><br />
		<input type="text" id="hb_decision_question" name="hb_decision_question" class="widefat"
			value="<?php echo esc_attr( $decision_question ); ?>" />
	</p>
	<p>
		<label for="hb_comment_open_at"><strong><?php esc_html_e( 'Comments open at', 'hearback-cabinet' ); ?></strong></label><br />
		<input type="datetime-local" id="hb_comment_open_at" name="hb_comment_open_at"
			value="<?php echo esc_attr( $comment_open_at ); ?>" />
	</p>
	<p>
		<label for="hb_response_by_date"><strong><?php esc_html_e( 'Response due by', 'hearback-cabinet' ); ?></strong></label><br />
		<input type="date" id="hb_response_by_date" name="hb_response_by_date"
			value="<?php echo esc_attr( $response_by_date ); ?>" />
	</p>
	<p>
		<label for="hb_response_owner_name"><strong><?php esc_html_e( 'Response owner', 'hearback-cabinet' ); ?></strong></label><br />
		<small><?php esc_html_e( 'Named before comments open, so accountability is visible to residents, not just tracked internally.', 'hearback-cabinet' ); ?></small><br />
		<input type="text" id="hb_response_owner_name" name="hb_response_owner_name"
			placeholder="<?php esc_attr_e( 'Name', 'hearback-cabinet' ); ?>"
			value="<?php echo esc_attr( $owner_name ); ?>" style="width: 48%;" />
		<input type="text" id="hb_response_owner_role" name="hb_response_owner_role"
			placeholder="<?php esc_attr_e( 'Role, e.g. Committee Chair', 'hearback-cabinet' ); ?>"
			value="<?php echo esc_attr( $owner_role ); ?>" style="width: 48%;" />
	</p>
	<hr />
	<p>
		<label>
			<input type="checkbox" name="hb_synthesis_published" value="1" <?php checked( ! empty( $synthesis_at ) ); ?> />
			<strong><?php esc_html_e( 'Publish "What we heard"', 'hearback-cabinet' ); ?></strong>
		</label><br />
		<small>
			<?php
			if ( $synthesis_at ) {
				printf(
					/* translators: %s: date */
					esc_html__( 'Published %s. Uncheck and update to unpublish.', 'hearback-cabinet' ),
					esc_html( $synthesis_at )
				);
			} else {
				esc_html_e( 'Makes the themes below, and any featured quotes, visible to residents. Check the box and click Update to publish now.', 'hearback-cabinet' );
			}
			?>
		</small>
	</p>
	<?php
}

function hb_render_response_meta_box( $post ) {
	$status      = get_post_meta( $post->ID, '_hb_response_status', true );
	$rationale   = get_post_meta( $post->ID, '_hb_response_rationale', true );
	$next_step   = get_post_meta( $post->ID, '_hb_response_next_step', true );
	$response_at = get_post_meta( $post->ID, '_hb_response_published_at', true );
	$options     = hb_get_outcome_options();
	?>
	<p>
		<label for="hb_response_status"><strong><?php esc_html_e( 'Outcome', 'hearback-cabinet' ); ?></strong></label><br />
		<select id="hb_response_status" name="hb_response_status">
			<option value=""><?php esc_html_e( '— Not yet decided —', 'hearback-cabinet' ); ?></option>
			<?php foreach ( $options as $option ) : ?>
				<option value="<?php echo esc_attr( $option['key'] ); ?>" <?php selected( $status, $option['key'] ); ?>><?php echo esc_html( $option['label'] ); ?></option>
			<?php endforeach; ?>
		</select><br />
		<small>
			<?php
			printf(
				/* translators: %s: link to the settings page */
				wp_kses( __( 'Edit the list of options on the <a href="%s">Settings</a> page.', 'hearback-cabinet' ), array( 'a' => array( 'href' => array() ) ) ),
				esc_url( admin_url( 'edit.php?post_type=hb_decision&page=hb-settings' ) )
			);
			?>
		</small>
	</p>
	<p>
		<label for="hb_response_rationale"><strong><?php esc_html_e( 'Rationale', 'hearback-cabinet' ); ?></strong></label><br />
		<textarea id="hb_response_rationale" name="hb_response_rationale" class="widefat" rows="3"><?php echo esc_textarea( $rationale ); ?></textarea>
	</p>
	<p>
		<label for="hb_response_next_step"><strong><?php esc_html_e( 'Next step', 'hearback-cabinet' ); ?></strong></label><br />
		<small><?php esc_html_e( 'What happens next, and whether there is still anything a resident can do — a hearing date, a follow-up meeting, a deadline. This is shown to residents even after an outcome is posted.', 'hearback-cabinet' ); ?></small><br />
		<textarea id="hb_response_next_step" name="hb_response_next_step" class="widefat" rows="3"><?php echo esc_textarea( $next_step ); ?></textarea>
	</p>
	<hr />
	<p>
		<label>
			<input type="checkbox" name="hb_response_published" value="1" <?php checked( ! empty( $response_at ) ); ?> />
			<strong><?php esc_html_e( 'Publish outcome', 'hearback-cabinet' ); ?></strong>
		</label><br />
		<small>
			<?php
			if ( $response_at ) {
				printf(
					/* translators: %s: date */
					esc_html__( 'Published %s. Uncheck and update to unpublish.', 'hearback-cabinet' ),
					esc_html( $response_at )
				);
			} else {
				esc_html_e( 'Fill in the outcome and next step above first, then check this box and click Update.', 'hearback-cabinet' );
			}
			?>
		</small>
	</p>
	<?php
}

function hb_render_automation_meta_box( $post ) {
	$source     = get_post_meta( $post->ID, '_hb_source', true );
	$source_url = get_post_meta( $post->ID, '_hb_source_url', true );
	$reference  = get_post_meta( $post->ID, '_hb_external_reference', true );
	?>
	<p>
		<label for="hb_source"><?php esc_html_e( 'Source', 'hearback-cabinet' ); ?></label><br />
		<select id="hb_source" name="hb_source" class="widefat">
			<option value="manual" <?php selected( $source, 'manual' ); ?>><?php esc_html_e( 'Entered manually', 'hearback-cabinet' ); ?></option>
			<option value="scraped" <?php selected( $source, 'scraped' ); ?>><?php esc_html_e( 'Created from scraped agenda/transcript data', 'hearback-cabinet' ); ?></option>
		</select>
	</p>
	<p>
		<label for="hb_external_reference"><?php esc_html_e( 'Reference / case number', 'hearback-cabinet' ); ?></label><br />
		<small><?php esc_html_e( 'A stable ID (e.g. a case number) so a future ingestion script can recognize this is the same item when it resurfaces at a later meeting, instead of creating a duplicate.', 'hearback-cabinet' ); ?></small><br />
		<input type="text" id="hb_external_reference" name="hb_external_reference" class="widefat" value="<?php echo esc_attr( $reference ); ?>" />
	</p>
	<p>
		<label for="hb_source_url"><?php esc_html_e( 'Source document/URL', 'hearback-cabinet' ); ?></label><br />
		<input type="url" id="hb_source_url" name="hb_source_url" class="widefat" value="<?php echo esc_attr( $source_url ); ?>" placeholder="https://" />
	</p>
	<?php
}

/**
 * Applies decision field values from a $_POST-shaped array to a decision
 * post. Shared by the normal per-post save (below) and the one-page
 * Workspace screen, so both save exactly the same logic - a fix or a new
 * field only has to be written once.
 */
function hb_apply_decision_fields( $post_id, $data ) {
	$text_fields = array(
		'hb_decision_question'   => '_hb_decision_question',
		'hb_response_by_date'    => '_hb_response_by_date',
		'hb_response_owner_name' => '_hb_response_owner_name',
		'hb_response_owner_role' => '_hb_response_owner_role',
		'hb_external_reference'  => '_hb_external_reference',
	);
	foreach ( $text_fields as $field => $meta_key ) {
		if ( isset( $data[ $field ] ) ) {
			update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $data[ $field ] ) ) );
		}
	}

	if ( isset( $data['hb_comment_open_at'] ) ) {
		update_post_meta( $post_id, '_hb_comment_open_at', sanitize_text_field( wp_unslash( $data['hb_comment_open_at'] ) ) );
	}

	if ( isset( $data['hb_source_url'] ) ) {
		update_post_meta( $post_id, '_hb_source_url', esc_url_raw( wp_unslash( $data['hb_source_url'] ) ) );
	}
	if ( isset( $data['hb_source'] ) ) {
		$source = sanitize_key( $data['hb_source'] );
		update_post_meta( $post_id, '_hb_source', in_array( $source, array( 'manual', 'scraped' ), true ) ? $source : 'manual' );
	}

	update_post_meta( $post_id, '_hb_comments_enabled', empty( $data['hb_comments_enabled'] ) ? 0 : 1 );

	// Synthesis publish toggle: set the timestamp the first time it's
	// checked, clear it if unchecked - never overwritten on every save.
	$synthesis_now = get_post_meta( $post_id, '_hb_synthesis_published_at', true );
	if ( ! empty( $data['hb_synthesis_published'] ) ) {
		if ( ! $synthesis_now ) {
			update_post_meta( $post_id, '_hb_synthesis_published_at', current_time( 'mysql' ) );
		}
	} else {
		delete_post_meta( $post_id, '_hb_synthesis_published_at' );
	}

	if ( isset( $data['hb_response_status'] ) ) {
		$status         = sanitize_text_field( wp_unslash( $data['hb_response_status'] ) );
		$allowed_keys   = wp_list_pluck( hb_get_outcome_options(), 'key' );
		$allowed_keys[] = ''; // "Not yet decided".
		if ( in_array( $status, $allowed_keys, true ) ) {
			update_post_meta( $post_id, '_hb_response_status', $status );
		}
	}
	if ( isset( $data['hb_response_rationale'] ) ) {
		update_post_meta( $post_id, '_hb_response_rationale', sanitize_textarea_field( wp_unslash( $data['hb_response_rationale'] ) ) );
	}
	if ( isset( $data['hb_response_next_step'] ) ) {
		update_post_meta( $post_id, '_hb_response_next_step', sanitize_textarea_field( wp_unslash( $data['hb_response_next_step'] ) ) );
	}

	$response_now = get_post_meta( $post_id, '_hb_response_published_at', true );
	if ( ! empty( $data['hb_response_published'] ) ) {
		if ( ! $response_now ) {
			update_post_meta( $post_id, '_hb_response_published_at', current_time( 'mysql' ) );
		}
	} else {
		delete_post_meta( $post_id, '_hb_response_published_at' );
	}

	/**
	 * Fires whenever a decision's meta is saved (from either screen). An
	 * add-on plugin can hook this to, for example, email the response
	 * owner once '_hb_response_published_at' first gets set.
	 */
	do_action( 'hb_decision_saved', $post_id );
}

function hb_save_decision_meta( $post_id ) {
	if ( ! isset( $_POST['hb_decision_nonce'] ) || ! wp_verify_nonce( $_POST['hb_decision_nonce'], 'hb_save_decision' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	hb_apply_decision_fields( $post_id, $_POST );
}
add_action( 'save_post_hb_decision', 'hb_save_decision_meta' );
