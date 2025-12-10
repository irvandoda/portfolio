<?php
/**
 * The core plugin class.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class SASEC {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @var SASEC_Loader
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		$this->version     = SASEC_VERSION;
		$this->plugin_name = 'superadmin-secure';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_rest_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-loader.php';
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-i18n.php';
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-logger.php';
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-detection.php';
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-emergency.php';
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-file-scan.php';
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-notifier.php';
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-htaccess.php';
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-ghost-mode.php';

		$this->loader = new SASEC_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 */
	private function set_locale() {
		$plugin_i18n = new SASEC_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 */
	private function define_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		require_once SASEC_PLUGIN_DIR . 'admin/class-sasec-admin.php';
		$plugin_admin = new SASEC_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 */
	private function define_public_hooks() {
		// Emergency login handler (priority 1, runs early)
		$emergency = new SASEC_Emergency();
		$this->loader->add_action( 'init', $emergency, 'check_emergency_login', 1 );

		// Login detection
		$detection = new SASEC_Detection();
		$this->loader->add_action( 'wp_login_failed', $detection, 'log_failed_login' );
		$this->loader->add_action( 'wp_login', $detection, 'log_successful_login', 10, 2 );

		// Ghost mode hooks
		$ghost_mode = new SASEC_Ghost_Mode();
		$this->loader->add_action( 'wp_insert_post', $ghost_mode, 'handle_post_creation', 10, 3 );
		$this->loader->add_action( 'pre_get_users', $ghost_mode, 'filter_user_queries' );
		$this->loader->add_filter( 'rest_prepare_user', $ghost_mode, 'filter_rest_user_response', 10, 3 );

		// File integrity scanner cron
		$file_scan = new SASEC_File_Scan();
		$this->loader->add_action( 'sasec_daily_file_scan', $file_scan, 'run_incremental_scan' );
		$this->loader->add_action( 'wp_handle_upload', $file_scan, 'scan_uploaded_file', 10, 2 );
	}

	/**
	 * Register REST API hooks.
	 */
	private function define_rest_hooks() {
		require_once SASEC_PLUGIN_DIR . 'includes/rest/routes.php';
		$rest_routes = new SASEC_REST_Routes();
		$this->loader->add_action( 'rest_api_init', $rest_routes, 'register_routes' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return SASEC_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}

