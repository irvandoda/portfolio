<?php
/**
 * Database migrations handler.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migrations class.
 */
class SASEC_Migrations {

	/**
	 * Current database version.
	 *
	 * @var string
	 */
	private $db_version = '1.0.0';

	/**
	 * Run all migrations.
	 */
	public function run_migrations() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_prefix    = $wpdb->prefix . 'sasec_';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Table: wp_sasec_logs
		$sql_logs = "CREATE TABLE {$table_prefix}logs (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			event_type VARCHAR(64) NOT NULL,
			severity TINYINT NOT NULL,
			user_id BIGINT NULL,
			username VARCHAR(191) NULL,
			ip VARCHAR(45) NULL,
			user_agent TEXT NULL,
			payload LONGTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			processed TINYINT DEFAULT 0,
			INDEX idx_event_type (event_type),
			INDEX idx_severity (severity),
			INDEX idx_user_id (user_id),
			INDEX idx_created_at (created_at),
			INDEX idx_processed (processed)
		) $charset_collate;";
		dbDelta( $sql_logs );

		// Table: wp_sasec_config
		$sql_config = "CREATE TABLE {$table_prefix}config (
			id INT AUTO_INCREMENT PRIMARY KEY,
			cfg_key VARCHAR(191) UNIQUE NOT NULL,
			cfg_value LONGTEXT NOT NULL,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_cfg_key (cfg_key)
		) $charset_collate;";
		dbDelta( $sql_config );

		// Table: wp_sasec_hidden_users
		$sql_hidden = "CREATE TABLE {$table_prefix}hidden_users (
			id INT AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT NOT NULL UNIQUE,
			hidden_since DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			note VARCHAR(255),
			INDEX idx_user_id (user_id)
		) $charset_collate;";
		dbDelta( $sql_hidden );

		// Table: wp_sasec_emergency_tokens
		$sql_tokens = "CREATE TABLE {$table_prefix}emergency_tokens (
			id BIGINT AUTO_INCREMENT PRIMARY KEY,
			token_hash CHAR(128) NOT NULL,
			user_id BIGINT NOT NULL,
			expires_at DATETIME NOT NULL,
			used TINYINT DEFAULT 0,
			created_ip VARCHAR(45) NULL,
			created_agent TEXT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_token_hash (token_hash),
			INDEX idx_user_id (user_id),
			INDEX idx_expires_at (expires_at),
			INDEX idx_used (used)
		) $charset_collate;";
		dbDelta( $sql_tokens );

		// Table: wp_sasec_file_checksums
		$sql_checksums = "CREATE TABLE {$table_prefix}file_checksums (
			id BIGINT AUTO_INCREMENT PRIMARY KEY,
			file_path TEXT NOT NULL,
			sha256 CHAR(64) NOT NULL,
			last_checked DATETIME DEFAULT CURRENT_TIMESTAMP,
			status TINYINT DEFAULT 1,
			INDEX idx_sha256 (sha256),
			INDEX idx_status (status),
			INDEX idx_last_checked (last_checked)
		) $charset_collate;";
		dbDelta( $sql_checksums );

		// Store database version
		update_option( 'sasec_db_version', $this->db_version );
	}

	/**
	 * Rollback migrations (for testing/debugging).
	 */
	public function rollback() {
		global $wpdb;
		$table_prefix = $wpdb->prefix . 'sasec_';

		$tables = array(
			'logs',
			'config',
			'hidden_users',
			'emergency_tokens',
			'file_checksums',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table_prefix}{$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		delete_option( 'sasec_db_version' );
	}
}

