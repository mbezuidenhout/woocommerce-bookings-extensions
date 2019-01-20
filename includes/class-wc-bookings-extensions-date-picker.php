<?php
/**
 * Class dependencies
 */
if ( ! class_exists( 'WC_Booking_Form_Picker' ) ) {
	include_once 'class-wc-bookings-extensions-picker.php';
}

/**
 * Date Picker class
 */
class WC_Bookings_Extensions_Bookings_Date_Picker extends WC_Bookings_Extensions_Bookings_Picker {

	private $field_type = 'date-picker';
	private $field_name = 'start_date';

	/**
	 * Constructor
	 *
	 * @param WC_Bookings_Extensions_Bookings_Search $search_form The booking form which called this picker
	 */
	public function __construct( $search_form ) {
		$this->search_form                     = $search_form;
		$this->args                            = array();
		$this->args['type']                    = $this->field_type;
		$this->args['name']                    = $this->field_name;
		$this->args['min_date']                = $this->search_form->get_min_date();
		$this->args['max_date']                = $this->search_form->get_max_date();
		$this->args['default_availability']    = $this->search_form->get_default_availability();
		$this->args['min_date_js']             = $this->get_min_date();
		$this->args['max_date_js']             = $this->get_max_date();
		$this->args['duration_type']           = $this->search_form->get_duration_type();
		$this->args['duration_unit']           = $this->search_form->get_duration_unit();
		$this->args['is_range_picker_enabled'] = $this->search_form->is_range_picker_enabled();
		$this->args['display']                 = $this->search_form->get_calendar_display_mode();
		$this->args['label']                   = $this->get_field_label( __( 'Date', 'woocommerce-bookings' ) );
		$this->args['product_type']            = $this->search_form->get_type();
		$this->args['default_date']            = date( 'Y-m-d', $this->get_default_date() );
	}

	/**
	 * Attempts to find what date to default to in the date picker
	 * by looking at the fist available block. Otherwise, the current date is used.
	 *
	 * @return int Timestamp
	 */
	public function get_default_date() {

		/**
		 * Filter woocommerce_bookings_override_form_default_date
		 *
		 * @since 1.9.8
		 * @param int $default_date unix time stamp.
		 * @param WC_Booking_Form_Picker $form_instance
		 */
		$default_date = apply_filters( 'woocommerce_bookings_override_form_default_date', null, $this );

		if ( $default_date ) {
			return $default_date;
		}

		$default_date = strtotime( 'midnight' );

		/**
		 * Filter wc_bookings_calendar_default_to_current_date. By default the calendar
		 * will show the current date first. If you would like it to display the first available date
		 * you can return false to this filter and then we'll search for the first available date,
		 * depending on the booked days calculation.
		 *
		 * @since 1.9.13
		 * @param bool
		 */

		if ( ! apply_filters( 'wc_bookings_calendar_default_to_current_date', true ) ) {
			/*
			 * Handles the case where a user can set all dates to be not-available by default.
			 * Also they add an availability rule where they are bookable at a future date in time.
			 */

			$now      = strtotime( 'midnight', current_time( 'timestamp' ) );
			$min      = $this->search_form->get_min_date();
			$max      = $this->search_form->get_max_date();
			$min_date = strtotime( 'midnight' );

			if ( ! empty( $min ) ) {
				$min_date = strtotime( "+{$min['value']} {$min['unit']}", $now );
			}

			/*
			 * Handling months differently due to performance impact it has. Get it in three
			 * months batches to ensure we can exit when we find the first one without going
			 * through all 12 months.
			 */
			for ( $i = 1; $i <= $max['value']; $i += 3 ) {

				/*
				 * $min_date calculated above first.
				 * Only add months up to the max value.
				 */
				$range_end_increment = ( $i + 3 ) > $max['value'] ? $max['value'] : ( $i + 3 );
				$max_date            = strtotime( "+ $range_end_increment month", $now );
				$blocks_in_range     = $this->search_form->get_blocks_in_range( $min_date, $max_date );
				$last_element        = end( $blocks_in_range );

				reset( $blocks_in_range ); // restore the internal pointer.

				if ( ! empty( $blocks_in_range ) && $blocks_in_range[0] > $last_element ) {
					/*
					 * In certain cases the starting date is at the end
					 * `product->get_available_blocks` expects it to be at the beginning.
					 */
					$blocks_in_range = array_reverse( $blocks_in_range );
				}

				$available_blocks = $this->search_form->get_available_blocks(
					array(
						'blocks' => $blocks_in_range,
					)
				);

				if ( ! empty( $available_blocks[0] ) ) {
					$default_date = $available_blocks[0];
					break;
				} // else continue with loop until we get a default date where the calendar can start at.

				$min_date = strtotime( '+' . $i . ' month', $now );
			}
		}

		return $default_date;
	}

}