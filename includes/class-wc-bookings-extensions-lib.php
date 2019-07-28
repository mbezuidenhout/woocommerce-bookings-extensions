<?php
/**
 * Library of static functions
 *
 * @package Woocommerce_Bookings_Extensions
 */

/**
 * Class WC_Bookings_Extensions_Lib
 */
class WC_Bookings_Extensions_Lib {

	/**
	 * Attempt to convert a date formatting string from PHP to Moment
	 *
	 * @param string $format PHP date format.
	 *
	 * @return string
	 */
	public static function convert_to_moment_format( $format ) {
		$replacements = array(
			'd' => 'DD',
			'D' => 'ddd',
			'j' => 'D',
			'l' => 'dddd',
			'N' => 'E',
			'S' => 'o',
			'w' => 'e',
			'z' => 'DDD',
			'W' => 'W',
			'F' => 'MMMM',
			'm' => 'MM',
			'M' => 'MMM',
			'n' => 'M',
			't' => '', // no equivalent.
			'L' => '', // no equivalent.
			'o' => 'YYYY',
			'Y' => 'YYYY',
			'y' => 'YY',
			'a' => 'a',
			'A' => 'A',
			'B' => '', // no equivalent.
			'g' => 'h',
			'G' => 'H',
			'h' => 'hh',
			'H' => 'HH',
			'i' => 'mm',
			's' => 'ss',
			'u' => 'SSS',
			'e' => 'zz', // deprecated since version 1.6.0 of moment.js.
			'I' => '', // no equivalent.
			'O' => '', // no equivalent.
			'P' => '', // no equivalent.
			'T' => '', // no equivalent.
			'Z' => '', // no equivalent.
			'c' => '', // no equivalent.
			'r' => '', // no equivalent.
			'U' => 'X',
		);

		return strtr( $format, $replacements );
	}

	/**
	 * Compare two bookings start dates for sorting
	 *
	 * @param \WC_Booking $a Booking to compare with.
	 * @param \WC_Booking $b Booking to compare with.
	 *
	 * @return int
	 * @throws \Exception Not an instance of WC_Booking.
	 */
	public static function bookings_sort_by_date( $a, $b ) {
		if ( is_a( $a, 'WC_Booking' ) && is_a( $b, 'WC_Booking' ) ) {
			if ( $a->get_start() === $b->get_start() ) {
				return 0;
			}

			return ( $a->get_start() > $b->get_start() ) ? 1 : - 1;
		} else {
			throw new \Exception( 'Array element not an instance of WC_Booking' );
		}
	}

	/**
	 * Get booking parameters in text
	 *
	 * @param \WC_Booking $booking Booking to extract data from.
	 *
	 * @return array
	 * @throws \Exception Not an instance of WC_Booking.
	 */
	public static function get_bookings_text_v2( $booking ) {
		if ( ! is_a( $booking, 'WC_Booking' ) ) {
			throw new \Exception( 'Not an instance of WC_Booking' );
		}

		return array(
			'product_id'      => $booking->get_product_id(),
			'product_name'    => $booking->get_product()->get_name(),
			'unix_start_time' => $booking->get_start(),
			'unix_end_time'   => $booking->get_end(),
			'status'          => $booking->get_status(),
			'order'           => self::map_order( $booking->get_order() ),
			'customer'        => self::map_customer( $booking->get_customer() ),
		);
	}

	/**
	 * Maps the order class to strings
	 *
	 * @param \WC_Order $order Order data to extract.
	 *
	 * @return array
	 */
	public static function map_order( $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return null;
		}

		return array(
			'order_number'       => $order->get_order_number(),
			'billing_company'    => $order->get_billing_company(),
			'billing_first_name' => $order->get_billing_first_name(),
			'billing_last_name'  => $order->get_billing_last_name(),
		);
	}

	/**
	 * Maps the customer class to strings
	 *
	 * @param object $customer Customer data object.
	 *
	 * @return array
	 * @see \WC_Order::get_customer()
	 */
	public static function map_customer( $customer ) {
		$customer_data = array();
		if ( property_exists( $customer, 'user_id' ) ) {
			$user = get_user_by( 'id', $customer->user_id );
			if ( is_a( $user, 'WP_User' ) ) {
				$customer_data = array(
					'user_id'      => $user->ID,
					'display_name' => $user->display_name,
					'email'        => $user->user_email,
				);
			} else {
				$customer_data = array(
					'user_id'      => $customer->user_id,
					'display_name' => str_replace( ' (Guest)', '', $customer->name ),
					'email'        => $customer->email,
				);
				if ( empty( $customer_data['display_name'] ) ) {
					$customer_data['display_name'] = __( 'Private function', 'woocommerce-bookings-extensions' );
				}
			}
		}

		return $customer_data;
	}

	/**
	 * Calculate costs.
	 *
	 * Take posted booking form values and then use these to quote a price for what has been chosen.
	 * Returns a string which is appended to the booking form.
	 */
	public static function calculate_costs() {
		$posted = array();

		if ( isset( $_POST['form'] ) ) {
			parse_str( $_POST['form'], $posted );
		}

		$booking_id = $posted['add-to-cart'];
		$product    = wc_get_product( $booking_id );

		if ( ! $product ) {
			wp_send_json(
				array(
					'result' => 'ERROR',
					'html'   => apply_filters( 'woocommerce_bookings_calculated_booking_cost_error_output', '<span class="booking-error">' . __( 'This booking is unavailable.', 'woocommerce-bookings' ) . '</span>', null, null ),
				)
			);
		}

		$product = new WC_Bookings_Extensions_Product_Booking( $product->get_id() );

		$booking_form = new WC_Bookings_Extensions_Form( $product );
		$cost         = $booking_form->calculate_booking_cost( $posted );

		if ( is_wp_error( $cost ) ) {
			wp_send_json(
				array(
					'result' => 'ERROR',
					'html'   => apply_filters( 'woocommerce_bookings_calculated_booking_cost_error_output', '<span class="booking-error">' . $cost->get_error_message() . '</span>', $cost, $product ),
				)
			);
		}

		if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
			if ( function_exists( 'wc_get_price_excluding_tax' ) ) {
				$display_price = wc_get_price_including_tax( $product, array( 'price' => $cost ) );
			} else {
				$display_price = $product->get_price_including_tax( 1, $cost );
			}
		} else {
			if ( function_exists( 'wc_get_price_excluding_tax' ) ) {
				$display_price = wc_get_price_excluding_tax( $product, array( 'price' => $cost ) );
			} else {
				$display_price = $product->get_price_excluding_tax( 1, $cost );
			}
		}

		if ( version_compare( WC_VERSION, '2.4.0', '>=' ) ) {
			$price_suffix = $product->get_price_suffix( $cost, 1 );
		} else {
			$price_suffix = $product->get_price_suffix();
		}

		// Build the output.
		$output = apply_filters( 'woocommerce_bookings_booking_cost_string', __( 'Booking cost', 'woocommerce-bookings' ), $product ) . ': <strong>' . wc_price( $display_price ) . $price_suffix . '</strong>';

		// Send the output.
		wp_send_json(
			array(
				'result' => 'SUCCESS',
				'html'   => apply_filters( 'woocommerce_bookings_calculated_booking_cost_success_output', $output, $display_price, $product ),
			)
		);
	}

}

