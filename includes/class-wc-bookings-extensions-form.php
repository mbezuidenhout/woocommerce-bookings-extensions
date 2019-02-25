<?php

class WC_Bookings_Extensions_Form extends WC_Booking_Form {


	public function calculate_booking_cost( $posted ) {

		// If duration is night and start date is before unbookable date then return validation error.
		if ( in_array( $this->product->get_duration_unit(), array( 'night' ) ) ) {
			$data       = $this->get_posted_data( $posted );
			$bookable   = $this->product->get_default_availability();
			$rules      = $this->product->get_availability_rules();
			$check_date = strtotime( "+1 day", $data['_start_date'] );
			foreach ( $this->product->get_availability_rules() as $rule ) {
				if ( WC_Product_Booking_Rule_Manager::does_rule_apply( $rule, $check_date ) ) {
					// passing $bookable into the next check as it overrides the previous value
					$bookable = WC_Product_Booking_Rule_Manager::check_timestamp_against_rule( $check_date, $rule, $bookable );
				}
			}

			if ( ! $bookable ) {
				return new WP_Error( 'Error', __( 'Sorry, bookings cannot start on this day.', 'woocommerce-bookings' ) );
			}

		}
		return parent::calculate_booking_cost( $posted );
	}
}