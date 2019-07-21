<?php
/**
 * New calendar class.
 *
 * @package Woocommerce_Bookings_Extensions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

		wp_register_script(
			'fullcalendar-admin-init',
			plugin_dir_url( __DIR__ ) . 'assets/js/fullcalendar-init.js',
			array(
				'fullcalendar-daygrid',
				'fullcalendar-timegrid',
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
				'events'              => array(
					'sourceUrl' => WC_Ajax::get_endpoint( 'wc_bookings_extensions_get_bookings' ),
					'targetUrl' => WC_Ajax::get_endpoint( 'wc_bookings_extensions_update_booking' ),
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

}