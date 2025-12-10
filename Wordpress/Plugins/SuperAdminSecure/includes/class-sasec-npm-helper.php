<?php
/**
 * NPM helper class for building admin UI.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NPM Helper class.
 */
class SASEC_NPM_Helper {

	/**
	 * Check if npm is installed.
	 *
	 * @return bool True if npm is available.
	 */
	public static function is_npm_installed() {
		// Check multiple possible npm paths
		$npm_paths = array( 'npm', '/usr/bin/npm', '/usr/local/bin/npm', '/opt/node/bin/npm' );
		
		foreach ( $npm_paths as $npm_path ) {
			$output = array();
			$return_var = 0;
			exec( "which {$npm_path} 2>&1 || command -v {$npm_path} 2>&1", $output, $return_var );
			if ( $return_var === 0 && ! empty( $output ) ) {
				// Verify it actually works
				exec( "{$npm_path} --version 2>&1", $version_output, $version_var );
				if ( $version_var === 0 ) {
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Install npm if not available (using nvm or direct install).
	 *
	 * @return bool True on success.
	 */
	public static function install_npm() {
		// Try to detect and use nvm
		if ( self::is_nvm_available() ) {
			return self::install_npm_via_nvm();
		}

		// Try to install via package manager (requires sudo, may not work)
		$os = self::detect_os();
		
		if ( $os === 'linux' ) {
			// Try to install Node.js via package manager
			exec( 'curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash - 2>&1', $output, $return_var );
			if ( $return_var === 0 ) {
				exec( 'sudo apt-get install -y nodejs 2>&1', $output, $return_var );
				return $return_var === 0;
			}
		}

		return false;
	}

	/**
	 * Check if nvm is available.
	 *
	 * @return bool True if nvm is available.
	 */
	private static function is_nvm_available() {
		$output = array();
		$return_var = 0;
		exec( 'command -v nvm 2>&1 || [ -s "$HOME/.nvm/nvm.sh" ] && echo "nvm exists" 2>&1', $output, $return_var );
		return ! empty( $output );
	}

	/**
	 * Install npm via nvm.
	 *
	 * @return bool True on success.
	 */
	private static function install_npm_via_nvm() {
		$nvm_script = getenv( 'HOME' ) . '/.nvm/nvm.sh';
		if ( file_exists( $nvm_script ) ) {
			exec( "source {$nvm_script} && nvm install 18 && nvm use 18 2>&1", $output, $return_var );
			return $return_var === 0;
		}
		return false;
	}

	/**
	 * Detect operating system.
	 *
	 * @return string OS type.
	 */
	private static function detect_os() {
		$os = strtolower( PHP_OS );
		if ( strpos( $os, 'linux' ) !== false ) {
			return 'linux';
		} elseif ( strpos( $os, 'darwin' ) !== false ) {
			return 'mac';
		} elseif ( strpos( $os, 'win' ) !== false ) {
			return 'windows';
		}
		return 'unknown';
	}

	/**
	 * Run npm install in admin directory.
	 *
	 * @return array Result with success status and output.
	 */
	public static function npm_install() {
		$admin_dir = SASEC_PLUGIN_DIR . 'admin';
		
		if ( ! is_dir( $admin_dir ) ) {
			return array(
				'success' => false,
				'message' => 'Admin directory not found',
			);
		}

		// Check if package.json exists
		if ( ! file_exists( $admin_dir . '/package.json' ) ) {
			return array(
				'success' => false,
				'message' => 'package.json not found',
			);
		}

		// Find npm executable
		$npm_cmd = self::find_npm_command();
		if ( ! $npm_cmd ) {
			return array(
				'success' => false,
				'message' => 'npm command not found',
			);
		}

		// Change to admin directory and run npm install
		$old_dir = getcwd();
		chdir( $admin_dir );

		$output = array();
		$return_var = 0;
		
		// Use exec with proper error handling and timeout
		$command = escapeshellcmd( $npm_cmd ) . ' install 2>&1';
		exec( $command, $output, $return_var );
		
		chdir( $old_dir );

		return array(
			'success' => $return_var === 0,
			'message' => implode( "\n", $output ),
			'output'  => $output,
		);
	}

	/**
	 * Find npm command path.
	 *
	 * @return string|false NPM command path or false.
	 */
	private static function find_npm_command() {
		$npm_paths = array( 'npm', '/usr/bin/npm', '/usr/local/bin/npm', '/opt/node/bin/npm' );
		
		foreach ( $npm_paths as $npm_path ) {
			$output = array();
			$return_var = 0;
			exec( "which {$npm_path} 2>&1 || command -v {$npm_path} 2>&1", $output, $return_var );
			if ( $return_var === 0 && ! empty( $output ) ) {
				// Verify it works
				exec( "{$npm_path} --version 2>&1", $version_output, $version_var );
				if ( $version_var === 0 ) {
					return $npm_path;
				}
			}
		}
		
		return false;
	}

	/**
	 * Run npm build in admin directory.
	 *
	 * @return array Result with success status and output.
	 */
	public static function npm_build() {
		$admin_dir = SASEC_PLUGIN_DIR . 'admin';
		
		if ( ! is_dir( $admin_dir ) ) {
			return array(
				'success' => false,
				'message' => 'Admin directory not found',
			);
		}

		// Check if node_modules exists
		if ( ! is_dir( $admin_dir . '/node_modules' ) ) {
			// Try to install first
			$install_result = self::npm_install();
			if ( ! $install_result['success'] ) {
				return $install_result;
			}
		}

		// Find npm command
		$npm_cmd = self::find_npm_command();
		if ( ! $npm_cmd ) {
			return array(
				'success' => false,
				'message' => 'npm command not found',
			);
		}

		$old_dir = getcwd();
		chdir( $admin_dir );

		$output = array();
		$return_var = 0;
		
		$command = escapeshellcmd( $npm_cmd ) . ' run build 2>&1';
		exec( $command, $output, $return_var );
		
		chdir( $old_dir );

		return array(
			'success' => $return_var === 0,
			'message' => implode( "\n", $output ),
			'output'  => $output,
		);
	}

	/**
	 * Ensure npm is available and build admin UI.
	 *
	 * @return array Result with success status and messages.
	 */
	public static function ensure_npm_and_build() {
		$messages = array();

		// Check if npm is installed
		if ( ! self::is_npm_installed() ) {
			$messages[] = 'NPM not found. Attempting to install...';
			
			$install_result = self::install_npm();
			if ( ! $install_result ) {
				return array(
					'success'  => false,
					'message'  => 'Failed to install npm. Please install Node.js and npm manually.',
					'messages' => $messages,
				);
			}
			
			$messages[] = 'NPM installed successfully.';
		} else {
			$messages[] = 'NPM is already installed.';
		}

		// Run npm install
		$messages[] = 'Running npm install...';
		$install_result = self::npm_install();
		
		if ( ! $install_result['success'] ) {
			return array(
				'success'  => false,
				'message'  => 'Failed to run npm install: ' . $install_result['message'],
				'messages' => $messages,
			);
		}
		
		$messages[] = 'npm install completed successfully.';

		// Run npm build
		$messages[] = 'Running npm build...';
		$build_result = self::npm_build();
		
		if ( ! $build_result['success'] ) {
			return array(
				'success'  => false,
				'message'  => 'Failed to run npm build: ' . $build_result['message'],
				'messages' => $messages,
			);
		}
		
		$messages[] = 'npm build completed successfully.';

		return array(
			'success'  => true,
			'message'  => 'NPM setup and build completed successfully.',
			'messages' => $messages,
		);
	}
}

