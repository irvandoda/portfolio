<?php
/**
 * Login detection and monitoring class.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detection class.
 */
class SASEC_Detection {

	/**
	 * Log failed login attempt.
	 *
	 * @param string $username Username that failed to login.
	 */
	public function log_failed_login( $username ) {
		if ( ! get_option( 'sasec_detection_enabled', true ) ) {
			return;
		}

		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-logger.php';
		$logger = new SASEC_Logger();

		$ip = $this->get_client_ip();
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		// Get geo data if available
		$geo_data = $this->get_geo_data( $ip );

		$payload = array(
			'referer'    => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : null,
			'geo'        => $geo_data,
			'timestamp'  => current_time( 'mysql' ),
		);

		$logger->log(
			'login_failed',
			6,
			array(
				'username'  => sanitize_user( $username ),
				'ip'        => $ip,
				'user_agent' => $ua,
				'payload'   => $payload,
			)
		);

		// Increment failure counter
		$this->increment_failure_counter( $username, $ip );

		// Check threshold and take action
		$this->check_threshold( $username, $ip );
	}

	/**
	 * Log successful login.
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user       User object.
	 */
	public function log_successful_login( $user_login, $user ) {
		if ( ! get_option( 'sasec_detection_enabled', true ) ) {
			return;
		}

		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-logger.php';
		$logger = new SASEC_Logger();

		$ip = $this->get_client_ip();
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		// Check if there were prior failures
		$prior_failures = $this->get_failure_count( $user_login, $ip );

		$payload = array(
			'prior_failures' => $prior_failures,
			'user_role'      => $user->roles,
			'geo'            => $this->get_geo_data( $ip ),
			'timestamp'      => current_time( 'mysql' ),
		);

		$severity = $prior_failures > 0 ? 7 : 4;

		$logger->log(
			'login_success',
			$severity,
			array(
				'user_id'    => $user->ID,
				'username'   => $user_login,
				'ip'         => $ip,
				'user_agent' => $ua,
				'payload'    => $payload,
			)
		);

		// Clear failure counters on successful login
		$this->clear_failure_counters( $user_login, $ip );
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

	/**
	 * Get geo data for IP (basic implementation).
	 *
	 * @param string $ip IP address.
	 * @return array Geo data.
	 */
	private function get_geo_data( $ip ) {
		// Basic implementation - can be extended with GeoIP library
		return array(
			'ip' => $ip,
		);
	}

	/**
	 * Increment failure counter for username/IP.
	 *
	 * @param string $username Username.
	 * @param string $ip       IP address.
	 */
	private function increment_failure_counter( $username, $ip ) {
		$window = absint( get_option( 'sasec_failed_login_window', 15 ) ); // minutes

		// Per-username counter
		$user_key = 'sasec_failures_' . md5( $username );
		$user_count = (int) get_transient( $user_key );
		set_transient( $user_key, $user_count + 1, $window * MINUTE_IN_SECONDS );

		// Per-IP counter
		$ip_key = 'sasec_failures_ip_' . md5( $ip );
		$ip_count = (int) get_transient( $ip_key );
		set_transient( $ip_key, $ip_count + 1, $window * MINUTE_IN_SECONDS );
	}

	/**
	 * Get failure count for username/IP.
	 *
	 * @param string $username Username.
	 * @param string $ip       IP address.
	 * @return int Failure count.
	 */
	private function get_failure_count( $username, $ip ) {
		$user_key = 'sasec_failures_' . md5( $username );
		$ip_key   = 'sasec_failures_ip_' . md5( $ip );

		$user_count = (int) get_transient( $user_key );
		$ip_count   = (int) get_transient( $ip_key );

		return max( $user_count, $ip_count );
	}

	/**
	 * Clear failure counters.
	 *
	 * @param string $username Username.
	 * @param string $ip       IP address.
	 */
	private function clear_failure_counters( $username, $ip ) {
		$user_key = 'sasec_failures_' . md5( $username );
		$ip_key   = 'sasec_failures_ip_' . md5( $ip );

		delete_transient( $user_key );
		delete_transient( $ip_key );
	}

	/**
	 * Check if threshold exceeded and take action.
	 *
	 * @param string $username Username.
	 * @param string $ip       IP address.
	 */
	private function check_threshold( $username, $ip ) {
		$threshold = absint( get_option( 'sasec_failed_login_threshold', 5 ) );
		$mode      = get_option( 'sasec_detection_mode', 'log' );

		$user_count = $this->get_failure_count( $username, $ip );

		if ( $user_count >= $threshold ) {
			// Log threshold exceeded
			require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-logger.php';
			$logger = new SASEC_Logger();
			$logger->log(
				'threshold_exceeded',
				8,
				array(
					'username' => $username,
					'ip'       => $ip,
					'count'    => $user_count,
					'threshold' => $threshold,
				)
			);

			// Take action if in protect mode
			if ( $mode === 'protect' ) {
				$this->block_ip( $ip );
			}
		}
	}

	/**
	 * Block IP address (via .htaccess or in-memory).
	 *
	 * @param string $ip IP address to block.
	 */
	private function block_ip( $ip ) {
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-htaccess.php';
		$htaccess = new SASEC_Htaccess();
		$htaccess->block_ip( $ip );
	}
}

