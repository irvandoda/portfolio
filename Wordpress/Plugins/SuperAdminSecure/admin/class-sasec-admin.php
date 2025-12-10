<?php
/**
 * Admin area functionality.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 */
class SASEC_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		// No external styles; keep UI minimal and dependency-free.
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		// No scripts required for the pure PHP admin UI.
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'SuperAdmin Secure', 'superadmin-secure' ),
			__( 'SASEC', 'superadmin-secure' ),
			'sasec_manage_settings',
			'sasec-dashboard',
			array( $this, 'render_admin_page' ),
			'dashicons-shield-alt',
			30
		);

		add_submenu_page(
			'sasec-dashboard',
			__( 'Dashboard', 'superadmin-secure' ),
			__( 'Dashboard', 'superadmin-secure' ),
			'sasec_manage_settings',
			'sasec-dashboard',
			array( $this, 'render_admin_page' )
		);

		add_submenu_page(
			'sasec-dashboard',
			__( 'Settings', 'superadmin-secure' ),
			__( 'Settings', 'superadmin-secure' ),
			'sasec_manage_settings',
			'sasec-settings',
			array( $this, 'render_admin_page' )
		);

		add_submenu_page(
			'sasec-dashboard',
			__( 'Logs', 'superadmin-secure' ),
			__( 'Logs', 'superadmin-secure' ),
			'sasec_view_logs',
			'sasec-logs',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		// Settings are handled via REST API
	}

	/**
	 * Render admin page.
	 */
	public function render_admin_page() {
		// Handle actions.
		$notice = '';
		$slug   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'sasec-dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( isset( $_POST['sasec_action'] ) && check_admin_referer( 'sasec_admin_action', 'sasec_nonce' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$action = sanitize_text_field( wp_unslash( $_POST['sasec_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			switch ( $action ) {
				case 'save_settings':
					$this->handle_save_settings();
					$notice = __( 'Settings saved.', 'superadmin-secure' );
					break;
				case 'set_emergency_password':
					$notice = $this->handle_set_emergency_password();
					break;
				case 'create_emergency_token':
					$notice = $this->handle_create_emergency_token();
					break;
				default:
					break;
			}
		}

		$current_settings = $this->get_settings();

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
			<?php endif; ?>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sasec-dashboard' ) ); ?>" class="nav-tab <?php echo $slug === 'sasec-dashboard' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Dashboard', 'superadmin-secure' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sasec-settings' ) ); ?>" class="nav-tab <?php echo $slug === 'sasec-settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings', 'superadmin-secure' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sasec-logs' ) ); ?>" class="nav-tab <?php echo $slug === 'sasec-logs' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Logs', 'superadmin-secure' ); ?></a>
			</h2>

			<?php
			if ( $slug === 'sasec-dashboard' || $slug === 'sasec-settings' ) {
				$this->render_settings_form( $current_settings );
				$this->render_emergency_password_form( $current_settings );
				$this->render_emergency_token_form( $current_settings );
			} elseif ( $slug === 'sasec-logs' ) {
				$this->render_logs_note();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Fetch settings.
	 *
	 * @return array
	 */
	private function get_settings() {
		return array(
			'emergency_enabled'          => (bool) get_option( 'sasec_emergency_enabled', true ),
			'emergency_custom_url_slug'  => get_option( 'sasec_emergency_custom_url_slug', 'pintubelakang' ),
			'emergency_token_ttl'        => absint( get_option( 'sasec_emergency_token_ttl', 15 ) ),
			'emergency_one_time'         => (bool) get_option( 'sasec_emergency_one_time', false ),
			'emergency_user_id'          => absint( get_option( 'sasec_emergency_user_id', 1 ) ),
			'detection_enabled'          => (bool) get_option( 'sasec_detection_enabled', true ),
			'detection_mode'             => get_option( 'sasec_detection_mode', 'log' ),
			'failed_login_threshold'     => absint( get_option( 'sasec_failed_login_threshold', 5 ) ),
			'failed_login_window'        => absint( get_option( 'sasec_failed_login_window', 15 ) ),
			'file_scan_enabled'          => (bool) get_option( 'sasec_file_scan_enabled', true ),
			'file_scan_mode'             => get_option( 'sasec_file_scan_mode', 'log' ),
			'notifications_enabled'      => (bool) get_option( 'sasec_notifications_enabled', true ),
			'notification_email'         => get_option( 'sasec_notification_email', '' ),
			'log_retention_days'         => absint( get_option( 'sasec_log_retention_days', 90 ) ),
		);
	}

	/**
	 * Handle saving settings.
	 */
	private function handle_save_settings() {
		$fields = array(
			'sasec_emergency_enabled'          => isset( $_POST['sasec_emergency_enabled'] ) ? 1 : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_emergency_custom_url_slug'  => isset( $_POST['sasec_emergency_custom_url_slug'] ) ? sanitize_title( wp_unslash( $_POST['sasec_emergency_custom_url_slug'] ) ) : 'pintubelakang', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_emergency_token_ttl'        => isset( $_POST['sasec_emergency_token_ttl'] ) ? absint( $_POST['sasec_emergency_token_ttl'] ) : 15, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_emergency_one_time'         => isset( $_POST['sasec_emergency_one_time'] ) ? 1 : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_emergency_user_id'          => isset( $_POST['sasec_emergency_user_id'] ) ? absint( $_POST['sasec_emergency_user_id'] ) : 1, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_detection_enabled'          => isset( $_POST['sasec_detection_enabled'] ) ? 1 : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_detection_mode'             => isset( $_POST['sasec_detection_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['sasec_detection_mode'] ) ) : 'log', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_failed_login_threshold'     => isset( $_POST['sasec_failed_login_threshold'] ) ? absint( $_POST['sasec_failed_login_threshold'] ) : 5, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_failed_login_window'        => isset( $_POST['sasec_failed_login_window'] ) ? absint( $_POST['sasec_failed_login_window'] ) : 15, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_file_scan_enabled'          => isset( $_POST['sasec_file_scan_enabled'] ) ? 1 : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_file_scan_mode'             => isset( $_POST['sasec_file_scan_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['sasec_file_scan_mode'] ) ) : 'log', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_notifications_enabled'      => isset( $_POST['sasec_notifications_enabled'] ) ? 1 : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_notification_email'         => isset( $_POST['sasec_notification_email'] ) ? sanitize_email( wp_unslash( $_POST['sasec_notification_email'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'sasec_log_retention_days'         => isset( $_POST['sasec_log_retention_days'] ) ? absint( $_POST['sasec_log_retention_days'] ) : 90, // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);

		foreach ( $fields as $key => $value ) {
			update_option( $key, $value );
		}

		// Flush rewrites when slug changes.
		flush_rewrite_rules();
	}

	/**
	 * Render settings form.
	 *
	 * @param array $settings Settings.
	 */
	private function render_settings_form( $settings ) {
		?>
		<h2><?php esc_html_e( 'Emergency Login', 'superadmin-secure' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'sasec_admin_action', 'sasec_nonce' ); ?>
			<input type="hidden" name="sasec_action" value="save_settings" />

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Emergency Login', 'superadmin-secure' ); ?></th>
					<td><input type="checkbox" name="sasec_emergency_enabled" <?php checked( $settings['emergency_enabled'], true ); ?> /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Custom URL Slug', 'superadmin-secure' ); ?></th>
					<td><input type="text" name="sasec_emergency_custom_url_slug" value="<?php echo esc_attr( $settings['emergency_custom_url_slug'] ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Token TTL (minutes)', 'superadmin-secure' ); ?></th>
					<td><input type="number" name="sasec_emergency_token_ttl" value="<?php echo esc_attr( $settings['emergency_token_ttl'] ); ?>" min="1" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Emergency User ID', 'superadmin-secure' ); ?></th>
					<td><input type="number" name="sasec_emergency_user_id" value="<?php echo esc_attr( $settings['emergency_user_id'] ); ?>" min="1" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'One-Time Password', 'superadmin-secure' ); ?></th>
					<td><input type="checkbox" name="sasec_emergency_one_time" <?php checked( $settings['emergency_one_time'], true ); ?> /></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Login Detection', 'superadmin-secure' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Detection', 'superadmin-secure' ); ?></th>
					<td><input type="checkbox" name="sasec_detection_enabled" <?php checked( $settings['detection_enabled'], true ); ?> /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Mode', 'superadmin-secure' ); ?></th>
					<td>
						<select name="sasec_detection_mode">
							<option value="log" <?php selected( $settings['detection_mode'], 'log' ); ?>><?php esc_html_e( 'Log only', 'superadmin-secure' ); ?></option>
							<option value="protect" <?php selected( $settings['detection_mode'], 'protect' ); ?>><?php esc_html_e( 'Protect', 'superadmin-secure' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Failed Login Threshold', 'superadmin-secure' ); ?></th>
					<td><input type="number" name="sasec_failed_login_threshold" value="<?php echo esc_attr( $settings['failed_login_threshold'] ); ?>" min="1" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Failed Login Window (minutes)', 'superadmin-secure' ); ?></th>
					<td><input type="number" name="sasec_failed_login_window" value="<?php echo esc_attr( $settings['failed_login_window'] ); ?>" min="1" /></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'File Integrity Scanner', 'superadmin-secure' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable File Scan', 'superadmin-secure' ); ?></th>
					<td><input type="checkbox" name="sasec_file_scan_enabled" <?php checked( $settings['file_scan_enabled'], true ); ?> /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Mode', 'superadmin-secure' ); ?></th>
					<td>
						<select name="sasec_file_scan_mode">
							<option value="log" <?php selected( $settings['file_scan_mode'], 'log' ); ?>><?php esc_html_e( 'Log only', 'superadmin-secure' ); ?></option>
							<option value="protect" <?php selected( $settings['file_scan_mode'], 'protect' ); ?>><?php esc_html_e( 'Protect', 'superadmin-secure' ); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Notifications', 'superadmin-secure' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Notifications', 'superadmin-secure' ); ?></th>
					<td><input type="checkbox" name="sasec_notifications_enabled" <?php checked( $settings['notifications_enabled'], true ); ?> /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Notification Email', 'superadmin-secure' ); ?></th>
					<td><input type="email" name="sasec_notification_email" value="<?php echo esc_attr( $settings['notification_email'] ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Log Retention (days)', 'superadmin-secure' ); ?></th>
					<td><input type="number" name="sasec_log_retention_days" value="<?php echo esc_attr( $settings['log_retention_days'] ); ?>" min="1" /></td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'superadmin-secure' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Render emergency password form.
	 *
	 * @param array $settings Settings.
	 */
	private function render_emergency_password_form( $settings ) {
		?>
		<hr />
		<h2><?php esc_html_e( 'Emergency Password', 'superadmin-secure' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'sasec_admin_action', 'sasec_nonce' ); ?>
			<input type="hidden" name="sasec_action" value="set_emergency_password" />

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'User ID', 'superadmin-secure' ); ?></th>
					<td><input type="number" name="sasec_emergency_user_id" value="<?php echo esc_attr( $settings['emergency_user_id'] ); ?>" min="1" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Password', 'superadmin-secure' ); ?></th>
					<td><input type="password" name="sasec_emergency_password" value="" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Confirm Password', 'superadmin-secure' ); ?></th>
					<td><input type="password" name="sasec_emergency_password_confirm" value="" class="regular-text" /></td>
				</tr>
			</table>

			<?php submit_button( __( 'Set Emergency Password', 'superadmin-secure' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Handle emergency password set.
	 *
	 * @return string Notice message.
	 */
	private function handle_set_emergency_password() {
		$user_id  = isset( $_POST['sasec_emergency_user_id'] ) ? absint( $_POST['sasec_emergency_user_id'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$pass     = isset( $_POST['sasec_emergency_password'] ) ? wp_unslash( $_POST['sasec_emergency_password'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$confirm  = isset( $_POST['sasec_emergency_password_confirm'] ) ? wp_unslash( $_POST['sasec_emergency_password_confirm'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $pass ) || $pass !== $confirm ) {
			return __( 'Password empty or confirmation mismatch.', 'superadmin-secure' );
		}

		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-emergency.php';
		$emergency = new SASEC_Emergency();
		$emergency->set_password( $pass, $user_id );

		update_option( 'sasec_emergency_user_id', $user_id );

		return __( 'Emergency password updated.', 'superadmin-secure' );
	}

	/**
	 * Render emergency token form.
	 *
	 * @param array $settings Settings.
	 */
	private function render_emergency_token_form( $settings ) {
		?>
		<hr />
		<h2><?php esc_html_e( 'Emergency Token', 'superadmin-secure' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'sasec_admin_action', 'sasec_nonce' ); ?>
			<input type="hidden" name="sasec_action" value="create_emergency_token" />

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'User ID', 'superadmin-secure' ); ?></th>
					<td><input type="number" name="sasec_emergency_user_id" value="<?php echo esc_attr( $settings['emergency_user_id'] ); ?>" min="1" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Token TTL (minutes)', 'superadmin-secure' ); ?></th>
					<td><input type="number" name="sasec_emergency_token_ttl" value="<?php echo esc_attr( $settings['emergency_token_ttl'] ); ?>" min="1" /></td>
				</tr>
			</table>

			<?php submit_button( __( 'Create Emergency Token', 'superadmin-secure' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Handle emergency token creation.
	 *
	 * @return string Notice message.
	 */
	private function handle_create_emergency_token() {
		$user_id = isset( $_POST['sasec_emergency_user_id'] ) ? absint( $_POST['sasec_emergency_user_id'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ttl     = isset( $_POST['sasec_emergency_token_ttl'] ) ? absint( $_POST['sasec_emergency_token_ttl'] ) : 15; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-emergency.php';
		$emergency = new SASEC_Emergency();
		$token     = $emergency->create_token( $user_id, $ttl, null, 'admin-panel' );

		if ( ! $token ) {
			return __( 'Failed to create token (maybe rate limited).', 'superadmin-secure' );
		}

		$slug = get_option( 'sasec_emergency_custom_url_slug', 'pintubelakang' );
		$url  = home_url( '/' . $slug . '?t=' . $token );

		return sprintf(
			/* translators: 1: token 2: URL */
			__( 'Token created: %1$s — URL: %2$s', 'superadmin-secure' ),
			$token,
			$url
		);
	}

	/**
	 * Render logs note.
	 */
	private function render_logs_note() {
		?>
		<p><?php esc_html_e( 'Logs are stored in the database. A minimal viewer is not yet implemented in PHP UI.', 'superadmin-secure' ); ?></p>
		<?php
	}
}

