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

	/**
	 * An array of element ids for the shortcode.
	 *
	 * @var array
	 */
	protected $calendars;

	const HOLIDAYS_CACHE_TIME = 604800;

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

	public static function register_styles() {
		wp_register_style(
			'fullcalendar-core',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/core/main.css',
			null,
			'4.4.2'
		);
		wp_register_style(
			'fullcalendar-daygrid',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/daygrid/main.css',
			null,
			'4.4.2'
		);
		wp_register_style(
			'fullcalendar-timegrid',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/timegrid/main.css',
			null,
			'4.4.2'
		);
		wp_register_style(
			'fullcalendar-list',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/list/main.css',
			null,
			'4.4.2'
		);
	}

	public static function register_scripts() {
		wp_register_script(
			'fullcalendar-core',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/core/main.js',
			null,
			'4.4.2',
			true
		);
		wp_register_script(
			'fullcalendar-interaction',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/interaction/main.js',
			array( 'fullcalendar-core' ),
			'4.4.2',
			true
		);
		wp_register_script(
			'fullcalendar-daygrid',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/daygrid/main.js',
			array( 'fullcalendar-core' ),
			'4.4.2',
			true
		);
		wp_register_script(
			'fullcalendar-timegrid',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/timegrid/main.js',
			array( 'fullcalendar-core' ),
			'4.4.2',
			true
		);
		wp_register_script(
			'fullcalendar-list',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages/list/main.js',
			array( 'fullcalendar-core' ),
			'4.4.2',
			true
		);
		wp_register_script(
			'fullcalendar-resource-common',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages-premium/resource-common/main.js',
			null,
			'4.4.2',
			true
		);
		wp_register_script(
			'fullcalendar-resource-daygrid',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages-premium/resource-daygrid/main.js',
			array( 'fullcalendar-resource-common' ),
			'4.4.2',
			true
		);
		wp_register_script(
			'fullcalendar-resource-timegrid',
			plugin_dir_url( __DIR__ ) . 'vendor/fullcalendar/fullcalendar-scheduler/packages-premium/resource-timegrid/main.js',
			array( 'fullcalendar-resource-common' ),
			'4.4.2',
			true
		);
	}

	public static function register_admin_scripts() {
		wp_register_script(
			'fullcalendar-admin-init',
			plugin_dir_url( __DIR__ ) . 'admin/js/fullcalendar-init.js',
			array(
				'jquery',
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
	}

	public static function register_public_scripts() {
		wp_register_script(
			'fullcalendar-user-init',
			plugin_dir_url( __DIR__ ) . 'public/js/fullcalendar-user-init.js',
			array(
				'jquery',
				'fullcalendar-daygrid',
				'fullcalendar-timegrid',
				'fullcalendar-list',
				'fullcalendar-resource-daygrid',
				'fullcalendar-resource-timegrid',
			),
			WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION,
			true
		);
	}

	public static function enqueue_styles() {
		self::register_styles();
		wp_enqueue_style( 'fullcalendar-core' );
		wp_enqueue_style( 'fullcalendar-daygrid' );
		wp_enqueue_style( 'fullcalendar-timegrid' );
		wp_enqueue_style( 'fullcalendar-list' );
	}

	public static function enqueue_scripts() {
		self::register_scripts();
		self::register_public_scripts();
		self::register_admin_scripts();
		wp_enqueue_script( 'jquery-blockui' );
	}

	/**
	 * WC_Bookings_Extensions_New_Calendar constructor.
	 */
	public function __construct() {
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
			$product_ids = $data_store->get_bookable_product_ids();

			$product_categories = array_map(
				function ( $a ) {
					if ( in_array( $a->name, array( 'Uncategorized', __( 'Uncategorized' ) ), true ) ) { // Removed the WordPress built-in category Uncategorized.
						return null;
					} else {
						return $a->term_id;
					}
				},
				get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => false,
					)
				)
			);
			foreach ( $product_ids as $product_id ) {
				$categories = array();
				$product    = wc_get_product( $product_id );
				foreach ( $product->get_category_ids() as $category ) {
					if ( in_array( $category, $product_categories, true ) ) {
						$categories[] = $category;
					}
				}
				$resources[] = array(
					'id'         => $product->get_id(),
					'title'      => $product->get_name(),
					'categories' => $categories,
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
		global $woocommerce;

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
				'updateEventTitle'    => __( 'Update event', 'woo-booking-extensions' ),
				'loggedInUserId'      => wp_get_current_user()->ID,
				'events'              => array(
					'sourceUrl'    => WC_Ajax::get_endpoint( 'wc_bookings_extensions_get_bookings' ),
					'wctargetUrl'  => WC_Ajax::get_endpoint( 'wc_bookings_extensions_update_booking' ),
					'wptargetUrl'  => admin_url( 'admin-ajax.php?action=wc_bookings_extensions_update_booking' ),
					'eventPageUrl' => admin_url( 'admin-ajax.php?action=wc_bookings_extensions_event_page' ),
					'nonce'        => wp_create_nonce( 'fullcalendar_options' ),
				),
			)
		);

		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		// WC Admin Style.
		if ( ! isset( $wp_styles->registered['woocommerce_admin'] ) ) {
			$wc = WooCommerce::instance();
			wp_register_style( 'woocommerce_admin', $woocommerce->plugin_url() . '/assets/css/admin.css', array(), $wc->version );
		}

		wp_enqueue_style( 'woocommerce_admin' );
		wp_enqueue_script( 'woocommerce-admin' );

		$screen = get_current_screen();
		$screen->show_screen_options();

		wc_get_template(
			'calendar.php',
			array(),
			'woocommerce-bookings-extensions',
			plugin_dir_path( __DIR__ ) . 'templates' . DIRECTORY_SEPARATOR
		);
	}

	/**
	 * Show pop-up windows with fill in form to create an event.
	 * Start and end time gets pre-populated from POST.
	 * Resource is pre-populated from POST.
	 */
	public function booking_page() {
		if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'fullcalendar_options' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

			if ( isset( $_REQUEST['id'] ) ) {
				$booking = new WC_Booking( sanitize_key( wp_unslash( $_REQUEST['id'] ) ) );
			} else {
				// Create a new booking with passed values.

				$timezone = new DateTimeZone( wc_timezone_string() );
				$interval = DateInterval::createFromDateString( $timezone->getOffset( new DateTime() ) . ' seconds' );

				$start = isset( $_REQUEST['start'] ) ? new DateTime( sanitize_text_field( wp_unslash( $_REQUEST['start'] ) ), $timezone ) : null;
				$end   = isset( $_REQUEST['end'] ) ? new DateTime( sanitize_text_field( wp_unslash( $_REQUEST['end'] ) ), $timezone ) : null;

				$product = isset( $_REQUEST['resource'] ) && ! empty( $_REQUEST['resource'] ) ? sanitize_key( wp_unslash( $_REQUEST['resource'] ) ) : null;
				$all_day = isset( $_REQUEST['allDay'] ) && 'true' === $_REQUEST['allDay'] ? true : false;
				if ( $all_day ) {
					$end->sub( new DateInterval( 'PT1S' ) ); // Shift the time back with 1 second for full day events.
				}

				if ( ! empty( $start ) ) {
					$start->add( $interval );
				}
				if ( ! empty( $end ) ) {
					$end->add( $interval );
				}

				if ( ! is_null( $product ) ) {
					$existing_booking = $this->get_bookings( $product, $start->getTimestamp(), $end->getTimestamp() );
				}
				if ( ! empty( $existing_booking ) ) {
					// Bookings exist in selected time.
					include plugin_dir_path( __DIR__ ) . 'admin/partials/event-time-notavailable.php';
					wp_die();
				}

				$booking = new WC_Booking();
				if ( ! empty( $start ) ) {
					$booking->set_start( $start->getTimestamp() );
				}
				if ( ! empty( $end ) ) {
					$booking->set_end( $end->getTimestamp() );
				}
				if ( ! empty( $product ) ) {
					$booking->set_product_id( $product );
				}
				$booking->set_all_day( $all_day );
			}

			$booking = apply_filters( 'woo_booking_extensions_calendar_booking', $booking );

			// Get published and private bookable products.
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
				'product_id'    => false,
				'default_view'  => 'dayGridMonth',
				'class'         => '',
				'header_left'   => 'prev,next today',
				'header_center' => 'title',
				'header_right'  => 'dayGridMonth,timeGridWeek',
			),
			$atts,
			'wcbooking_calendar'
		);

		$user = wp_get_current_user();

		if ( $user->has_cap( 'manage_options' ) ) {
			$resources = $this->get_resources();
		} else {
			$atts['header_left']   = str_replace( 'resourceTimeGridDay', 'timeGridDay', $atts['header_left'] );
			$atts['header_center'] = str_replace( 'resourceTimeGridDay', 'timeGridDay', $atts['header_center'] );
			$atts['header_right']  = str_replace( 'resourceTimeGridDay', 'timeGridDay', $atts['header_right'] );
			$resources             = '';
		}

		$product = wc_get_product( $atts['product_id'] ? intval( $atts['product_id'] ) : false );

		$product_id = '';
		if ( ! empty( $product ) ) {
			$product_id = $product->get_id();
		}

		$element_id        = 'wbe-calendar-' . $product_id;
		$this->calendars[] = array(
			'elementId'    => $element_id,
			'productId'    => $product_id,
			'headerLeft'   => $atts['header_left'],
			'headerCenter' => $atts['header_center'],
			'headerRight'  => $atts['header_right'],
			'defaultView'  => $atts['default_view'],
			'resources'    => $resources,
		);

		wp_enqueue_script( 'fullcalendar-user-init' );

		wp_localize_script(
			'fullcalendar-user-init',
			'fullcalendarOptions',
			array(
				'schedulerLicenseKey' => get_option( 'woocommerce_bookings_extensions_fullcalendar_license', '' ),
				'defaultDate'         => date( 'Y-m-d' ),
				'calendars'           => $this->calendars,
				'events'              => array(
					'sourceUrl' => WC_Ajax::get_endpoint( 'wc_bookings_extensions_get_bookings' ),
					'nonce'     => wp_create_nonce( 'fullcalendar_options' ),
				),
			)
		);

		if ( empty( $atts['class'] ) ) {
			$atts['class'] = 'wbe-calendar';
		} else {
			$atts['class'] = 'wbe-calendar ' . $atts['class'];
		}

		return wc_get_template_html(
			'fullcalendar.php',
			[
				'element_id' => $element_id,
				'class'      => $atts['class'],
			],
			'woocommerce-bookings-extensions',
			plugin_dir_path( __DIR__ ) . 'templates/'
		);
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
			$booking  = new WC_Booking( sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			if ( isset( $_REQUEST['order_id'] ) && $booking->get_order_id() !== $_REQUEST['order_id'] ) {
				$booking->set_order_id( sanitize_text_field( wp_unslash( $_REQUEST['order_id'] ) ) );
			}
			if ( isset( $_REQUEST['customer_id'] ) && $booking->get_customer_id() !== $_REQUEST['customer_id'] ) {
				$booking->set_customer_id( sanitize_text_field( wp_unslash( $_REQUEST['customer_id'] ) ) );
			}
			if ( 'true' === $_REQUEST['allDay'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
				$booking->set_all_day( true );
			} else {
				$booking->set_all_day( false );
			}
			if ( ! empty( $_REQUEST['start'] ) ) {
				$start = new DateTime( sanitize_text_field( wp_unslash( $_REQUEST['start'] ) ) );
				$booking->set_start( (int) $start->getTimestamp() + $offset );
			}
			if ( ! empty( $_REQUEST['end'] ) ) {
				$end = new DateTime( sanitize_text_field( wp_unslash( $_REQUEST['end'] ) ) );
				$booking->set_end( (int) $end->getTimestamp() + $offset );
			}
			if ( isset( $_REQUEST['resource'] ) && $booking->get_product_id() !== $_REQUEST['resource'] ) {
				$booking->set_product_id( (int) $_REQUEST['resource'] );
			}
			if ( isset( $_REQUEST['persons'] ) ) {
				$booking->set_person_counts( sanitize_text_field( wp_unslash( $_REQUEST['persons'] ) ) );
			}
			if ( isset( $_REQUEST['booking_status'] ) && $booking->get_status() !== $_REQUEST['booking_status'] ) {
				$booking->set_status( sanitize_text_field( wp_unslash( $_REQUEST['booking_status'] ) ) );
			}

			// If time or resource has changed then check proposed changes first.
			// Get existing bookings on new proposed date.
			$existing_bookings = $this->get_bookings( $booking->get_product(), $booking->get_start(), $booking->get_end() );

			// If a booking exists and the id is not ours then fail.
			if ( is_array( $existing_bookings ) && ( count( $existing_bookings ) > 1 || ( $existing_bookings[0] instanceof WC_Booking && $existing_bookings[0]->get_id() !== $booking->get_id() ) ) ) {
				http_response_code( 409 );
				echo wp_json_encode(
					array(
						'status' => 409,
						'error'  => 'Conflict',
					)
				);
			} else {
				do_action( 'woo_booking_extensions_before_save', $booking );

				if ( ! empty( $booking->get_changes() ) ) {
					$booking_id = $booking->save();
				} else {
					$booking_id = $booking->get_id();
				}

				if ( isset( $_REQUEST['guest_name'] ) ) {
					update_post_meta( $booking_id, 'booking_guest_name', sanitize_text_field( wp_unslash( $_REQUEST['guest_name'] ) ) );

					do_action( 'woo_booking_extensions_before_save_meta', $booking_id );

					$booking->save_meta_data();
				}

				echo wp_json_encode( array( 'status' => 200 ) );
			}
		} catch ( Exception $e ) {
			http_response_code( 400 );
			echo wp_json_encode(
				array(
					'status' => 400,
					'error'  => 'Bad Request',
				)
			);
		}
		if ( ! isset( $_REQUEST['wc-ajax'] ) ) {
			// Die here to give proper json return.
			wp_die();
		}
	}

	/**
	 * Get a list of bookings for FullCalendar.
	 *
	 * @return bool|false|string
	 * @throws Exception Throws exception on incorrect date format.
	 */
	public function get_bookings_ajax() {
		if ( false === check_ajax_referer( 'fullcalendar_options' ) ) {
			return false;
		}
		$product_id = null;
		if ( isset( $_REQUEST['product_id'] ) && ! empty( $_REQUEST['product_id'] ) ) {
			$product_id = intval( $_REQUEST['product_id'] );
		} elseif ( ! wp_get_current_user()->has_cap( 'edit_wc_bookings' ) ) {
			http_response_code( 401 );
			return wp_json_encode( array( array() ) );
		}
		try {
			$from = new DateTime( wp_unslash( $_REQUEST['start'] ) );
			$to   = new DateTime( wp_unslash( $_REQUEST['end'] ) );
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

			if ( $booking->is_all_day() ) {
				$end->add( new DateInterval( 'PT1M' ) );
			}

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
				if ( wp_get_current_user()->has_cap( 'edit_wc_bookings' ) ) {
					$customer           = $booking->get_customer();
					$guest_name         = $booking->get_meta( 'booking_guest_name' );
					$persons            = $booking->get_persons();
					$background_color   = '#3788d8';
					$border_color       = '#3788d8';
					$categories         = $booking->get_product()->get_category_ids();
					$product_categories = array_map(
						function ( $a ) {
							return $a->term_id;
						},
						get_terms(
							array(
								'taxonomy'   => 'product_cat',
								'hide_empty' => false,
							)
						)
					);
					$created_user       = get_userdata( $booking->get_meta( '_booking_created_user_id' ) );
					$color              = get_user_meta( $created_user->ID, 'wbe_calendar_color', true );
					$categories         = array_intersect( $categories, $product_categories );
					$event              = array(
						'id'                 => $booking->get_id(),
						'resourceId'         => $booking->get_product_id(),
						'resourceCategories' => $categories,
						'start'              => $start->format( 'c' ),
						'end'                => $end->format( 'c' ),
						'title'              => $booking->get_product()->get_name(),
						'url'                => admin_url( 'post.php?post=' . $booking->get_id() . '&action=edit' ),
						'allDay'             => $booking->is_all_day() ? true : false,
						'backgroundColor'    => empty( $color ) ? $background_color : $color,
						'borderColor'        => empty( $color ) ? $border_color : $color,
						'createdById'        => false === $created_user ? 0 : $created_user->ID,
						'createdBy'          => false === $created_user ? 'Guest' : ( empty( $created_user->display_name ) ? $created_user->user_email : $created_user->display_name ),
						'status'             => $booking->get_status(),
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
						'id'              => $booking->get_id(),
						'resourceId'      => hash( 'md4', $booking->get_product_id() ),
						'start'           => $start->format( 'c' ),
						'end'             => $end->format( 'c' ),
						'title'           => '',
						'allDay'          => $booking->is_all_day() ? true : false,
						'backgroundColor' => '#e60016',
						'borderColor'     => '#e60016',
						'description'     => __( 'Time not available.', 'woo-booking-extensions' ),
					);
				}

				$events[] = apply_filters( 'wbe_event_data', $event, false );
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
		return false;
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
	 * Get an array of bookings ordered by booking start date.
	 * Also returns bookings from linked bookable products like combined rooms.
	 *
	 * @param int|WC_Product $product WooCommerce product ID.
	 * @param int            $from    Unix from time.
	 * @param int            $to      Unix to time.
	 * @return \WC_Booking[]
	 * @throws Exception
	 */
	public function get_bookings( $product, $from, $to ) {
		$products = [];
		if ( is_null( $product ) ) {
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
			if ( ! $product instanceof WC_Product ) {
				$product = wc_get_product( $product );
			}
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

		$bookings = [];

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
				if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
					wp_upload_bits( 'holidays.ics', null, wp_remote_retrieve_body( $response ), '2019/07' );
				}
			}
		}

		if ( file_exists( $cache_file ) ) {
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
		} else {
			return array();
		}

	}

	/**
	 * Output for the calendar shortcode.
	 *
	 * @param array $atts List of shortcode attributes.
	 *
	 * @return false|string
	 */
	public function get_overview_output( $atts ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$cal_id = wp_rand( 0, 1000 );

		$products    = array();
		$bookings    = array();
		$product_ids = explode( ',', $atts['product_ids'] );
		foreach ( $product_ids as $pos => $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product instanceof WC_Product_Booking ) {
				$products[] = $product;
			} else {
				unset( $product_ids[ $pos ] );
			}
		}

		wp_register_script(
			'calendar-overview',
			plugin_dir_url( __DIR__ ) . 'public/js/booking-overview' . $suffix . '.js',
			array(
				'jquery',
				'wc-bookings-moment',
			),
			WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION,
			true
		);
		wp_enqueue_script( 'calendar-overview' );
		wp_localize_script(
			'calendar-overview',
			'calendarOverview',
			array(
				'products'   => $product_ids,
				'calendarId' => $cal_id,
				'nonce'      => wp_create_nonce( 'fullcalendar_options' ),
				'url'        => WC_Ajax::get_endpoint( 'wc_bookings_extensions_get_bookings' ),
			)
		);

		ob_start();

		wc_get_template(
			'calendaroverview.php',
			array(
				'products'    => $products,
				'bookings'    => $bookings,
				'calendar_id' => $cal_id,
				'class'       => $atts['class'],
			),
			'woocommerce-bookings-extensions',
			plugin_dir_path( __DIR__ ) . 'templates/'
		);

		return ob_get_clean();
	}

}