<?php
/**
 * Plugin Name:       Docket Ingest
 * Plugin URI:        https://github.com/mrwhinna-mw/hearback_wordpress
 * Description:       Upload a meeting agenda, minutes, or transcript and have it drafted into pending Public Docket items for a human to review and approve. Never publishes anything on its own.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Your Organization
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       docket-ingest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DI_VERSION', '0.1.0' );
define( 'DI_PATH', plugin_dir_path( __FILE__ ) );
define( 'DI_URL', plugin_dir_url( __FILE__ ) );

require_once DI_PATH . 'includes/extract-text.php';
require_once DI_PATH . 'includes/ai-extract.php';
require_once DI_PATH . 'includes/create-items.php';
require_once DI_PATH . 'includes/admin-page.php';
require_once DI_PATH . 'includes/meta-box.php';

/**
 * This plugin is useless without Public Docket (it writes hb_decision
 * posts) and without AI Engine (it runs the extraction). Rather than
 * fail confusingly at upload time, say so up front.
 */
function di_missing_dependencies() {
	$missing = array();
	if ( ! post_type_exists( 'hb_decision' ) ) {
		$missing[] = 'Public Docket';
	}
	if ( ! class_exists( 'Meow_MWAI_API' ) ) {
		$missing[] = 'AI Engine';
	}
	return $missing;
}

function di_dependency_notice() {
	$missing = di_missing_dependencies();
	if ( empty( $missing ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: %s: comma-separated plugin names */
				__( 'Docket Ingest needs these plugins active to work: %s', 'docket-ingest' ),
				implode( ', ', $missing )
			)
		)
	);
}
add_action( 'admin_notices', 'di_dependency_notice' );
