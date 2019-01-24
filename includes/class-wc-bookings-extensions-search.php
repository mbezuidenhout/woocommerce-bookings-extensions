<?php

class WC_Bookings_Extensions_Bookings_Search {

	/** @var WC_Product_Booking[] Array of WC_Product_Booking */
	protected $products;

	/** @var array Array of fields to display on search form */
	protected $fields;

	/** @var Array of extended fields from this plugin */
	protected $extension_fields;

	/** @var int Duration block sizes */
	protected $duration;

	/** @var string Duration unit of month|day|hour|minuet */
	protected $duration_unit;

	/** @var array Date picker min date */
	protected $min_date;

	/** @var array Date picker max date */
	protected $max_date;
	/**
	 *
	 *
	 * @param string $method        include or exclude below list of $ids
	 * @param array $ids            List of ids to include or exclude
	 * @param string $duration_unit Unit of month, day, hour or minute
	 * @param int $duration         Duration block of $duration_unit size
	 */
	public function __construct( $method, $ids, $duration_unit, $duration ) {
		$this->duration_unit = $duration_unit;
		$this->duration      = $duration;

		$args = array(
			'status' => 'publish',
			'type'   => 'booking',
			'limit'  => null,
		);


		if ( ! empty( $ids ) ) {
			if ( 'exclude' === $method ) {
				$args['exclude'] = $ids;
			} elseif ( 'include' === $method ) {
				$args['include'] = $ids;
			}
		}

		$query          = new WC_Product_Query( $args );
		$this->products = $query->get_products();

		foreach ( $this->products as $key => $product ) {
			if ( $product->is_purchasable() === false ||
				$this->duration_unit !== $product->get_duration_unit() ||
				$this->duration_unit === $product->get_duration_unit() &&
				$this->duration !== $product->get_duration() ) {
				unset( $this->products[ $key ] );
			}
		}

		$this->products = array_values( $this->products );
	}

	public function output() {

		$this->scripts();
		$this->prepare_fields();

		foreach ( $this->fields as $key => $field ) {
			wc_get_template( 'booking-form/' . $field['type'] . '.php', array( 'field' => $field ), 'woocommerce-bookings', WC_BOOKINGS_TEMPLATE_PATH );
		}

		$this->extension_fields = array_merge(
			$this->extension_fields,
			array(
				array(
					'type'  => 'hidden-field',
					'name'  => 'ids',
					'value' => implode( ',', $this->get_ids() ),
				),
				array(
					'type'  => 'hidden-field',
					'name'  => 'duration',
					'value' => $this->duration,
				),
				array(
					'type'  => 'hidden-field',
					'name'  => 'duration_unit',
					'value' => $this->duration_unit,
				),
			)
		);
		foreach ( $this->extension_fields as $key => $field ) {
			wc_get_template( $field['type'] . '.php', array( 'field' => $field ), 'woocommerce-booking-extensions', untrailingslashit( plugin_dir_path( __DIR__ ) ) . '/templates/' );
		}

	}

	public function get_ids() {
		$ids = array();
		foreach ( $this->products as $product ) {
			$ids[] = $product->get_id();
		}

		$ids = apply_filters( 'wc_bookings_extensions_search_product_ids', $ids );

		return $ids;
	}

	protected function scripts() {
		global $wp_locale;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$wc_bookings_booking_form_args = array(
			'closeText'                  => __( 'Close', 'woocommerce-bookings' ),
			'currentText'                => __( 'Today', 'woocommerce-bookings' ),
			'prevText'                   => __( 'Previous', 'woocommerce-bookings' ),
			'nextText'                   => __( 'Next', 'woocommerce-bookings' ),
			'monthNames'                 => array_values( $wp_locale->month ),
			'monthNamesShort'            => array_values( $wp_locale->month_abbrev ),
			'dayNames'                   => array_values( $wp_locale->weekday ),
			'dayNamesShort'              => array_values( $wp_locale->weekday_abbrev ),
			'dayNamesMin'                => array_values( $wp_locale->weekday_initial ),
			'firstDay'                   => get_option( 'start_of_week' ),
			'current_time'               => date( 'Ymd', current_time( 'timestamp' ) ),
			'check_availability_against' => '',
			'duration_unit'              => $this->duration_unit,
			'resources_assignment'       => 'customer',
			'isRTL'                      => is_rtl(),
			'default_availability'       => $this->get_default_availability(),
		);

		/** @see WC_Bookings_Extensions_Public::search_booking_products */
		$wc_bookings_date_picker_args = array(
			//'ajax_url'                   => WC_AJAX::get_endpoint( 'wc_bookings_extensions_search' ),
			'ajax_url'        => admin_url( 'admin-ajax.php?action=wc_bookings_extensions_search' ),
			'datepicker_args' => array(
				'minDate'    => 0,
				'maxDae'     => '+6M',
				'dateFormat' => $this->convert_to_moment_format( get_option( 'date_format' ) ),
			),
		);

		wp_enqueue_script( 'wc-bookings-moment', WC_BOOKINGS_PLUGIN_URL . '/assets/js/lib/moment-with-locales' . $suffix . '.js', array(), WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION, true );
		wp_enqueue_script( 'wc-bookings-moment-timezone', WC_BOOKINGS_PLUGIN_URL . '/assets/js/lib/moment-timezone-with-data' . $suffix . '.js', array(), WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION, true );

		wp_enqueue_script( 'wc-bookings-booking-form', plugin_dir_url( __DIR__ ) . 'public/js/search-form' . $suffix . '.js', array( 'jquery', 'jquery-blockui' ), WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION, true );
		wp_localize_script( 'wc-bookings-booking-form', 'wc_bookings_booking_form', $wc_bookings_booking_form_args );

		// Variables for JS scripts
		$booking_form_params = array(
			'action'                     => 'wc_bookings_extensions_search_result',
			'cache_ajax_requests'        => 'false',
			'ajax_url'                   => admin_url( 'admin-ajax.php' ),
			'i18n_date_unavailable'      => __( 'This date is unavailable', 'woocommerce-bookings' ),
			'i18n_date_fully_booked'     => __( 'This date is fully booked and unavailable', 'woocommerce-bookings' ),
			'i18n_date_partially_booked' => __( 'This date is partially booked - but bookings still remain', 'woocommerce-bookings' ),
			'i18n_date_available'        => __( 'This date is available', 'woocommerce-bookings' ),
			'i18n_start_date'            => __( 'Choose a Start Date', 'woocommerce-bookings' ),
			'i18n_end_date'              => __( 'Choose an End Date', 'woocommerce-bookings' ),
			'i18n_dates'                 => __( 'Dates', 'woocommerce-bookings' ),
			'i18n_choose_options'        => __( 'Please select the options for your booking and make sure duration rules apply.', 'woocommerce-bookings' ),
			'i18n_clear_date_selection'  => __( 'To clear selection, pick a new start date', 'woocommerce-bookings' ),
			'pao_pre_30'                 => ( defined( 'WC_PRODUCT_ADDONS_VERSION' ) && version_compare( WC_PRODUCT_ADDONS_VERSION, '3.0', '<' ) ) ? 'true' : 'false',
			'pao_active'                 => class_exists( 'WC_Product_Addons' ),
			'timezone_conversion'        => wc_should_convert_timezone(),
			'client_firstday'            => 'yes' === get_option( 'woocommerce_bookings_client_firstday', 'no' ),
			'server_timezone'            => wc_booking_get_timezone_string(),
			'server_time_format'         => $this->convert_to_moment_format( get_option( 'time_format' ) ),
		);

		wp_localize_script( 'wc-bookings-booking-form', 'booking_form_params', apply_filters( 'booking_form_params', $booking_form_params ) );

		//wp_deregister_script( 'wc-bookings-date-picker' ); // Replace default date picker action
		wp_register_script( 'wc-bookings-extensions-date-picker', plugin_dir_url( __DIR__ ) . 'public/js/date-picker' . $suffix . '.js', array( 'wc-bookings-moment', 'wc-bookings-booking-form', 'jquery-ui-datepicker', 'underscore' ), WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION, true );
		wp_localize_script( 'wc-bookings-extensions-date-picker', 'wc_bookings_extensions_date_picker_args', $wc_bookings_date_picker_args );
	}

	protected function prepare_fields() {
		// Destroy existing fields
		$this->fields = array();

		// Add fields in order
		$this->duration_field();
		$this->persons_field();
		$this->date_field();

		$this->fields = apply_filters( 'booking_form_fields', $this->fields );
	}

	protected function duration_field() {
		// Get the duration type for each product
		$min = 0;
		$max = 1;
		foreach ( $this->products as $product ) {
			if ( 0 === $min || $min > $product->get_min_duration() ) {
				$min = $product->get_min_duration();
			}
			if ( $max < $product->get_max_duration() ) {
				$max = $product->get_max_duration();
			}
		}

		$after = '';
		switch ( $this->duration_unit ) {
			case 'month':
				if ( $this->duration > 1 ) {
					/* translators: 1: product duration */
					$after = sprintf( __( '&times; %s Months', 'woocommerce-bookings' ), $this->duration_unit );
				} else {
					$after = __( 'Month(s)', 'woocommerce-bookings' );
				}
				break;
			case 'day':
				if ( $this->duration % 7 ) {
					if ( $this->duration > 1 ) {
						/* translators: 1: product duration */
						$after = sprintf( __( '&times; %s days', 'woocommerce-bookings' ), $this->duration_unit );
					} else {
						$after = __( 'Day(s)', 'woocommerce-bookings' );
					}
				} else {
					if ( 1 === ( $this->duration / 7 ) ) {
						$after = __( 'Week(s)', 'woocommerce-bookings' );
					} else {
						/* translators: 1: product duration in weeks */
						$after = sprintf( __( '&times; %s weeks', 'woocommerce-bookings' ), $this->duration_unit / 7 );
					}
				}
				break;
			case 'hour':
				if ( $this->duration > 1 ) {
					/* translators: 1: product duration */
					$after = sprintf( __( '&times; %s hours', 'woocommerce-bookings' ), $this->duration );
				} else {
					$after = __( 'Hour(s)', 'woocommerce-bookings' );
				}
				break;
			case 'minute':
				if ( $this->duration > 1 ) {
					/* translators: 1: product duration */
					$after = sprintf( __( '&times; %s minutes', 'woocommerce-bookings' ), $this->duration );
				} else {
					$after = __( 'Minute(s)', 'woocommerce-bookings' );
				}
				break;
		}

		$this->add_field(
			array(
				'type'  => 'number',
				'name'  => 'duration',
				'label' => __( 'Duration', 'woocommerce-bookings' ),
				'after' => $after,
				'min'   => $min,
				'max'   => $max,
				'step'  => 1,
			)
		);
	}

	protected function persons_field() {
		$min         = 0;
		$max         = 1;
		$has_persons = false;
		foreach ( $this->products as $product ) {
			$has_persons |= $product->has_persons();
			if ( 0 === $min || $min > $product->get_min_persons() ) {
				$min = $product->get_min_persons();
			}
			if ( $max < $product->get_max_persons() ) {
				$max = $product->get_max_persons();
			}
		}

		if ( $has_persons ) {
			$this->add_field(
				array(
					'type'  => 'number',
					'name'  => 'persons',
					'label' => __( 'Persons', 'woocommerce-bookings' ),
					'min'   => $min,
					'max'   => $max,
					'step'  => 1,
				)
			);
		}
	}

	public function get_min_date() {
		if ( ! empty( $this->min_date ) ) {
			return $this->min_date;
		}

		$min = array(
			'value' => 0,
			'unit'  => 'day',
		);

		$this->min_date = $min;
		return $this->min_date;
	}

	public function get_max_date() {
		if ( ! empty( $this->max_date ) ) {
			return $this->max_date;
		}

		$max = array(
			'value' => 12,
			'unit'  => 'month',
		);

		$this->max_date = $max;
		return $this->max_date;
	}

	public function get_default_availability() {
		$availability = false;
		foreach ( $this->products as $product ) {
			$availability |= $product->get_default_availability();
		}
		return $availability;
	}

	public function get_duration_type() {
		return $this->duration;
	}

	public function get_duration_unit() {
		return $this->duration_unit;
	}

	public function is_range_picker_enabled() {
		$enable = false;
		foreach ( $this->products as $product ) {
			$enable |= $product->get_enable_range_picker();
		}
		return $enable;
	}

	public function get_calendar_display_mode() {
		return 'always_visible';
	}

	public function get_type() {
		return 'booking';
	}

	protected function date_field() {
		$picker = null;

		// Get date picker specific to the duration unit for this product
		switch ( $this->duration_unit ) {
			case 'month':
				//include_once 'class-wc-booking-form-month-picker.php';
				//$picker = new WC_Booking_Form_Month_Picker( $this );
				break;
			case 'day':
			case 'minute':
			case 'hour':
				include_once 'class-wc-bookings-extensions-date-field.php';
				$picker = new WC_Bookings_Extensions_Bookings_Date_Field( $this );
				break;
			default:
				break;
		}

		if ( ! is_null( $picker ) ) {
			$this->extension_fields[] = $picker->get_args();
		}
	}

	/**
	 * Add Field
	 * @param  array $field
	 * @return void
	 */
	public function add_field( $field ) {
		$default = array(
			'name'  => '',
			'class' => array(),
			'label' => '',
			'type'  => 'text',
		);

		$field = wp_parse_args( $field, $default );

		if ( ! $field['name'] || ! $field['type'] ) {
			return;
		}

		$nicename = 'wc_bookings_field_' . sanitize_title( $field['name'] );

		$field['name']    = $nicename;
		$field['class'][] = $nicename;

		$this->fields[ sanitize_title( $field['name'] ) ] = $field;
	}

	/**
	 * Attempt to convert a date formatting string from PHP to Moment
	 *
	 * @param string $format
	 * @return string
	 */
	protected function convert_to_moment_format( $format ) {
		$replacements = array(
			'd' => 'DD',
			'D' => 'ddd',
			'j' => 'D',
			'l' => 'dddd',
			'N' => 'E',
			'S' => 'o',
			'w' => 'e',
			'z' => 'DDD',
			'W' => 'W',
			'F' => 'MMMM',
			'm' => 'MM',
			'M' => 'MMM',
			'n' => 'M',
			't' => '', // no equivalent
			'L' => '', // no equivalent
			'o' => 'YYYY',
			'Y' => 'YYYY',
			'y' => 'YY',
			'a' => 'a',
			'A' => 'A',
			'B' => '', // no equivalent
			'g' => 'h',
			'G' => 'H',
			'h' => 'hh',
			'H' => 'HH',
			'i' => 'mm',
			's' => 'ss',
			'u' => 'SSS',
			'e' => 'zz', // deprecated since version 1.6.0 of moment.js
			'I' => '', // no equivalent
			'O' => '', // no equivalent
			'P' => '', // no equivalent
			'T' => '', // no equivalent
			'Z' => '', // no equivalent
			'c' => '', // no equivalent
			'r' => '', // no equivalent
			'U' => 'X',
		);

		return strtr( $format, $replacements );
	}

	public function get_availability_html( $date, $duration, $persons = null ) {
		global $product, $post;
		$available_products = $this->products;

		// Remove bookable products that cannot accommodate the specified nr of persons
		if ( null !== $persons ) {
			foreach ( $available_products as $key => $product ) {
				if ( $product->get_has_persons() && $persons < $product->get_min_persons() || $persons > $product->get_max_persons() ) {
					unset( $available_products[ $key ] );
				}
			}
		}

		if ( empty( $available_products ) ) {
			return false;
		}

		$available_blocks = array();

		$interval      = (int) $duration * $product->get_duration();
		$base_interval = $product->get_duration();

		if ( 'hour' === $product->get_duration_unit() ) {
			$interval      = $interval * 60;
			$base_interval = $base_interval * 60;
		}

		$first_block_time = $product->get_first_block_time();
		$from             = strtotime( $first_block_time ? $first_block_time : 'midnight', $date );
		$to               = strtotime( '+ 1 day', $from ) + $interval;
		$to               = strtotime( 'midnight', $to ) - 1;

		foreach ( $available_products as $product ) {
			$custom_product = new WC_Bookings_Extensions_Product_Booking( $product->get_id() );
			$booked         = array();
			/** @var WC_Booking[] $existing_bookings */
			$existing_bookings = $custom_product->get_bookings_in_date_range( $from, $to );
			foreach ( $existing_bookings as $existing_booking ) {
				$booked[] = array( $existing_booking->get_start(), $existing_booking->get_end() + $custom_product->get_buffer_period() * 60 );
			}
			$block_in_range = $custom_product->get_blocks_in_range( $from, $to, array( $interval, $base_interval ), 0, $booked );
			if ( ! empty( $block_in_range ) ) {
				$available_blocks[ $product->get_id() ] = $block_in_range;
			}
		}

		$html_block = '';
		foreach ( $available_products as $product ) {
			if ( in_array( $product->get_id(), array_keys( $available_blocks ), true ) ) {
				$post = get_post( intval( $product->get_id() ) );
				ob_start();
				wc_get_template_part( 'content', 'product' );
				$html_block .= '<li>' . ob_get_contents() . '</li>';
				// wc_get_template( 'product.php', $product, 'woocommerce-bookings-extensions', plugin_dir_path( __DIR__ ) . 'templates/' );
				ob_clean();

			}
		}
		if ( empty( $html_block ) ) {
			$html_block .= __( 'This date is unavailable', 'woocommerce-bookings' );
		}


		return $html_block;
	}

}