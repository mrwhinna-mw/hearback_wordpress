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

	di_render_review( $items, esc_url_raw( wp_unslash( $_POST['di_source_url'] ?? '' ) ), $original_name );
}

function di_render_review( $items, $source_url, $source_file ) {
	$truncated = ! empty( $items[0]['_truncated'] );
	?>
	<p>
		<?php
		printf(
			/* translators: %d: number of items */
			esc_html( _n( '%d item found. Review it, then create the ones you want.', '%d items found. Review them, then create the ones you want.', count( $items ), 'docket-ingest' ) ),
			count( $items )
		);
		?>
	</p>
	<?php if ( $truncated ) : ?>
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
				<?php $existing = di_find_existing( $item['external_reference'] ); ?>
				<tr>
					<td>
						<input type="checkbox" name="di_selected[]" value="<?php echo esc_attr( $i ); ?>" <?php checked( ! $existing ); ?>>
					</td>
					<td>
						<strong><?php echo esc_html( $item['title'] ); ?></strong>
						<?php if ( $existing ) : ?>
							<span class="dashicons dashicons-warning"></span>
							<em><?php
							printf(
								/* translators: %s: link to existing item */
								esc_html__( 'Already exists as %s — unchecked by default.', 'docket-ingest' ),
								'#' . esc_html( $existing )
							);
							?></em>
						<?php endif; ?>
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
		<?php submit_button( __( 'Create Selected as Pending', 'docket-ingest' ) ); ?>
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

	$selected = isset( $_POST['di_selected'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['di_selected'] ) ) : array();
	if ( empty( $selected ) ) {
		di_render_upload_form( __( 'No items were selected, so nothing was created.', 'docket-ingest' ) );
		return;
	}

	// Re-sanitize: this came back through the browser and is untrusted again.
	$items = array();
	foreach ( $selected as $index ) {
		if ( isset( $raw[ $index ] ) ) {
			$items[] = di_normalize_item( $raw[ $index ] );
		}
	}

	$result = di_create_items(
		$items,
		esc_url_raw( wp_unslash( $_POST['di_source_url'] ?? '' ) ),
		sanitize_file_name( wp_unslash( $_POST['di_source_file'] ?? '' ) )
	);

	di_render_result( $result );
}

function di_render_result( $result ) {
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
