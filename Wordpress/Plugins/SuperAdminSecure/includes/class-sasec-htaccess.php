<?php
/**
 * .htaccess manager class.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Htaccess class.
 */
class SASEC_Htaccess {

	/**
	 * Block IP address.
	 *
	 * @param string $ip IP address to block.
	 */
	public function block_ip( $ip ) {
		if ( ! $this->is_apache() ) {
			return; // Only works on Apache
		}

		$htaccess_path = ABSPATH . '.htaccess';
		$marker        = 'SASEC';

		$rules = $this->generate_block_rule( $ip );

		insert_with_markers( $htaccess_path, $marker, array( $rules ) );
	}

	/**
	 * Generate block rule for IP.
	 *
	 * @param string $ip IP address.
	 * @return string Rule string.
	 */
	private function generate_block_rule( $ip ) {
		return "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteCond %{REMOTE_ADDR} ^" . preg_quote( $ip, '/' ) . "$\nRewriteRule ^(.*)$ - [F,L]\n</IfModule>";
	}

	/**
	 * Check if server is Apache.
	 *
	 * @return bool True if Apache.
	 */
	private function is_apache() {
		return strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== false;
	}

	/**
	 * Remove block for IP.
	 *
	 * @param string $ip IP address.
	 */
	public function unblock_ip( $ip ) {
		if ( ! $this->is_apache() ) {
			return;
		}

		$htaccess_path = ABSPATH . '.htaccess';
		$marker        = 'SASEC';

		// Remove marker section
		insert_with_markers( $htaccess_path, $marker, array() );
	}

	/**
	 * Get blocked IPs from .htaccess.
	 *
	 * @return array List of blocked IPs.
	 */
	public function get_blocked_ips() {
		$htaccess_path = ABSPATH . '.htaccess';
		if ( ! file_exists( $htaccess_path ) ) {
			return array();
		}

		$content = file_get_contents( $htaccess_path );
		$ips     = array();

		if ( preg_match_all( '/RewriteCond %{REMOTE_ADDR} \^([^\$]+)\$/', $content, $matches ) ) {
			$ips = $matches[1];
		}

		return $ips;
	}
}

