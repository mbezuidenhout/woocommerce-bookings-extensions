<?php

/**
 * The public-facing functionality of the plugin.
 *
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
class WC_Bookings_Extensions_Public {

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
	 * @param    string    $plugin_name       The name of the plugin.
	 * @param    string    $version     The version of this plugin.
	 * @param    string    $uri         The plugin's uri
	 */
	public function __construct( $plugin_name, $version, $uri ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->uri         = $uri;

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

	/**
	 * Get a list of time blocks available on a date.
	 */
	public function get_time_blocks_for_date() {

		// clean posted data
		$posted = array();
		parse_str( $_POST['form'], $posted );
		if ( empty( $posted['add-to-cart'] ) ) {
			return false;
		}

		// Product Checking
		$booking_id = $posted['add-to-cart'];
		$product    = new WC_Bookings_Extensions_Product_Booking( wc_get_product( $booking_id ) );
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

		$first_block_time = $product->get_first_block_time();
		$from             = strtotime( $first_block_time ? $first_block_time : 'midnight', $timestamp );
		$standard_from    = $from;

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
		} elseif ( $product->has_resources() && $resources && count( $resources ) === 1 ) {
			$resource_id_to_check = current( $resources )->ID;
		} else {
			$resource_id_to_check = 0;
		}

		$blocks = $product->get_blocks_in_range( $from, $to, array( $interval, $base_interval ), $resource_id_to_check );
		// Get dependent products blocks
		$dependent_product_ids = $product->get_meta( 'booking_dependencies' );
		if ( is_array( $dependent_product_ids ) ) {
			foreach ( $dependent_product_ids as $dependent_product_id ) {
				$dependent_product = new WC_Bookings_Extensions_Product_Booking( wc_get_product( $dependent_product_id ) );
				/** @var \WC_Booking[] $dep_prod_existing_bookings */
				$dep_prod_existing_bookings = WC_Bookings_Controller::get_all_existing_bookings( $dependent_product, $from, $to );
				foreach ( $dep_prod_existing_bookings as $existing_booking ) {
					$block_size = $interval + $product->get_buffer_period();
					foreach ( $blocks as $key => $block ) {
						if ( $existing_booking->is_within_block( $block, strtotime( "+{$block_size} minutes", $block ) ) ) {
							unset( $blocks[ $key ] );
						}
					}
				}
			}
			$blocks = array_values( $blocks );
		}

		$block_html = $this->get_time_slots_html( $product, $blocks, array( $interval, $base_interval ), $resource_id_to_check, $from, $to );

		if ( empty( $block_html ) ) {
			$block_html .= '<li>' . __( 'No blocks available.', 'woocommerce-bookings' ) . '</li>';
		}

		die( $block_html );
	}

	/**
	 * Find available blocks and return HTML for the user to choose a block. Used in class-wc-bookings-ajax.php.
	 *
	 * @param \WC_Product_Booking $bookable_product
	 * @param  array              $blocks
	 * @param  array              $intervals
	 * @param  integer            $resource_id
	 * @param  integer            $from The starting date for the set of blocks
	 * @param  integer            $to
	 *
	 * @return string
	 * @throws WC_Data_Exception
	 */
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
	 * @param WC_Bookings_Extensions_Product_Booking $bookable_product
	 * @param array                                  $blocks
	 * @param array                                  $intervals
	 * @param int                                    $resource_id
	 * @param int                                    $from
	 * @param int                                    $to
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
		$interval                         = $bookable_product->get_check_start_block_only() ? $base_interval : $interval;

		$blocks = $bookable_product->get_available_blocks(
			array(
				'blocks'      => $blocks,
				'intervals'   => $intervals,
				'resource_id' => $resource_id,
				'from'        => $from,
				'to'          => $to,
			)
		);

		/** @var WC_Booking[] $existing_bookings */
		$existing_bookings = WC_Bookings_Controller::get_all_existing_bookings( $bookable_product, $from, $to );
		// Add buffer period to each booking
		foreach ( $existing_bookings as &$existing_booking ) {
			$existing_booking->set_end( strtotime( "+{$bookable_product->get_buffer_period_minutes()} minutes", $existing_booking->get_end() ) );
		}
		/** @var int[]|null $dependent_products */
		$dependent_products = $bookable_product->get_meta( 'booking_dependencies' );
		if ( is_array( $dependent_products ) ) {
			foreach ( $dependent_products as $dependent_product ) {
				$dependent_product = new WC_Bookings_Extensions_Product_Booking( $dependent_product );
				$dep_prod_bookings = WC_Bookings_Controller::get_all_existing_bookings( $dependent_product, $from, $to );
				foreach ( $dep_prod_bookings as &$dep_prod_booking ) {
					$dep_prod_booking->set_end( strtotime( "+{$dependent_product->get_buffer_period_minutes()} minutes", $dep_prod_booking->get_end() ) );
				}
				$existing_bookings = array_merge( $existing_bookings, $dep_prod_bookings );
			}
		}

		$booking_resource = $resource_id ? $bookable_product->get_resource( $resource_id ) : null;
		$available_slots  = array();

		foreach ( $blocks as $block ) {
			$resources = array();

			// Figure out how much qty have, either based on combined resource quantity,
			// single resource, or just product.
			if ( $bookable_product->has_resources() && ( is_null( $booking_resource ) || ! $booking_resource->has_qty() ) ) {
				$available_qty = 0;

				/** @var \WC_Product_Booking_Resource $resource */
				foreach ( $bookable_product->get_resources() as $resource ) {

					// Only include if it is available for this selection.
					if ( ! WC_Product_Booking_Rule_Manager::check_availability_rules_against_date( $bookable_product, $resource->get_id(), $block ) ) {
						continue;
					}

					if ( in_array( $bookable_product->get_duration_unit(), array( 'minute', 'hour' ), true ) &&
						! $bookable_product->check_availability_rules_against_time( $block, strtotime( "+{$interval} minutes", $block ), $resource->get_id() ) ) {
						continue;
					}

					$available_qty                   += $resource->get_qty();
					$resources[ $resource->get_id() ] = $resource->get_qty();
				}
			} elseif ( $bookable_product->has_resources() && $booking_resource && $booking_resource->has_qty() ) {
				// Only include if it is available for this selection. We set this block to be bookable by default, unless some of the rules apply.
				if ( ! $bookable_product->check_availability_rules_against_time( $block, strtotime( "+{$interval} minutes", $block ), $booking_resource->get_id() ) ) {
					continue;
				}

				$available_qty                            = $booking_resource->get_qty();
				$resources[ $booking_resource->get_id() ] = $booking_resource->get_qty();
			} else {
				$available_qty = $bookable_product->get_qty();
				$resources[0]  = $bookable_product->get_qty();
			}

			$qty_booked_in_block = 0;

			foreach ( $existing_bookings as $existing_booking ) {
				if ( $existing_booking->is_within_block( $block, strtotime( "+{$interval} minutes", $block ) ) ) {
					$qty_to_add = $bookable_product->has_person_qty_multiplier() ? max( 1, array_sum( $existing_booking->get_persons() ) ) : 1;
					if ( $bookable_product->has_resources() ) {
						if ( $existing_booking->get_resource_id() === absint( $resource_id ) ) {
							// Include the quantity to subtract if an existing booking matches the selected resource id
							$qty_booked_in_block      += $qty_to_add;
							$resources[ $resource_id ] = ( isset( $resources[ $resource_id ] ) ? $resources[ $resource_id ] : 0 ) - $qty_to_add;
						} elseif ( ( is_null( $booking_resource ) || ! $booking_resource->has_qty() ) && $existing_booking->get_resource() ) {
							// Include the quantity to subtract if the resource is auto selected (null/resource id empty)
							// but the existing booking includes a resource
							$qty_booked_in_block                              += $qty_to_add;
							$resources[ $existing_booking->get_resource_id() ] = ( isset( $resources[ $existing_booking->get_resource_id() ] ) ? $resources[ $existing_booking->get_resource_id() ] : 0 ) - $qty_to_add;
						}
					} else {
						$qty_booked_in_block += $qty_to_add;
						$resources[0]         = ( isset( $resources[0] ) ? $resources[0] : 0 ) - $qty_to_add;
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
			wp_send_json(
				array(
					'result' => 'ERROR',
					'html'   => apply_filters( 'woocommerce_bookings_calculated_booking_cost_error_output', '<span class="booking-error">' . __( 'This booking is unavailable.', 'woocommerce-bookings' ) . '</span>', null, null ),
				)
			);
		}

		$product = new WC_Bookings_Extensions_Product_Booking( $product->get_id() );

		$booking_form = new WC_Booking_Form( $product );
		$cost         = $booking_form->calculate_booking_cost( $posted );

		if ( is_wp_error( $cost ) ) {
			wp_send_json(
				array(
					'result' => 'ERROR',
					'html'   => apply_filters( 'woocommerce_bookings_calculated_booking_cost_error_output', '<span class="booking-error">' . $cost->get_error_message() . '</span>', $cost, $product ),
				)
			);
		}

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
		wp_send_json(
			array(
				'result' => 'SUCCESS',
				'html'   => apply_filters( 'woocommerce_bookings_calculated_booking_cost_success_output', $output, $display_price, $product ),
			)
		);
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

		if ( ! is_wc_booking_product( $product ) ) {
			return $passed;
		}

		$product = new WC_Bookings_Extensions_Product_Booking( $product->get_id() );

		$booking_form = new WC_Booking_Form( $product );
		$data         = $booking_form->get_posted_data();
		$validate     = $booking_form->is_bookable( $data );

		if ( is_wp_error( $validate ) ) {
			wc_add_notice( $validate->get_error_message(), 'error' );
			return false;
		}

		// Check validation on dependents
		$dependent_products_ids = $product->get_meta( 'booking_dependencies' );
		if ( is_array( $dependent_products_ids ) ) {
			foreach ( $dependent_products_ids as $depenent_products_id ) {
				$dependent_product = new WC_Bookings_Extensions_Product_Booking( $depenent_products_id );
				// Adjust check range by 1 second less on start and end
				$existing_bookings = $dependent_product->get_bookings_in_date_range( $data['_start_date'] + 1, $data['_end_date'] - 1 );
				if ( ! empty( $existing_bookings ) ) {
					$error = new WP_Error( 'Error', __( 'Sorry, the selected block is not available', 'woocommerce-bookings' ) );
					wc_add_notice( $error->get_error_message(), 'error' );
					return false;
				}
			}
		}

		return $passed;
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

		if ( ! is_wc_booking_product( $product ) ) {
			return $cart_item_meta;
		}

		$product = new WC_Bookings_Extensions_Product_Booking( $product->get_id() );

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
	 * Processes the shortcode wcbooking_search
	 *
	 * Usage: wcbooking_search duration_unit="{month|day|hour|minute}" duration="<Integer value of unit size>"
	 * [method="{include|exclude}" ids="<Comma separated ist of product ids>"]
	 *
	 * The search will only include products of type Bookable Product/WC_Bookings
	 *
	 * @param array $atts Attributes passed by the shortcode
	 * @return string
	 */
	public function global_search_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'method'        => 'exclude',
				'ids'           => '',
				'duration_unit' => 'day',
				'duration'      => 1,
			),
			$atts,
			'wcbooking_search'
		);

		$ids = array_unique( explode( ',', preg_replace( '/[^0-9,]/', '', $atts['ids'] ) ) );
		$key = array_search( '', $ids, true );
		if ( false !== $key ) {
			unset( $ids[ $key ] );
		}

		$ids = array_values( $ids );

		$search_form = new WC_Bookings_Extensions_Bookings_Search( $atts['method'], $ids, $atts['duration_unit'], intval( $atts['duration'] ) );

		ob_start();

		wc_get_template( 'globalsearch.php', array( 'bookings_search_form' => $search_form ), 'woocommerce-bookings-extensions', plugin_dir_path( __DIR__ ) . 'templates/' );

		return ob_get_clean();
	}

	/**
	 * Sends back array for bookings global search shortcode js
	 */
	public function search_booking_products() {
		$request = $_GET;

		$data = array(
			'availability_rules'    => array(),
			'buffer_days'           => array(),
			'fully_booked_days'     => array(),
			'max_date'              => strtotime( $request['max_date'] ),
			'min_date'              => strtotime( $request['min_date'] ),
			'partially_booked_days' => array(),
			'restricted_days'       => false,
			'unavailable_days'      => array(),
		);

		wp_send_json( $data );
	}

	/**
	 * Sends html of bookable products that are available for specified date
	 */
	public function search_result() {
		check_ajax_referer( 'search-bookings' );
		$posted = array();
		parse_str( $_POST['form'], $posted );
		$default = array(
			'wc_bookings_field_start_date_year'  => null,
			'wc_bookings_field_start_date_month' => null,
			'wc_bookings_field_start_date_day'   => null,
			'wc_bookings_field_duration'         => 1,
			'wc_bookings_field_persons'          => null,
			'duration_unit'                      => 'day',
			'duration'                           => 1,
		);

		$posted = wp_parse_args( $posted, $default );

		$ids = array_unique( array_map( 'intval', explode( ',', preg_replace( '/[^0-9,]/', '', $posted['ids'] ) ) ) );
		$key = array_search( '', $ids, true );
		if ( false !== $key ) {
			unset( $ids[ $key ] );
		}

		$ids = array_values( $ids );

		$booking_search = new WC_Bookings_Extensions_Bookings_Search( 'include', $ids, $posted['duration_unit'], intval( $posted['duration'] ) );

		$date = strtotime( intval( $_REQUEST['year'] ) . '-' . ( intval( $_REQUEST['month'] ) ) . '-' . intval( $_REQUEST['day'] ) );

		$availability_html = $booking_search->get_availability_html( $date, intval( $posted['wc_bookings_field_duration'] ), intval( $posted['wc_bookings_field_persons'] ) );

		$res = array(
			'result' => 'SUCCESS',
			'html'   => $availability_html,
		);

		wp_send_json( $res );
	}

	public function find_booked_day_blocks( $product_id, $min_date = null, $max_date = null, $timezone_offset = null ) {
		try {

			$args                          = array();
			$product                       = new WC_Product_Booking( $product_id );
			$args['availability_rules']    = array();
			$args['availability_rules'][0] = $product->get_availability_rules();
			$args['min_date']              = ! is_null( $min_date ) ? strtotime( $min_date ) : $product->get_min_date();
			$args['max_date']              = ! is_null( $max_date ) ? strtotime( $max_date ) : $product->get_max_date();

			$min_date        = ( is_null( $min_date ) ) ? strtotime( "+{$args['min_date']['value']} {$args['min_date']['unit']}", current_time( 'timestamp' ) ) : $args['min_date'];
			$max_date        = ( is_null( $max_date ) ) ? strtotime( "+{$args['max_date']['value']} {$args['max_date']['unit']}", current_time( 'timestamp' ) ) : $args['max_date'];
			$timezone_offset = ! is_null( $timezone_offset ) ? $timezone_offset : 0;

			if ( $product->has_resources() ) {
				foreach ( $product->get_resources() as $resource ) {
					$args['availability_rules'][ $resource->ID ] = $product->get_availability_rules( $resource->ID );
				}
			}

			$booked = WC_Bookings_Controller::find_booked_day_blocks( $product_id, $min_date, $max_date, 'Y-n-j', $timezone_offset );

			$args['partially_booked_days'] = $booked['partially_booked_days'];
			$args['fully_booked_days']     = $booked['fully_booked_days'];
			$args['unavailable_days']      = $booked['unavailable_days'];
			$args['restricted_days']       = $product->has_restricted_days() ? $product->get_restricted_days() : false;

			$buffer_days = array();
			if ( ! in_array( $product->get_duration_unit(), array( 'minute', 'hour' ), true ) ) {
				$buffer_days = WC_Bookings_Controller::get_buffer_day_blocks_for_booked_days( $product, $args['fully_booked_days'] );
			}

			$args['buffer_days'] = $buffer_days;

			return $args;

		} catch ( Exception $e ) {

			wp_die();

		}
	}

	/**
	 * This endpoint is supposed to replace the back-end logic in booking-form.
	 */
	public function find_booked_day_blocks_ajax() {
		check_ajax_referer( 'find-booked-day-blocks', 'security' );

		$product_id = absint( $_GET['product_id'] );

		if ( empty( $product_id ) ) {
			wp_send_json_error( 'Missing product ID' );
			exit;
		}

		$args = $this->find_booked_day_blocks( intval( $product_id ), $_GET['min_date'], $_GET['max_date'], $_GET['timezone_offset'] );

		$product                = wc_get_product( $product_id );
		$dependent_products_ids = $product->get_meta( 'booking_dependencies' );

		if ( ! empty( $dependent_products_ids ) ) {
			foreach ( $dependent_products_ids as $dependent_product_id ) {
				$dependent_args = $this->find_booked_day_blocks( intval( $dependent_product_id ), $_GET['min_date'], $_GET['max_date'], $_GET['timezone_offset'] );

				// Merge data together. Note that only fully and partially booked data gets merged

				// Add fully booked days and remove out of partially booked list
				foreach ( $dependent_args['fully_booked_days'] as $day => $val ) {
					$args['fully_booked_days'][ $day ] = $val;
					if ( array_key_exists( $day, $args['partially_booked_days'] ) ) {
						unset( $args['partially_booked_days'][ $day ] );
					}
				}

				// Add partially booked days
				foreach ( $dependent_args['partially_booked_days'] as $day => $val ) {
					if ( ! array_key_exists( $day, $args['fully_booked_days'] ) ) {
						$args['partially_booked_days'][ $day ] = $val;
					}
				}
			}
		}

		wp_send_json( $args );

	}

}
