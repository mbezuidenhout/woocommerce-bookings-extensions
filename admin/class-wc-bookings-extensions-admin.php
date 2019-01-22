<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Woocommerce_Bookings_Extensions
 * @subpackage Woocommerce_Bookings_Extensions/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommerce_Bookings_Extensions
 * @subpackage Woocommerce_Bookings_Extensions/admin
 * @author     Marius Bezuidenhout <marius.bezuidenhout@gmail.com>
 */
class WC_Bookings_Extensions_Admin {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-bookings-extensions-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-bookings-extensions-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function booking_extensions_data() {
		global $post, $bookable_product;

		if ( empty( $bookable_product ) || $bookable_product->get_id() !== $post->ID ) {
			$bookable_product = new WC_Bookings_Extensions_Product_Booking( $post->ID );
		}

		include 'partials/html-booking-extensions-data.php';
	}

	/**
	 * Set data in 3.0.x
	 *
	 * @version  1.10.7
	 * @param    int    $post_id
	 */
	public function add_extra_props( $post_id ) {
		$product = wc_get_product( $post_id );
		if ( 'booking' === $product->get_type() ) {
			$block_start  = isset( $_POST['_wc_booking_extensions_block_start'] ) ? $_POST['_wc_booking_extensions_block_start'] : '';
			$dependencies = isset( $_POST['_wc_booking_extensions_dependent_products'] ) ? $_POST['_wc_booking_extensions_dependent_products'] : array();

			// Remove vica-versa dependencies
			$old_dependencies    = $product->get_meta( 'booking_dependencies' );
			$remove_depencendies = array_diff( $old_dependencies, $dependencies );
			foreach ( $remove_depencendies as $dependency ) {
				$dependent_product              = wc_get_product( $dependency );
				$dependent_product_dependencies = $dependent_product->get_meta( 'booking_dependencies' );
				if ( is_array( $dependent_product_dependencies ) ) {
					$key = array_search( $product->get_id(), $dependent_product_dependencies, true );
					if ( false !== $key ) {
						unset( $dependent_product_dependencies[ $key ] );
						$dependent_product_dependencies = array_unique( $dependent_product_dependencies );
						$dependent_product->update_meta_data( 'booking_dependencies', $dependent_product_dependencies );
						$dependent_product->save();
					}
				}
			}

			// Add vice-versa dependency
			foreach ( $dependencies as $key => $dependency ) {
				$dependent_product = wc_get_product( $dependency );
				if ( 'booking' !== $dependent_product->get_type() ) {
					unset( $dependencies[ $key ] );
				} else {
					$dependent_product_dependencies = $dependent_product->get_meta( 'booking_dependencies' );
					if ( ! is_array( $dependent_product_dependencies ) ) {
						$dependent_product_dependencies = array();
					}
					$dependent_product_dependencies[] = $product->get_id();
					$dependent_product_dependencies   = array_unique( $dependent_product_dependencies );
					$dependent_product->update_meta_data( 'booking_dependencies', $dependent_product_dependencies );
					$dependent_product->save();
				}
			}

			$dependencies = array_unique( $dependencies );

			$product->update_meta_data( 'block_starts', wc_clean( $block_start ) );
			$product->update_meta_data( 'booking_dependencies', wc_clean( $dependencies ) );

			$product->save();

		}
	}

	/**
	 * Adds functionality to bookable product to define inter dependent products
	 * Note that multi level dependencies are not supported in searches
	 */
	public function show_booking_dependencies_options() {
		$post    = get_post( intval( $_GET['post'] ) );
		$product = wc_get_product( $post->ID );
		$action  = wc_clean( $_GET['action'] );

		/** @var \WC_Product_Data_Store_CPT $data_store */
		$data_store = WC_Data_Store::load( 'product' );
		$ids        = $data_store->search_products( null, 'booking', false, false, null );

		foreach ( $ids as $id ) {
			if ( $post->ID === $id || 0 === $id ) {
				continue;
			}
			$bookable_product_ids[] = $id;
		}

		if ( 'edit' === $action && 'booking' === $product->get_type() ) {
			include 'partials/dependencies.php';
		}

	}



}
