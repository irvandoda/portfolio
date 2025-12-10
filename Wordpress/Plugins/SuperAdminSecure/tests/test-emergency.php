<?php
/**
 * Tests for emergency login.
 *
 * @package SuperAdminSecure
 */

class Test_Emergency extends WP_UnitTestCase {

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Define secret key for tests
		if ( ! defined( 'SASEC_SECRET_KEY' ) ) {
			define( 'SASEC_SECRET_KEY', 'test-secret-key-for-unit-tests-min-32-chars-long' );
		}
	}

	/**
	 * Test emergency token creation.
	 */
	public function test_create_emergency_token() {
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-emergency.php';
		$emergency = new SASEC_Emergency();

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$token   = $emergency->create_token( $user_id, 15 );

		$this->assertNotFalse( $token );
		$this->assertEquals( 64, strlen( $token ) ); // 32 bytes = 64 hex chars
	}

	/**
	 * Test emergency password set.
	 */
	public function test_set_emergency_password() {
		require_once SASEC_PLUGIN_DIR . 'includes/class-sasec-emergency.php';
		$emergency = new SASEC_Emergency();

		$user_id  = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$password = 'test-emergency-password-123';
		$result   = $emergency->set_password( $password, $user_id );

		$this->assertTrue( $result );
	}
}

