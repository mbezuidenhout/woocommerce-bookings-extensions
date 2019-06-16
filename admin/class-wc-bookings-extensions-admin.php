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

        $suffix = defined( 'SCRIPT_CSS' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-bookings-extensions-admin' . $suffix . '.css', array(), $this->version, 'all' );

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

	/**
	 * Add product fields
	 *
	 * @param int $product_id
	 */
	public function enqueue_product_data_scripts( $product_id ) {
		$js_data = array(
			'data_tip'     => __( 'Rules with the override flag set will override any other rule if matched.' ),
			'ext_override' => array(),
		);

		/** @var WC_Product_Booking $bookable_product */
		$bookable_product = wc_get_product( $product_id );
		if ( is_a( $bookable_product, 'WC_Product_Booking' ) ) {
			$pricing = $bookable_product->get_pricing();
			foreach ( $pricing as $key => $val ) {
				if ( isset( $val['ext_override'] ) && true === $val['ext_override'] ) {
					$js_data['ext_override'][] = $key;
				}
			}
		}

		wp_enqueue_script( $this->plugin_name . '-product-data-js', plugin_dir_url( __FILE__ ) . 'js/woocommerce-bookings-extensions-products.min.js', array( 'jquery' ), $this->version, true );
		wp_localize_script(
			$this->plugin_name . '-product-data-js',
			'wc_bookings_extensions_product_data',
			$js_data
		);
	}

	/**
	 * Set props
	 *
	 * @param \WC_Product_Booking $product
	 */
	public function set_ext_props( $product ) {
		// Only set props if the product is a bookable product.
		if ( ! is_a( $product, 'WC_Product_Booking' ) ) {
			return;
		}

		$pricing = $product->get_pricing();

		$row_size = isset( $_POST['wc_booking_pricing_type'] ) ? sizeof( $_POST['wc_booking_pricing_type'] ) : 0;
		for ( $i = 0; $i < $row_size; $i ++ ) {
			if ( isset( $_POST['wc_booking_ext_pricing_override'][ $i ] ) && 'days' === $pricing[ $i ]['type'] ) {
				$pricing[ $i ]['ext_override'] = true;
			} else {
				unset( $pricing[ $i ]['ext_override'] );
			}
		}

		$product->set_pricing( $pricing );

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

	public function calendar_page_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'calendar-view', plugin_dir_url( __FILE__ ) . 'js/calendar-view' . $suffix . '.js', array( 'jquery' ), $this->version, false );
	}

	public function add_admin_booking_fields( $post_id ) {
        $booking = new WC_Booking( $post_id );
        $value   = $booking->get_meta('booking_guest_name');
	    ?>
        <p class="form-field form-field-wide">
            <label for="booking_guest_name"><?php _e('Guest Name', 'flatsome-vodacom' ) ?></label><input type="text" style="" name="booking_guest_name" id="booking_guest_name" value="<?php echo $value ?>" placeholder="N/A"> </p>
        <?php
    }

    /**
     * Save handler.
     *
     * @version 1.10.2
     *
     * @param  int     $post_id Post ID.
     * @param  WP_Post $post    Post object.
     */
    public function save_meta_box( $post_id, $post ) {
        if ( ! isset( $_POST['wc_bookings_details_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['wc_bookings_details_meta_box_nonce'], 'wc_bookings_details_meta_box' ) ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if ( empty( $_POST['post_ID'] ) || intval( $_POST['post_ID'] ) !== $post_id ) {
            return $post_id;
        }

        if ( ! in_array( $post->post_type, array( 'wc_booking' ) ) ) {
            return $post_id;
        }

        // Get booking object.
        $booking    = new WC_Booking( $post_id );

        update_post_meta( $booking->get_id(), 'booking_guest_name', $_POST['booking_guest_name'] );

        $booking->save_meta_data();
    }

    public function load_extensions() {
        // Replace Calendar page
        remove_submenu_page( 'edit.php?post_type=wc_booking', 'booking_calendar' );
        $calendar_page       = add_submenu_page( 'edit.php?post_type=wc_booking', __( 'Calendar', 'woocommerce-bookings' ), __( 'Calendar', 'woocommerce-bookings' ), 'manage_bookings', 'booking_calendar', array( $this, 'calendar_page' ) );

        // Add action for screen options on this new page
        add_action( 'admin_print_scripts-' . $calendar_page, array( $this, 'admin_calendar_page_scripts' ) );
    }

    /**
     * calendar_page_scripts.
     */
    public function admin_calendar_page_scripts() {
        wp_enqueue_script( 'jquery-ui-datepicker' );
    }

    /**
     * Output the calendar page.
     */
    public function calendar_page() {
        require_once( 'class-wc-bookings-extensions-calendar.php' );
        $page = new WC_Bookings_Extensions_Calendar();
        $page->output();
    }

}
