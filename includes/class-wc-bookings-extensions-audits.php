<?php
/**
 * Methods for doing audits on bookings. Integration with WP Security Audit Log.
 *
 * @package Woocommerce_Bookings_Extensions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Bookings_Extensions_Audits.
 */
class WC_Bookings_Extensions_Audits {

	/**
	 * Stores Bookings.
	 *
	 * @var WC_Booking[]
	 */
	protected $bookings;

	/**
	 *  Singleton instance of this class.
	 *
	 * @var WC_Bookings_Extensions_New_Calendar
	 */
	private static $instance;

	/**
	 * Get Instance creates a singleton class that's cached to stop duplicate instances
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Fires immediately before an existing post is updated in the database.
	 *
	 * @since 1.7.0
	 *
	 * @param int   $post_ID Post ID.
	 * @param array $data    Array of unslashed post data.
	 */
	public function log_booking_update( $post_ID, $data ) {
		if ( isset( $data['post_type'] ) && 'wc_booking' === $data['post_type'] ) {
			do_action( 'booking_updated', $post_ID, $data );
			if ( is_user_logged_in() ) {
				$booking = get_wc_booking( $post_ID );
				$booking->update_meta_data( '_booking_modified_user_id', wp_get_current_user()->ID );
				$booking->save_meta_data();
			}
		}
	}

	/**
	 * Fires immediately after a booking has been inserted into the database.
	 *
	 * @since 1.7.0
	 * @param int $post_ID Post ID.
	 */
	public function log_booking_created( $post_ID ) {
		if ( is_user_logged_in() ) {
			$booking = get_wc_booking( $post_ID );
			$booking->update_meta_data( '_booking_created_user_id', wp_get_current_user()->ID );
			$booking->save_meta_data();
		}
	}

}
