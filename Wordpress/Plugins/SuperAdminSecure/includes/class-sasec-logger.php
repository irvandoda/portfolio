<?php
/**
 * Logger class for security events.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger class.
 */
class SASEC_Logger {

	/**
	 * Log an event.
	 *
	 * @param string $event_type Event type (e.g., 'login_failed', 'emergency_login').
	 * @param int    $severity   Severity level (1-10, 10 being most critical).
	 * @param array  $data       Additional data to log.
	 * @return int|false Log ID on success, false on failure.
	 */
	public function log( $event_type, $severity = 5, $data = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'sasec_logs';

		$defaults = array(
			'user_id'   => get_current_user_id(),
			'username'  => null,
			'ip'        => $this->get_client_ip(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : null,
			'payload'   => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		// Anonymize IP if configured
		if ( get_option( 'sasec_anonymize_ips', false ) ) {
			$data['ip'] = $this->anonymize_ip( $data['ip'] );
		}

		// Get username if user_id is set
		if ( $data['user_id'] && ! $data['username'] ) {
			$user = get_user_by( 'id', $data['user_id'] );
			if ( $user ) {
				$data['username'] = $user->user_login;
			}
		}

		$payload_json = wp_json_encode( $data['payload'] );

		$result = $wpdb->insert(
			$table_name,
			array(
				'event_type'  => sanitize_text_field( $event_type ),
				'severity'    => absint( $severity ),
				'user_id'     => $data['user_id'] ? absint( $data['user_id'] ) : null,
				'username'    => $data['username'] ? sanitize_text_field( $data['username'] ) : null,
				'ip'          => $data['ip'] ? sanitize_text_field( $data['ip'] ) : null,
				'user_agent'  => $data['user_agent'],
				'payload'     => $payload_json,
				'processed'   => 0,
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d' )
		);

		if ( $result ) {
			$log_id = $wpdb->insert_id;

			// Trigger notification if severity is high
			if ( $severity >= 7 ) {
				$this->trigger_notification( $event_type, $severity, $data );
			}

			// Clean old logs
			$this->clean_old_logs();

			return $log_id;
		}

		return false;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string IP address.
	 */
	private function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_REAL_IP',        // Nginx proxy
			'HTTP_X_FORWARDED_FOR',  // Proxy
			'REMOTE_ADDR',            // Standard
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated IPs (X-Forwarded-For)
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
	 * Anonymize IP address (store /24 for IPv4).
	 *
	 * @param string $ip IP address.
	 * @return string Anonymized IP.
	 */
	private function anonymize_ip( $ip ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$parts = explode( '.', $ip );
			return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
		} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			// For IPv6, mask last 64 bits
			$parts = explode( ':', $ip );
			$parts[4] = '0000';
			$parts[5] = '0000';
			$parts[6] = '0000';
			$parts[7] = '0000';
			return implode( ':', $parts );
		}
		return $ip;
	}

	/**
	 * Trigger notification for high-severity events.
	 *
	 * @param string $event_type Event type.
	 * @param int    $severity   Severity level.
	 * @param array  $data       Event data.
	 */
	private function trigger_notification( $event_type, $severity, $data ) {
		if ( ! get_option( 'sasec_notifications_enabled', true ) ) {
			return;
		}

		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-notifier.php';
		$notifier = new SASEC_Notifier();
		$notifier->send_alert( $event_type, $severity, $data );
	}

	/**
	 * Clean old logs based on retention policy.
	 */
	private function clean_old_logs() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sasec_logs';

		$retention_days = absint( get_option( 'sasec_log_retention_days', 90 ) );
		$cutoff_date    = date( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE created_at < %s",
				$cutoff_date
			)
		);
	}

	/**
	 * Get logs with pagination.
	 *
	 * @param array $args Query arguments.
	 * @return array Logs and total count.
	 */
	public function get_logs( $args = array() ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sasec_logs';

		$defaults = array(
			'per_page'   => 50,
			'page'       => 1,
			'event_type' => null,
			'severity'   => null,
			'user_id'    => null,
			'search'     => null,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		if ( $args['event_type'] ) {
			$where[]        = 'event_type = %s';
			$where_values[] = $args['event_type'];
		}

		if ( $args['severity'] !== null ) {
			$where[]        = 'severity = %d';
			$where_values[] = absint( $args['severity'] );
		}

		if ( $args['user_id'] ) {
			$where[]        = 'user_id = %d';
			$where_values[] = absint( $args['user_id'] );
		}

		if ( $args['search'] ) {
			$where[]        = '(username LIKE %s OR ip LIKE %s OR user_agent LIKE %s)';
			$search_term    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_clause = implode( ' AND ', $where );

		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Get total count
		$count_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
		if ( ! empty( $where_values ) ) {
			$count_query = $wpdb->prepare( $count_query, $where_values );
		}
		$total = (int) $wpdb->get_var( $count_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Get logs
		$query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$where_values[] = $args['per_page'];
		$where_values[] = $offset;
		$query = $wpdb->prepare( $query, $where_values );

		$logs = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Decode payload JSON
		foreach ( $logs as &$log ) {
			if ( $log['payload'] ) {
				$log['payload'] = json_decode( $log['payload'], true );
			}
		}

		return array(
			'logs'  => $logs,
			'total' => $total,
			'pages' => ceil( $total / $args['per_page'] ),
		);
	}
}

