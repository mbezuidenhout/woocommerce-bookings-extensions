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
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 * @param string $uri The plugin's uri.
	 *
	 * @since    1.0.0
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

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-bookings-extensions-public' . $suffix . '.css', array(), $this->version, 'all' );

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

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-bookings-extensions-public' . $suffix . '.js', array( 'jquery' ), $this->version, false );
		if( version_compare( WC_BOOKINGS_VERSION, '1.14' ) >= 0 ) {
			wp_register_script( 'wc-bookings-moment', WC_BOOKINGS_PLUGIN_URL . '/dist/js/lib/moment-with-locales.js', array(), WC_BOOKINGS_VERSION, true );
		} else {
			wp_register_script( 'wc-bookings-moment', WC_BOOKINGS_PLUGIN_URL . '/assets/js/lib/moment-with-locales.js', array(), WC_BOOKINGS_VERSION, true );
		}
	}

	/**
	 * Get a list of time blocks available on a date.
	 */
	public function get_time_blocks_for_date() {

		// clean posted data.
		$posted = array();
		if ( isset( $_POST['form'] ) ) {
			parse_str( $_POST['form'], $posted );
		}
		if ( empty( $posted['add-to-cart'] ) ) {
			return false;
		}

		// Product Checking.
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

		// Cap the upper range.
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
		// Get dependent products blocks.
		$dependent_product_ids = $product->get_meta( 'booking_dependencies' );
		if ( is_array( $dependent_product_ids ) ) {
			foreach ( $dependent_product_ids as $dependent_product_id ) {
				$dependent_product = new WC_Bookings_Extensions_Product_Booking( wc_get_product( $dependent_product_id ) );
				/** @var \WC_Booking[] $dep_prod_existing_bookings Existing booking. */
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
	 * @param \WC_Product_Booking $bookable_product Instance of WC_Product_Booking.
	 * @param array               $blocks           Number of blocks.
	 * @param array               $intervals        Array of available blocks.
	 * @param integer             $resource_id      WooCommerce product ID.
	 * @param integer             $from             The starting date for the set of blocks.
	 * @param integer             $to               The end date for the set of blocks.
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
	 * Get an array of time slots.
	 *
	 * @param WC_Bookings_Extensions_Product_Booking $bookable_product Instance of WC_Bookings_Extensions_Product_Booking.
	 * @param array                                  $blocks           An array of blocks.
	 * @param array                                  $intervals        An array of intervals in minutes.
	 * @param int                                    $resource_id      WooCommerce product id.
	 * @param int                                    $from             Unix from time.
	 * @param int                                    $to               Unix to time.
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
		// Add buffer period to each booking.
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
							// Include the quantity to subtract if an existing booking matches the selected resource id.
							$qty_booked_in_block      += $qty_to_add;
							$resources[ $resource_id ] = ( isset( $resources[ $resource_id ] ) ? $resources[ $resource_id ] : 0 ) - $qty_to_add;
						} elseif ( ( is_null( $booking_resource ) || ! $booking_resource->has_qty() ) && $existing_booking->get_resource() ) {
							// Include the quantity to subtract if the resource is auto selected (null/resource id empty)
							// but the existing booking includes a resource.
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

	/**
	 * Get an array of booked day blocks.
	 *
	 * @param int $product_id      WooCommerce product id.
	 * @param int $min_date        Unix start time.
	 * @param int $max_date        Unix end time.
	 * @param int $timezone_offset Time offset in hours.
	 *
	 * @return array
	 */
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

				// Merge data together. Note that only fully and partially booked data gets merged.

				// Add fully booked days and remove out of partially booked list.
				foreach ( $dependent_args['fully_booked_days'] as $day => $val ) {
					$args['fully_booked_days'][ $day ] = $val;
					if ( array_key_exists( $day, $args['partially_booked_days'] ) ) {
						unset( $args['partially_booked_days'][ $day ] );
					}
				}

				// Add partially booked days.
				foreach ( $dependent_args['partially_booked_days'] as $day => $val ) {
					if ( ! array_key_exists( $day, $args['fully_booked_days'] ) ) {
						$args['partially_booked_days'][ $day ] = $val;
					}
				}
			}
		}

		wp_send_json( $args );

	}

	/**
	 * Get an array of bookings
	 *
	 * @param int    $product_id Product ID to fetch.
	 * @param string $range Range of dates to fetch [now|next].
	 * @return \WC_Booking[][]
	 * @throws Exception
	 */
	protected function get_bookings_v1( $product_id, $range = 'now' ) { // Remove complate function by 2019-07.
		$products = array();
		if ( is_null( $product_id ) ) {
			/** @var \WC_Product_Data_Store_CPT $data_store */
			$data_store = WC_Data_Store::load( 'product' );
			$ids        = $data_store->search_products( null, 'booking', false, false, null );
			foreach ( $ids as $id ) {
				$product = wc_get_product( $id );
				if ( is_a( $product, 'WC_Product_Booking' ) ) {
					$products[] = $product;
				}
			}
		} else {
			$product = wc_get_product( $product_id );
			if ( $product && 'booking' === $product->get_type() ) {
				$products[] = $product;
			}
		}

		$bookings = array();
		foreach ( $products as $product ) {
			/** @var \WC_Product_Booking $product */
			$now        = current_time( 'timestamp' );
			$start_time = strtotime( 'today' );
			$end_time   = strtotime( 'tomorrow' );
			switch ( $range ) {
				case 'now':
				case 'day':
					break;
				case 'week':
					$end_time = strtotime( '+ 1 week', $start_time );
					break;
				case 'now-next':
				case 'next':
				default:
					$end_time = strtotime( '+ 1 month', $start_time );
					break;
			}
			/** @var \WC_Booking[] $product_bookings */
			$product_bookings  = $product->get_bookings_in_date_range( $start_time, $end_time );
			$_product_bookings = array();
			$now_next_range    = false;
			if ( 'now' === substr( $range, 0, 3 ) ) {
				foreach ( $product_bookings as $key => $product_booking ) {
					if ( $product_booking->get_start() <= $now && $product_booking->get_end() >= $now ) {
						$now_next_range           = true;
						$_product_bookings['now'] = $product_booking;
						break;
					}
				}
			}
			if ( 'next' === substr( $range, -4 ) ) {
				foreach ( $product_bookings as $key => $product_booking ) {
					if ( $product_booking->get_start() <= $now && $product_booking->get_end() >= $now ) {
						continue;
					}
					$now_next_range            = true;
					$_product_bookings['next'] = $product_booking;
					break;
				}
			}
			if ( $now_next_range ) {
				$bookings[ $product->get_id() ] = $_product_bookings;
			} else {
				$bookings[ $product->get_id() ] = $product_bookings;
			}
		}

		return $bookings;
	}

	/**
	 * Usage: https://<server>/wc-bookings/fetch?username=<username>&password=<password>&product_id=<product_id>
	 *
	 * @throws Exception
	 */
	public function get_bookings_page() {
		$defaults = array(
			'username'   => '',
			'password'   => '',
			'product_id' => null,
		);

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$args               = wp_parse_args( sanitize_post( $_GET ), $defaults );  // phpcs:ignore WordPress.Security.NonceVerification
		$user               = wp_authenticate( $args['username'], $args['password'] );
		$args['product_id'] = intval( $args['product_id'] );
		if ( ! $user instanceof WP_User || ! user_can( $user, 'edit_others_posts' ) ) { // User doesn't exist or can't see bookings.
			wp_die( 'Invalid user or does not have sufficient privileges' );
		}

		if ( 0 === $args['product_id'] ) {
			wp_die( 'Product ID cannot be null' );
		}

		$product = wc_get_product( $args['product_id'] );
		if ( ! is_a( $product, 'WC_Product' ) ) {
			wp_die( 'Invalid product ID' );
		}

		$calendar = WC_Bookings_Extensions_New_Calendar::get_instance();

		$bookings = $calendar->get_bookings( $args['product_id'], time(), time() + 86400 );

		$script_params = array(
			'server_unix_time' => strtotime( 'now' ),
			'date_format'      => WC_Bookings_Extensions_Lib::convert_to_moment_format( get_option( 'date_format' ) ),
			'time_format'      => WC_Bookings_Extensions_Lib::convert_to_moment_format( get_option( 'time_format' ) ),
			'ajax_url'         => get_site_url() . '/wp-json/wc-bookings/fetch',
			'username'         => $args['username'],
			'password'         => $args['password'],
			'product_id'       => $args['product_id'],
			'action'           => 'wc_bookings_booking_data',
			'text'             => array(
				'in_use'    => __( 'In-use', 'woocommerce-bookings-extensions' ),
				'available' => __( 'Available', 'woocommerce-bookings-extensions' ),
			),
		);

		wp_register_script( 'booking-view', plugin_dir_url( __DIR__ ) . 'public/js/booking-view' . $suffix . '.js', array( 'jquery', 'wc-bookings-moment' ), WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION, true );
		wp_localize_script( 'booking-view', 'booking_view_params', $script_params );

		wc_get_template(
			'booking-view.php',
			array(
				'bookings' => $bookings,
				'product'  => $product,
			),
			'woocommerce-bookings-extensions',
			plugin_dir_path( __DIR__ ) . 'templates' . DIRECTORY_SEPARATOR
		);

	}

	/**
	 * Usage: https://<server>/wp-json/wc-bookings/fetch?username=<username>&password=<password>
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_bookings_json() {
		$defaults = array(
			'username'   => '',
			'password'   => '',
			'range'      => 'now',
			'product_id' => null,
			'version'    => 1,
			'from'       => null,
			'to'         => null,
		);
		$args     = wp_parse_args( sanitize_post( $_REQUEST ), $defaults ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $args['product_id'] ) ) {
			$args['product_id'] = intval( $args['product_id'] );
		}
		$args['version'] = intval( $args['version'] );
		$user            = wp_authenticate( $args['username'], $args['password'] );
		if ( ! $user instanceof WP_User || ! user_can( $user, 'edit_others_posts' ) ) { // User doesn't exist or can't see bookings
			wp_die( 'Invalid user or does not have sufficient privileges' );
		}

		if ( 1 === $args['version'] ) {
			$bookings = $this->get_bookings_v1( $args['product_id'], $args['range'] );
			return $this->get_bookings_text_v1( $bookings );
		} elseif ( 2 === $args['version'] ) {
			$calendar = WC_Bookings_Extensions_New_Calendar::get_instance();
			$bookings = $calendar->get_bookings( $args['product_id'], $args['from'], $args['to'] );
			$product  = wc_get_product( $args['product_id'] );
			$bookings = array_map( array( 'WC_Bookings_Extensions_Lib', 'get_bookings_text_v2' ), $bookings );
			return array(
				'options'  => array(
					'server_unix_time'     => strtotime( 'now' ),
					'date_format'          => WC_Bookings_Extensions_Lib::convert_to_moment_format( get_option( 'date_format' ) ),
					'time_format'          => WC_Bookings_Extensions_Lib::convert_to_moment_format( get_option( 'time_format' ) ),
					'default_product_name' => $product->get_name(),
				),
				'bookings' => $bookings,
			);
		}
	}

	private function get_bookings_text_v1( $bookings ) { // Remove complete function by 2019-07.
		$bookings_arr = array();
		foreach ( $bookings as $key => $bookings_for_product ) {
			foreach ( $bookings_for_product as $k => $booking ) {
				if ( ! key_exists( $key, $bookings_arr ) ) {
					$bookings_arr[ $key ] = array();
				}
				$customer = $booking->get_customer();
				$order    = $booking->get_order();
				$product  = $booking->get_product();
				/** @var WP_User $user */
				$display_name = '';
				$email        = '';
				$company_name = '';
				$organizer    = '';
				if ( property_exists( $customer, 'user_id' ) ) {
					$user = get_user_by( 'id', $customer->user_id );
					if ( is_a( $user, 'WP_User' ) ) {
						$display_name = $user->display_name;
						$email        = $user->user_email;
					} else {
						$display_name = str_replace( ' (Guest)', '', $customer->name );
						$email        = $customer->email;
					}
					if ( is_a( $order, 'WC_Order' ) ) {
						$company_name = $order->get_billing_company();
						if ( empty( $company_name ) ) {
							$organizer = $display_name;
						} else {
							$organizer = $company_name;
						}
					} else {
						$organizer = __( 'Private function', 'woocommerce-bookings-extensions' );
					}
				}
				$bookings_arr[ $key ][ $k ] = array(
					'product_id'      => $booking->get_product_id(),
					'product_name'    => $product->get_name(),
					'unix_start_time' => $booking->get_start(),
					'unix_end_time'   => $booking->get_end(),
					'display_name'    => $display_name,
					'email'           => $email,
					'order_id'        => $booking->get_order_id(),
					'status'          => $booking->get_status(), // unpaid|complete|in-cart.
					'company_name'    => $company_name,
					'organizer'       => $organizer,
				);
			}
		}
		return $bookings_arr;
	}

	/**
	 * Add routes used for event displays.
	 */
	public function add_routes() {
		if ( ! class_exists( 'WP_Route' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'includes' . DIRECTORY_SEPARATOR . 'class-wp-route.php';
		}

		WP_Route::get( 'wc-bookings/fetch', array( $this, 'get_bookings_page' ) );
	}

	/**
	 * Add routes used for event display data.
	 */
	public function add_rest_routes() {
		register_rest_route(
			'wc-bookings',
			'/fetch',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_bookings_json' ),
			)
		);
	}

	/**
	 * Overrides the booking cost for scenarios where there is a different block cost for specific days
	 *
	 * @param int             $booking_cost Booking cost in predefined currency.
	 * @param WC_Booking_Form $booking_form Instance of WC_Booking_Form.
	 * @param array           $posted       Form data.
	 *
	 * @return int
	 */
	public function override_booking_cost( $booking_cost, $booking_form, $posted ) {
		// Get posted data.
		$data = $booking_form->get_posted_data( $posted );

		// Get costs.
		$costs   = $booking_form->product->get_costs();
		$pricing = $booking_form->product->get_pricing();

		$index = 1;
		foreach ( $pricing as $pricing_rule ) {
			if ( true === $pricing_rule['ext_override'] ) {
				$costs[ $index ]['ext_override'] = true;
			}
			$index++;
		}

		$block_duration  = $booking_form->product->get_duration();
		$blocks_booked   = isset( $data['_duration'] ) ? absint( $data['_duration'] ) : $block_duration;
		$block_unit      = $booking_form->product->get_duration_unit();
		$block_timestamp = $data['_start_date'];

		$block_cost       = 0;
		$base_cost        = 0;
		$total_block_cost = 0;
		$has_override     = false;

		for ( $block = 0; $block < $blocks_booked; $block ++ ) {
			$block_start_time_offset = $block * $block_duration;
			$block_start_time        = $booking_form->get_formatted_times( strtotime( "+{$block_start_time_offset} {$block_unit}", $block_timestamp ) );
			foreach ( $costs as $rule ) {
				$type  = $rule[0];
				$rules = $rule[1];
				if ( isset( $rule['ext_override'] ) && true === $rule['ext_override'] && 'days' === $type ) {
					$check_date    = $block_start_time['timestamp'];
					$checking_date = $booking_form->get_formatted_times( $check_date );
					$date_key      = 'days' === $type ? 'day_of_week' : substr( $type, 0, -1 );
					$rule          = $rules[ $checking_date[ $date_key ] ];
					if ( is_array( $rule ) ) {
						$has_override = true;
						$block_cost   = self::apply_cost( $block_cost, $rule['block'][0], $rule['block'][1] );
						$base_cost    = self::apply_cost( $base_cost, $rule['base'][0], $rule['base'][1] );
					}
				}
			}
			$total_block_cost += $block_cost;
		}

		if ( $has_override ) {
			$booking_cost = max( 0, $total_block_cost + $base_cost );

			if ( ! empty( $data['_persons'] ) ) {
				if ( $booking_form->product->get_has_person_cost_multiplier() ) {
					$booking_cost = $booking_cost * array_sum( $data['_persons'] );
				}
			}
		}

		return $booking_cost;
	}

	/**
	 * Scripts for booking form.
	 */
	public function add_booking_form_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'wc-booking-extensions-form-js', plugin_dir_url( __DIR__ ) . 'public/js/single-product' . $suffix . '.js', array( 'jquery' ), WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION, true );
	}

	/**
	 * Apply a cost
	 *
	 * @param  float  $base       Base cost for time slot.
	 * @param  string $multiplier Calculation action to perform [times|divide|minus|equals].
	 * @param  float  $cost       New cost for calculation.
	 * @return float
	 */
	private static function apply_cost( $base, $multiplier, $cost ) {
		$base = floatval( $base );
		$cost = floatval( $cost );

		switch ( $multiplier ) {
			case 'times':
				$new_cost = $base * $cost;
				break;
			case 'divide':
				$new_cost = $base / $cost;
				break;
			case 'minus':
				$new_cost = $base - $cost;
				break;
			case 'equals':
				$new_cost = $cost;
				break;
			default:
				$new_cost = $base + $cost;
				break;
		}
		return $new_cost;
	}

}
