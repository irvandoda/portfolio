<?php
/**
 * Fired during plugin deactivation.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation class.
 */
class SASEC_Deactivator {

	/**
	 * Deactivate the plugin.
	 */
	public static function deactivate() {
		// Clear scheduled cron events
		wp_clear_scheduled_hook( 'sasec_daily_file_scan' );

		// Flush rewrite rules
		flush_rewrite_rules();
	}
}

