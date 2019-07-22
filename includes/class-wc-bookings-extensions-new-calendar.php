<?php
/**
 * New calendar class.
 *
 * @package Woocommerce_Bookings_Extensions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ICal\ICal;

/**
 * WC_Bookings_Calendar.
 */
class WC_Bookings_Extensions_New_Calendar {

	/**
	 * Stores Bookings.
	 *
	 * @var WC_Booking[]
	 */
	protected $bookings;

	/**
	 *  Singleton instance of this class.
	 *
	 * @var WC_Bookings_Extensions_New_Calendar
	 */
	private static $instance;

	public const HOLIDAYS_CACHE_TIME = 604800;

	/**
	 * Get Instance creates a singleton class that's cached to stop duplicate instances
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * WC_Bookings_Extensions_New_Calendar constructor.
	 */
	public function __construct() {
		wp_register_style(
			'fullcalendar-core',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/core/main.css',
			null,
			'4.2.0'
		);
		wp_register_style(
			'fullcalendar-daygrid',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/daygrid/main.css',
			null,
			'4.2.0'
		);
		wp_register_style(
			'fullcalendar-timegrid',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/timegrid/main.css',
			null,
			'4.2.0'
		);
		wp_register_style(
			'fullcalendar-list',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/list/main.css',
			null,
			'4.2.0'
		);

		wp_register_script(
			'fullcalendar-core',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/core/main.js',
			null,
			'4.2.0',
			true
		);
		wp_register_script(
			'fullcalendar-interaction',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/interaction/main.js',
			array( 'fullcalendar-core' ),
			'4.2.0',
			true
		);
		wp_register_script(
			'fullcalendar-daygrid',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/daygrid/main.js',
			array( 'fullcalendar-core' ),
			'4.2.0',
			true
		);
		wp_register_script(
			'fullcalendar-timegrid',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/timegrid/main.js',
			array( 'fullcalendar-core' ),
			'4.2.0',
			true
		);
		wp_register_script(
			'fullcalendar-list',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/list/main.js',
			array( 'fullcalendar-core' ),
			'4.2.0',
			true
		);
		wp_register_script(
			'fullcalendar-resource-common',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/resource-common/main.js',
			null,
			'4.2.0',
			true
		);
		wp_register_script(
			'fullcalendar-resource-daygrid',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/resource-daygrid/main.js',
			array( 'fullcalendar-resource-common' ),
			'4.2.0',
			true
		);
		wp_register_script(
			'fullcalendar-resource-timegrid',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/resource-timegrid/main.js',
			array( 'fullcalendar-resource-common' ),
			'4.2.0',
			true
		);

		wp_enqueue_style( 'fullcalendar-core' );
		wp_enqueue_style( 'fullcalendar-daygrid' );
		wp_enqueue_style( 'fullcalendar-timegrid' );
		wp_enqueue_style( 'fullcalendar-list' );

		wp_register_script(
			'fullcalendar-admin-init',
			plugin_dir_url( __DIR__ ) . 'assets/js/fullcalendar-init.js',
			array(
				'fullcalendar-daygrid',
				'fullcalendar-timegrid',
				'fullcalendar-list',
				'fullcalendar-interaction',
				'fullcalendar-resource-daygrid',
				'fullcalendar-resource-timegrid',
			),
			WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION,
			true
		);

		wp_register_script(
			'fullcalendar-user-init',
			plugin_dir_url( __DIR__ ) . 'assets/js/fullcalendar-user-init.js',
			array(
				'fullcalendar-daygrid',
				'fullcalendar-timegrid',
			),
			WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION,
			true
		);

		wp_enqueue_script( 'jquery-blockui' );
	}

	/**
	 * Get the list of bookable products.
	 */
	protected function get_resources() {
		$resources = array();
		try {
			/**
			 * Bookable products.
			 *
			 * @var WC_Product_Booking_Data_Store_CPT $data_store Instance of WC_Product_Booking_Data_Store_CPT.
			 */
			$data_store = WC_Data_Store::load( 'product-booking' );
			/**
			 * Array of WC_Product_Booking.
			 *
			 * @var WC_Product_Booking[] $products Array of WC_Product_Booking.
			 */
			$products = $data_store->get_products(
				array(
					'status' => array( 'publish', 'private' ),
					'limit'  => 30,
				)
			);
			foreach ( $products as $product ) {
				$resources[] = array(
					'id'    => $product->get_id(),
					'title' => $product->get_name(),
				);
			}
		} catch ( Exception $e ) {
			return array();
		}

		return $resources;
	}

	/**
	 * Output the calendar view.
	 */
	public function admin_output() {
		add_thickbox(); // Add the WordPress admin thickbox js and css.
		wp_enqueue_script( 'fullcalendar-admin-init' );

		wp_localize_script(
			'fullcalendar-admin-init',
			'fullcalendarOptions',
			array(
				'resources'           => $this->get_resources(),
				'schedulerLicenseKey' => get_option( 'woocommerce_bookings_extensions_fullcalendar_license', '' ),
				'defaultDate'         => date( 'Y-m-d' ),
				'defaultView'         => 'resourceTimeGridDay',
				'confirmMoveMessage'  => __( 'Are you sure you want to change this event?', 'woo-booking-extensions' ),
				'confirmAddMessage'   => __( 'Do you want to add an event here?', 'woo-booking-extensions' ),
				'createEventTitle'    => __( 'Create event', 'woo-booking-extensions' ),
				'events'              => array(
					'sourceUrl' => WC_Ajax::get_endpoint( 'wc_bookings_extensions_get_bookings' ),
					'targetUrl' => WC_Ajax::get_endpoint( 'wc_bookings_extensions_update_booking' ),
					'newUrl'    => admin_url( 'admin-ajax.php?action=wc_bookings_extensions_update_booking' ),
					'nonce'     => wp_create_nonce( 'fullcalendar_options' ),
				),
			)
		);

		$screen = get_current_screen();
		$screen->show_screen_options();

		wc_get_template(
			'calendar.php',
			array(),
			'woocommerce-bookings-extensions',
			plugin_dir_path( __DIR__ ) . 'templates/'
		);
	}

	/**
	 * Booking page
	 */
	public function booking_page() {
		if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'fullcalendar_options' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

			$timezone = new DateTimeZone( wc_timezone_string() );
			$interval = DateInterval::createFromDateString( $timezone->getOffset( new DateTime() ) . ' seconds' );

			$start   = isset( $_REQUEST['start'] ) ? new DateTime( sanitize_text_field( wp_unslash( $_REQUEST['start'] ), $timezone ) ) : null;
			$end     = isset( $_REQUEST['end'] ) ? new DateTime( sanitize_text_field( wp_unslash( $_REQUEST['end'] ) ), $timezone ) : null;
			$product = isset( $_REQUEST['resource'] ) ? wc_get_product( sanitize_key( wp_unslash( $_REQUEST['resource'] ) ) ) : null;
			$all_day = isset( $_REQUEST['allDay'] ) && 'true' === $_REQUEST['allDay'] ? 'yes' : 'no';
			if ( ! empty( $start ) ) {
				$start->add( $interval );
			}
			if ( ! empty( $end ) ) {
				$end->add( $interval );
			}
			add_filter(
				'get_booking_products_args',
				function ( $post_args ) {
					$post_args['post_status'] = array( 'publish', 'private' );
					return $post_args;
				}
			);

			include plugin_dir_path( __DIR__ ) . 'admin/partials/event.php';
		}
		wp_die(); // this is required to terminate immediately and return a proper response.
	}

	/**
	 * Get the template for the calendar shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function get_shortcode_output( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'product_id' => false,
			),
			$atts,
			'wcbooking_calendar'
		);

		$product = wc_get_product( $atts['product_id'] ? intval( $atts['product_id'] ) : false );

		$element_id = 'wbe-calendar-' . $product->get_id() . '-' . wp_rand( 1000, 9999 );

		wp_enqueue_script( 'fullcalendar-user-init' );

		wp_localize_script(
			'fullcalendar-user-init',
			'fullcalendarOptions',
			array(
				'schedulerLicenseKey' => get_option( 'woocommerce_bookings_extensions_fullcalendar_license', '' ),
				'defaultDate'         => date( 'Y-m-d' ),
				'elementId'           => $element_id,
				'events'              => array(
					'sourceUrl' => WC_Ajax::get_endpoint( 'wc_bookings_extensions_get_bookings' ),
					'nonce'     => wp_create_nonce( 'fullcalendar_options' ),
					'productId' => $product->get_id(),
				),
			)
		);

		ob_start();

		wc_get_template( 'fullcalendar.php', array( 'element_id' => $element_id ), 'woocommerce-bookings-extensions', plugin_dir_path( __DIR__ ) . 'templates/' );

		return ob_get_clean();
	}


	/**
	 * Update a booking from full calendar user interaction.
	 */
	public function update_booking_ajax() {
		if ( false === check_ajax_referer( 'fullcalendar_options' ) ) {
			http_response_code( 401 );
			echo wp_json_encode(
				array(
					'status' => 401,
					'error'  => 'Invalid nonce',
				)
			);
		}
		try {
			$timezone = new DateTimeZone( wc_timezone_string() );
			$offset   = $timezone->getOffset( new DateTime() );
			$booking  = new WC_Booking( $_REQUEST['id'] );
			if ( 'true' === $_REQUEST['allDay'] ) {
				$booking->set_all_day( true );
			} else {
				$booking->set_all_day( false );
			}
			if ( ! empty( $_REQUEST['start'] ) ) {
				$start = new DateTime( $_REQUEST['start'] );
				$booking->set_start( (int) $start->format( 'U' ) + $offset );
			}
			if ( ! empty( $_REQUEST['end'] ) ) {
				$end = new DateTime( $_REQUEST['end'] );
				$booking->set_end( (int) $end->format( 'U' ) + $offset );
			}
			if ( ! empty( $_REQUEST['resource'] ) ) {
				$booking->set_product_id( (int) $_REQUEST['resource'] );
			}
			$booking->save();
			echo wp_json_encode( array( 'status' => 200 ) );
		} catch ( Exception $e ) {
			http_response_code( 400 );
			echo wp_json_encode(
				array(
					'status' => 400,
					'error'  => 'Bad Request',
				)
			);
		}
	}

	/**
	 * Get a list of bookings for FullCalendar.
	 *
	 * @return bool|false|string
	 * @throws Exception
	 */
	public function get_bookings_ajax() {
		if ( false === check_ajax_referer( 'fullcalendar_options' ) ) {
			return false;
		}
		$product_id = null;
		if ( isset( $_REQUEST['product_id'] ) ) {
			$product_id = intval( $_REQUEST['product_id'] );
		} elseif ( ! wp_get_current_user()->has_cap( 'manage_bookings' ) ) {
			http_response_code( 401 );
			return wp_json_encode( array( array() ) );
		}
		try {
			$from = new DateTime( $_REQUEST['start'] );
			$to   = new DateTime( $_REQUEST['end'] );
		} catch ( Exception $e ) {
			$from = new DateTime();
			$to   = new DateTime();
			$from->modify( '-1 month' );
			$to->modify( '+1 month' );
		}

		try {
			$bookings = $this->get_bookings( $product_id, $from->getTimestamp(), $to->getTimestamp() );
		} catch ( Exception $e ) {
			$logger = new WC_Logger();
			$logger->add( 'getbookings', $e->getMessage() );
			$bookings = array();
		}

		$events = array();

		$timezone = new DateTimeZone( wc_timezone_string() );
		$offset   = $timezone->getOffset( new DateTime() );
		foreach ( $bookings as $booking ) {
			$start = DateTime::createFromFormat( 'U', $booking->get_start() - $offset, $timezone );
			$end   = DateTime::createFromFormat( 'U', $booking->get_end() - $offset, $timezone );

			if ( empty( $product_id ) ) {
				// Add background events to each dependent product.
				$dependent_product_ids = $booking->get_product()->get_meta( 'booking_dependencies' );
				foreach ( $dependent_product_ids as $id ) {
					$events[] = array(
						'resourceId' => $id,
						'start'      => $start->format( 'c' ),
						'end'        => $end->format( 'c' ),
						'rendering'  => 'background',
					);
				}
			}
			try {
				if ( wp_get_current_user()->has_cap( 'manage_bookings' ) ) {
					$customer   = $booking->get_customer();
					$guest_name = $booking->get_meta( 'booking_guest_name' );
					$persons    = $booking->get_persons();
					$event      = array(
						'id'         => $booking->get_id(),
						'resourceId' => $booking->get_product_id(),
						'start'      => $start->format( 'c' ),
						'end'        => $end->format( 'c' ),
						'title'      => $booking->get_product()->get_name(),
						'url'        => admin_url( 'post.php?post=' . $booking->get_id() . '&action=edit' ),
						'allDay'     => $booking->is_all_day() ? true : false,
					);
					if ( ! empty( $guest_name ) ) {
						$event['bookedFor'] = $guest_name;
					}
					if ( ! empty( $customer->name ) ) {
						$event['bookedBy'] = $customer->name;
					}
					if ( $persons > 0 ) {
						$event['persons'] = $persons;
					}
				} else {
					$event = array(
						'id'         => hash( 'md4', $booking->get_id() ),
						'resourceId' => hash( 'md4', $booking->get_product_id() ),
						'start'      => $start->format( 'c' ),
						'end'        => $end->format( 'c' ),
						'title'      => 'Booked on: ' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $booking->get_date_created() ),
					);
					$user  = wp_get_current_user();
					if ( $user->ID !== $booking->get_customer_id() ) {
						$event['backgroundColor'] = 'lightgray';
						$event['borderColor']     = 'silver';
						$event['title']           = '';
					}
				}

				$events[] = $event;
			} catch ( Exception $e ) {
				$logger = new WC_Logger();
				$logger->add( 'getbookings', $e->getMessage() );
			}
		}

		$ical = new ICal();
		foreach ( $this->get_external_events( $from, $to ) as $external_event ) {
			$start   = $ical->iCalDateToDateTime( $external_event->dtstart );
			$end     = $ical->iCalDateToDateTime( $external_event->dtend );
			$all_day = false;
			if ( '000000' === $start->format( 'His' ) && '000000' === $end->format( 'His' ) ) {
				$all_day = true;
			}
			$event    = array(
				'id'              => $external_event->uid,
				'allDay'          => $all_day,
				'start'           => $start->format( 'c' ),
				'end'             => $end->format( 'c' ),
				'title'           => $external_event->summary,
				'backgroundColor' => 'ivory',
				'borderColor'     => 'beige',
				'textColor'       => 'black',
				'isExternal'      => true,
			);
			$events[] = $event;
		}

		echo wp_json_encode( $events );
	}

	/**
	 * Check if cache has expired by looking at file modification time.
	 *
	 * @param string $file File to check.
	 * @param bool   $remove_file Remove file is exipired.
	 * @return bool
	 */
	public static function cache_file_expired( $file, $remove_file ) {
		$age = time() - filemtime( $file );
		if ( $age > self::HOLIDAYS_CACHE_TIME ) {
			if ( $remove_file ) {
				unlink( $file );
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get an array of bookings ordered by booking start date
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @param int $from       Unix from time.
	 * @param int $to         Unix to time.
	 * @return \WC_Booking[]
	 * @throws Exception
	 */
	public function get_bookings( $product_id, $from, $to ) {
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
			foreach ( $product->get_meta( 'booking_dependencies' ) as $dependency ) { // Get dependent bookable products.
				$product = wc_get_product( intval( $dependency ) );
				if ( $product && 'booking' === $product->get_type() ) {
					$products[] = $product;
				}
			}
		}

		$bookings = array();

		foreach ( $products as $product ) {
			$bookings = array_merge( $bookings, $product->get_bookings_in_date_range( $from, $to ) );
		}

		usort( $bookings, array( 'WC_Bookings_Extensions_Lib', 'bookings_sort_by_date' ) );

		return $bookings;
	}

	/**
	 * Get an array of holidays.
	 *
	 * @param DateTime $from From date to return.
	 * @param DateTime $to   To date to return.
	 * @return \ICal\Event[]
	 */
	protected function get_external_events( $from, $to ) {
		$holidays_ics = get_option( 'woocommerce_bookings_extensions_holidays', '' );

		$upload_dir = wp_get_upload_dir();
		$cache_file = $upload_dir['basedir'] . '/2019/07/holidays.ics';

		if ( ! empty( $holidays_ics ) ) {
			if ( ! file_exists( $cache_file ) || self::cache_file_expired( $cache_file, true ) ) {
				$response = wp_remote_get( $holidays_ics, array( 'sslverify' => false ) );
				wp_upload_bits( 'holidays.ics', null, wp_remote_retrieve_body( $response ), '2019/07' );
			}
		}

		try {
			$ical   = new ICal(
				$cache_file,
				array(
					'defaultTimeZone' => get_option( 'timezone_string' ),
				)
			);
			$events = $ical->eventsFromRange( $from->format( 'Y-m-d H:i:s' ), $to->format( 'Y-m-d H:i:s' ) );
		} catch ( \Exception $e ) {
			return array();
		}

		return $events;
	}

}