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
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
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

		$suffix = defined( 'SCRIPT_CSS' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-bookings-extensions-admin' . $suffix . '.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Add product fields
	 *
	 * @param int $product_id WooCommerce product id.
	 */
	public function enqueue_product_data_scripts( $product_id ) {
		$js_data = array(
			'data_tip'     => __( 'Rules with the override flag set will override any other rule if matched.' ),
			'ext_override' => array(),
		);

		/**
		 * Bookable product
		 *
		 * @var WC_Product_Booking $bookable_product
		 */
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
	 * @param WC_Product_Booking $product Instance of WC_Product_Booking.
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

	/**
	 * Output extra options for bookable products.
	 */
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
	 * @param int $post_id WooCommerce post id.
	 *
	 * @version  1.10.7
	 */
	public function add_extra_props( $post_id ) {
		$product = wc_get_product( $post_id );
		if ( 'booking' === $product->get_type() ) {
			$block_start  = isset( $_POST['_wc_booking_extensions_block_start'] ) ? $_POST['_wc_booking_extensions_block_start'] : '';
			$dependencies = isset( $_POST['_wc_booking_extensions_dependent_products'] ) ? $_POST['_wc_booking_extensions_dependent_products'] : array();

			// Remove vice-versa dependencies.
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

			// Add vice-versa dependency.
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

		/**
		 * Instance of WC_Product_Data_Store_CPT.
		 *
		 * @var \WC_Product_Data_Store_CPT $data_store
		 */
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

	/**
	 * Add options to booking details page.
	 */
	public function calendar_page_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'calendar-view', plugin_dir_url( __FILE__ ) . 'js/calendar-view' . $suffix . '.js', array( 'jquery' ), $this->version, false );
	}

	/** Add custom admin input fields for new bookings
	 *
	 * @param int $post_id WordPress post ID.
	 */
	public function add_admin_booking_fields( $post_id ) {
		$booking = new WC_Booking( $post_id );
		$value   = $booking->get_meta( 'booking_guest_name' );
		?>
        <p class="form-field form-field-wide">
            <label for="booking_guest_name"><?php _e( 'Guest Name', 'flatsome-vodacom' ) ?></label>
            <input type="text" style="" name="booking_guest_name" id="booking_guest_name" value="<?php echo $value ?>"
                   placeholder="N/A">
        </p>
		<?php
	}

	/**
	 * Save handler.
	 *
	 * @param int     $post_id  Post ID.
	 * @param WP_Post $post     Post object.
	 *
	 * @return int
	 * @version 1.10.2
	 */
	public function save_meta_box( $post_id, $post ) {
		if ( ! isset( $_POST['wc_bookings_details_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['wc_bookings_details_meta_box_nonce'], 'wc_bookings_details_meta_box' ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( empty( $_POST['post_ID'] ) || intval( $_POST['post_ID'] ) !== $post_id ) {
			return $post_id;
		}

		if ( ! in_array( $post->post_type, array( 'wc_booking' ), true ) ) {
			return $post_id;
		}

		if ( isset( $_POST['booking_guest_name'] ) ) {
			// Get booking object.
			$booking = new WC_Booking( $post_id );

			update_post_meta( $booking->get_id(), 'booking_guest_name', sanitize_text_field( wp_unslash( $_POST['booking_guest_name'] ) ) );

			$booking->save_meta_data();
		}
	}

	/**
	 * Removes old calendar menu page and add FullCalendar page.
	 */
	public function change_calendar() {
		remove_submenu_page( 'edit.php?post_type=wc_booking', 'booking_calendar' );

		$enable_calendar = get_option( 'woocommerce_bookings_extensions_fullcalendar', false );
		if ( 'yes' === $enable_calendar ) {
			// This will replace the calendar above.
			$calendar_page_screen = add_submenu_page(
				'edit.php?post_type=wc_booking',
				__( 'Full Calendar', 'woocommerce-booking-extensions' ),
				__( 'Full Calendar', 'woocommerce-booking-extensions' ),
				'manage_bookings',
				'full_calendar',
				array(
					$this,
					'full_calendar_page',
				)
			);

			// Add screen options.
			add_filter( 'manage_' . $calendar_page_screen . '_columns', array( $this, 'add_product_categories' ) );
		} else {
			// Replace Calendar page.
			$calendar_page = add_submenu_page(
				'edit.php?post_type=wc_booking',
				__( 'Calendar', 'woocommerce-bookings' ),
				__( 'Calendar', 'woocommerce-bookings' ),
				'manage_bookings',
				'new_booking_calendar',
				array(
					$this,
					'calendar_page',
				)
			);

			// Add action for screen options on this new page.
			add_action( 'admin_print_scripts-' . $calendar_page, array( $this, 'admin_calendar_page_scripts' ) );
		}
	}

	/**
	 * Add list of product categories to be displayed in the screen options drop-down.
	 *
	 * @param array $categories List of product cateogories.
	 *
	 * @return array
	 */
	public function add_product_categories( $categories ) {
		/**
		 * Get list of product categories in WooCommerce
		 *
		 * @var WP_Term[] $product_categories
		 */
		$product_categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );

		if ( ! isset( $categories['_title'] ) ) {
			$categories['_title'] = __( 'Show product categories', 'woo-bookings-extensions' );
		}

		$categories['wbe-uncategorized'] = __( 'Uncategorized' );

		foreach ( $product_categories as $category ) {
			$cat_term_id = 'wbe-category-' . $category->term_id;
			if ( ! isset( $categories[ $cat_term_id ] ) && ! in_array( $category->name, array( 'Uncategorized', __( 'Uncategorized' ) ), true ) ) { // Ignore WordPress built-in category "Uncategorized".
				$categories[ $cat_term_id ] = $category->name;
			}
		}

		return $categories;
	}

	/**
	 * Calendar_page_scripts.
	 */
	public function admin_calendar_page_scripts() {
		wp_enqueue_script( 'jquery-ui-datepicker' );
	}

	/**
	 * Output the calendar page.
	 */
	public function calendar_page() {
		require_once 'class-wc-bookings-extensions-calendar.php';
		$page = new WC_Bookings_Extensions_Calendar();
		$page->output();
	}

	/**
	 * Output for the new calendar page.
	 */
	public function full_calendar_page() {
		$page = WC_Bookings_Extensions_New_Calendar::get_instance();
		$page->admin_output();
	}

	/**
	 * Callback action to add admin menu options.
	 *
	 * @param array $settings An array of options.
	 *
	 * @return array
	 */
	public function add_admin_settings( $settings ) {
		$insert_pos = array_search(
			array(
				'type' => 'sectionend',
				'id'   => 'woocommerce_bookings_calendar_settings',
			),
			$settings,
			true
		);

		$bookings_settings = array(
			array(
				'title'   => __( 'Enable Full Calendar', 'woocommerce-bookings-extensions' ),
				'id'      => 'woocommerce_bookings_extensions_fullcalendar',
				'default' => false,
				'type'    => 'checkbox',
				'desc'    => __( 'Enable the interactive calendar feature.', 'woocommerce-bookings-extensions' ),
			),
			array(
				'title'    => __( 'Full Calendar License', 'woocommerce-bookings-extensions' ),
				'id'       => 'woocommerce_bookings_extensions_fullcalendar_license',
				'default'  => '',
				'type'     => 'text',
				'desc_tip' => true,
				'desc'     => __( 'If using FullCalendar then enter license here. https://fullcalendar.io/license ', 'woocommerce-bookings-extensions' ),
			),
			array(
				'title'    => __( 'Holidays ICS URI', 'woocommerce-bookings-extensions' ),
				'id'       => 'woocommerce_bookings_extensions_holidays',
				'default'  => '',
				'type'     => 'text',
				'desc_tip' => true,
				'desc'     => __( 'URI where an ICS file can be found with holidays', 'woocommerce-bookings-extensions' ),
			),
		);

		array_splice( $settings, $insert_pos, 0, $bookings_settings );

		return $settings;
	}

	/**
	 * Add the estimate terms meta boxes.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/add_meta_boxes
	 */
	public function add_meta_boxes( $post ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_style( 'media-upload-css', plugin_dir_url( __FILE__ ) . 'css/booking-edit' . $suffix . '.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'media-upload-css' );
		wp_register_script( 'booking-files-meta-box', plugin_dir_url( __FILE__ ) . 'js/files-meta-box' . $suffix . '.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'booking-files-meta-box' );
		$args = array(
			'nonce'        => wp_create_nonce( 'files_meta' ),
			'title'        => __( 'Files', 'woo-bookings-extensions' ),
			'url'          => admin_url( "media-upload.php?inline=true&type=file&tab=type&post_id={$post->ID}" ),
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'deleteAction' => 'delete_booking_file',
		);
		wp_localize_script( 'booking-files-meta-box', 'filesOptions', $args );
		wp_enqueue_script( 'media-upload' );
		add_thickbox(); // Add the WordPress admin thickbox js and css.
		add_meta_box(
			'filesdiv',
			__( 'Files', 'woo-bookings-extensions' ),
			array(
				$this,
				'meta_box_file',
			),
			'wc_booking',
			'side'
		);

	}

	/**
	 * Display the content of the terms meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function meta_box_file( $post ) {
		echo '<p><button type="button" class="button media-button" id="booking_add_file">' . esc_html__( 'Add file', 'woo-bookings-extensions' ) . '</button></p>';
		/** @var WP_Post[] $files */
		$files = get_attached_media( null, $post->ID );
		echo '<ul id="booking_files">';
		foreach ( $files as $file ) :
			?>
            <li>
                <a href="<?php esc_url_raw( $file->guid ); ?>"><?php echo esc_attr( $file->post_title ); ?></a>
                <a data-id="<?php echo esc_attr( $file->ID ); ?>" class="remove-file del-button">X</a>
            </li>
		<?php
		endforeach;
		echo '</ul>';
	}

	/**
	 * Display the file upload iframe page.
	 */
	public function upload_booking_file_page() {
		include plugin_dir_path( __FILE__ ) . 'partials/file.php';
		wp_die(); // this is required to terminate immediately and return a proper response.
	}

	/**
	 * Delete the file from this booking.
	 */
	public function delete_booking_file() {
		check_ajax_referer( 'files_meta', '_wpnonce' );

		$id     = isset( $_REQUEST['id'] ) ? sanitize_key( wp_unslash( $_REQUEST['id'] ) ) : null;
		$errors = array();
		if ( ! empty( $id ) ) {
			$post = get_post( $id );
			if ( 'attachment' === $post->post_type ) {
				$file = get_attached_file( $id );
				if ( file_exists( $file ) && ! is_writable( dirname( $file ) ) ) {
					$errors[] = __( 'File cannot be deleted', 'woo-bookings-extensions' );
				}
				if ( empty( $errors ) && file_exists( $file ) && is_writable( dirname( $file ) ) ) {
					if ( ! unlink( $file ) ) {
						$errors[] = __( 'File cannot be deleted', 'woo-bookings-extensions' );
					}
				}
				wp_delete_post( $id );
				if ( empty( $errors ) ) {
					wp_send_json_success();
				} else {
					wp_send_json_error( array( 'errors' => $errors ) );
				}
			}
		}
		wp_send_json_error();
	}

	/**
	 * Change the All Bookings table columns.
	 *
	 * @param array $existing_columns Current columns that will be displayed.
	 *
	 * @return array
	 */
	public function edit_columns( $existing_columns ) {
		if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
			$existing_columns = [];
		}
		$columns = [
			'customer'       => __( 'Customer', 'woocommerce-bookings-extensions' ),
			'user_created'   => __( 'User Created', 'woocommerce-bookings-extensions' ),
			'user_modified'  => __( 'User Modified', 'woocommerce-bookings-extensions' ),
			'payment_method' => __( 'Payment', 'woocommerce-bookings-extensions' ),
		];

		$customer_column_pos = array_search( 'customer', array_keys( $existing_columns ), true );

		return array_slice( $existing_columns, 0, $customer_column_pos, true ) + $columns + array_slice( $existing_columns, $customer_column_pos + 1, null, true );
	}

	/**
	 * Define our custom columns shown in admin.
	 *
	 * @param string $column The column name.
	 *
	 * @global WC_Booking $booking , WC_Post $post
	 */
	public function custom_columns( $column ) {
		global $post, $booking;
		if ( ! is_a( $booking, 'WC_Booking' ) || $booking->get_id() !== $post->ID ) {
			$booking = new WC_Booking( $post->ID );
		}

		switch ( $column ) {
			case 'user_created':
				$created_user_id = $booking->get_meta( '_booking_created_user_id' );
				if ( ! empty( $created_user_id ) ) {
					$user_created      = get_userdata( $created_user_id );
					$user_created_name = esc_html( $user_created->display_name );
					if ( $user_created->user_email ) {
						$user_created_name = '<a href="mailto:' . esc_attr( $user_created->user_email ) . '">' . $user_created_name . '</a>';
					}
				} else {
					$user_created_name = '-';
				}

				echo $user_created_name; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				break;
			case 'user_modified':
				$modified_user_id = $booking->get_meta( '_booking_modified_user_id' );
				if ( ! empty( $modified_user_id ) ) {
					$user_modified      = get_userdata( $modified_user_id );
					$user_modified_name = esc_html( $user_modified->display_name );
					if ( $user_modified->user_email ) {
						$user_modified_name = '<a href="mailto:' . esc_attr( $user_modified->user_email ) . '">' . $user_modified_name . '</a>';
					}
				} else {
					$user_modified_name = '-';
				}

				echo $user_modified_name; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				break;
			case 'payment_method':
				$the_order = $booking->get_order();
				if ( $the_order instanceof \WC_Order ) {
					$payment_method = wc_get_payment_gateway_by_order( $the_order );
					echo esc_html( $payment_method->method_title );
				} else {
					echo esc_html( '-' );
				}
				break;
		}
	}

}
