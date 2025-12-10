<?php
/**
 * Ghost mode class for hiding superadmin activity.
 *
 * @package SuperAdminSecure
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ghost Mode class.
 */
class SASEC_Ghost_Mode {

	/**
	 * Handle post creation (set proxy author).
	 *
	 * @param int    $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool   $update  Whether this is an update.
	 */
	public function handle_post_creation( $post_id, $post, $update ) {
		if ( ! get_option( 'sasec_ghost_mode_enabled', false ) ) {
			return;
		}

		$current_user_id = get_current_user_id();
		if ( ! $current_user_id ) {
			return;
		}

		// Check if user is hidden
		if ( ! $this->is_hidden_user( $current_user_id ) ) {
			return;
		}

		$proxy_user_id = absint( get_option( 'sasec_ghost_proxy_user_id', 1 ) );

		// Store real author in meta
		update_post_meta( $post_id, '_sasec_real_author', $current_user_id );

		// Set proxy author
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_author' => $proxy_user_id,
			)
		);
	}

	/**
	 * Filter user queries to hide hidden users.
	 *
	 * @param WP_User_Query $query User query object.
	 */
	public function filter_user_queries( $query ) {
		if ( ! get_option( 'sasec_ghost_mode_enabled', false ) ) {
			return;
		}

		// Only filter for users without sasec_view_ghosts capability
		if ( current_user_can( 'sasec_view_ghosts' ) ) {
			return;
		}

		$hidden_user_ids = $this->get_hidden_user_ids();
		if ( empty( $hidden_user_ids ) ) {
			return;
		}

		$exclude = $query->query_vars['exclude'] ?? array();
		if ( ! is_array( $exclude ) ) {
			$exclude = array();
		}

		$query->query_vars['exclude'] = array_merge( $exclude, $hidden_user_ids );
	}

	/**
	 * Filter REST API user response.
	 *
	 * @param WP_REST_Response $response Response object.
	 * @param WP_User         $user     User object.
	 * @param WP_REST_Request $request  Request object.
	 * @return WP_REST_Response Modified response.
	 */
	public function filter_rest_user_response( $response, $user, $request ) {
		if ( ! get_option( 'sasec_ghost_mode_enabled', false ) ) {
			return $response;
		}

		if ( current_user_can( 'sasec_view_ghosts' ) ) {
			return $response;
		}

		if ( $this->is_hidden_user( $user->ID ) ) {
			// Return 404 or empty response
			return new WP_Error( 'rest_user_not_found', __( 'User not found.', 'superadmin-secure' ), array( 'status' => 404 ) );
		}

		return $response;
	}

	/**
	 * Check if user is hidden.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if hidden.
	 */
	private function is_hidden_user( $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sasec_hidden_users';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d",
				$user_id
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Get all hidden user IDs.
	 *
	 * @return array List of user IDs.
	 */
	private function get_hidden_user_ids() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sasec_hidden_users';

		$user_ids = $wpdb->get_col( "SELECT user_id FROM {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array_map( 'absint', $user_ids );
	}

	/**
	 * Add user to hidden list.
	 *
	 * @param int    $user_id User ID.
	 * @param string $note    Optional note.
	 * @return bool True on success.
	 */
	public function hide_user( $user_id, $note = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sasec_hidden_users';

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id' => absint( $user_id ),
				'note'    => sanitize_text_field( $note ),
			),
			array( '%d', '%s' )
		);

		return $result !== false;
	}

	/**
	 * Remove user from hidden list.
	 *
	 * @param int $user_id User ID.
	 * @return bool True on success.
	 */
	public function unhide_user( $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sasec_hidden_users';

		$result = $wpdb->delete(
			$table_name,
			array( 'user_id' => absint( $user_id ) ),
			array( '%d' )
		);

		return $result !== false;
	}
}

