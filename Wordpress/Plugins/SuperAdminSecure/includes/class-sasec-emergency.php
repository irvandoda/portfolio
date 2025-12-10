<?php
/**
 * Emergency login handler.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Emergency class.
 */
class SASEC_Emergency {

	/**
	 * Check for emergency login request.
	 */
	public function check_emergency_login() {
		if ( ! get_option( 'sasec_emergency_enabled', false ) ) {
			return;
		}

		$slug = get_option( 'sasec_emergency_custom_url_slug', 'sasec-emergency' );
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		// Check if request matches emergency URL
		if ( strpos( $request_uri, '/' . $slug ) === false ) {
			return;
		}

		// Check IP whitelist if configured
		if ( ! $this->check_ip_whitelist() ) {
			$this->log_emergency_attempt( 'ip_not_whitelisted', array() );
			wp_die( esc_html__( 'Access denied.', 'superadmin-secure' ), esc_html__( 'Forbidden', 'superadmin-secure' ), array( 'response' => 403 ) );
		}

		// Handle token mode
		if ( isset( $_GET['t'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_GET['t'] ) );
			$this->handle_token_login( $token );
			return;
		}

		// Handle password mode (POST request)
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['sasec_password'] ) ) {
			$password = sanitize_text_field( wp_unslash( $_POST['sasec_password'] ) );
			$this->handle_password_login( $password );
			return;
		}

		// Show login form
		$this->render_emergency_form();
		exit;
	}

	/**
	 * Handle token-based emergency login.
	 *
	 * @param string $token Token string.
	 */
	private function handle_token_login( $token ) {
		global $wpdb;

		if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
			wp_die( esc_html__( 'Emergency login is not properly configured.', 'superadmin-secure' ), esc_html__( 'Configuration Error', 'superadmin-secure' ), array( 'response' => 500 ) );
		}

		$token_hash = hash_hmac( 'sha512', $token, SASEC_SECRET_KEY );
		$table_name = $wpdb->prefix . 'sasec_emergency_tokens';

		$token_record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE token_hash = %s AND used = 0 AND expires_at > NOW()",
				$token_hash
			),
			ARRAY_A
		);

		if ( ! $token_record ) {
			$this->log_emergency_attempt( 'token_invalid', array( 'token_provided' => true ) );
			wp_die( esc_html__( 'Invalid or expired token.', 'superadmin-secure' ), esc_html__( 'Access Denied', 'superadmin-secure' ), array( 'response' => 403 ) );
		}

		// Mark token as used
		$wpdb->update(
			$table_name,
			array( 'used' => 1 ),
			array( 'id' => $token_record['id'] ),
			array( '%d' ),
			array( '%d' )
		);

		// Log successful emergency login
		$this->log_emergency_attempt( 'emergency_login_success', array( 'user_id' => $token_record['user_id'], 'method' => 'token' ) );

		// Set auth cookie
		$user = get_user_by( 'id', $token_record['user_id'] );
		if ( $user ) {
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID, true );
			do_action( 'wp_login', $user->user_login, $user );

			// Redirect to admin
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	/**
	 * Handle password-based emergency login.
	 *
	 * @param string $password Password provided.
	 */
	private function handle_password_login( $password ) {
		global $wpdb;

		if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
			wp_die( esc_html__( 'Emergency login is not properly configured.', 'superadmin-secure' ), esc_html__( 'Configuration Error', 'superadmin-secure' ), array( 'response' => 500 ) );
		}

		$table_name = $wpdb->prefix . 'sasec_config';
		$config      = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT cfg_value FROM {$table_name} WHERE cfg_key = %s",
				'emergency_password_hash'
			)
		);

		if ( ! $config ) {
			$this->log_emergency_attempt( 'password_not_configured', array() );
			wp_die( esc_html__( 'Emergency password is not configured.', 'superadmin-secure' ), esc_html__( 'Configuration Error', 'superadmin-secure' ), array( 'response' => 500 ) );
		}

		// Decrypt and verify password
		$stored_hash = $this->decrypt_config( $config );

		if ( ! password_verify( $password, $stored_hash ) ) {
			$this->log_emergency_attempt( 'password_invalid', array( 'password_provided' => true ) );
			wp_die( esc_html__( 'Invalid password.', 'superadmin-secure' ), esc_html__( 'Access Denied', 'superadmin-secure' ), array( 'response' => 403 ) );
		}

		// Check if one-time password and mark as used
		$one_time = get_option( 'sasec_emergency_one_time', true );
		if ( $one_time ) {
			$used_key = 'sasec_emergency_password_used';
			if ( get_option( $used_key, false ) ) {
				$this->log_emergency_attempt( 'password_already_used', array() );
				wp_die( esc_html__( 'This password has already been used and cannot be reused.', 'superadmin-secure' ), esc_html__( 'Access Denied', 'superadmin-secure' ), array( 'response' => 403 ) );
			}
			update_option( $used_key, true );
		}

		// Get user ID from config
		$user_id = absint( get_option( 'sasec_emergency_user_id', 1 ) );

		// Log successful emergency login
		$this->log_emergency_attempt( 'emergency_login_success', array( 'user_id' => $user_id, 'method' => 'password' ) );

		// Set auth cookie
		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID, true );
			do_action( 'wp_login', $user->user_login, $user );

			// Redirect to admin
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	/**
	 * Render emergency login form.
	 */
	private function render_emergency_form() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php esc_html_e( 'Emergency Login', 'superadmin-secure' ); ?></title>
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					background: #f0f0f1;
					display: flex;
					align-items: center;
					justify-content: center;
					min-height: 100vh;
					margin: 0;
				}
				.login-form {
					background: white;
					padding: 2rem;
					border-radius: 8px;
					box-shadow: 0 2px 10px rgba(0,0,0,0.1);
					max-width: 400px;
					width: 100%;
				}
				.login-form h1 {
					margin-top: 0;
					color: #1d2327;
				}
				.login-form input[type="password"] {
					width: 100%;
					padding: 12px;
					border: 1px solid #ddd;
					border-radius: 4px;
					font-size: 16px;
					box-sizing: border-box;
				}
				.login-form button {
					width: 100%;
					padding: 12px;
					background: #2271b1;
					color: white;
					border: none;
					border-radius: 4px;
					font-size: 16px;
					cursor: pointer;
					margin-top: 1rem;
				}
				.login-form button:hover {
					background: #135e96;
				}
				.error {
					color: #d63638;
					margin-top: 0.5rem;
				}
			</style>
		</head>
		<body>
			<div class="login-form">
				<h1><?php esc_html_e( 'Emergency Login', 'superadmin-secure' ); ?></h1>
				<form method="post">
					<label for="sasec_password"><?php esc_html_e( 'Emergency Password:', 'superadmin-secure' ); ?></label>
					<input type="password" id="sasec_password" name="sasec_password" required autofocus>
					<button type="submit"><?php esc_html_e( 'Login', 'superadmin-secure' ); ?></button>
				</form>
			</div>
		</body>
		</html>
		<?php
	}

	/**
	 * Check IP whitelist.
	 *
	 * @return bool True if IP is whitelisted or whitelist is disabled.
	 */
	private function check_ip_whitelist() {
		$whitelist = get_option( 'sasec_emergency_ip_whitelist', '' );
		if ( empty( $whitelist ) ) {
			return true; // No whitelist configured, allow all
		}

		$ip = $this->get_client_ip();
		$whitelist_ips = array_map( 'trim', explode( "\n", $whitelist ) );

		return in_array( $ip, $whitelist_ips, true );
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
	 * Log emergency attempt.
	 *
	 * @param string $event_type Event type.
	 * @param array  $data       Additional data.
	 */
	private function log_emergency_attempt( $event_type, $data = array() ) {
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-logger.php';
		$logger = new SASEC_Logger();

		$ip = $this->get_client_ip();
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$logger->log(
			$event_type,
			9,
			array_merge(
				$data,
				array(
					'ip'         => $ip,
					'user_agent' => $ua,
				)
			)
		);
	}

	/**
	 * Create emergency token.
	 *
	 * @param int    $user_id      User ID.
	 * @param int    $ttl_minutes  Time to live in minutes.
	 * @param string $created_ip   IP address of creator.
	 * @param string $created_agent User agent of creator.
	 * @return string|false Token string on success, false on failure.
	 */
	public function create_token( $user_id, $ttl_minutes = 15, $created_ip = null, $created_agent = null ) {
		global $wpdb;

		if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
			return false;
		}

		// Rate limit check
		if ( ! $this->check_rate_limit( $user_id ) ) {
			return false;
		}

		$token = bin2hex( random_bytes( 32 ) ); // 64 character hex string
		$token_hash = hash_hmac( 'sha512', $token, SASEC_SECRET_KEY );

		$expires_at = date( 'Y-m-d H:i:s', time() + ( $ttl_minutes * 60 ) );

		$table_name = $wpdb->prefix . 'sasec_emergency_tokens';

		$result = $wpdb->insert(
			$table_name,
			array(
				'token_hash'   => $token_hash,
				'user_id'      => absint( $user_id ),
				'expires_at'   => $expires_at,
				'created_ip'   => $created_ip ? sanitize_text_field( $created_ip ) : null,
				'created_agent' => $created_agent ? sanitize_text_field( $created_agent ) : null,
			),
			array( '%s', '%d', '%s', '%s', '%s' )
		);

		if ( $result ) {
			// Log token creation
			$this->log_emergency_attempt( 'emergency_token_created', array( 'user_id' => $user_id, 'ttl_minutes' => $ttl_minutes ) );

			// Send notification
			require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-notifier.php';
			$notifier = new SASEC_Notifier();
			$notifier->send_alert( 'emergency_token_created', 8, array( 'user_id' => $user_id ) );

			return $token;
		}

		return false;
	}

	/**
	 * Check rate limit for token creation.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if within rate limit.
	 */
	private function check_rate_limit( $user_id ) {
		$rate_limit_hours = absint( get_option( 'sasec_emergency_rate_limit_hours', 1 ) );
		$key = 'sasec_emergency_token_' . $user_id;

		$last_created = get_transient( $key );
		if ( $last_created !== false ) {
			return false; // Rate limited
		}

		set_transient( $key, time(), $rate_limit_hours * HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Encrypt config value.
	 *
	 * @param string $value Value to encrypt.
	 * @return string Encrypted value.
	 */
	private function encrypt_config( $value ) {
		if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
			return $value;
		}

		$method = 'AES-256-CBC';
		$key    = hash( 'sha256', SASEC_SECRET_KEY );
		$iv     = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $method ) );
		$encrypted = openssl_encrypt( $value, $method, $key, 0, $iv );
		return base64_encode( $iv . $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt config value.
	 *
	 * @param string $encrypted_value Encrypted value.
	 * @return string Decrypted value.
	 */
	private function decrypt_config( $encrypted_value ) {
		if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
			return $encrypted_value;
		}

		$method = 'AES-256-CBC';
		$key    = hash( 'sha256', SASEC_SECRET_KEY );
		$data   = base64_decode( $encrypted_value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$iv_len = openssl_cipher_iv_length( $method );
		$iv     = substr( $data, 0, $iv_len );
		$encrypted = substr( $data, $iv_len );
		return openssl_decrypt( $encrypted, $method, $key, 0, $iv );
	}

	/**
	 * Set emergency password.
	 *
	 * @param string $password Plaintext password.
	 * @param int    $user_id  User ID for emergency login.
	 * @return bool True on success.
	 */
	public function set_password( $password, $user_id = 1 ) {
		global $wpdb;

		if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
			return false;
		}

		$hashed = password_hash( $password, PASSWORD_BCRYPT );
		$encrypted = $this->encrypt_config( $hashed );

		$table_name = $wpdb->prefix . 'sasec_config';

		$wpdb->replace(
			$table_name,
			array(
				'cfg_key'   => 'emergency_password_hash',
				'cfg_value' => $encrypted,
			),
			array( '%s', '%s' )
		);

		update_option( 'sasec_emergency_user_id', absint( $user_id ) );

		// Reset one-time password flag
		delete_option( 'sasec_emergency_password_used' );

		return true;
	}
}

