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
class WC_Bookings_Extensions {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WC_Bookings_Extensions_Loader $loader Maintains and registers all hooks for the plugin.
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

		$this->uri = plugin_dir_url( dirname( __FILE__ ) );

		$this->load_dependencies();
		$this->set_locale();
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
		}
		if ( ! is_plugin_active( 'woocommerce-bookings' . DIRECTORY_SEPARATOR . 'woocommerce-bookings.php' ) || ! is_plugin_active( 'woocommerce' . DIRECTORY_SEPARATOR . 'woocommerce.php' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_bookings_extensions_woocommerce_bookings_admin_notice' ) );
		} else {
			$this->define_admin_hooks();
			$this->define_public_hooks();
		}
	}

	/**
	 * Display an admin notice about required plugins.
	 */
	public function woocommerce_bookings_extensions_woocommerce_bookings_admin_notice() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'WooCommerce Bookings Extensions is enabled but not effective. It requires WooCommerce and WooCommerce Bookings in order to work.', 'woocommerce-bookings-extensions' ); ?></p>
		</div>
		<?php
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes' . DIRECTORY_SEPARATOR . 'class-wc-bookings-extensions-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes' . DIRECTORY_SEPARATOR . 'class-wc-bookings-extensions-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin' . DIRECTORY_SEPARATOR . 'class-wc-bookings-extensions-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public' . DIRECTORY_SEPARATOR . 'class-wc-bookings-extensions-public.php';

		/**
		 * The class responsible for display the global search form that occur by using the
		 * shortcode
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes' . DIRECTORY_SEPARATOR . 'class-wc-bookings-extensions-search.php';

		/**
		 * Class of static functions.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes' . DIRECTORY_SEPARATOR . 'class-wc-bookings-extensions-lib.php';

		/**
		 * Plugin shortcode functions
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public' . DIRECTORY_SEPARATOR . 'class-wc-bookings-extensions-shortcodes.php';

		/**
		 * Full calendar class
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes' . DIRECTORY_SEPARATOR . 'class-wc-bookings-extensions-new-calendar.php';

		/**
		 * Audit system class
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes' . DIRECTORY_SEPARATOR . 'class-wc-bookings-extensions-audits.php';

		/**
		 * Composer libraries
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

		$this->loader = new WC_Bookings_Extensions_Loader();

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

		$plugin_i18n = new WC_Bookings_Extensions_i18n();

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

		$plugin_admin = new WC_Bookings_Extensions_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		/** @see WC_Bookings_Extensions_Admin::booking_extensions_data */
		$this->loader->add_action( 'woocommerce_product_options_general_product_data', $plugin_admin, 'booking_extensions_data' );

		// Saving data.
		/** @see WC_Bookings_Extensions_Admin::add_extra_props */
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'add_extra_props' );

		/** @see WC_Bookings_Extensions_Admin::show_booking_dependencies_options */
		$this->loader->add_action( 'woocommerce_product_options_related', $plugin_admin, 'show_booking_dependencies_options' );

		/** @see WC_Bookings_Extensions_Admin::calendar_page_scripts */
		$this->loader->add_action( 'admin_print_scripts-wc_booking_page_booking_calendar', $plugin_admin, 'calendar_page_scripts' );

		$this->loader->add_action( 'woocommerce_bookings_after_bookings_pricing', $plugin_admin, 'enqueue_product_data_scripts' );
		$this->loader->add_action( 'woocommerce_admin_process_product_object', $plugin_admin, 'set_ext_props', 30 );

		$this->loader->add_action( 'woocommerce_admin_booking_data_after_booking_details', $plugin_admin, 'add_admin_booking_fields' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_meta_box', 10, 2 );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'change_calendar', 50 );
		$this->loader->add_filter( 'woocommerce_general_settings', $plugin_admin, 'add_admin_settings', 12 );

		$post_type = 'wc_booking';
		$this->loader->add_action( "add_meta_boxes_{$post_type}", $plugin_admin, 'add_meta_boxes' );
		$this->loader->add_action( 'wp_ajax_upload_booking_file', $plugin_admin, 'upload_booking_file_page' );
		$this->loader->add_action( 'wp_ajax_delete_booking_file', $plugin_admin, 'delete_booking_file' );
		$this->loader->add_filter( 'manage_edit-wc_booking_columns', $plugin_admin, 'edit_columns', 20 );
		$this->loader->add_action( 'manage_wc_booking_posts_custom_column', $plugin_admin, 'custom_columns', 20 );

		$this->loader->add_action( 'personal_options', $plugin_admin, 'calendar_colour' );

		$this->loader->add_action( 'personal_options_update', $plugin_admin, 'profile_update' );
		$this->loader->add_action( 'edit_user_profile_update', $plugin_admin, 'profile_update' );
    }

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public     = new WC_Bookings_Extensions_Public( $this->get_plugin_name(), $this->get_version(), $this->uri );
		$plugin_shortcodes = new WC_Bookings_Extensions_Shortcodes();
		$plugin_calendar   = WC_Bookings_Extensions_New_Calendar::get_instance();
		$plugin_audits     = WC_Bookings_Extensions_Audits::get_instance();

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action( 'plugins_loaded', $this, 'load_extensions', 11 );
		// Replace WooCommerce Bookings ajax handlers
		/** @see WC_Bookings_Extensions_Public::get_time_blocks_for_date */
		$this->loader->add_action( 'wp_ajax_wc_bookings_get_blocks', $plugin_public, 'get_time_blocks_for_date', 9 );
		$this->loader->add_action( 'wp_ajax_nopriv_wc_bookings_get_blocks', $plugin_public, 'get_time_blocks_for_date', 9 );
		/** @see WC_Bookings_Extensions_Public::get_end_time_html_ajax */
		$this->loader->add_action( 'wp_ajax_wc_bookings_get_end_time_html', $plugin_public, 'get_end_time_html_ajax', 9 );
		$this->loader->add_action( 'wp_ajax_nopriv_wc_bookings_get_end_time_html', $plugin_public, 'get_end_time_html_ajax', 9 );
		/** @see WC_Bookings_Extensions_Public::find_booked_day_blocks_ajax */
		$this->loader->add_action( 'wc_ajax_wc_bookings_find_booked_day_blocks', $plugin_public, 'find_booked_day_blocks_ajax', 9 );

		/** @see WC_Bookings_Extensions_Public::calculate_costs */
		add_action( 'wp_ajax_wc_bookings_calculate_costs', 'WC_Bookings_Extensions_Lib::calculate_costs', 9 );
		add_action( 'wp_ajax_nopriv_wc_bookings_calculate_costs', 'WC_Bookings_Extensions_Lib::calculate_costs', 9 );

		/** @see WC_Bookings_Extensions_Public::search_booking_products */
		$this->loader->add_action( 'wp_ajax_wc_bookings_extensions_search', $plugin_shortcodes, 'search_booking_products' );
		$this->loader->add_action( 'wp_ajax_nopriv_wc_bookings_extensions_search', $plugin_shortcodes, 'search_booking_products' );

		/** @see WC_Bookings_Extensions_Public::search_result */
		$this->loader->add_action( 'wp_ajax_wc_bookings_extensions_search_result', $plugin_public, 'search_result' );
		$this->loader->add_action( 'wp_ajax_nopriv_wc_bookings_extensions_search_result', $plugin_public, 'search_result' );

		/** @see WC_Bookings_Extensions_Public::get_bookings_v1 */
		//$this->loader->add_action( 'woocommerce_api_wc_bookings_fetch', $plugin_public, 'get_bookings' );

		/** @see WC_Bookings_Extensions_Public::add_routes */
		$this->loader->add_action( 'init', $plugin_public, 'add_routes' );
		/** @see WC_Bookings_Extensions_Public::add_rest_routes */
		$this->loader->add_action( 'rest_api_init', $plugin_public, 'add_rest_routes' );

		/** @see WC_Bookings_Extensions_Public::adjust_booking_cost */
		$this->loader->add_filter( 'booking_form_calculated_booking_cost', $plugin_public, 'override_booking_cost', 8, 3 );

		// Notice that the global search does not support multi level dependencies
		// Short codes should not be active in the admin panel.
		/** @see WC_Bookings_Extensions_Public::global_search_shortcode() */
		$this->loader->add_shortcode( 'wcbooking_search', $plugin_shortcodes, 'global_search_shortcode' );
		$this->loader->add_shortcode( 'wcbooking_calendar', $plugin_shortcodes, 'calendar_shortcode' );
		$this->loader->add_shortcode( 'wcbooking_overview', $plugin_shortcodes, 'overview_shortcode' );

		$this->loader->add_action( 'woocommerce_before_booking_form', $plugin_public, 'add_booking_form_scripts' );

		$this->loader->add_action( 'wc_ajax_wc_bookings_extensions_get_bookings', $plugin_calendar, 'get_bookings_ajax' );
		// wc_ajax skips the loading of some module to speed up processing.
		$this->loader->add_action( 'wc_ajax_wc_bookings_extensions_update_booking', $plugin_calendar, 'update_booking_ajax' );
		$this->loader->add_action( 'wp_ajax_wc_bookings_extensions_update_booking', $plugin_calendar, 'update_booking_ajax' );
		$this->loader->add_action( 'wp_ajax_wc_bookings_extensions_event_page', $plugin_calendar, 'booking_page' );

		$this->loader->add_action( 'pre_post_update', $plugin_audits, 'log_booking_update', 10, 2 );
		$this->loader->add_action( 'woocommerce_new_booking', $plugin_audits, 'log_booking_created' );

		$this->loader->add_action( 'woocommerce_delete_booking', $plugin_public, 'delete_dependent_transients' );
		$this->loader->add_action( 'woocommerce_trash_booking', $plugin_public, 'delete_dependent_transients' );
		$this->loader->add_action( 'woocommerce_new_booking', $plugin_public, 'delete_dependent_transients' );
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
	 * @return    WC_Bookings_Extensions_Loader    Orchestrates the hooks of the plugin.
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

	/**
	 * Searches through the list of filters registered with WordPress and removes by class and function name
	 *
	 * @param string $tag
	 * @param string $class_name
	 * @param string $function_name
	 * @param integer $priority
	 */
	public function remove_filter_by_class( $tag, $class_name, $function_name, $priority ) {
		global $wp_filter;

		if ( isset( $wp_filter[ $tag ] ) ) {
			/** @var WP_Hook $hook */
			$hook      = &$wp_filter[ $tag ];
			$callbacks = &$hook->callbacks[ $priority ];
			foreach ( $callbacks as $callback_id => $callback ) {
				if ( is_array( $callback['function'] ) ) {

					if ( is_a( $callback['function'][0], $class_name ) && $function_name === $callback['function'][1] ) {
						unset( $callbacks[ $callback_id ] );
					}
				}
			}
		}
	}

	/**
	 * Replaces parts of the WooCommerce Bookings components
	 */
	public function load_extensions() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes' . DIRECTORY_SEPARATOR . 'class-wc-bookings-custom.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes' . DIRECTORY_SEPARATOR . 'class-wc-bookings-extensions-product-booking.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes' . DIRECTORY_SEPARATOR . 'class-wc-bookings-extensions-cart-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes' . DIRECTORY_SEPARATOR . 'class-wc-bookings-extensions-form.php';

		$cart_manager = new WC_Bookings_Extensions_Cart_Manager();

		/** @see WC_Booking_Cart_Manager::validate_add_cart_item */
		$this->remove_filter_by_class( 'woocommerce_add_to_cart_validation', 'WC_Booking_Cart_Manager', 'validate_add_cart_item', 10 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $cart_manager, 'validate_add_cart_item' ), 10, 3 );

		/** @see WC_Booking_Cart_Manager::add_cart_item_data */
		$this->remove_filter_by_class( 'woocommerce_add_cart_item_data', 'WC_Booking_Cart_Manager', 'add_cart_item_data', 10 );
		add_filter( 'woocommerce_add_cart_item_data', array( $cart_manager, 'add_cart_item_data' ), 10, 3 );
    }

}