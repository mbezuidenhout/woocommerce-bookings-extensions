<?php

class WC_Bookings_Custom extends WC_Bookings {
	public function __construct() {
		parent::includes();
	}
}

// Ensure that WooCommerce Bookings class files are included
new WC_Bookings_Custom;