<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://tripturbine.com/
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
class Woocommerce_Bookings_Extensions_Admin {

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
		$this->version = $version;

	}

	/**
	 * Check installation dependencies
	 *
	 * @since    1.0.0
	 */
	public function woocommerce_bookings_extensions_install() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if( ! class_exists( 'WC_Bookings' ) ) {
			add_action( 'admin_notices', array ($this, 'woocommerce_bookings_extensions_woocommerce_bookings_admin_notice' ) );
		}
	}

	public function woocommerce_bookings_extensions_woocommerce_bookings_admin_notice() {
		?>
		<div class="error">
			<p><?php _e( 'WooCommerce Bookings Extensions is enabled but not effective. It requires WooCommerce Bookings in order to work.', 'woocommerce-bookings-extensions' ); ?></p>
		</div>
		<?php
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
			$bookable_product = new WC_Booking_Extensions_Product_Booking( $post->ID );
		}

		include( 'partials/html-booking-extensions-data.php' );
    }

	/**
	 * Set data in 3.0.x
	 *
	 * @version  1.10.7
	 * @param    WC_Product_Booking|WC_Booking_Extensions_Product_Booking $product
     * @param    WC_Data_Store $product_data
     * @return   WC_Product_Booking
	 */
	public function add_extra_props( $post_id ) {
		$product = wc_get_product( $post_id );
		$block_start = isset( $_POST['_wc_booking_extensions_block_start'] ) ? $_POST['_wc_booking_extensions_block_start'] : '';
		$product->update_meta_data( 'block_starts', wc_clean( $block_start ) );
		$product->save();
	}

}
