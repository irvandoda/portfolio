<?php
/**
 * Plugin Name: SuperAdmin Secure
 * Plugin URI: https://github.com/yourusername/superadmin-secure
 * Description: Comprehensive security plugin for WordPress superadmin protection, login detection, emergency access, file integrity scanning, and ghost mode.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: superadmin-secure
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package SuperAdminSecure
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'SASEC_VERSION', '1.0.0' );
define( 'SASEC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SASEC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SASEC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Define SASEC_SECRET_KEY if not already defined (use default).
 * This will be set properly during activation.
 */
if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
	// Default secret key (will be set in wp-config during activation)
	define( 'SASEC_SECRET_KEY', 'rahasia' );
}

/**
 * Admin notice for missing SASEC_SECRET_KEY.
 */
function sasec_missing_secret_key_notice() {
	?>
	<div class="notice notice-error">
		<p><strong>SuperAdmin Secure:</strong> <?php esc_html_e( 'SASEC_SECRET_KEY is not defined in wp-config.php. Please add the following line to your wp-config.php file:', 'superadmin-secure' ); ?></p>
		<code>define('SASEC_SECRET_KEY', 'your-secret-key-here-min-32-chars');</code>
		<p><?php esc_html_e( 'Generate a secure random key (minimum 32 characters). Until this is set, features requiring encryption will be disabled.', 'superadmin-secure' ); ?></p>
	</div>
	<?php
}

/**
 * The code that runs during plugin activation.
 */
function activate_superadmin_secure() {
	require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-activator.php';
	SASEC_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_superadmin_secure() {
	require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-deactivator.php';
	SASEC_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_superadmin_secure' );
register_deactivation_hook( __FILE__, 'deactivate_superadmin_secure' );

/**
 * Core plugin class.
 */
require_once SASEC_PLUGIN_DIR . 'includes/class-sasec.php';

/**
 * Begins execution of the plugin.
 */
function run_superadmin_secure() {
	$plugin = new SASEC();
	$plugin->run();
}
run_superadmin_secure();

