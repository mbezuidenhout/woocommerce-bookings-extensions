<?php
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
			plugin_dir_url( __DIR__ ) . 'assets/fullcalendar-scheduler-4.2.0/packages/core/main.css',
			);
		wp_register_style(
			'fullcalendar-daygrid',
			plugin_dir_url( __DIR__ ) . 'assets/fullcalendar-scheduler-4.2.0/packages/daygrid/main.css',
			);
		wp_register_style(
			'fullcalendar-timegrid',
			plugin_dir_url( __DIR__ ) . 'assets/fullcalendar-scheduler-4.2.0/packages/timegrid/main.css',
			);

		wp_register_script(
			'fullcalendar-core',
			plugin_dir_url( __DIR__ ) . 'assets/fullcalendar-scheduler-4.2.0/packages/core/main.js',
			array(),
			false,
			true
		);
		wp_register_script(
			'fullcalendar-interaction',
			plugin_dir_url( __DIR__ ) . 'assets/fullcalendar-scheduler-4.2.0/packages/interaction/main.js',
			array( 'fullcalendar-core' ),
			false,
			true
		);
		wp_register_script(
			'fullcalendar-daygrid',
			plugin_dir_url( __DIR__ ) . 'assets/fullcalendar-scheduler-4.2.0/packages/daygrid/main.js',
			array( 'fullcalendar-core' ),
			false,
			true
		);
		wp_register_script(
			'fullcalendar-timegrid',
			plugin_dir_url( __DIR__ ) . 'assets/fullcalendar-scheduler-4.2.0/packages/timegrid/main.js',
			array( 'fullcalendar-core' ),
			false,
			true
		);
		wp_register_script(
			'fullcalendar-resource-common',
			plugin_dir_url( __DIR__ ) . 'assets/fullcalendar-scheduler-4.2.0/packages/resource-common/main.js',
			array(),
			false,
			true
		);
		wp_register_script(
			'fullcalendar-resource-daygrid',
			plugin_dir_url( __DIR__ ) . 'assets/fullcalendar-scheduler-4.2.0/packages/resource-daygrid/main.js',
			array( 'fullcalendar-resource-common' ),
			false,
			true
		);
		wp_register_script(
			'fullcalendar-resource-timegrid',
			plugin_dir_url( __DIR__ ) . 'assets/fullcalendar-scheduler-4.2.0/packages/resource-timegrid/main.js',
			array( 'fullcalendar-resource-common' ),
			false,
			true
		);

		wp_enqueue_style( 'fullcalendar-core' );
		wp_enqueue_style( 'fullcalendar-daygrid' );
		wp_enqueue_style( 'fullcalendar-timegrid' );

		wp_register_script(
			'fullcalendar-init',
			plugin_dir_url( __DIR__ ) . 'assets/js/fullcalendar-init.js',
			array(
				'fullcalendar-daygrid',
				'fullcalendar-timegrid',
				'fullcalendar-interaction',
				'fullcalendar-resource-daygrid',
				'fullcalendar-resource-timegrid',
			),
			false,
			true
		);

		wp_enqueue_script( 'fullcalendar-init' );

		wp_localize_script(
			'fullcalendar-init',
			'fullcalendarOptions',
			array(
				'resources'           => $this->get_resources(),
				'schedulerLicenseKey' => 'GPL-My-Project-Is-Open-Source',
				'defaultDate'         => date( 'Y-m-d' ),
				'defaultView'         => 'resourceTimeGridDay',
				'events'              => array(
					'sourceUrl' => WC_Ajax::get_endpoint( 'wc_bookings_extensions_get_bookings' ),
					'nonce'     => wp_create_nonce( 'fullcalendar_options' ),
				),
			)
		);

	}

	/**
	 * Get the list of bookable products.
	 */
	protected function get_resources() {
		$resources = array();
		try {
			/** @var WC_Product_Booking_Data_Store_CPT $data_store Bookable producs. */
			$data_store = WC_Data_Store::load( 'product-booking' );
			/** @var WC_Product_Booking[] $products */
			$products = $data_store->get_products( array( 'status' => array( 'publish', 'private' ), 'limit' => 30 ) );
			foreach ( $products as $product ) {
				$resources[] = array( 'id' => $product->get_id(), 'title' => $product->get_name() );
			}
		} catch ( Exception $e ) {
			return array();
		}

		return $resources;
	}

	/**
	 * Add meta boxes to calendar
	 */
	public static function add_meta_boxes() {
		add_meta_box( 'resources', __( 'Resources', 'woocommerce-booking-extensions' ), 'WC_Bookings_Extensions_New_Calendar::output_resource_options', 'admin_page_new_calendar' );
	}

	public static function output_resource_options() {
		echo "HALLO";
	}

	/**
	 * Output the calendar view.
	 */
	public function output() {
		wc_get_template(
			'calendar.php',
			array(),
			'woocommerce-bookings-extensions',
			plugin_dir_path( __DIR__ ) . 'templates/'
		);
	}

}