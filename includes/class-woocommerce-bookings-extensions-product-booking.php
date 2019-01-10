<?php

WC_Bookings::includes();

class WC_Booking_Extensions_Product_Booking extends WC_Product_Booking {

	public function get_blocks_in_range( $start_date, $end_date, $intervals = array(), $resource_id = 0, $booked = array() ) {
		return parent::get_blocks_in_range($start_date, $end_date, $intervals, $resource_id, $booked );
	}
}