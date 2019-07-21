<?php

class WC_Bookings_Extensions_Cart_Manager extends WC_Booking_Cart_Manager {

	public function __construct() {
		$this->id = 'WC_Bookings_Extensions_Cart_Manager';
	}

	/**
	 * Add posted data to the cart item
	 *
	 * @param mixed $cart_item_meta
	 * @param mixed $product_id
	 * @return array $cart_item_meta
	 */
	public function add_cart_item_data( $cart_item_meta, $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! is_wc_booking_product( $product ) ) {
			return $cart_item_meta;
		}

		$product = new WC_Bookings_Extensions_Product_Booking( $product->get_id() );

		$booking_form                       = new WC_Booking_Form( $product );
		$cart_item_meta['booking']          = $booking_form->get_posted_data( $_POST ); // phpcs:ignore
		$cart_item_meta['booking']['_cost'] = $booking_form->calculate_booking_cost( $_POST ); // phpcs:ignore

		// Create the new booking.
		$new_booking = $this->create_booking_from_cart_data( $cart_item_meta, $product_id );

		// Store in cart.
		$cart_item_meta['booking']['_booking_id'] = $new_booking->get_id();

		// Schedule this item to be removed from the cart if the user is inactive.
		$this->schedule_cart_removal( $new_booking->get_id() );

		return $cart_item_meta;
	}

	/**
	 * Create booking from cart data
	 *
	 * @param        $cart_item_meta
	 * @param        $product_id
	 * @param string $status
	 *
	 * @return WC_Booking
	 */
	private function create_booking_from_cart_data( $cart_item_meta, $product_id, $status = 'in-cart' ) {
		// Create the new booking.
		$new_booking_data = array(
			'product_id'     => $product_id, // Booking ID.
			'cost'           => $cart_item_meta['booking']['_cost'], // Cost of this booking.
			'start_date'     => $cart_item_meta['booking']['_start_date'],
			'end_date'       => $cart_item_meta['booking']['_end_date'],
			'all_day'        => $cart_item_meta['booking']['_all_day'],
			'local_timezone' => $cart_item_meta['booking']['_local_timezone'],
		);

		// Check if the booking has resources.
		if ( isset( $cart_item_meta['booking']['_resource_id'] ) ) {
			$new_booking_data['resource_id'] = $cart_item_meta['booking']['_resource_id']; // ID of the resource.
		}

		// Checks if the booking allows persons.
		if ( isset( $cart_item_meta['booking']['_persons'] ) ) {
			$new_booking_data['persons'] = $cart_item_meta['booking']['_persons']; // Count of persons making booking.
		}

		$new_booking = get_wc_booking( $new_booking_data );
		$new_booking->create( $status );

		return $new_booking;
	}

	/**
	 * When a booking is added to the cart, validate it
	 *
	 * @param bool               $passed     If passed validation.
	 * @param WC_Product_Booking $product_id WooCommerce product id.
	 * @param int                $qty        Quantity added in cart.
	 * @return bool
	 */
	public function validate_add_cart_item( $passed, $product_id, $qty ) {
		$product = wc_get_product( $product_id );

		if ( ! is_wc_booking_product( $product ) ) {
			return $passed;
		}

		$product = new WC_Bookings_Extensions_Product_Booking( $product->get_id() );

		$booking_form = new WC_Booking_Form( $product );
		$data         = $booking_form->get_posted_data();
		$validate     = $booking_form->is_bookable( $data );

		if ( is_wp_error( $validate ) ) {
			wc_add_notice( $validate->get_error_message(), 'error' );
			return false;
		}

		// Check validation on dependents.
		$dependent_products_ids = $product->get_meta( 'booking_dependencies' );
		if ( is_array( $dependent_products_ids ) ) {
			foreach ( $dependent_products_ids as $depenent_products_id ) {
				$dependent_product = new WC_Bookings_Extensions_Product_Booking( $depenent_products_id );
				// Adjust check range by 1 second less on start and end.
				$existing_bookings = $dependent_product->get_bookings_in_date_range( $data['_start_date'] + 1, $data['_end_date'] - 1 );
				if ( ! empty( $existing_bookings ) ) {
					$error = new WP_Error( 'Error', __( 'Sorry, the selected block is not available', 'woocommerce-bookings' ) );
					wc_add_notice( $error->get_error_message(), 'error' );
					return false;
				}
			}
		}

		return $passed;
	}

}