<?php
/**
 * Calendar customizations
 *
 * @since      1.0.0
 *
 * @package    Woocommerce_Bookings_Extensions
 * @subpackage Woocommerce_Bookings_Extensions/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/../../woocommerce-bookings/includes/admin/class-wc-bookings-calendar.php';

/**
 * WC_Bookings_Calendar.
 */
class WC_Bookings_Extensions_Calendar extends WC_Bookings_Calendar {

	/**
	 * Stores Bookings.
	 *
	 * @var WC_Booking[]
	 */
	protected $bookings;

	/**
	 * Output the calendar view.
	 */
	public function output() {
		wp_enqueue_script( 'wc-enhanced-select' );

		$product_filter = isset( $_REQUEST['filter_bookings'] ) ? absint( $_REQUEST['filter_bookings'] ) : '';
		$view           = isset( $_REQUEST['view'] ) && 'day' === $_REQUEST['view'] ? 'day' : 'month';

		if ( 'day' === $view ) {
			$day            = isset( $_REQUEST['calendar_day'] ) ? wc_clean( $_REQUEST['calendar_day'] ) : date( 'Y-m-d' );
			$this->bookings = WC_Bookings_Controller::get_bookings_in_date_range(
				strtotime( 'midnight', strtotime( $day ) ),
				strtotime( 'midnight +1 day', strtotime( $day ) ) - 1,
				$product_filter,
				false
			);
		} else {
			$month = isset( $_REQUEST['calendar_month'] ) ? absint( $_REQUEST['calendar_month'] ) : date( 'n' );
			$year  = isset( $_REQUEST['calendar_year'] ) ? absint( $_REQUEST['calendar_year'] ) : date( 'Y' );

			if ( $year < ( date( 'Y' ) - 10 ) || $year > 2100 ) {
				$year = date( 'Y' );
			}

			if ( $month > 12 ) {
				$month = 1;
				$year ++;
			}

			if ( $month < 1 ) {
				$month = 12;
				$year --;
			}

			$start_of_week = absint( get_option( 'start_of_week', 1 ) );
			$last_day      = date( 't', strtotime( "$year-$month-01" ) );
			$start_date_w  = absint( date( 'w', strtotime( "$year-$month-01" ) ) );
			$end_date_w    = absint( date( 'w', strtotime( "$year-$month-$last_day" ) ) );

			// Calc day offset.
			$day_offset = $start_date_w - $start_of_week;
			$day_offset = $day_offset >= 0 ? $day_offset : 7 - abs( $day_offset );

			// Cald end day offset.
			$end_day_offset = 7 - ( $last_day % 7 ) - $day_offset;
			$end_day_offset = $end_day_offset >= 0 && $end_day_offset < 7 ? $end_day_offset : 7 - abs( $end_day_offset );

			// We want to get the last minute of the day, so we will go forward one day to midnight and subtract a min.
			$end_day_offset++;

			$start_time     = strtotime( "-{$day_offset} day", strtotime( "$year-$month-01" ) );
			$end_time       = strtotime( "+{$end_day_offset} day midnight", strtotime( "$year-$month-$last_day" ) );
			$this->bookings = WC_Bookings_Controller::get_bookings_in_date_range(
				$start_time,
				$end_time,
				$product_filter,
				false
			);
		}

		include __DIR__ . '/../../woocommerce-bookings/includes/admin/views/html-calendar-' . $view . '.php';
	}


	/**
	 * List bookings for a day.
	 *
	 * @param string $day Day of month.
	 * @param string $month Month.
	 * @param string $year Year.
	 */
	public function list_bookings( $day, $month, $year ) {
		$date_start = strtotime( "$year-$month-$day midnight" ); // Midnight today.
		$date_end   = strtotime( "$year-$month-$day tomorrow" ); // Midnight next day.

		foreach ( $this->bookings as $booking ) {
			if ( $booking->get_start() < $date_end && $booking->get_end() > $date_start ) {
				echo '<li><a href="' . admin_url( 'post.php?post=' . $booking->get_id() . '&action=edit' ) . '">';
				echo '<strong>#' . $booking->get_id() . ' - ';
				$product = $booking->get_product();
				if ( $product ) {
					echo $product->get_title();
				}
				echo '</strong>';
				echo '<ul>';
				$customer = $booking->get_customer();
				if ( $customer && ! empty( $customer->name ) ) {
					echo '<li>' . __( 'Booked by', 'woocommerce-bookings' ) . ' ' . $customer->name . '</li>';
				}
				$guest_name = $booking->get_meta( 'booking_guest_name' );
				if ( ! empty( $guest_name ) ) {
					echo '<li>' . __( 'Booked for', 'woocommerce-bookings-extensions' ) . ' ' . $guest_name . '</li>';
				}
				echo '<li>';
				if ( $booking->is_all_day() ) {
					echo __( 'All Day', 'woocommerce-bookings' );
				} else {
					echo $booking->get_start_date() . '&mdash;' . $booking->get_end_date();
				}
				echo '</li>';
				$resource = $booking->get_resource();
				if ( $resource ) {
					echo '<li>' . __( 'Resource #', 'woocommerce-bookings' ) . $resource->ID . ' - ' . $resource->post_title . '</li>';
				}
				$persons = $booking->get_persons();
				foreach ( $persons as $person_id => $person_count ) {
					echo '<li>';
					/* translators: 1: person id 2: person name 3: person count */
					printf( __( 'Person #%1$s - %2$s (%3$s)', 'woocommerce-bookings' ), $person_id, get_the_title( $person_id ), $person_count );
					echo '</li>';
				}
				echo '</ul></a>';
				echo '</li>';
			}
		}
	}
}