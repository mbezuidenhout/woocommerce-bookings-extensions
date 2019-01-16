<?php

class WC_Booking_Extensions_Bookings_Search {

	/** @var WC_Product_Booking[] Array of WC_Product_Booking */
	protected $products;

	/** @var array Array of fields to display on search form */
	protected $fields;

	protected $duration;

	protected $duration_unit;

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
		$this->duration = $duration;

		$args = array(
			'status'    => 'publish',
			'type'      => 'booking',
			'limit'     => null
		);

		$query = new WC_Product_Query( $args );
		$this->products = $query->get_products();

		if( 'exclude' == $method && ! empty($ids) ) {
			foreach( $ids as $id )
				foreach( $this->products as $key => $product ) {
					if( $id == $product->get_id() ) {
						unset($this->products[$key]);
						continue 2;
					}
				}
		} elseif( 'include' == $method && ! empty($ids) ) {
			foreach( $this->products as $key => $product )
				if( !in_array( $product->get_id(), $ids ) )
					unset($this->products[$key]);
		}

		foreach( $this->products as $key => $product ) {
			if( $product->is_purchasable() === false || $this->duration_unit != $product->get_duration_unit() && $this->duration != $product->get_duration() )
				unset($this->products['key']);
		}

		$this->products = array_values( $this->products );
	}

	public function output() {
		if ( empty( $this->products ) ) {
			$notice_html  = '<strong>' . esc_html__( 'No products found!', 'woocommerce-booking-extensions' ) . '</strong><br><br>';
			$notice_html .= 'No products has been found matching the shortcode criteria';

			WC_Admin_Notices::add_custom_notice('woocommerce_booking_extensions_no_products', $notice_html );
			return false;
		}

		$this->scripts();
		$this->prepare_fields();

		foreach ( $this->fields as $key => $field ) {
			wc_get_template( 'booking-form/' . $field['type'] . '.php', array( 'field' => $field ), 'woocommerce-bookings', WC_BOOKINGS_TEMPLATE_PATH );
		}

	}

	protected function scripts() {
		// wp_enqueue_script();
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
		foreach( $this->products as $product ) {
			if( 0 == $min || $min > $product->get_min_duration() )
				$min = $product->get_min_duration();
			if( $max < $product->get_max_duration() )
				$max = $product->get_max_duration();
		}

		$after = '';
		switch( $this->duration_unit ) {
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
					if ( 1 == ( $this->duration / 7 ) ) {
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

		$this->add_field( array(
			'type'  => 'number',
			'name'  => 'duration',
			'label' => __( 'Duration', 'woocommerce-bookings' ),
			'after' => $after,
			'min'   => $min,
			'max'   => $max,
			'step'  => 1,
		) );
	}

	protected function persons_field() {
		$min = 0;
		$max = 1;
		$has_persons = false;
		foreach( $this->products as $product ) {
			$has_persons |= $product->has_persons();
			if( 0 == $min || $min > $product->get_min_persons() )
				$min = $product->get_min_persons();
			if( $max < $product->get_max_persons() )
				$max = $product->get_max_persons();
		}

		if( $has_persons ) {
			$this->add_field( array(
				'type'  => 'number',
				'name'  => 'persons',
				'label' => __( 'Persons', 'woocommerce-bookings' ),
				'min'   => $min,
				'max'   => $max,
				'step'  => 1
			) );
		}
	}

	protected function date_field() {
		$picker = null;

		// Get date picker specific to the duration unit for this product
		switch ( $this->duration_unit ) {
			case 'month':
				//include_once( 'class-wc-booking-form-month-picker.php' );
				//$picker = new WC_Booking_Form_Month_Picker( $this );
				break;
			case 'day':
				//include_once( 'class-wc-booking-form-date-picker.php' );
				//$picker = new WC_Booking_Form_Date_Picker( $this );
				break;
			case 'minute':
			case 'hour':
				//include_once( 'class-wc-booking-form-datetime-picker.php' );
				//$picker = new WC_Booking_Form_Datetime_Picker( $this );
				break;
			default:
				break;
		}

		if ( ! is_null( $picker ) ) {
			$this->add_field( $picker->get_args() );
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
}