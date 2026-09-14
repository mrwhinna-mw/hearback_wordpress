<?php
/**
 * Plugin Name:       Public Docket
 * Plugin URI:        https://github.com/mrwhinna-mw/heaback_mw
 * Description:       A multi-item public docket for local government bodies. Residents comment on open items; admins sort feedback into themes and publish an outcome. Built on the HearBack model, generalized beyond single "decisions."
 * Version:           0.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Your Organization
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hearback-cabinet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'HB_VERSION', '0.2.0' );
define( 'HB_PATH', plugin_dir_path( __FILE__ ) );
define( 'HB_URL', plugin_dir_url( __FILE__ ) );

require_once HB_PATH . 'includes/post-types.php';
require_once HB_PATH . 'includes/settings.php';
require_once HB_PATH . 'includes/timeline.php';
require_once HB_PATH . 'includes/meta-boxes-decision.php';
require_once HB_PATH . 'includes/meta-boxes-theme.php';
require_once HB_PATH . 'includes/meta-boxes-submission.php';
require_once HB_PATH . 'includes/admin-workspace.php';
require_once HB_PATH . 'includes/admin-columns.php';
require_once HB_PATH . 'includes/submission-handler.php';
require_once HB_PATH . 'includes/render.php';
require_once HB_PATH . 'includes/shortcodes.php';
require_once HB_PATH . 'includes/template-loader.php';

/**
 * Enqueue front-end styles. Every element uses the hb- prefix so a theme
 * can safely override any of it.
 */
function hb_enqueue_assets() {
	wp_enqueue_style( 'hearback-fonts', 'https://fonts.googleapis.com/css2?family=Lora:wght@400;600&family=Lato:ital,wght@0,400;0,700;1,400&display=swap', array(), null );
	wp_enqueue_style( 'hearback-cabinet', HB_URL . 'assets/css/hearback.css', array( 'hearback-fonts' ), HB_VERSION );
}
add_action( 'wp_enqueue_scripts', 'hb_enqueue_assets' );

/**
 * Admin-only script: disables the "featured quote" checkbox on a
 * submission unless that submission's consent checkbox is checked.
 * This mirrors HearBack's original rule that a quote can only be
 * public if the author consented to it.
 */
function hb_enqueue_admin_assets( $hook ) {
	global $post_type;
	if ( 'hb_submission' === $post_type ) {
		wp_enqueue_script( 'hearback-admin', HB_URL . 'assets/js/admin-submission.js', array( 'jquery' ), HB_VERSION, true );
	}
}
add_action( 'admin_enqueue_scripts', 'hb_enqueue_admin_assets' );

/**
 * Activation: register post types immediately so the rewrite flush
 * that follows actually has the /decisions/ archive to flush in.
 */
function hb_activate() {
	hb_register_post_types();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'hb_activate' );

function hb_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'hb_deactivate' );
