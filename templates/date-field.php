<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

wp_enqueue_script( 'wc-bookings-extensions-date-picker' );

?>
<div class="wc_bookings_field_date-container">
	<label for="search-datepicker">Date:</label>
	<input class="wc_bookings_field_date" type="text" id="search-datepicker" name="search-datepicker">
</div>
