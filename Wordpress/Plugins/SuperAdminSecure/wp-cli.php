<?php
/**
 * WP-CLI commands for SuperAdmin Secure.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * SuperAdmin Secure WP-CLI commands.
 */
class SASEC_WP_CLI_Command extends WP_CLI_Command {

	/**
	 * Run file integrity scan.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Type of scan (baseline|incremental)
	 * ---
	 * default: incremental
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp sasec scan --type=incremental
	 *     wp sasec scan --type=baseline
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function scan( $args, $assoc_args ) {
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-file-scan.php';
		$file_scan = new SASEC_File_Scan();

		$type = isset( $assoc_args['type'] ) ? $assoc_args['type'] : 'incremental';

		WP_CLI::line( "Running {$type} file scan..." );

		if ( $type === 'baseline' ) {
			$count = $file_scan->create_baseline();
			WP_CLI::success( "Baseline created with {$count} files." );
		} else {
			$result = $file_scan->run_incremental_scan();
			WP_CLI::success(
				sprintf(
					'Scan complete. Changes: %d, New files: %d, Missing files: %d',
					$result['changes'],
					$result['new_files'],
					$result['missing_files']
				)
			);
		}
	}

	/**
	 * Quarantine a file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to file to quarantine
	 *
	 * ## EXAMPLES
	 *
	 *     wp sasec quarantine /path/to/suspicious.php
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function quarantine( $args, $assoc_args ) {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'File path is required.' );
		}

		$file_path = $args[0];

		if ( ! file_exists( $file_path ) ) {
			WP_CLI::error( "File does not exist: {$file_path}" );
		}

		$quarantine_dir = WP_CONTENT_DIR . '/sasec-quarantine';
		if ( ! is_dir( $quarantine_dir ) ) {
			wp_mkdir_p( $quarantine_dir );
		}

		$quarantine_path = $quarantine_dir . '/' . basename( $file_path ) . '.' . time();

		if ( rename( $file_path, $quarantine_path ) ) {
			chmod( $quarantine_path, 0444 );
			WP_CLI::success( "File quarantined: {$quarantine_path}" );
		} else {
			WP_CLI::error( "Failed to quarantine file: {$file_path}" );
		}
	}

	/**
	 * Create emergency token.
	 *
	 * ## OPTIONS
	 *
	 * [--user=<user_id>]
	 * : User ID for emergency login
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--ttl=<minutes>]
	 * : Time to live in minutes
	 * ---
	 * default: 15
	 * ---
	 *
	 * [--one-time]
	 * : Create one-time token
	 *
	 * ## EXAMPLES
	 *
	 *     wp sasec emergency create --user=1 --ttl=30
	 *     wp sasec emergency create --user=1 --ttl=15 --one-time
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function emergency( $args, $assoc_args ) {
		if ( empty( $args[0] ) || $args[0] !== 'create' ) {
			WP_CLI::error( 'Usage: wp sasec emergency create [options]' );
		}

		if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
			WP_CLI::error( 'SASEC_SECRET_KEY is not defined in wp-config.php' );
		}

		$user_id     = isset( $assoc_args['user'] ) ? absint( $assoc_args['user'] ) : 1;
		$ttl_minutes = isset( $assoc_args['ttl'] ) ? absint( $assoc_args['ttl'] ) : 15;

		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-emergency.php';
		$emergency = new SASEC_Emergency();

		$token = $emergency->create_token( $user_id, $ttl_minutes );

		if ( $token === false ) {
			WP_CLI::error( 'Failed to create emergency token. Rate limit may be active.' );
		}

		$slug = get_option( 'sasec_emergency_custom_url_slug', 'sasec-emergency' );
		$url  = home_url( '/' . $slug . '?t=' . $token );

		WP_CLI::line( '' );
		WP_CLI::line( 'Emergency token created successfully!' );
		WP_CLI::line( '' );
		WP_CLI::line( "Token: {$token}" );
		WP_CLI::line( "URL: {$url}" );
		WP_CLI::line( "TTL: {$ttl_minutes} minutes" );
		WP_CLI::line( '' );
		WP_CLI::warning( 'This token will only be shown once. Save it now!' );
	}

	/**
	 * Export logs.
	 *
	 * ## OPTIONS
	 *
	 * [--since=<date>]
	 * : Export logs since date (YYYY-MM-DD)
	 *
	 * [--format=<format>]
	 * : Export format (json|csv)
	 * ---
	 * default: json
	 * ---
	 *
	 * [--output=<file>]
	 * : Output file path
	 *
	 * ## EXAMPLES
	 *
	 *     wp sasec logs export --since=2024-01-01 --format=json
	 *     wp sasec logs export --since=2024-01-01 --format=csv --output=/tmp/logs.csv
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function logs( $args, $assoc_args ) {
		if ( empty( $args[0] ) || $args[0] !== 'export' ) {
			WP_CLI::error( 'Usage: wp sasec logs export [options]' );
		}

		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-logger.php';
		$logger = new SASEC_Logger();

		$query_args = array(
			'per_page' => 1000,
			'page'     => 1,
		);

		if ( isset( $assoc_args['since'] ) ) {
			// Filter by date would need to be added to logger
			// For now, get all logs
		}

		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'json';
		$output = isset( $assoc_args['output'] ) ? $assoc_args['output'] : null;

		$all_logs = array();
		$page     = 1;

		do {
			$result = $logger->get_logs( array_merge( $query_args, array( 'page' => $page ) ) );
			$all_logs = array_merge( $all_logs, $result['logs'] );
			$page++;
		} while ( $page <= $result['pages'] );

		if ( $format === 'json' ) {
			$content = wp_json_encode( $all_logs, JSON_PRETTY_PRINT );
		} else {
			// CSV format
			$csv = array();
			$csv[] = 'ID,Event Type,Severity,User ID,Username,IP,User Agent,Created At';
			foreach ( $all_logs as $log ) {
				$csv[] = sprintf(
					'%s,%s,%s,%s,%s,%s,%s,%s',
					$log['id'],
					$log['event_type'],
					$log['severity'],
					$log['user_id'] ?? '',
					$log['username'] ?? '',
					$log['ip'] ?? '',
					str_replace( ',', ';', $log['user_agent'] ?? '' ),
					$log['created_at']
				);
			}
			$content = implode( "\n", $csv );
		}

		if ( $output ) {
			file_put_contents( $output, $content );
			WP_CLI::success( "Logs exported to: {$output}" );
		} else {
			WP_CLI::line( $content );
		}
	}
}

WP_CLI::add_command( 'sasec', 'SASEC_WP_CLI_Command' );

