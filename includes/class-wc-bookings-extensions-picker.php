<?php
/**
 * Picker class
 */
abstract class WC_Bookings_Extensions_Bookings_Picker {

	/** @var WC_Product_Booking */
	protected $search_form;
	protected $args = array();

	/**
	 * Get the label for the field based on booking durations and type
	 * @param  string $text text to insert into label string
	 * @return string
	 */
	protected function get_field_label( $text ) {
		// If the duration is > 1, dates and times are 'start' times and should thus have different labels
		if ( $this->search_form->get_duration_type() === 'customer' && $this->search_form->get_max_duration() > 1 && ! in_array( $this->search_form->get_duration_unit(), array( 'hour', 'minute' ), true ) ) {
			/* translators: 1: Text to insert into label string */
			$date_label = __( 'Start %s', 'woocommerce-bookings' );
		} else {
			$date_label = '%s';
		}

		return sprintf( $date_label, $text );
	}

	/**
	 * Get the min date in date picker format
	 * @return string
	 */
	protected function get_min_date() {
		$js_string = '';
		$min_date  = $this->search_form->get_min_date();
		if ( $min_date['value'] ) {
			$unit = strtolower( substr( $min_date['unit'], 0, 1 ) );

			if ( in_array( $unit, array( 'd', 'w', 'y', 'm' ), true ) ) {
				$js_string = "+{$min_date['value']}{$unit}";
			} elseif ( 'h' === $unit ) {

				// if less than 24 hours are entered, we determine if the time falls in today or tomorrow.
				// if more than 24 hours are entered, we determine how many days should be marked off
				if ( 24 > $min_date['value'] ) {
					$current_d = date( 'd', current_time( 'timestamp' ) );
					$min_d     = date( 'd', strtotime( "+{$min_date['value']} hour", current_time( 'timestamp' ) ) );
					$js_string = '+' . ( $current_d === $min_d ? 0 : 1 ) . 'd';
				} else {
					$min_d     = (int) ( $min_date['value'] / 24 );
					$js_string = '+' . $min_d . 'd';
				}
			}
		}
		return $js_string;
	}

	/**
	 * Get the max date in date picker format
	 * @return string
	 */
	protected function get_max_date() {
		$js_string = '';
		$max_date  = $this->search_form->get_max_date();
		$unit      = strtolower( substr( $max_date['unit'], 0, 1 ) );

		if ( in_array( $unit, array( 'd', 'w', 'y', 'm' ), true ) ) {
			$js_string = "+{$max_date['value']}{$unit}";
		} elseif ( 'h' === $unit ) {
			$current_d = date( 'd', current_time( 'timestamp' ) );
			$max_d     = date( 'd', strtotime( "+{$max_date['value']}{$unit}", current_time( 'timestamp' ) ) );
			$js_string = '+' . ( $current_d === $max_d ? 0 : 1 ) . 'd';
		}
		return $js_string;
	}

	/**
	 * Return args for the field
	 * @return array
	 */
	public function get_args() {
		return $this->args;
	}
}
