<?php
/**
 * Plugin shortcodes.
 *
 * @since      1.0.0
 *
 * @package    Woocommerce_Bookings_Extensions
 * @subpackage Woocommerce_Bookings_Extensions/public
 */

/**
 * Plugin shortcodes.
 *
 * Defines shortcode functionality and helper functions.
 *
 * @package    Woocommerce_Bookings_Extensions
 * @subpackage Woocommerce_Bookings_Extensions/public
 * @author     Marius Bezuidenhout <marius.bezuidenhout@gmail.com>
 */
class WC_Bookings_Extensions_Shortcodes {

	/**
	 * Processes the shortcode wcbooking_search.
	 *
	 * Usage: wcbooking_search duration_unit="{month|day|hour|minute}" duration="<Integer value of unit size>"
	 * [method="{include|exclude}" ids="<Comma separated ist of product ids>"]
	 *
	 * The search will only include products of type Bookable Product/WC_Bookings
	 *
	 * @param array $atts Attributes passed by the shortcode.
	 *
	 * @return string
	 */
	public function global_search_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'method'        => 'exclude',
				'ids'           => '',
				'duration_unit' => 'day',
				'duration'      => 1,
			),
			$atts,
			'wcbooking_search'
		);

		$ids = array_unique( explode( ',', preg_replace( '/[^0-9,]/', '', $atts['ids'] ) ) );
		$key = array_search( '', $ids, true );
		if ( false !== $key ) {
			unset( $ids[ $key ] );
		}

		$ids = array_values( $ids );

		try {
			$search_form = new WC_Bookings_Extensions_Bookings_Search( $atts['method'], $ids, $atts['duration_unit'], intval( $atts['duration'] ) );
		} catch ( Exception $e ) {
			$logger = new WC_Logger();
			$logger->add( 'globalsearchshortcode', $e->getMessage() );

			return '';
		}

		ob_start();

		wc_get_template( 'globalsearch.php', array( 'bookings_search_form' => $search_form ), 'woocommerce-bookings-extensions', plugin_dir_path( __DIR__ ) . 'templates/' );

		return ob_get_clean();
	}

	/**
	 * Sends back array for bookings global search shortcode js
	 */
	public function search_booking_products() {
		$request = $_GET;

		$data = array(
			'availability_rules'    => array(),
			'buffer_days'           => array(),
			'fully_booked_days'     => array(),
			'max_date'              => strtotime( $request['max_date'] ),
			'min_date'              => strtotime( $request['min_date'] ),
			'partially_booked_days' => array(),
			'restricted_days'       => false,
			'unavailable_days'      => array(),
		);

		wp_send_json( $data );
	}


	/**
	 * Output a calendar for the currently displaying product.
	 *
	 * @param array $atts Shortcode attributes array.
	 * @return string
	 */
	public function calendar_shortcode( $atts ) {
		$page = WC_Bookings_Extensions_New_Calendar::get_instance();
		return $page->get_shortcode_output( $atts );
	}

	/**
	 * Output the calendar overview.
	 *
	 * @param array $atts An array of attributes.
	 *
	 * @return string
	 */
	public function overview_shortcode( $atts ) {
		$page = WC_Bookings_Extensions_New_Calendar::get_instance();

		return $page->get_overview_output( $atts );
	}

}