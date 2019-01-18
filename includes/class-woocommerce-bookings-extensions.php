<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    Woocommerce_Bookings_Extensions
 * @subpackage Woocommerce_Bookings_Extensions/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woocommerce_Bookings_Extensions
 * @subpackage Woocommerce_Bookings_Extensions/includes
 * @author     Marius Bezuidenhout <marius.bezuidenhout@gmail.com>
 */
class Woocommerce_Bookings_Extensions {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Woocommerce_Bookings_Extensions_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * This plugin's uri
	 *
	 * @since   1.1.0
	 * @access  protected
	 * @var     string  $uri    The uri of this plugin.
	 */
	protected $uri;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION' ) ) {
			$this->version = WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'woocommerce-bookings-extensions';

		$this->uri = plugin_dir_url( dirname( __FILE__  ) );

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Woocommerce_Bookings_Extensions_Loader. Orchestrates the hooks of the plugin.
	 * - Woocommerce_Bookings_Extensions_i18n. Defines internationalization functionality.
	 * - Woocommerce_Bookings_Extensions_Admin. Defines all hooks for the admin area.
	 * - Woocommerce_Bookings_Extensions_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-bookings-extensions-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-bookings-extensions-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocommerce-bookings-extensions-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woocommerce-bookings-extensions-public.php';

		/**
		 * The class responsible for display the global search form that occur by using the
		 * shortcode
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-bookings-extensions-search.php';

		$this->loader = new Woocommerce_Bookings_Extensions_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Woocommerce_Bookings_Extensions_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Woocommerce_Bookings_Extensions_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Woocommerce_Bookings_Extensions_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		/** @see Woocommerce_Bookings_Extensions_Admin::booking_extensions_data */
		$this->loader->add_action( 'woocommerce_product_options_general_product_data', $plugin_admin, 'booking_extensions_data' );

		// Saving data.
		/** @see Woocommerce_Bookings_Extensions_Admin::add_extra_props */
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'add_extra_props');

		/** @see Woocommerce_Bookings_Extensions_Admin::show_booking_dependencies_options */
		$this->loader->add_action( 'woocommerce_product_options_related', $plugin_admin, 'show_booking_dependencies_options' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Woocommerce_Bookings_Extensions_Public( $this->get_plugin_name(), $this->get_version(), $this->uri );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action( 'plugins_loaded', $this, 'load_extensions', 11 );
		// Replace WooCommerce Bookings ajax handlers
		//$this->loader->remove_action( 'wp_ajax_wc_bookings_get_blocks', 'WC_Bookings_Ajax', 'get_time_blocks_for_date', 10 );
		$this->loader->add_action( 'wp_ajax_wc_bookings_get_blocks', $plugin_public, 'get_time_blocks_for_date', 9 );
		//$this->loader->remove_action( 'wp_ajax_nopriv_wc_bookings_get_blocks', 'WC_Bookings_Ajax', 'get_time_blocks_for_date', 10 );
		$this->loader->add_action( 'wp_ajax_nopriv_wc_bookings_get_blocks', $plugin_public, 'get_time_blocks_for_date', 9 );

		/** @see Woocommerce_Bookings_Extensions_Public::calculate_costs */
		$this->loader->add_action( 'wp_ajax_wc_bookings_calculate_costs', $plugin_public, 'calculate_costs', 9 );
		$this->loader->add_action( 'wp_ajax_nopriv_wc_bookings_calculate_costs', $plugin_public, 'calculate_costs', 9 );

		/** @see Woocommerce_Bookings_Extensions_Public::search_booking_products */
		$this->loader->add_action( 'wp_ajax_wc_booking_extensions_search', $plugin_public, 'search_booking_products' );
		$this->loader->add_action( 'wp_ajax_nopriv_wc_booking_extensions_search', $plugin_public, 'search_booking_products' );

		/** @see Woocommerce_Bookings_Extensions_Public::search_result */
		$this->loader->add_action( 'wp_ajax_wc_bookings_extensions_search_result', $plugin_public, 'search_result' );
		$this->loader->add_action( 'wp_ajax_nopriv_wc_bookings_extensions_search_result', $plugin_public, 'search_result' );

		if (! is_admin()) {
			/** @see Woocommerce_Bookings_Extensions_Public::global_search_shortcode() */
			$this->loader->add_shortcode( 'wcbooking_search', $plugin_public, 'global_search_shortcode' );
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Woocommerce_Bookings_Extensions_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public function remove_filter_by_class( $tag, $class_name, $function_name, $priority ) {
		global $wp_filter;

		if ( isset( $wp_filter[$tag] )) {
			/** @var WP_Hook $hook */
			$hook = &$wp_filter[$tag];
			$callbacks = &$hook->callbacks[$priority];
			foreach( $callbacks as $callback_id => $callback ) {
				if( is_array( $callback['function'] ) ) {

					if( is_a( $callback['function'][0], $class_name ) && $function_name == $callback['function'][1] ) {
						unset( $callbacks[$callback_id] );
					}
				}
			}
		}
	}

	public function load_extensions() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-bookings-extensions-product-booking.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-bookings-extensions-cart-manager.php';

		$plugin_public = new Woocommerce_Bookings_Extensions_Public( $this->get_plugin_name(), $this->get_version(), $this->uri );
		$cart_manager = new WC_Booking_Extensions_Cart_Manager();

		$this->remove_filter_by_class( 'woocommerce_add_to_cart_validation', 'WC_Booking_Cart_Manager', 'validate_add_cart_item', 10 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $plugin_public, 'validate_add_cart_item' ), 10, 3 );

		$this->remove_filter_by_class( 'woocommerce_add_cart_item_data', 'WC_Booking_Cart_Manager', 'add_cart_item_data', 10);
		add_filter( 'woocommerce_add_cart_item_data', array( $cart_manager, 'add_cart_item_data' ), 10, 3 );
	}
}
