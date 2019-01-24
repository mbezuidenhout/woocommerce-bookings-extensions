<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

wp_enqueue_script( 'wc-bookings-extensions-date-picker' );

?>
<p class="form-field form-field-wide wc_bookings_field_datepicker">
	<label for="search-datepicker">Date:</label>
</p>
<div class="wc_bookings_field_date-container">
	<input class="wc_bookings_field_date" type="text" id="search-datepicker" name="search-datepicker">
</div>
