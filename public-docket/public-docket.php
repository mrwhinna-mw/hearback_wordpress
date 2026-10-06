<?php
/**
 * Plugin Name:       Public Docket
 * Plugin URI:        https://github.com/mrwhinna-mw/hearback_wordpress
 * Description:       A public comment docket for local boards. Residents comment on the items coming before a board, staff group the feedback into themes, and the board publishes an outcome with a next step. Optionally drafts items from an uploaded agenda using an AI provider you configure.
 * Version:           0.4.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            HearBack
 * Author URI:        https://mrwhinna-mw.github.io/hearback_wordpress/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       public-docket
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'HB_VERSION', '0.4.0' );
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
 * Document ingest. Reads an uploaded agenda and drafts pending docket
 * items from it. Loaded in the admin only - it adds one screen and one
 * meta box, and nothing it does touches the public site. It needs the
 * AI Engine plugin to reach a model; without it the screen explains
 * what is missing rather than failing at upload time.
 */
if ( is_admin() ) {
	require_once HB_PATH . 'includes/ingest/extract-text.php';
	require_once HB_PATH . 'includes/ingest/ai-extract.php';
	require_once HB_PATH . 'includes/ingest/ai-choices.php';
	require_once HB_PATH . 'includes/ingest/create-items.php';
	require_once HB_PATH . 'includes/ingest/updates.php';
	require_once HB_PATH . 'includes/ingest/admin-page.php';
	require_once HB_PATH . 'includes/ingest/meta-box.php';
}

/**
 * Enqueue front-end styles. Every element uses the hb- prefix so a theme
 * can safely override any of it.
 *
 * No webfont is loaded from here on purpose: a plugin should not make a
 * request to a third party on behalf of every visitor. The stylesheet
 * asks for Lora and Lato and falls back to Georgia and Arial, so a theme
 * that provides those faces gets them and everyone else gets a sane
 * system stack.
 */
function hb_enqueue_assets() {
	wp_enqueue_style( 'public-docket', HB_URL . 'assets/css/hearback.css', array(), HB_VERSION );
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
		wp_enqueue_script( 'public-docket-admin', HB_URL . 'assets/js/admin-submission.js', array( 'jquery' ), HB_VERSION, true );
	}
}
add_action( 'admin_enqueue_scripts', 'hb_enqueue_admin_assets' );

/**
 * Activation: register post types immediately so the rewrite flush
 * that follows actually has the /docket/ archive to flush in.
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
