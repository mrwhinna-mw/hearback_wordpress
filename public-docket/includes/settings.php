<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outcome options are stored as a single option: a list of
 * [key, label, tone] rows. 'tone' is one of positive/neutral/negative
 * and only exists so the public page can still pick a sensible accent
 * color for arbitrary, org-defined wording - the plugin has no idea
 * what "Referred to committee" means, but it can render it in the
 * "neutral" style if you say so.
 */
function hb_get_default_outcome_options() {
	return array(
		array(
			'key'   => 'proceed',
			'label' => __( 'Proceed', 'hearback-cabinet' ),
			'tone'  => 'positive',
		),
		array(
			'key'   => 'do_not_proceed',
			'label' => __( 'Do not proceed', 'hearback-cabinet' ),
			'tone'  => 'negative',
		),
		array(
			'key'   => 'not_yet',
			'label' => __( 'Not yet', 'hearback-cabinet' ),
			'tone'  => 'neutral',
		),
	);
}

function hb_get_outcome_options() {
	$options = get_option( 'hb_outcome_options' );
	if ( ! is_array( $options ) || empty( $options ) ) {
		return hb_get_default_outcome_options();
	}
	return $options;
}

function hb_get_outcome_option( $key ) {
	foreach ( hb_get_outcome_options() as $option ) {
		if ( $option['key'] === $key ) {
			return $option;
		}
	}
	return null;
}

function hb_add_settings_menu() {
	add_submenu_page(
		'edit.php?post_type=hb_decision',
		__( 'Public Docket Settings', 'hearback-cabinet' ),
		__( 'Settings', 'hearback-cabinet' ),
		'manage_options',
		'hb-settings',
		'hb_render_settings_page',
		99 // Last item in the menu.
	);
}
add_action( 'admin_menu', 'hb_add_settings_menu' );

function hb_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'hearback-cabinet' ) );
	}

	$options = hb_get_outcome_options();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Public Docket Settings', 'hearback-cabinet' ); ?></h1>

		<?php if ( isset( $_GET['hb_settings_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'hearback-cabinet' ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Outcome options', 'hearback-cabinet' ); ?></h2>
		<p><?php esc_html_e( 'These are the choices available in the "Outcome" dropdown on every docket item. Word them however fits your organization - not every item resolves as a simple yes/no.', 'hearback-cabinet' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="hb_save_settings" />
			<?php wp_nonce_field( 'hb_save_settings', 'hb_settings_nonce' ); ?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Label', 'hearback-cabinet' ); ?></th>
						<th style="width:200px;"><?php esc_html_e( 'Tone (for color)', 'hearback-cabinet' ); ?></th>
						<th style="width:80px;"><?php esc_html_e( 'Delete', 'hearback-cabinet' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $options as $option ) : ?>
						<tr>
							<td>
								<input type="hidden" name="option_key[]" value="<?php echo esc_attr( $option['key'] ); ?>" />
								<input type="text" name="option_label[]" value="<?php echo esc_attr( $option['label'] ); ?>" class="regular-text" />
							</td>
							<td>
								<select name="option_tone[]">
									<option value="positive" <?php selected( $option['tone'], 'positive' ); ?>><?php esc_html_e( 'Positive (green)', 'hearback-cabinet' ); ?></option>
									<option value="neutral" <?php selected( $option['tone'], 'neutral' ); ?>><?php esc_html_e( 'Neutral (amber)', 'hearback-cabinet' ); ?></option>
									<option value="negative" <?php selected( $option['tone'], 'negative' ); ?>><?php esc_html_e( 'Negative (red)', 'hearback-cabinet' ); ?></option>
								</select>
							</td>
							<td><label><input type="checkbox" name="option_delete[]" value="<?php echo esc_attr( $option['key'] ); ?>" /> <?php esc_html_e( 'Delete', 'hearback-cabinet' ); ?></label></td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<td><input type="text" name="new_option_label" class="regular-text" placeholder="<?php esc_attr_e( 'New outcome label', 'hearback-cabinet' ); ?>" /></td>
						<td>
							<select name="new_option_tone">
								<option value="positive"><?php esc_html_e( 'Positive (green)', 'hearback-cabinet' ); ?></option>
								<option value="neutral" selected><?php esc_html_e( 'Neutral (amber)', 'hearback-cabinet' ); ?></option>
								<option value="negative"><?php esc_html_e( 'Negative (red)', 'hearback-cabinet' ); ?></option>
							</select>
						</td>
						<td></td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'hearback-cabinet' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}

function hb_handle_settings_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'hearback-cabinet' ) );
	}
	if ( ! isset( $_POST['hb_settings_nonce'] ) || ! wp_verify_nonce( $_POST['hb_settings_nonce'], 'hb_save_settings' ) ) {
		wp_die( esc_html__( 'Security check failed. Please go back and try again.', 'hearback-cabinet' ) );
	}

	$existing_keys = isset( $_POST['option_key'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['option_key'] ) ) : array();
	$labels        = isset( $_POST['option_label'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['option_label'] ) ) : array();
	$tones         = isset( $_POST['option_tone'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['option_tone'] ) ) : array();
	$delete_keys   = isset( $_POST['option_delete'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['option_delete'] ) ) : array();
	$allowed_tones = array( 'positive', 'neutral', 'negative' );

	$new_options = array();
	foreach ( $existing_keys as $i => $key ) {
		if ( in_array( $key, $delete_keys, true ) ) {
			continue;
		}
		$label = isset( $labels[ $i ] ) ? $labels[ $i ] : '';
		$tone  = isset( $tones[ $i ] ) && in_array( $tones[ $i ], $allowed_tones, true ) ? $tones[ $i ] : 'neutral';
		if ( '' === trim( $label ) ) {
			continue; // Blanked-out label - drop the row instead of keeping an empty option.
		}
		$new_options[] = array(
			'key'   => $key,
			'label' => $label,
			'tone'  => $tone,
		);
	}

	if ( ! empty( $_POST['new_option_label'] ) ) {
		$new_label = sanitize_text_field( wp_unslash( $_POST['new_option_label'] ) );
		$new_tone  = isset( $_POST['new_option_tone'] ) && in_array( $_POST['new_option_tone'], $allowed_tones, true )
			? sanitize_key( $_POST['new_option_tone'] )
			: 'neutral';
		$new_options[] = array(
			'key'   => sanitize_key( $new_label ) . '_' . wp_generate_password( 4, false, false ),
			'label' => $new_label,
			'tone'  => $new_tone,
		);
	}

	// Never leave the list empty - fall back to defaults rather than
	// leaving every docket item's Outcome dropdown with no choices.
	if ( empty( $new_options ) ) {
		$new_options = hb_get_default_outcome_options();
	}

	update_option( 'hb_outcome_options', $new_options );

	wp_safe_redirect(
		add_query_arg(
			array(
				'post_type'          => 'hb_decision',
				'page'               => 'hb-settings',
				'hb_settings_saved'  => '1',
			),
			admin_url( 'edit.php' )
		)
	);
	exit;
}
add_action( 'admin_post_hb_save_settings', 'hb_handle_settings_save' );
