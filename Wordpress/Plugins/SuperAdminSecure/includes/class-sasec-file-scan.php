<?php
/**
 * File integrity scanner class.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * File Scanner class.
 */
class SASEC_File_Scan {

	/**
	 * Create baseline checksums.
	 */
	public function create_baseline() {
		global $wpdb;

		if ( ! get_option( 'sasec_file_scan_enabled', true ) ) {
			return;
		}

		$table_name = $wpdb->prefix . 'sasec_file_checksums';
		$wpdb->query( "TRUNCATE TABLE {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$scan_paths = $this->get_scan_paths();
		$count = 0;

		foreach ( $scan_paths as $path ) {
			if ( ! is_dir( $path ) ) {
				continue;
			}

			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $files as $file ) {
				if ( $file->isFile() && $this->should_scan_file( $file->getPathname() ) ) {
					$file_path = $file->getPathname();
					$sha256    = hash_file( 'sha256', $file_path );

					$wpdb->insert(
						$table_name,
						array(
							'file_path'   => $file_path,
							'sha256'      => $sha256,
							'status'      => 1,
						),
						array( '%s', '%s', '%d' )
					);

					$count++;
				}
			}
		}

		update_option( 'sasec_baseline_created', current_time( 'mysql' ) );
		update_option( 'sasec_baseline_file_count', $count );

		return $count;
	}

	/**
	 * Run incremental scan.
	 */
	public function run_incremental_scan() {
		if ( ! get_option( 'sasec_file_scan_enabled', true ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'sasec_file_checksums';

		$scan_paths = $this->get_scan_paths();
		$changes    = array();
		$new_files  = array();
		$missing_files = array();

		// Get all baseline files
		$baseline_files = $wpdb->get_results( "SELECT file_path, sha256 FROM {$table_name} WHERE status = 1", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$baseline_map = array();
		foreach ( $baseline_files as $file ) {
			$baseline_map[ $file['file_path'] ] = $file['sha256'];
		}

		// Scan current files
		$current_files = array();
		foreach ( $scan_paths as $path ) {
			if ( ! is_dir( $path ) ) {
				continue;
			}

			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $files as $file ) {
				if ( $file->isFile() && $this->should_scan_file( $file->getPathname() ) ) {
					$file_path = $file->getPathname();
					$sha256    = hash_file( 'sha256', $file_path );
					$current_files[ $file_path ] = $sha256;

					// Check if file changed
					if ( isset( $baseline_map[ $file_path ] ) ) {
						if ( $baseline_map[ $file_path ] !== $sha256 ) {
							$changes[] = array(
								'file_path' => $file_path,
								'old_hash'  => $baseline_map[ $file_path ],
								'new_hash'  => $sha256,
							);
						}
					} else {
						$new_files[] = $file_path;
					}
				}
			}
		}

		// Find missing files
		foreach ( $baseline_map as $file_path => $hash ) {
			if ( ! isset( $current_files[ $file_path ] ) && file_exists( $file_path ) === false ) {
				$missing_files[] = $file_path;
			}
		}

		// Log changes
		if ( ! empty( $changes ) || ! empty( $new_files ) || ! empty( $missing_files ) ) {
			require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-logger.php';
			$logger = new SASEC_Logger();

			$severity = 7; // High severity for file changes
			$payload = array(
				'changes'      => $changes,
				'new_files'    => $new_files,
				'missing_files' => $missing_files,
			);

			$logger->log( 'file_integrity_change', $severity, array( 'payload' => $payload ) );

			// Take action if in protect mode
			$mode = get_option( 'sasec_file_scan_mode', 'log' );
			if ( $mode === 'protect' ) {
				$this->quarantine_suspicious_files( $changes, $new_files );
			}
		}

		// Update checksums
		foreach ( $current_files as $file_path => $sha256 ) {
			$wpdb->replace(
				$table_name,
				array(
					'file_path'   => $file_path,
					'sha256'      => $sha256,
					'status'      => 1,
				),
				array( '%s', '%s', '%d' )
			);
		}

		return array(
			'changes'      => count( $changes ),
			'new_files'    => count( $new_files ),
			'missing_files' => count( $missing_files ),
		);
	}

	/**
	 * Scan uploaded file for suspicious content.
	 *
	 * @param array $file     File data.
	 * @param string $action  Action type.
	 * @return array File data (possibly modified).
	 */
	public function scan_uploaded_file( $file, $action ) {
		if ( ! get_option( 'sasec_file_scan_enabled', true ) ) {
			return $file;
		}

		$file_path = $file['tmp_name'];
		$file_name = $file['name'];

		// Check for PHP in uploads
		if ( $this->is_php_file( $file_name ) && ! $this->is_allowed_php_upload( $file_name ) ) {
			require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-logger.php';
			$logger = new SASEC_Logger();
			$logger->log(
				'php_upload_blocked',
				8,
				array(
					'filename' => $file_name,
					'ip'       => $this->get_client_ip(),
				)
			);

			$file['error'] = __( 'PHP files are not allowed in uploads.', 'superadmin-secure' );
			return $file;
		}

		// Check for shell signatures
		if ( $this->detect_shell_signatures( $file_path ) ) {
			require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-logger.php';
			$logger = new SASEC_Logger();
			$logger->log(
				'shell_signature_detected',
				9,
				array(
					'filename' => $file_name,
					'ip'       => $this->get_client_ip(),
				)
			);

			$file['error'] = __( 'File contains suspicious content and was blocked.', 'superadmin-secure' );
			return $file;
		}

		return $file;
	}

	/**
	 * Get scan paths.
	 *
	 * @return array List of paths to scan.
	 */
	private function get_scan_paths() {
		$paths = array(
			ABSPATH . 'wp-admin',
			ABSPATH . 'wp-includes',
			WP_CONTENT_DIR . '/themes',
			WP_CONTENT_DIR . '/plugins',
		);

		// Exclude this plugin's directory from scans
		$paths = array_filter( $paths, function( $path ) {
			return strpos( $path, SASEC_PLUGIN_DIR ) === false;
		} );

		return apply_filters( 'sasec_scan_paths', $paths );
	}

	/**
	 * Check if file should be scanned.
	 *
	 * @param string $file_path File path.
	 * @return bool True if should scan.
	 */
	private function should_scan_file( $file_path ) {
		// Skip certain file types
		$skip_extensions = array( 'log', 'tmp', 'cache', 'bak' );
		$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		if ( in_array( $extension, $skip_extensions, true ) ) {
			return false;
		}

		// Skip node_modules, vendor, etc.
		$skip_dirs = array( 'node_modules', 'vendor', '.git', '.svn' );
		foreach ( $skip_dirs as $dir ) {
			if ( strpos( $file_path, $dir ) !== false ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Detect shell signatures in file.
	 *
	 * @param string $file_path File path.
	 * @return bool True if shell signature detected.
	 */
	private function detect_shell_signatures( $file_path ) {
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return false;
		}

		$content = file_get_contents( $file_path, false, null, 0, 8192 ); // Read first 8KB
		if ( $content === false ) {
			return false;
		}

		$signatures = array(
			'eval(',
			'base64_decode(',
			'exec(',
			'system(',
			'shell_exec(',
			'passthru(',
			'preg_replace.*\/e',
			'assert(',
			'create_function(',
			'call_user_func.*eval',
		);

		foreach ( $signatures as $signature ) {
			if ( preg_match( '/' . $signature . '/i', $content ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if file is PHP.
	 *
	 * @param string $filename Filename.
	 * @return bool True if PHP file.
	 */
	private function is_php_file( $filename ) {
		$php_extensions = array( 'php', 'php3', 'php4', 'php5', 'phtml', 'pht' );
		$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		return in_array( $extension, $php_extensions, true );
	}

	/**
	 * Check if PHP upload is allowed.
	 *
	 * @param string $filename Filename.
	 * @return bool True if allowed.
	 */
	private function is_allowed_php_upload( $filename ) {
		// Allow specific PHP files if needed
		return false;
	}

	/**
	 * Quarantine suspicious files.
	 *
	 * @param array $changes   Changed files.
	 * @param array $new_files New files.
	 */
	private function quarantine_suspicious_files( $changes, $new_files ) {
		$quarantine_dir = WP_CONTENT_DIR . '/sasec-quarantine';
		if ( ! is_dir( $quarantine_dir ) ) {
			wp_mkdir_p( $quarantine_dir );
		}

		$files_to_quarantine = array_merge(
			array_column( $changes, 'file_path' ),
			$new_files
		);

		foreach ( $files_to_quarantine as $file_path ) {
			if ( file_exists( $file_path ) ) {
				$quarantine_path = $quarantine_dir . '/' . basename( $file_path ) . '.' . time();
				if ( rename( $file_path, $quarantine_path ) ) {
					chmod( $quarantine_path, 0444 ); // Read-only
					update_option( 'sasec_quarantined_' . md5( $file_path ), $quarantine_path );
				}
			}
		}
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

