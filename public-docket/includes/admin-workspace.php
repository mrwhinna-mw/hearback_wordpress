<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Workspace: everything a board member needs to run one docket
 * item, on one page. It's built entirely on top of the same post types
 * and meta keys as the per-post-type screens elsewhere in this plugin
 * - it doesn't replace them, it's just a friendlier front door.
 */
$GLOBALS['hb_workspace_hook'] = null;

function hb_register_workspace_menu() {
	// Position 1 puts it right under "Add New" in the Public Docket
	// menu, ahead of Themes and Submissions - it's meant to be the
	// default place a board member lands.
	$GLOBALS['hb_workspace_hook'] = add_submenu_page(
		'edit.php?post_type=hb_decision',
		__( 'Docket workspace', 'hearback-cabinet' ),
		__( 'Workspace', 'hearback-cabinet' ),
		'edit_posts',
		'hb-workspace',
		'hb_render_workspace_page',
		1
	);
}
add_action( 'admin_menu', 'hb_register_workspace_menu' );

function hb_enqueue_workspace_assets( $hook ) {
	if ( $hook === $GLOBALS['hb_workspace_hook'] ) {
		wp_enqueue_style( 'hearback-workspace', HB_URL . 'assets/css/workspace.css', array(), HB_VERSION );
		wp_enqueue_script( 'hearback-workspace', HB_URL . 'assets/js/workspace.js', array(), HB_VERSION, true );
	}
}
add_action( 'admin_enqueue_scripts', 'hb_enqueue_workspace_assets' );

function hb_render_workspace_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'hearback-cabinet' ) );
	}

	// Include pending/draft items, not just published ones, so an item
	// created by a future automated ingestion script - which lands as
	// "pending" until a human approves it - is actually findable here
	// rather than invisible to the person who needs to review it.
	$decisions = get_posts(
		array(
			'post_type'      => 'hb_decision',
			'post_status'    => array( 'publish', 'pending', 'draft' ),
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$current_id = isset( $_GET['decision_id'] ) ? absint( $_GET['decision_id'] ) : 0;
	if ( ! $current_id && ! empty( $decisions ) ) {
		$current_id = $decisions[0]->ID;
	}

	echo '<div class="wrap hb-workspace">';
	echo '<h1>' . esc_html__( 'Docket workspace', 'hearback-cabinet' ) . '</h1>';

	if ( isset( $_GET['hb_saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved.', 'hearback-cabinet' ) . '</p></div>';
	}

	if ( empty( $decisions ) ) {
		printf(
			'<p>%s <a href="%s">%s</a></p>',
			esc_html__( 'No docket items yet.', 'hearback-cabinet' ),
			esc_url( admin_url( 'post-new.php?post_type=hb_decision' ) ),
			esc_html__( 'Create your first one', 'hearback-cabinet' )
		);
		echo '</div>';
		return;
	}

	echo '<form method="get" class="hb-decision-switcher">';
	echo '<input type="hidden" name="post_type" value="hb_decision" />';
	echo '<input type="hidden" name="page" value="hb-workspace" />';
	echo '<label for="hb-decision-select"><strong>' . esc_html__( 'Item:', 'hearback-cabinet' ) . '</strong></label> ';
	echo '<select id="hb-decision-select" name="decision_id" onchange="this.form.submit()">';
	foreach ( $decisions as $d ) {
		$title = $d->post_title ? $d->post_title : __( '(untitled)', 'hearback-cabinet' );
		if ( 'publish' !== $d->post_status ) {
			/* translators: 1: item title, 2: status (e.g. Pending) */
			$title = sprintf( __( '%1$s (%2$s)', 'hearback-cabinet' ), $title, get_post_status_object( $d->post_status )->label );
		}
		printf(
			'<option value="%d" %s>%s</option>',
			(int) $d->ID,
			selected( $current_id, $d->ID, false ),
			esc_html( $title )
		);
	}
	echo '</select>';
	echo ' <a href="' . esc_url( admin_url( 'post-new.php?post_type=hb_decision' ) ) . '" class="button">' . esc_html__( '+ New item', 'hearback-cabinet' ) . '</a>';
	echo '</form>';

	if ( $current_id ) {
		hb_render_workspace_form( $current_id );
	}

	echo '</div>';
}

function hb_render_workspace_form( $decision_id ) {
	$decision = get_post( $decision_id );
	if ( ! $decision || 'hb_decision' !== $decision->post_type ) {
		return;
	}

	$decision_question = get_post_meta( $decision_id, '_hb_decision_question', true );
	$comment_open_at   = get_post_meta( $decision_id, '_hb_comment_open_at', true );
	$response_by_date  = get_post_meta( $decision_id, '_hb_response_by_date', true );
	$owner_name        = get_post_meta( $decision_id, '_hb_response_owner_name', true );
	$owner_role        = get_post_meta( $decision_id, '_hb_response_owner_role', true );
	$synthesis_at      = get_post_meta( $decision_id, '_hb_synthesis_published_at', true );
	$response_status   = get_post_meta( $decision_id, '_hb_response_status', true );
	$rationale         = get_post_meta( $decision_id, '_hb_response_rationale', true );
	$next_step         = get_post_meta( $decision_id, '_hb_response_next_step', true );
	$response_at       = get_post_meta( $decision_id, '_hb_response_published_at', true );
	$comments_enabled  = hb_comments_enabled( $decision_id );
	$source            = get_post_meta( $decision_id, '_hb_source', true );
	$source_url        = get_post_meta( $decision_id, '_hb_source_url', true );
	$reference         = get_post_meta( $decision_id, '_hb_external_reference', true );
	$outcome_options   = hb_get_outcome_options();

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

	$submissions = get_posts(
		array(
			'post_type'      => 'hb_submission',
			'posts_per_page' => -1,
			'meta_key'       => '_hb_decision_id',
			'meta_value'     => $decision_id,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$status_labels = hb_get_status_labels();
	$status_label  = $status_labels[ hb_get_decision_status( $decision_id ) ];
	?>
	<p class="hb-workspace-status">
		<?php
		printf(
			/* translators: %s: current status label */
			esc_html__( 'Current status: %s', 'hearback-cabinet' ),
			'<strong>' . esc_html( $status_label ) . '</strong>'
		);
		?>
		<?php if ( 'publish' !== $decision->post_status ) : ?>
			&mdash; <em><?php echo esc_html( get_post_status_object( $decision->post_status )->label ); ?>,
			<?php esc_html_e( 'not yet visible to residents', 'hearback-cabinet' ); ?></em>
		<?php endif; ?>
	</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hb-workspace-form">
		<input type="hidden" name="action" value="hb_save_workspace" />
		<input type="hidden" name="decision_id" value="<?php echo esc_attr( $decision_id ); ?>" />
		<?php wp_nonce_field( 'hb_save_workspace_' . $decision_id, 'hb_workspace_nonce' ); ?>

		<h2><?php esc_html_e( '1. Item details', 'hearback-cabinet' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="hb_title"><?php esc_html_e( 'Title', 'hearback-cabinet' ); ?></label></th>
				<td><input type="text" id="hb_title" name="hb_title" class="large-text" value="<?php echo esc_attr( $decision->post_title ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="hb_context"><?php esc_html_e( 'Context', 'hearback-cabinet' ); ?></label></th>
				<td>
					<textarea id="hb_context" name="hb_context" rows="4" class="large-text"><?php echo esc_textarea( $decision->post_content ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Plain-language background: why this item exists.', 'hearback-cabinet' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label>
					<input type="checkbox" name="hb_comments_enabled" value="1" <?php checked( $comments_enabled ); ?> />
					<?php esc_html_e( 'Collect public comments', 'hearback-cabinet' ); ?>
				</label></th>
				<td><p class="description"><?php esc_html_e( 'Turn off for a purely informational item - hides the comment form, themes, and timeline below, and shows just the outcome when one is posted.', 'hearback-cabinet' ); ?></p></td>
			</tr>
			<tr>
				<th><label for="hb_decision_question"><?php esc_html_e( 'The question', 'hearback-cabinet' ); ?></label></th>
				<td><input type="text" id="hb_decision_question" name="hb_decision_question" class="large-text" value="<?php echo esc_attr( $decision_question ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="hb_comment_open_at"><?php esc_html_e( 'Comments open at', 'hearback-cabinet' ); ?></label></th>
				<td><input type="datetime-local" id="hb_comment_open_at" name="hb_comment_open_at" value="<?php echo esc_attr( $comment_open_at ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="hb_response_by_date"><?php esc_html_e( 'Response due by', 'hearback-cabinet' ); ?></label></th>
				<td><input type="date" id="hb_response_by_date" name="hb_response_by_date" value="<?php echo esc_attr( $response_by_date ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="hb_response_owner_name"><?php esc_html_e( 'Response owner', 'hearback-cabinet' ); ?></label></th>
				<td>
					<input type="text" id="hb_response_owner_name" name="hb_response_owner_name" placeholder="<?php esc_attr_e( 'Name', 'hearback-cabinet' ); ?>" value="<?php echo esc_attr( $owner_name ); ?>" style="width: 48%;" />
					<input type="text" name="hb_response_owner_role" placeholder="<?php esc_attr_e( 'Role', 'hearback-cabinet' ); ?>" value="<?php echo esc_attr( $owner_role ); ?>" style="width: 48%;" />
				</td>
			</tr>
			<tr>
				<th><label for="hb_external_reference"><?php esc_html_e( 'Reference / case number', 'hearback-cabinet' ); ?></label></th>
				<td><input type="text" id="hb_external_reference" name="hb_external_reference" class="regular-text" value="<?php echo esc_attr( $reference ); ?>" />
					<p class="description"><?php esc_html_e( 'A stable ID so a future ingestion script recognizes this as the same matter across meetings.', 'hearback-cabinet' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="hb_source"><?php esc_html_e( 'Source', 'hearback-cabinet' ); ?></label></th>
				<td>
					<select id="hb_source" name="hb_source">
						<option value="manual" <?php selected( $source, 'manual' ); ?>><?php esc_html_e( 'Entered manually', 'hearback-cabinet' ); ?></option>
						<option value="scraped" <?php selected( $source, 'scraped' ); ?>><?php esc_html_e( 'Scraped from agenda/transcript', 'hearback-cabinet' ); ?></option>
					</select>
					<input type="url" name="hb_source_url" class="regular-text" placeholder="https://" value="<?php echo esc_attr( $source_url ); ?>" />
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( '2. Themes', 'hearback-cabinet' ); ?></h2>
		<table class="wp-list-table widefat fixed striped hb-theme-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'hearback-cabinet' ); ?></th>
					<th><?php esc_html_e( 'Description', 'hearback-cabinet' ); ?></th>
					<th style="width:80px;"><?php esc_html_e( 'Delete', 'hearback-cabinet' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $themes ) ) : ?>
					<tr><td colspan="3"><em><?php esc_html_e( 'No themes yet - add one below.', 'hearback-cabinet' ); ?></em></td></tr>
				<?php endif; ?>
				<?php foreach ( $themes as $theme ) : ?>
					<tr>
						<td><input type="text" name="theme_title[<?php echo esc_attr( $theme->ID ); ?>]" value="<?php echo esc_attr( $theme->post_title ); ?>" class="regular-text" /></td>
						<td><input type="text" name="theme_description[<?php echo esc_attr( $theme->ID ); ?>]" value="<?php echo esc_attr( $theme->post_content ); ?>" class="large-text" /></td>
						<td><label><input type="checkbox" name="theme_delete[]" value="<?php echo esc_attr( $theme->ID ); ?>" /> <?php esc_html_e( 'Delete', 'hearback-cabinet' ); ?></label></td>
					</tr>
				<?php endforeach; ?>
				<tr class="hb-add-theme-row">
					<td><input type="text" name="new_theme_title" class="regular-text" placeholder="<?php esc_attr_e( 'New theme title', 'hearback-cabinet' ); ?>" /></td>
					<td><input type="text" name="new_theme_description" class="large-text" placeholder="<?php esc_attr_e( 'Optional description', 'hearback-cabinet' ); ?>" /></td>
					<td></td>
				</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( '3. Submissions', 'hearback-cabinet' ); ?></h2>
		<table class="wp-list-table widefat fixed striped hb-submission-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Comment', 'hearback-cabinet' ); ?></th>
					<th><?php esc_html_e( 'Neighborhood', 'hearback-cabinet' ); ?></th>
					<th><?php esc_html_e( 'Consent', 'hearback-cabinet' ); ?></th>
					<th><?php esc_html_e( 'Theme', 'hearback-cabinet' ); ?></th>
					<th><?php esc_html_e( 'Featured', 'hearback-cabinet' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $submissions ) ) : ?>
					<tr><td colspan="5"><em><?php esc_html_e( 'No submissions yet.', 'hearback-cabinet' ); ?></em></td></tr>
				<?php endif; ?>
				<?php foreach ( $submissions as $submission ) : ?>
					<?php
					$neighborhood = get_post_meta( $submission->ID, '_hb_neighborhood', true );
					$consent      = (bool) get_post_meta( $submission->ID, '_hb_consent', true );
					$theme_id     = (int) get_post_meta( $submission->ID, '_hb_theme_id', true );
					$featured     = (bool) get_post_meta( $submission->ID, '_hb_featured', true );
					?>
					<tr class="hb-submission-row">
						<td><?php echo esc_html( wp_trim_words( $submission->post_content, 16 ) ); ?></td>
						<td><?php echo esc_html( $neighborhood ? $neighborhood : '—' ); ?></td>
						<td>
							<input type="hidden" class="hb-consent-flag" value="<?php echo $consent ? '1' : '0'; ?>" />
							<?php echo $consent ? esc_html__( 'Yes', 'hearback-cabinet' ) : esc_html__( 'No', 'hearback-cabinet' ); ?>
						</td>
						<td>
							<select name="submission_theme[<?php echo esc_attr( $submission->ID ); ?>]">
								<option value=""><?php esc_html_e( '— Unassigned —', 'hearback-cabinet' ); ?></option>
								<?php foreach ( $themes as $theme ) : ?>
									<option value="<?php echo esc_attr( $theme->ID ); ?>" <?php selected( $theme_id, $theme->ID ); ?>><?php echo esc_html( $theme->post_title ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<input type="checkbox" class="hb-featured-checkbox" name="submission_featured[]" value="<?php echo esc_attr( $submission->ID ); ?>" <?php checked( $featured ); ?> <?php disabled( ! $consent ); ?> />
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Themes you add above become selectable here immediately after you save - you may need to save once to create new themes, then again to assign submissions to them.', 'hearback-cabinet' ); ?></p>

		<h2><?php esc_html_e( '4. Publish "What we heard"', 'hearback-cabinet' ); ?></h2>
		<p>
			<label>
				<input type="checkbox" name="hb_synthesis_published" value="1" <?php checked( ! empty( $synthesis_at ) ); ?> />
				<?php esc_html_e( 'Make the themes and any featured quotes visible to residents.', 'hearback-cabinet' ); ?>
			</label>
		</p>

		<h2><?php esc_html_e( '5. Outcome', 'hearback-cabinet' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="hb_response_status"><?php esc_html_e( 'Outcome', 'hearback-cabinet' ); ?></label></th>
				<td>
					<select id="hb_response_status" name="hb_response_status">
						<option value=""><?php esc_html_e( '— Not yet decided —', 'hearback-cabinet' ); ?></option>
						<?php foreach ( $outcome_options as $option ) : ?>
							<option value="<?php echo esc_attr( $option['key'] ); ?>" <?php selected( $response_status, $option['key'] ); ?>><?php echo esc_html( $option['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php
					printf(
						' <a href="%s">%s</a>',
						esc_url( admin_url( 'edit.php?post_type=hb_decision&page=hb-settings' ) ),
						esc_html__( 'Edit options', 'hearback-cabinet' )
					);
					?>
				</td>
			</tr>
			<tr>
				<th><label for="hb_response_rationale"><?php esc_html_e( 'Rationale', 'hearback-cabinet' ); ?></label></th>
				<td><textarea id="hb_response_rationale" name="hb_response_rationale" rows="3" class="large-text"><?php echo esc_textarea( $rationale ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="hb_response_next_step"><?php esc_html_e( 'Next step', 'hearback-cabinet' ); ?></label></th>
				<td><textarea id="hb_response_next_step" name="hb_response_next_step" rows="3" class="large-text"><?php echo esc_textarea( $next_step ); ?></textarea></td>
			</tr>
		</table>
		<p>
			<label>
				<input type="checkbox" name="hb_response_published" value="1" <?php checked( ! empty( $response_at ) ); ?> />
				<?php esc_html_e( 'Publish this outcome to residents.', 'hearback-cabinet' ); ?>
			</label>
		</p>

		<p class="submit">
			<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save everything', 'hearback-cabinet' ); ?></button>
		</p>
	</form>
	<?php
}

/**
 * One handler for the whole page: item fields, theme add/rename/
 * delete, submission theme assignment + featured flags, and the two
 * publish toggles - all in a single save.
 */
function hb_handle_workspace_save() {
	$decision_id = isset( $_POST['decision_id'] ) ? absint( $_POST['decision_id'] ) : 0;

	if ( ! $decision_id || 'hb_decision' !== get_post_type( $decision_id ) ) {
		wp_die( esc_html__( 'Invalid item.', 'hearback-cabinet' ) );
	}
	if ( ! current_user_can( 'edit_post', $decision_id ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this item.', 'hearback-cabinet' ) );
	}
	if ( ! isset( $_POST['hb_workspace_nonce'] ) || ! wp_verify_nonce( $_POST['hb_workspace_nonce'], 'hb_save_workspace_' . $decision_id ) ) {
		wp_die( esc_html__( 'Security check failed. Please go back and try again.', 'hearback-cabinet' ) );
	}

	// 1. Title + context (post_title / post_content), then everything
	// else via the same field-saving function the per-post screen uses.
	wp_update_post(
		array(
			'ID'           => $decision_id,
			'post_title'   => sanitize_text_field( wp_unslash( $_POST['hb_title'] ?? '' ) ),
			'post_content' => sanitize_textarea_field( wp_unslash( $_POST['hb_context'] ?? '' ) ),
		)
	);
	hb_apply_decision_fields( $decision_id, $_POST );

	// 2. Themes: rename/update existing, delete marked ones, add one new.
	if ( ! empty( $_POST['theme_title'] ) && is_array( $_POST['theme_title'] ) ) {
		foreach ( $_POST['theme_title'] as $theme_id => $title ) {
			$theme_id = absint( $theme_id );
			if ( hb_get_decision_id_for( $theme_id ) !== $decision_id || ! current_user_can( 'edit_post', $theme_id ) ) {
				continue; // Not this item's theme - ignore.
			}
			$description = isset( $_POST['theme_description'][ $theme_id ] ) ? $_POST['theme_description'][ $theme_id ] : '';
			wp_update_post(
				array(
					'ID'           => $theme_id,
					'post_title'   => sanitize_text_field( wp_unslash( $title ) ),
					'post_content' => sanitize_text_field( wp_unslash( $description ) ),
				)
			);
		}
	}
	if ( ! empty( $_POST['theme_delete'] ) && is_array( $_POST['theme_delete'] ) ) {
		foreach ( $_POST['theme_delete'] as $theme_id ) {
			$theme_id = absint( $theme_id );
			if ( hb_get_decision_id_for( $theme_id ) === $decision_id && current_user_can( 'delete_post', $theme_id ) ) {
				wp_trash_post( $theme_id );
			}
		}
	}
	if ( ! empty( $_POST['new_theme_title'] ) ) {
		$new_theme_id = wp_insert_post(
			array(
				'post_type'    => 'hb_theme',
				'post_status'  => 'publish',
				'post_title'   => sanitize_text_field( wp_unslash( $_POST['new_theme_title'] ) ),
				'post_content' => sanitize_text_field( wp_unslash( $_POST['new_theme_description'] ?? '' ) ),
			)
		);
		if ( $new_theme_id && ! is_wp_error( $new_theme_id ) ) {
			update_post_meta( $new_theme_id, '_hb_decision_id', $decision_id );
		}
	}

	// 3. Submissions: theme assignment + featured flag (consent-gated).
	$submission_ids = get_posts(
		array(
			'post_type'      => 'hb_submission',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_hb_decision_id',
			'meta_value'     => $decision_id,
		)
	);
	$featured_ids = ! empty( $_POST['submission_featured'] ) && is_array( $_POST['submission_featured'] )
		? array_map( 'absint', $_POST['submission_featured'] )
		: array();

	foreach ( $submission_ids as $submission_id ) {
		if ( ! current_user_can( 'edit_post', $submission_id ) ) {
			continue;
		}
		if ( isset( $_POST['submission_theme'][ $submission_id ] ) ) {
			$theme_id = absint( $_POST['submission_theme'][ $submission_id ] );
			if ( $theme_id && hb_get_decision_id_for( $theme_id ) === $decision_id ) {
				update_post_meta( $submission_id, '_hb_theme_id', $theme_id );
			} else {
				delete_post_meta( $submission_id, '_hb_theme_id' );
			}
		}
		$consent  = (bool) get_post_meta( $submission_id, '_hb_consent', true );
		$featured = $consent && in_array( $submission_id, $featured_ids, true );
		update_post_meta( $submission_id, '_hb_featured', $featured ? 1 : 0 );
	}

	$redirect = add_query_arg(
		array(
			'post_type'   => 'hb_decision',
			'page'        => 'hb-workspace',
			'decision_id' => $decision_id,
			'hb_saved'    => '1',
		),
		admin_url( 'edit.php' )
	);
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_hb_save_workspace', 'hb_handle_workspace_save' );
