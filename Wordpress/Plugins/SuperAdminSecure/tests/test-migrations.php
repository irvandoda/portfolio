<?php
/**
 * Tests for migrations.
 *
 * @package SuperAdminSecure
 */

class Test_Migrations extends WP_UnitTestCase {

	/**
	 * Test database migrations.
	 */
	public function test_migrations_create_tables() {
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-migrations.php';
		$migrations = new SASEC_Migrations();
		$migrations->run_migrations();

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
			$table_name = $table_prefix . $table;
			$this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name );
		}
	}

	/**
	 * Test rollback.
	 */
	public function test_migrations_rollback() {
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-migrations.php';
		$migrations = new SASEC_Migrations();
		$migrations->rollback();

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
			$table_name = $table_prefix . $table;
			$this->assertFalse( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name );
		}
	}
}

