<?php

class WC_Booking_Extensions_Cart_Manager extends WC_Booking_Cart_Manager {

	public function __construct() {
		$this->id = 'wc_booking_extensions_cart_manager';
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
		$product = new WC_Booking_Extensions_Product_Booking( $product->get_id() );

		if ( ! is_wc_booking_product( $product ) ) {
			return $cart_item_meta;
		}

		$booking_form                       = new WC_Booking_Form( $product );
		$cart_item_meta['booking']          = $booking_form->get_posted_data( $_POST );
		$cart_item_meta['booking']['_cost'] = $booking_form->calculate_booking_cost( $_POST );

		// Create the new booking
		$new_booking = $this->create_booking_from_cart_data( $cart_item_meta, $product_id );

		// Store in cart
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
		// Create the new booking
		$new_booking_data = array(
			'product_id'     => $product_id, // Booking ID
			'cost'           => $cart_item_meta['booking']['_cost'], // Cost of this booking
			'start_date'     => $cart_item_meta['booking']['_start_date'],
			'end_date'       => $cart_item_meta['booking']['_end_date'],
			'all_day'        => $cart_item_meta['booking']['_all_day'],
			'local_timezone' => $cart_item_meta['booking']['_local_timezone'],
		);

		// Check if the booking has resources
		if ( isset( $cart_item_meta['booking']['_resource_id'] ) ) {
			$new_booking_data['resource_id'] = $cart_item_meta['booking']['_resource_id']; // ID of the resource
		}

		// Checks if the booking allows persons
		if ( isset( $cart_item_meta['booking']['_persons'] ) ) {
			$new_booking_data['persons'] = $cart_item_meta['booking']['_persons']; // Count of persons making booking
		}

		$new_booking = get_wc_booking( $new_booking_data );
		$new_booking->create( $status );

		return $new_booking;
	}

}