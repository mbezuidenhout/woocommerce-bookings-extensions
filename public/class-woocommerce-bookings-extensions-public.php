<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://tripturbine.com/
 * @since      1.0.0
 *
 * @package    Woocommerce_Bookings_Extensions
 * @subpackage Woocommerce_Bookings_Extensions/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woocommerce_Bookings_Extensions
 * @subpackage Woocommerce_Bookings_Extensions/public
 * @author     Marius Bezuidenhout <marius.bezuidenhout@gmail.com>
 */
class Woocommerce_Bookings_Extensions_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * This plugin's uri
	 *
	 * @since   1.1.0
	 * @access  protected
	 * @var     string  $uri    The uri of this plugin.
	 */
	protected $uri;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $uri ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->uri = $uri;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Bookings_Extensions_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Bookings_Extensions_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-bookings-extensions-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Bookings_Extensions_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Bookings_Extensions_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-bookings-extensions-public.js', array( 'jquery' ), $this->version, false );

	}

	public function get_time_blocks_for_date() {

		// clean posted data
		$posted = array();
		parse_str( $_POST['form'], $posted );
		if ( empty( $posted['add-to-cart'] ) ) {
			return false;
		}

		// Product Checking
		$booking_id   = $posted['add-to-cart'];
		$product      = new WC_Booking_Extensions_Product_Booking( wc_get_product( $booking_id ) );
		if ( ! $product ) {
			return false;
		}

		// Check selected date.
		if ( ! empty( $posted['wc_bookings_field_start_date_year'] ) && ! empty( $posted['wc_bookings_field_start_date_month'] ) && ! empty( $posted['wc_bookings_field_start_date_day'] ) ) {
			$year      = max( date( 'Y' ), absint( $posted['wc_bookings_field_start_date_year'] ) );
			$month     = absint( $posted['wc_bookings_field_start_date_month'] );
			$day       = absint( $posted['wc_bookings_field_start_date_day'] );
			$timestamp = strtotime( "{$year}-{$month}-{$day}" );
		}
		if ( empty( $timestamp ) ) {
			die( '<li>' . esc_html__( 'Please enter a valid date.', 'woocommerce-bookings' ) . '</li>' );
		}

		if ( ! empty( $posted['wc_bookings_field_duration'] ) ) {
			$interval = (int) $posted['wc_bookings_field_duration'] * $product->get_duration();
		} else {
			$interval = $product->get_duration();
		}

		$base_interval = $product->get_duration();

		if ( 'hour' === $product->get_duration_unit() ) {
			$interval      = $interval * 60;
			$base_interval = $base_interval * 60;
		}

		$first_block_time     = $product->get_first_block_time();
		$from                 = strtotime( $first_block_time ? $first_block_time : 'midnight', $timestamp );
		$standard_from        = $from;

		// Get an extra day before/after so front-end can get enough blocks to fill out 24 hours in client time.
		if ( isset( $posted['get_prev_day'] ) ) {
			$from = strtotime( '- 1 day', $from );
		}
		$to = strtotime( '+ 1 day', $standard_from ) + $interval;
		if ( isset( $posted['get_next_day'] ) ) {
			$to = strtotime( '+ 1 day', $to );
		}

		// cap the upper range
		$to                   = strtotime( 'midnight', $to ) - 1;

		$resource_id_to_check = ( ! empty( $posted['wc_bookings_field_resource'] ) ? $posted['wc_bookings_field_resource'] : 0 );
		$resource             = $product->get_resource( absint( $resource_id_to_check ) );
		$resources            = $product->get_resources();

		if ( $resource_id_to_check && $resource ) {
			$resource_id_to_check = $resource->ID;
		} elseif ( $product->has_resources() && $resources && sizeof( $resources ) === 1 ) {
			$resource_id_to_check = current( $resources )->ID;
		} else {
			$resource_id_to_check = 0;
		}

		$blocks     = $product->get_blocks_in_range( $from, $to, array( $interval, $base_interval ), $resource_id_to_check );
		//$block_html = wc_bookings_get_time_slots_html( $product, $blocks, array( $interval, $base_interval ), $resource_id_to_check, $from, $to );
		$block_html = $this->get_time_slots_html( $product, $blocks, array( $interval, $base_interval ), $resource_id_to_check, $from, $to );

		if ( empty( $block_html ) ) {
			$block_html .= '<li>' . __( 'No blocks available.', 'woocommerce-bookings' ) . '</li>';
		}

		die( $block_html );
	}

	private function get_time_slots_html( $bookable_product, $blocks, $intervals = array(), $resource_id = 0, $from = 0, $to = 0 ) {
		$available_blocks = $this->get_time_slots( $bookable_product, $blocks, $intervals, $resource_id, $from, $to );
		$block_html       = '';

		foreach ( $available_blocks as $block => $quantity ) {
			if ( $quantity['available'] > 0 ) {
				if ( $quantity['booked'] ) {
					/* translators: 1: quantity available */
					$block_html .= '<li class="block" data-block="' . esc_attr( date( 'Hi', $block ) ) . '"><a href="#" data-value="' . get_time_as_iso8601( $block ) . '">' . date_i18n( get_option( 'time_format' ), $block ) . ' <small class="booking-spaces-left">(' . sprintf( _n( '%d left', '%d left', $quantity['available'], 'woocommerce-bookings' ), absint( $quantity['available'] ) ) . ')</small></a></li>';
				} else {
					$block_html .= '<li class="block" data-block="' . esc_attr( date( 'Hi', $block ) ) . '"><a href="#" data-value="' . get_time_as_iso8601( $block ) . '">' . date_i18n( get_option( 'time_format' ), $block ) . '</a></li>';
				}
			}
		}

		return apply_filters( 'wc_bookings_get_time_slots_html', $block_html, $available_blocks, $blocks );
	}

	/**
	 * @param WC_Booking_Extensions_Product_Booking $bookable_product
	 * @param array $blocks
	 * @param array $intervals
	 * @param int $resource_id
	 * @param int $from
	 * @param int $to
	 *
	 * @return array
	 * @throws WC_Data_Exception
	 */
	private function get_time_slots( $bookable_product, $blocks, $intervals = array(), $resource_id = 0, $from = 0, $to = 0 ) {
		if ( empty( $intervals ) ) {
			$default_interval = 'hour' === $bookable_product->get_duration_unit() ? $bookable_product->get_duration() * 60 : $bookable_product->get_duration();
			$intervals        = array( $default_interval, $default_interval );
		}

		list( $interval, $base_interval ) = $intervals;
		$interval = $bookable_product->get_check_start_block_only() ? $base_interval : $interval;

		$blocks   = $bookable_product->get_available_blocks( array(
			'blocks'      => $blocks,
			'intervals'   => $intervals,
			'resource_id' => $resource_id,
			'from'        => $from,
			'to'          => $to,
		) );

		$existing_bookings = WC_Bookings_Controller::get_all_existing_bookings( $bookable_product, $from, $to );

		$booking_resource  = $resource_id ? $bookable_product->get_resource( $resource_id ) : null;
		$available_slots   = array();

		foreach ( $blocks as $block ) {
			$resources = array();

			// Figure out how much qty have, either based on combined resource quantity,
			// single resource, or just product.
			if ( $bookable_product->has_resources() && ( is_null( $booking_resource ) || ! $booking_resource->has_qty() ) ) {
				$available_qty = 0;

				foreach ( $bookable_product->get_resources() as $resource ) {

					// Only include if it is available for this selection.
					if ( ! WC_Product_Booking_Rule_Manager::check_availability_rules_against_date( $bookable_product, $resource->get_id(), $block ) ) {
						continue;
					}

					if ( in_array( $bookable_product->get_duration_unit(), array( 'minute', 'hour' ) )
					     && ! $bookable_product->check_availability_rules_against_time( $block, strtotime( "+{$interval} minutes", $block ), $resource->get_id() ) ) {
						continue;
					}

					$available_qty += $resource->get_qty();
					$resources[ $resource->get_id() ] = $resource->get_qty();
				}
			} elseif ( $bookable_product->has_resources() && $booking_resource && $booking_resource->has_qty() ) {
				// Only include if it is available for this selection. We set this block to be bookable by default, unless some of the rules apply.
				if ( ! $bookable_product->check_availability_rules_against_time( $block, strtotime( "+{$interval} minutes", $block ), $booking_resource->get_id() ) ) {
					continue;
				}

				$available_qty = $booking_resource->get_qty();
				$resources[ $booking_resource->get_id() ] = $booking_resource->get_qty();
			} else {
				$available_qty = $bookable_product->get_qty();
				$resources[0] = $bookable_product->get_qty();
			}

			$qty_booked_in_block = 0;

			/** @var WC_Booking $existing_booking */
			foreach ( $existing_bookings as $existing_booking ) {
				$buffer = $bookable_product->get_buffer_period_minutes() ?: 0;
				$existing_booking->set_end( $existing_booking->get_end() + $buffer ); // Add buffer after booking
				if ( $existing_booking->is_within_block( $block, strtotime( "+{$interval} minutes", $block ) ) ) {
					$qty_to_add = $bookable_product->has_person_qty_multiplier() ? max( 1, array_sum( $existing_booking->get_persons() ) ) : 1;
					if ( $bookable_product->has_resources() ) {
						if ( $existing_booking->get_resource_id() === absint( $resource_id ) ) {
							// Include the quantity to subtract if an existing booking matches the selected resource id
							$qty_booked_in_block += $qty_to_add;
							$resources[ $resource_id ] = ( isset( $resources[ $resource_id ] ) ? $resources[ $resource_id ] : 0 ) - $qty_to_add;
						} elseif ( ( is_null( $booking_resource ) || ! $booking_resource->has_qty() ) && $existing_booking->get_resource() ) {
							// Include the quantity to subtract if the resource is auto selected (null/resource id empty)
							// but the existing booking includes a resource
							$qty_booked_in_block += $qty_to_add;
							$resources[ $existing_booking->get_resource_id() ] = ( isset( $resources[ $existing_booking->get_resource_id() ] ) ? $resources[ $existing_booking->get_resource_id() ] : 0 ) - $qty_to_add;
						}
					} else {
						$qty_booked_in_block += $qty_to_add;
						$resources[0] = ( isset( $resources[0] ) ? $resources[0] : 0 ) - $qty_to_add;
					}
				}
			}

			$available_slots[ $block ] = array(
				'booked'    => $qty_booked_in_block,
				'available' => $available_qty - $qty_booked_in_block,
				'resources' => $resources,
			);
		}

		return $available_slots;
	}

	/**
	 * Calculate costs.
	 *
	 * Take posted booking form values and then use these to quote a price for what has been chosen.
	 * Returns a string which is appended to the booking form.
	 */
	public function calculate_costs() {
		$posted = array();

		parse_str( $_POST['form'], $posted );

		$booking_id = $posted['add-to-cart'];
		$product    = wc_get_product( $booking_id );

		if ( ! $product ) {
			wp_send_json( array(
				'result' => 'ERROR',
				'html'   => apply_filters( 'woocommerce_bookings_calculated_booking_cost_error_output', '<span class="booking-error">' . __( 'This booking is unavailable.', 'woocommerce-bookings' ) . '</span>', null, null ),
			) );
		}

		$product = new WC_Booking_Extensions_Product_Booking( $product->get_id() );

		$booking_form     = new WC_Booking_Form( $product );
		$cost             = $booking_form->calculate_booking_cost( $posted );

		if ( is_wp_error( $cost ) ) {
			wp_send_json( array(
				'result' => 'ERROR',
				'html'   => apply_filters( 'woocommerce_bookings_calculated_booking_cost_error_output', '<span class="booking-error">' . $cost->get_error_message() . '</span>', $cost, $product ),
			) );
		}

		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

		if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
			if ( function_exists( 'wc_get_price_excluding_tax' ) ) {
				$display_price = wc_get_price_including_tax( $product, array( 'price' => $cost ) );
			} else {
				$display_price = $product->get_price_including_tax( 1, $cost );
			}
		} else {
			if ( function_exists( 'wc_get_price_excluding_tax' ) ) {
				$display_price = wc_get_price_excluding_tax( $product, array( 'price' => $cost ) );
			} else {
				$display_price = $product->get_price_excluding_tax( 1, $cost );
			}
		}

		if ( version_compare( WC_VERSION, '2.4.0', '>=' ) ) {
			$price_suffix = $product->get_price_suffix( $cost, 1 );
		} else {
			$price_suffix = $product->get_price_suffix();
		}

		// Build the output
		$output = apply_filters( 'woocommerce_bookings_booking_cost_string', __( 'Booking cost', 'woocommerce-bookings' ), $product ) . ': <strong>' . wc_price( $display_price ) . $price_suffix . '</strong>';

		// Send the output
		wp_send_json( array(
			'result' => 'SUCCESS',
			'html'   => apply_filters( 'woocommerce_bookings_calculated_booking_cost_success_output', $output, $display_price, $product ),
		) );
	}

	/**
	 * When a booking is added to the cart, validate it
	 *
	 * @param mixed $passed
	 * @param mixed $product_id
	 * @param mixed $qty
	 * @return bool
	 */
	public function validate_add_cart_item( $passed, $product_id, $qty ) {
		$product = wc_get_product( $product_id );
		$product = new WC_Booking_Extensions_Product_Booking( $product->get_id() );

		if ( ! is_wc_booking_product( $product ) ) {
			return $passed;
		}

		$booking_form = new WC_Booking_Form( $product );
		$data         = $booking_form->get_posted_data();
		$validate     = $booking_form->is_bookable( $data );

		if ( is_wp_error( $validate ) ) {
			wc_add_notice( $validate->get_error_message(), 'error' );
			return false;
		}

		return $passed;
	}

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


	public function replace_booking_scripts() {
		$wc_bookings_date_picker_args = array(
			'ajax_url'                   => WC_AJAX::get_endpoint( 'wc_bookings_find_booked_day_blocks' ),
		);

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_deregister_script( 'wc-bookings-date-picker' );
		wp_register_script( 'wc-bookings-date-picker', $this->uri . '/public/js/date-picker' . $suffix . '.js', array( 'wc-bookings-booking-form', 'jquery-ui-datepicker', 'underscore' ), WC_BOOKINGS_VERSION, true );
		wp_localize_script( 'wc-bookings-date-picker', 'wc_bookings_date_picker_args', $wc_bookings_date_picker_args );
	}

}
