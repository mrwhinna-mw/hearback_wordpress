<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DI_CAPABILITY', 'edit_others_posts' );

function di_register_admin_page() {
	add_submenu_page(
		'edit.php?post_type=hb_decision',
		__( 'Ingest Document', 'docket-ingest' ),
		__( 'Ingest Document', 'docket-ingest' ),
		DI_CAPABILITY,
		'docket-ingest',
		'di_render_admin_page'
	);
}
add_action( 'admin_menu', 'di_register_admin_page', 20 );

function di_render_admin_page() {
	if ( ! current_user_can( DI_CAPABILITY ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'docket-ingest' ) );
	}

	echo '<div class="wrap"><h1>' . esc_html__( 'Ingest Document', 'docket-ingest' ) . '</h1>';

	$missing = di_missing_dependencies();
	if ( ! empty( $missing ) ) {
		printf(
			'<div class="notice notice-error"><p>%s</p></div></div>',
			esc_html( sprintf( __( 'Cannot run: %s not active.', 'docket-ingest' ), implode( ', ', $missing ) ) )
		);
		return;
	}

	$action = isset( $_POST['di_action'] ) ? sanitize_key( wp_unslash( $_POST['di_action'] ) ) : '';

	if ( 'review' === $action ) {
		di_handle_upload();
	} elseif ( 'create' === $action ) {
		di_handle_create();
	} else {
		di_render_upload_form();
	}

	echo '</div>';
}

function di_render_upload_form( $error = '' ) {
	if ( $error ) {
		printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $error ) );
	}
	?>
	<p>
		<?php esc_html_e( 'Upload a meeting agenda, minutes, or transcript. Items are drafted as Pending for review — nothing is published automatically.', 'docket-ingest' ); ?>
	</p>
	<form method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'di_upload' ); ?>
		<input type="hidden" name="di_action" value="review">
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="di_file"><?php esc_html_e( 'Document', 'docket-ingest' ); ?></label></th>
				<td>
					<input type="file" name="di_file" id="di_file" accept=".txt,.md,.docx" required>
					<p class="description">
						<?php
						printf(
							/* translators: %s: list of file extensions */
							esc_html__( 'Supported: %s. For a PDF, open it, copy the text, and save it as .txt.', 'docket-ingest' ),
							esc_html( implode( ', ', di_supported_extensions() ) )
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="di_source_url"><?php esc_html_e( 'Source URL', 'docket-ingest' ); ?></label></th>
				<td>
					<input type="url" name="di_source_url" id="di_source_url" class="regular-text" placeholder="https://anc6a.org/wp-content/uploads/...">
					<p class="description"><?php esc_html_e( 'Optional. Where this document is published, recorded on every item it creates.', 'docket-ingest' ); ?></p>
				</td>
			</tr>
			<?php di_render_ai_rows(); ?>
			<tr>
				<th scope="row"><label for="di_extra_instructions"><?php esc_html_e( 'Extra instructions', 'docket-ingest' ); ?></label></th>
				<td>
					<textarea name="di_extra_instructions" id="di_extra_instructions" rows="3" class="large-text"
						placeholder="<?php esc_attr_e( 'e.g. Our case numbers look like ZC-2026-14. Treat liquor licence items as commentable even when listed under consent.', 'docket-ingest' ); ?>"><?php echo esc_textarea( get_option( 'di_extra_instructions', '' ) ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Optional, remembered between uploads. Added to the instructions the AI is given when reading documents — useful for how your organization words things. It cannot override the built-in rules that stop the AI inventing details or requiring a verbatim quote.', 'docket-ingest' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Analyze Document', 'docket-ingest' ) ); ?>
	</form>
	<?php
}

/**
 * Environment and model dropdowns. AI Engine never displays an
 * environment's ID, so asking admins to type one meant sending them to a
 * terminal; picking by name removes that step entirely.
 */
function di_render_ai_rows() {
	$envs = di_environments();

	if ( empty( $envs ) ) {
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'AI provider', 'docket-ingest' ); ?></th>
			<td>
				<p><strong><?php esc_html_e( 'No AI provider is set up yet.', 'docket-ingest' ); ?></strong>
				<?php esc_html_e( 'Add an API key under Meow Apps > AI Engine > Settings > AI, then come back to this page.', 'docket-ingest' ); ?></p>
			</td>
		</tr>
		<?php
		return;
	}

	$env_id     = di_selected_env_id( $envs );
	$env_models = array();
	$models_js  = array();
	foreach ( $envs as $env ) {
		$models_js[ $env['id'] ] = $env['models'];
		if ( $env['id'] === $env_id ) {
			$env_models = $env['models'];
		}
	}

	$saved_model = get_option( 'di_model', '' );
	$model_ids   = wp_list_pluck( $env_models, 'id' );
	$use_custom  = empty( $env_models ) || ( '' !== $saved_model && ! in_array( $saved_model, $model_ids, true ) );
	$selected    = in_array( $saved_model, $model_ids, true ) ? $saved_model : ( $env_models ? $env_models[0]['id'] : '' );
	?>
	<tr>
		<th scope="row"><label for="di_env_id"><?php esc_html_e( 'AI provider', 'docket-ingest' ); ?></label></th>
		<td>
			<select name="di_env_id" id="di_env_id">
				<?php foreach ( $envs as $env ) : ?>
					<option value="<?php echo esc_attr( $env['id'] ); ?>" <?php selected( $env['id'], $env_id ); ?>>
						<?php
						echo esc_html(
							$env['name'] . ' (' . di_provider_label( $env['type'] ) . ')'
							. ( $env['has_key'] ? '' : ' - ' . __( 'no API key added', 'docket-ingest' ) )
						);
						?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'The AI connections set up in Meow Apps > AI Engine > Settings > AI.', 'docket-ingest' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="di_model"><?php esc_html_e( 'Model', 'docket-ingest' ); ?></label></th>
		<td>
			<select name="di_model" id="di_model">
				<?php foreach ( $env_models as $model ) : ?>
					<option value="<?php echo esc_attr( $model['id'] ); ?>" <?php selected( ! $use_custom && $model['id'] === $selected ); ?>>
						<?php
						echo esc_html(
							$model['latest']
								/* translators: %s: model name */
								? sprintf( __( '%s (always latest)', 'docket-ingest' ), $model['name'] )
								: $model['name']
						);
						?>
					</option>
				<?php endforeach; ?>
				<option value="__custom__" <?php selected( $use_custom ); ?>><?php esc_html_e( 'Other - type a model name', 'docket-ingest' ); ?></option>
			</select>
			<input type="text" name="di_model_custom" id="di_model_custom" class="regular-text"
				value="<?php echo esc_attr( $use_custom ? $saved_model : '' ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. gemini-flash-lite-latest', 'docket-ingest' ); ?>"
				style="<?php echo $use_custom ? '' : 'display:none;'; ?>margin-top:6px;">
			<p class="description" id="di_model_empty" style="<?php echo empty( $env_models ) ? '' : 'display:none;'; ?>">
				<?php esc_html_e( 'AI Engine has no model list for this provider yet. Open the provider in AI Engine\'s settings to refresh it, or type a model name.', 'docket-ingest' ); ?>
			</p>
			<p class="description"><?php esc_html_e( '"Always latest" models follow the provider\'s newest release, so they are the least likely to be retired.', 'docket-ingest' ); ?></p>
		</td>
	</tr>
	<script>
	( function () {
		var models = <?php echo wp_json_encode( $models_js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
		var labels = <?php echo wp_json_encode( array( 'latest' => __( '%s (always latest)', 'docket-ingest' ), 'other' => __( 'Other - type a model name', 'docket-ingest' ) ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
		var env = document.getElementById( 'di_env_id' );
		var model = document.getElementById( 'di_model' );
		var custom = document.getElementById( 'di_model_custom' );
		var empty = document.getElementById( 'di_model_empty' );

		function syncCustom() {
			custom.style.display = model.value === '__custom__' ? '' : 'none';
		}

		// Options are rebuilt rather than hidden: Safari ignores display:none
		// on <option>, so hiding would leave other providers' models selectable.
		env.addEventListener( 'change', function () {
			var list = models[ env.value ] || [];
			model.innerHTML = '';
			list.forEach( function ( m ) {
				var o = document.createElement( 'option' );
				o.value = m.id;
				o.textContent = m.latest ? labels.latest.replace( '%s', m.name ) : m.name;
				model.appendChild( o );
			} );
			var other = document.createElement( 'option' );
			other.value = '__custom__';
			other.textContent = labels.other;
			model.appendChild( other );
			model.value = list.length ? list[0].id : '__custom__';
			empty.style.display = list.length ? 'none' : '';
			syncCustom();
		} );
		model.addEventListener( 'change', syncCustom );
	} )();
	</script>
	<?php
}

function di_handle_upload() {
	check_admin_referer( 'di_upload' );

	$model = sanitize_text_field( wp_unslash( $_POST['di_model'] ?? '' ) );
	if ( '__custom__' === $model ) {
		$model = sanitize_text_field( wp_unslash( $_POST['di_model_custom'] ?? '' ) );
	}

	// Remembered so the next upload starts with the same choices.
	update_option( 'di_env_id', sanitize_text_field( wp_unslash( $_POST['di_env_id'] ?? '' ) ) );
	update_option( 'di_model', $model );
	update_option(
		'di_extra_instructions',
		mb_substr( sanitize_textarea_field( wp_unslash( $_POST['di_extra_instructions'] ?? '' ) ), 0, 2000 )
	);

	if ( empty( $_FILES['di_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['di_file']['tmp_name'] ) ) {
		di_render_upload_form( __( 'No file was received.', 'docket-ingest' ) );
		return;
	}

	$original_name = sanitize_file_name( $_FILES['di_file']['name'] );
	$text          = di_extract_text( $_FILES['di_file']['tmp_name'], $original_name );

	if ( is_wp_error( $text ) ) {
		di_render_upload_form( $text->get_error_message() );
		return;
	}

	$items = di_extract_items( $text );
	if ( is_wp_error( $items ) ) {
		di_render_upload_form( $items->get_error_message() );
		return;
	}

	$sorted = di_sort_items( $items );

	di_render_review(
		$sorted,
		esc_url_raw( wp_unslash( $_POST['di_source_url'] ?? '' ) ),
		$original_name
	);
}

/**
 * Splits what the document contains into items that are new to the docket
 * and updates to items already on it, asking the AI what each repeat
 * mention actually adds.
 */
function di_sort_items( $items ) {
	$meeting_date = isset( $items[0]['_meeting_date'] ) ? $items[0]['_meeting_date'] : '';

	$new         = array();
	$updates     = array();
	$unchanged   = array();
	$comparisons = 0;

	foreach ( $items as $item ) {
		$existing = di_find_existing( $item['external_reference'] );

		if ( ! $existing ) {
			$new[] = $item;
			continue;
		}

		if ( $comparisons >= DI_MAX_UPDATE_COMPARISONS ) {
			$unchanged[] = array(
				'title'    => $item['title'],
				'existing' => $existing,
				'note'     => __( 'Not compared - this document mentions more existing items than one upload checks.', 'docket-ingest' ),
			);
			continue;
		}

		++$comparisons;
		$update = di_extract_update( $existing, $item, $meeting_date );

		// A failed comparison shouldn't lose the whole upload; the item is
		// simply reported as already on the docket, uncompared.
		if ( is_wp_error( $update ) ) {
			$unchanged[] = array(
				'title'    => $item['title'],
				'existing' => $existing,
				'note'     => sprintf(
					/* translators: %s: error message */
					__( 'Could not compare against the existing item: %s', 'docket-ingest' ),
					$update->get_error_message()
				),
			);
			continue;
		}

		if ( $update['has_update'] ) {
			$updates[] = $update;
		} else {
			$unchanged[] = array(
				'title'    => $item['title'],
				'existing' => $existing,
				'note'     => __( 'Already on the docket; nothing new in this document.', 'docket-ingest' ),
			);
		}
	}

	return array(
		'new'       => $new,
		'updates'   => $updates,
		'unchanged' => $unchanged,
		'truncated' => ! empty( $items[0]['_truncated'] ),
	);
}

function di_render_review( $sorted, $source_url, $source_file ) {
	$items     = $sorted['new'];
	$updates   = $sorted['updates'];
	$unchanged = $sorted['unchanged'];
	?>
	<p>
		<?php
		printf(
			/* translators: 1: number of new items, 2: number of updates */
			esc_html__( 'This document has %1$s and %2$s.', 'docket-ingest' ),
			esc_html( sprintf( _n( '%d new item', '%d new items', count( $items ), 'docket-ingest' ), count( $items ) ) ),
			esc_html( sprintf( _n( '%d update to an existing item', '%d updates to existing items', count( $updates ), 'docket-ingest' ), count( $updates ) ) )
		);
		?>
	</p>
	<?php if ( $sorted['truncated'] ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'The document was long and only its first part was analyzed. Split it and upload the rest separately.', 'docket-ingest' ); ?></p></div>
	<?php endif; ?>
	<div class="notice notice-info">
		<p><?php esc_html_e( 'Everything except the question was copied from the document. The question was written by the AI — read it before approving.', 'docket-ingest' ); ?></p>
	</div>
	<form method="post">
		<?php wp_nonce_field( 'di_create' ); ?>
		<input type="hidden" name="di_action" value="create">
		<input type="hidden" name="di_source_url" value="<?php echo esc_attr( $source_url ); ?>">
		<input type="hidden" name="di_source_file" value="<?php echo esc_attr( $source_file ); ?>">
		<input type="hidden" name="di_items" value="<?php echo esc_attr( wp_json_encode( $items ) ); ?>">
		<input type="hidden" name="di_updates" value="<?php echo esc_attr( wp_json_encode( $updates ) ); ?>">

		<?php if ( ! empty( $updates ) ) : ?>
			<h2><?php esc_html_e( 'Updates to items already on the docket', 'docket-ingest' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th style="width:2.5em;"><?php esc_html_e( 'Apply', 'docket-ingest' ); ?></th>
						<th><?php esc_html_e( 'What this document adds', 'docket-ingest' ); ?></th>
						<th><?php esc_html_e( 'Verbatim from document', 'docket-ingest' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $updates as $i => $update ) :
					$has_conflicts  = ! empty( $update['conflicts'] );
					$existing_step  = get_post_meta( $update['existing_id'], '_hb_response_next_step', true );
					$is_published   = 'publish' === get_post_status( $update['existing_id'] );
					?>
					<tr>
						<td>
							<input type="checkbox" name="di_update_selected[]" value="<?php echo esc_attr( $i ); ?>" <?php checked( ! $has_conflicts ); ?>>
						</td>
						<td>
							<strong><?php echo esc_html( $update['title'] ); ?></strong>
							<a href="<?php echo esc_url( get_edit_post_link( $update['existing_id'] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'docket-ingest' ); ?></a>
							<p><?php echo esc_html( $update['summary'] ); ?></p>

							<?php if ( ! empty( $update['next_step'] ) ) : ?>
								<p>
									<label>
										<input type="checkbox" name="di_update_nextstep[]" value="<?php echo esc_attr( $i ); ?>" <?php checked( '' === $existing_step ); ?>>
										<?php
										printf(
											/* translators: %s: the proposed next step */
											esc_html__( 'Set "Next step" to: %s', 'docket-ingest' ),
											'<em>' . esc_html( $update['next_step'] ) . '</em>'
										);
										?>
									</label>
									<?php if ( '' !== $existing_step ) : ?>
										<br><span class="dashicons dashicons-warning"></span>
										<em><?php
										printf(
											/* translators: %s: the next step already written on the item */
											esc_html__( 'This would replace what someone already wrote: "%s"', 'docket-ingest' ),
											esc_html( $existing_step )
										);
										?></em>
									<?php endif; ?>
								</p>
							<?php endif; ?>

							<?php if ( $has_conflicts ) : ?>
								<div class="notice notice-warning inline" style="margin:8px 0;padding:6px 10px;">
									<p style="margin:0 0 4px;"><strong><?php esc_html_e( 'This document contradicts what is already recorded — unchecked by default.', 'docket-ingest' ); ?></strong></p>
									<ul style="list-style:disc;margin:0 0 0 1.4em;">
										<?php foreach ( $update['conflicts'] as $conflict ) : ?>
											<li>
												<?php echo esc_html( $conflict['detail'] ); ?> —
												<?php
												printf(
													/* translators: 1: value already on the item, 2: value in the new document */
													esc_html__( 'recorded: "%1$s"; this document: "%2$s"', 'docket-ingest' ),
													esc_html( $conflict['already_recorded'] ),
													esc_html( $conflict['newer_document'] )
												);
												?>
												<?php if ( ! empty( $conflict['quote'] ) ) : ?>
													<br><em>&ldquo;<?php echo esc_html( $conflict['quote'] ); ?>&rdquo;</em>
												<?php endif; ?>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>

							<?php if ( $is_published ) : ?>
								<p class="description"><?php esc_html_e( 'This item is public — an applied update appears on the site immediately.', 'docket-ingest' ); ?></p>
							<?php endif; ?>
						</td>
						<td style="max-width:28em;">
							<?php if ( ! empty( $update['quote'] ) ) : ?>
								<blockquote style="margin:0;font-style:italic;"><?php echo esc_html( $update['quote'] ); ?></blockquote>
							<?php else : ?>
								<em><?php esc_html_e( 'No quote provided — verify this one manually.', 'docket-ingest' ); ?></em>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( ! empty( $items ) ) : ?>
			<h2><?php esc_html_e( 'New items', 'docket-ingest' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th style="width:2.5em;"><?php esc_html_e( 'Add', 'docket-ingest' ); ?></th>
						<th><?php esc_html_e( 'Item', 'docket-ingest' ); ?></th>
						<th><?php esc_html_e( 'Verbatim from document', 'docket-ingest' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $items as $i => $item ) : ?>
					<tr>
						<td>
							<input type="checkbox" name="di_selected[]" value="<?php echo esc_attr( $i ); ?>" checked>
						</td>
						<td>
							<strong><?php echo esc_html( $item['title'] ); ?></strong>
							<?php if ( ! empty( $item['question'] ) ) : ?>
								<p><em><?php esc_html_e( 'Drafted question:', 'docket-ingest' ); ?></em> <?php echo esc_html( $item['question'] ); ?></p>
							<?php endif; ?>
							<p>
								<?php if ( ! empty( $item['external_reference'] ) ) : ?>
									<code><?php echo esc_html( $item['external_reference'] ); ?></code>
								<?php endif; ?>
								<?php if ( ! empty( $item['address'] ) ) : ?>
									<?php echo esc_html( $item['address'] ); ?>
								<?php endif; ?>
							</p>
						</td>
						<td style="max-width:28em;">
							<?php if ( ! empty( $item['source_quote'] ) ) : ?>
								<blockquote style="margin:0;font-style:italic;"><?php echo esc_html( $item['source_quote'] ); ?></blockquote>
							<?php else : ?>
								<em><?php esc_html_e( 'No quote provided — verify this one manually.', 'docket-ingest' ); ?></em>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( ! empty( $unchanged ) ) : ?>
			<h2><?php esc_html_e( 'Mentioned, but nothing to do', 'docket-ingest' ); ?></h2>
			<ul style="list-style:disc;margin-left:2em;">
				<?php foreach ( $unchanged as $skip ) : ?>
					<li>
						<strong><?php echo esc_html( $skip['title'] ); ?></strong> — <?php echo esc_html( $skip['note'] ); ?>
						<a href="<?php echo esc_url( get_edit_post_link( $skip['existing'] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'docket-ingest' ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( empty( $items ) && empty( $updates ) ) : ?>
			<p><?php esc_html_e( 'Nothing to add or update from this document.', 'docket-ingest' ); ?></p>
		<?php else : ?>
			<?php submit_button( __( 'Apply Selected', 'docket-ingest' ) ); ?>
		<?php endif; ?>
	</form>
	<?php
}

function di_handle_create() {
	check_admin_referer( 'di_create' );

	$raw = json_decode( wp_unslash( $_POST['di_items'] ?? '[]' ), true );
	if ( ! is_array( $raw ) ) {
		di_render_upload_form( __( 'Something went wrong reading the reviewed items. Please upload the document again.', 'docket-ingest' ) );
		return;
	}

	$selected        = isset( $_POST['di_selected'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['di_selected'] ) ) : array();
	$update_selected = isset( $_POST['di_update_selected'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['di_update_selected'] ) ) : array();

	if ( empty( $selected ) && empty( $update_selected ) ) {
		di_render_upload_form( __( 'Nothing was selected, so nothing was changed.', 'docket-ingest' ) );
		return;
	}

	$source_url  = esc_url_raw( wp_unslash( $_POST['di_source_url'] ?? '' ) );
	$source_file = sanitize_file_name( wp_unslash( $_POST['di_source_file'] ?? '' ) );

	// Re-sanitize: this came back through the browser and is untrusted again.
	$items = array();
	foreach ( $selected as $index ) {
		if ( isset( $raw[ $index ] ) ) {
			$items[] = di_normalize_item( $raw[ $index ] );
		}
	}

	$result = di_create_items( $items, $source_url, $source_file );

	$result['updated']       = array();
	$result['update_errors'] = array();

	if ( ! empty( $update_selected ) ) {
		$raw_updates  = json_decode( wp_unslash( $_POST['di_updates'] ?? '[]' ), true );
		$next_step_on = isset( $_POST['di_update_nextstep'] )
			? array_map( 'intval', (array) wp_unslash( $_POST['di_update_nextstep'] ) )
			: array();

		foreach ( $update_selected as $index ) {
			if ( ! isset( $raw_updates[ $index ] ) || ! is_array( $raw_updates[ $index ] ) ) {
				continue;
			}
			$update  = di_normalize_update( $raw_updates[ $index ] );
			$applied = di_apply_update(
				$update,
				in_array( $index, $next_step_on, true ),
				$source_file,
				$source_url
			);

			if ( is_wp_error( $applied ) ) {
				$result['update_errors'][] = $applied->get_error_message();
			} else {
				$result['updated'][] = $update;
			}
		}
	}

	di_render_result( $result );
}

/**
 * Updates make the same round trip through the browser as items do, so
 * they get the same treatment on the way back: nothing is trusted.
 */
function di_normalize_update( $raw ) {
	$text = function ( $key ) use ( $raw ) {
		return isset( $raw[ $key ] ) && is_string( $raw[ $key ] ) ? sanitize_textarea_field( $raw[ $key ] ) : '';
	};

	$conflicts = array();
	if ( ! empty( $raw['conflicts'] ) && is_array( $raw['conflicts'] ) ) {
		foreach ( $raw['conflicts'] as $conflict ) {
			if ( is_array( $conflict ) && ! empty( $conflict['detail'] ) ) {
				$conflicts[] = array_map( 'sanitize_text_field', array_filter( $conflict, 'is_string' ) );
			}
		}
	}

	return array(
		'existing_id'  => isset( $raw['existing_id'] ) ? (int) $raw['existing_id'] : 0,
		'title'        => $text( 'title' ),
		'summary'      => $text( 'summary' ),
		'quote'        => $text( 'quote' ),
		'next_step'    => $text( 'next_step' ),
		'meeting_date' => $text( 'meeting_date' ),
		'conflicts'    => $conflicts,
	);
}

function di_render_result( $result ) {
	if ( ! empty( $result['updated'] ) ) {
		echo '<div class="notice notice-success"><p>' . esc_html(
			sprintf(
				/* translators: %d: number of items updated */
				_n( '%d item updated.', '%d items updated.', count( $result['updated'] ), 'docket-ingest' ),
				count( $result['updated'] )
			)
		) . '</p></div>';

		echo '<ul style="list-style:disc;margin-left:2em;">';
		foreach ( $result['updated'] as $update ) {
			printf(
				'<li><a href="%s">%s</a> — %s</li>',
				esc_url( get_edit_post_link( $update['existing_id'] ) ),
				esc_html( $update['title'] ),
				esc_html( $update['summary'] )
			);
		}
		echo '</ul>';
	}

	foreach ( isset( $result['update_errors'] ) ? $result['update_errors'] : array() as $error ) {
		printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $error ) );
	}

	if ( ! empty( $result['created'] ) ) {
		echo '<div class="notice notice-success"><p>' . esc_html(
			sprintf(
				/* translators: %d: number created */
				_n( '%d pending item created.', '%d pending items created.', count( $result['created'] ), 'docket-ingest' ),
				count( $result['created'] )
			)
		) . '</p></div>';

		echo '<ul style="list-style:disc;margin-left:2em;">';
		foreach ( $result['created'] as $post_id ) {
			printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( get_edit_post_link( $post_id ) ),
				esc_html( get_the_title( $post_id ) )
			);
		}
		echo '</ul>';
	}

	if ( ! empty( $result['skipped'] ) ) {
		echo '<div class="notice notice-info"><p>' . esc_html__( 'Skipped as duplicates:', 'docket-ingest' ) . '</p><ul style="list-style:disc;margin-left:2em;">';
		foreach ( $result['skipped'] as $skip ) {
			printf(
				'<li>%s (<code>%s</code>)</li>',
				esc_html( $skip['title'] ),
				esc_html( $skip['ref'] )
			);
		}
		echo '</ul></div>';
	}

	foreach ( $result['errors'] as $error ) {
		printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $error ) );
	}

	printf(
		'<p><a class="button" href="%s">%s</a> <a class="button button-primary" href="%s">%s</a></p>',
		esc_url( admin_url( 'edit.php?post_type=hb_decision&page=docket-ingest' ) ),
		esc_html__( 'Ingest Another', 'docket-ingest' ),
		esc_url( admin_url( 'edit.php?post_type=hb_decision&page=hb-workspace' ) ),
		esc_html__( 'Review in Workspace', 'docket-ingest' )
	);
}
