<?php
/**
 * REST API routes.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST Routes class.
 */
class SASEC_REST_Routes {

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		$namespace = 'sasec/v1';

		// Settings endpoints
		register_rest_route(
			$namespace,
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_settings_permission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'check_settings_permission' ),
					'args'                => array(
						'settings' => array(
							'required' => true,
							'type'     => 'object',
						),
					),
				),
			)
		);

		// File scan endpoint
		register_rest_route(
			$namespace,
			'/scan/file',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'run_file_scan' ),
				'permission_callback' => array( $this, 'check_scan_permission' ),
			)
		);

		// Emergency token creation
		register_rest_route(
			$namespace,
			'/emergency/create',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_emergency_token' ),
				'permission_callback' => array( $this, 'check_emergency_permission' ),
				'args'                => array(
					'user_id'     => array(
						'required' => true,
						'type'     => 'integer',
					),
					'ttl_minutes' => array(
						'required' => false,
						'type'     => 'integer',
						'default'  => 15,
					),
				),
			)
		);

		// Emergency password set
		register_rest_route(
			$namespace,
			'/emergency/password',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'set_emergency_password' ),
				'permission_callback' => array( $this, 'check_emergency_permission' ),
				'args'                => array(
					'password' => array(
						'required' => true,
						'type'     => 'string',
					),
					'user_id'  => array(
						'required' => false,
						'type'     => 'integer',
						'default'  => 1,
					),
				),
			)
		);

		// Logs endpoint
		register_rest_route(
			$namespace,
			'/logs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_logs' ),
				'permission_callback' => array( $this, 'check_logs_permission' ),
				'args'                => array(
					'per_page'   => array(
						'default' => 50,
						'type'    => 'integer',
					),
					'page'       => array(
						'default' => 1,
						'type'    => 'integer',
					),
					'event_type' => array(
						'type' => 'string',
					),
					'severity'   => array(
						'type' => 'integer',
					),
					'search'     => array(
						'type' => 'string',
					),
				),
			)
		);

		// Test email endpoint
		register_rest_route(
			$namespace,
			'/test/email',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'test_email' ),
				'permission_callback' => array( $this, 'check_settings_permission' ),
			)
		);
	}

	/**
	 * Check settings permission.
	 *
	 * @return bool True if user has permission.
	 */
	public function check_settings_permission() {
		return current_user_can( 'sasec_manage_settings' );
	}

	/**
	 * Check scan permission.
	 *
	 * @return bool True if user has permission.
	 */
	public function check_scan_permission() {
		return current_user_can( 'sasec_run_scan' );
	}

	/**
	 * Check emergency permission.
	 *
	 * @return bool True if user has permission.
	 */
	public function check_emergency_permission() {
		return current_user_can( 'sasec_handle_emergency' );
	}

	/**
	 * Check logs permission.
	 *
	 * @return bool True if user has permission.
	 */
	public function check_logs_permission() {
		return current_user_can( 'sasec_view_logs' );
	}

	/**
	 * Get settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_settings( $request ) {
		$settings = array(
			'emergency_enabled'          => get_option( 'sasec_emergency_enabled', false ),
			'emergency_custom_url_slug'  => get_option( 'sasec_emergency_custom_url_slug', 'sasec-emergency' ),
			'emergency_token_ttl'        => get_option( 'sasec_emergency_token_ttl', 15 ),
			'emergency_one_time'         => get_option( 'sasec_emergency_one_time', false ),
			'emergency_user_id'          => get_option( 'sasec_emergency_user_id', 1 ),
			'detection_enabled'          => get_option( 'sasec_detection_enabled', true ),
			'detection_mode'             => get_option( 'sasec_detection_mode', 'log' ),
			'failed_login_threshold'     => get_option( 'sasec_failed_login_threshold', 5 ),
			'failed_login_window'        => get_option( 'sasec_failed_login_window', 15 ),
			'file_scan_enabled'          => get_option( 'sasec_file_scan_enabled', true ),
			'file_scan_mode'             => get_option( 'sasec_file_scan_mode', 'log' ),
			'ghost_mode_enabled'         => get_option( 'sasec_ghost_mode_enabled', false ),
			'notifications_enabled'       => get_option( 'sasec_notifications_enabled', true ),
			'notification_email'         => get_option( 'sasec_notification_email', '' ),
			'log_retention_days'         => get_option( 'sasec_log_retention_days', 90 ),
		);

		return rest_ensure_response( $settings );
	}

	/**
	 * Update settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function update_settings( $request ) {
		$settings = $request->get_param( 'settings' );

		if ( ! is_array( $settings ) ) {
			return new WP_Error( 'invalid_settings', __( 'Settings must be an object.', 'superadmin-secure' ), array( 'status' => 400 ) );
		}

		foreach ( $settings as $key => $value ) {
			$option_name = 'sasec_' . $key;
			update_option( $option_name, $value );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Run file scan.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function run_file_scan( $request ) {
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-file-scan.php';
		$file_scan = new SASEC_File_Scan();

		$result = $file_scan->run_incremental_scan();

		return rest_ensure_response( $result );
	}

	/**
	 * Create emergency token.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function create_emergency_token( $request ) {
		if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
			return new WP_Error( 'secret_key_missing', __( 'SASEC_SECRET_KEY is not defined.', 'superadmin-secure' ), array( 'status' => 500 ) );
		}

		$user_id     = $request->get_param( 'user_id' );
		$ttl_minutes = $request->get_param( 'ttl_minutes' ) ?? 15;

		$ip     = $this->get_client_ip();
		$agent  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-emergency.php';
		$emergency = new SASEC_Emergency();

		$token = $emergency->create_token( $user_id, $ttl_minutes, $ip, $agent );

		if ( $token === false ) {
			return new WP_Error( 'token_creation_failed', __( 'Failed to create token. Rate limit may be active.', 'superadmin-secure' ), array( 'status' => 429 ) );
		}

		$slug = get_option( 'sasec_emergency_custom_url_slug', 'sasec-emergency' );
		$url  = home_url( '/' . $slug . '?t=' . $token );

		return rest_ensure_response(
			array(
				'token' => $token,
				'url'   => $url,
				'ttl'   => $ttl_minutes,
			)
		);
	}

	/**
	 * Set emergency password.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function set_emergency_password( $request ) {
		if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
			return new WP_Error( 'secret_key_missing', __( 'SASEC_SECRET_KEY is not defined.', 'superadmin-secure' ), array( 'status' => 500 ) );
		}

		$password = $request->get_param( 'password' );
		$user_id  = $request->get_param( 'user_id' ) ?? 1;

		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-emergency.php';
		$emergency = new SASEC_Emergency();

		$result = $emergency->set_password( $password, $user_id );

		if ( ! $result ) {
			return new WP_Error( 'password_set_failed', __( 'Failed to set emergency password.', 'superadmin-secure' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get logs.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_logs( $request ) {
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-logger.php';
		$logger = new SASEC_Logger();

		$args = array(
			'per_page'   => $request->get_param( 'per_page' ) ?? 50,
			'page'       => $request->get_param( 'page' ) ?? 1,
			'event_type' => $request->get_param( 'event_type' ),
			'severity'   => $request->get_param( 'severity' ),
			'search'     => $request->get_param( 'search' ),
		);

		$result = $logger->get_logs( $args );

		return rest_ensure_response( $result );
	}

	/**
	 * Test email.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function test_email( $request ) {
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-notifier.php';
		$notifier = new SASEC_Notifier();

		$notifier->send_alert(
			'test_email',
			5,
			array(
				'message' => __( 'This is a test email from SuperAdmin Secure.', 'superadmin-secure' ),
			)
		);

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get client IP address.
	 *
	 * @return string IP address.
	 */
	private function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}

