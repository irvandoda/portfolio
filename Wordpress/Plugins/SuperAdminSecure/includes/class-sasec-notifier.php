<?php
/**
 * Notification handler class.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notifier class.
 */
class SASEC_Notifier {

	/**
	 * Send alert notification.
	 *
	 * @param string $event_type Event type.
	 * @param int    $severity   Severity level.
	 * @param array  $data       Event data.
	 */
	public function send_alert( $event_type, $severity, $data = array() ) {
		if ( ! get_option( 'sasec_notifications_enabled', true ) ) {
			return;
		}

		$channels = get_option( 'sasec_notification_channels', array( 'email' ) );

		if ( in_array( 'email', $channels, true ) ) {
			$this->send_email( $event_type, $severity, $data );
		}

		if ( in_array( 'webhook', $channels, true ) ) {
			$this->send_webhook( $event_type, $severity, $data );
		}
	}

	/**
	 * Send email notification.
	 *
	 * @param string $event_type Event type.
	 * @param int    $severity   Severity level.
	 * @param array  $data       Event data.
	 */
	private function send_email( $event_type, $severity, $data ) {
		$email = get_option( 'sasec_notification_email' );
		if ( empty( $email ) ) {
			$email = get_option( 'admin_email' );
		}

		if ( empty( $email ) ) {
			return;
		}

		$subject = sprintf(
			'[%s] Security Alert: %s (Severity: %d/10)',
			get_bloginfo( 'name' ),
			ucwords( str_replace( '_', ' ', $event_type ) ),
			$severity
		);

		$message = $this->format_email_message( $event_type, $severity, $data );

		// Use SMTP if configured
		$smtp_enabled = get_option( 'sasec_smtp_enabled', false );
		if ( $smtp_enabled ) {
			$this->send_smtp_email( $email, $subject, $message );
		} else {
			wp_mail( $email, $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
		}
	}

	/**
	 * Format email message.
	 *
	 * @param string $event_type Event type.
	 * @param int    $severity   Severity level.
	 * @param array  $data       Event data.
	 * @return string Formatted message.
	 */
	private function format_email_message( $event_type, $severity, $data ) {
		$site_name = get_bloginfo( 'name' );
		$site_url  = home_url();
		$timestamp = current_time( 'mysql' );

		$severity_label = $this->get_severity_label( $severity );

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
		</head>
		<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
			<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
				<h2 style="color: #d63638;">Security Alert</h2>
				<p><strong>Site:</strong> <?php echo esc_html( $site_name ); ?> (<?php echo esc_url( $site_url ); ?>)</p>
				<p><strong>Event Type:</strong> <?php echo esc_html( ucwords( str_replace( '_', ' ', $event_type ) ) ); ?></p>
				<p><strong>Severity:</strong> <span style="color: <?php echo esc_attr( $this->get_severity_color( $severity ) ); ?>;"><?php echo esc_html( $severity_label ); ?> (<?php echo esc_html( $severity ); ?>/10)</span></p>
				<p><strong>Timestamp:</strong> <?php echo esc_html( $timestamp ); ?></p>

				<h3>Event Details:</h3>
				<pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;"><?php echo esc_html( wp_json_encode( $data, JSON_PRETTY_PRINT ) ); ?></pre>

				<hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">
				<p style="font-size: 12px; color: #666;">
					This is an automated security alert from SuperAdmin Secure plugin.
				</p>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get severity label.
	 *
	 * @param int $severity Severity level.
	 * @return string Label.
	 */
	private function get_severity_label( $severity ) {
		if ( $severity >= 9 ) {
			return 'Critical';
		} elseif ( $severity >= 7 ) {
			return 'High';
		} elseif ( $severity >= 5 ) {
			return 'Medium';
		} else {
			return 'Low';
		}
	}

	/**
	 * Get severity color.
	 *
	 * @param int $severity Severity level.
	 * @return string Color hex.
	 */
	private function get_severity_color( $severity ) {
		if ( $severity >= 9 ) {
			return '#d63638';
		} elseif ( $severity >= 7 ) {
			return '#f56e28';
		} elseif ( $severity >= 5 ) {
			return '#f0b849';
		} else {
			return '#72aee6';
		}
	}

	/**
	 * Send email via SMTP.
	 *
	 * @param string $to      Recipient email.
	 * @param string $subject Subject.
	 * @param string $message Message body.
	 */
	private function send_smtp_email( $to, $subject, $message ) {
		$smtp_host = get_option( 'sasec_smtp_host' );
		$smtp_port = get_option( 'sasec_smtp_port', 587 );
		$smtp_user = get_option( 'sasec_smtp_user' );
		$smtp_pass = $this->get_decrypted_smtp_password();
		$smtp_encryption = get_option( 'sasec_smtp_encryption', 'tls' );

		if ( empty( $smtp_host ) || empty( $smtp_user ) || empty( $smtp_pass ) ) {
			return;
		}

		// Use PHPMailer if available
		if ( ! class_exists( 'PHPMailer\PHPMailer\PHPMailer' ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		}

		$mail = new PHPMailer\PHPMailer\PHPMailer( true );

		try {
			$mail->isSMTP();
			$mail->Host       = $smtp_host;
			$mail->SMTPAuth   = true;
			$mail->Username   = $smtp_user;
			$mail->Password   = $smtp_pass;
			$mail->SMTPSecure = $smtp_encryption;
			$mail->Port       = $smtp_port;

			$mail->setFrom( $smtp_user, get_bloginfo( 'name' ) );
			$mail->addAddress( $to );
			$mail->isHTML( true );
			$mail->Subject = $subject;
			$mail->Body    = $message;

			$mail->send();
		} catch ( Exception $e ) {
			error_log( 'SASEC SMTP Error: ' . $mail->ErrorInfo );
		}
	}

	/**
	 * Get decrypted SMTP password.
	 *
	 * @return string Decrypted password.
	 */
	private function get_decrypted_smtp_password() {
		global $wpdb;

		if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
			return '';
		}

		$table_name = $wpdb->prefix . 'sasec_config';
		$encrypted = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT cfg_value FROM {$table_name} WHERE cfg_key = %s",
				'smtp_password'
			)
		);

		if ( ! $encrypted ) {
			return '';
		}

		return $this->decrypt_config( $encrypted );
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
	 * Send webhook notification.
	 *
	 * @param string $event_type Event type.
	 * @param int    $severity   Severity level.
	 * @param array  $data       Event data.
	 */
	private function send_webhook( $event_type, $severity, $data ) {
		$webhook_url = get_option( 'sasec_webhook_url' );
		if ( empty( $webhook_url ) ) {
			return;
		}

		$payload = array(
			'event_type' => $event_type,
			'severity'   => $severity,
			'site'       => get_bloginfo( 'name' ),
			'site_url'   => home_url(),
			'timestamp'  => current_time( 'mysql' ),
			'data'       => $data,
		);

		$body = wp_json_encode( $payload );

		// Generate HMAC signature
		if ( defined( 'SASEC_SECRET_KEY' ) ) {
			$signature = hash_hmac( 'sha256', $body, SASEC_SECRET_KEY );
		} else {
			$signature = '';
		}

		$args = array(
			'body'    => $body,
			'headers' => array(
				'Content-Type'      => 'application/json',
				'X-SASEC-Signature' => $signature,
			),
			'timeout' => 10,
		);

		wp_remote_post( $webhook_url, $args );
	}
}

