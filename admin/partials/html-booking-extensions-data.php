<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="options_group show_if_booking">
	<?php
    /** @var \WC_Booking_Extensions_Product_Booking $bookable_product */
	woocommerce_wp_select( array(
		'id'                   => '_wc_booking_extensions_block_start',
		'value'                => $bookable_product->get_block_starts( 'edit' ),
		'label'                => __( 'Blocks start', 'woocommerce-bookings-extensions' ),
		'description'          => __( 'Choose on what intervals blocks can start', 'woocommerce-bookings-extensions' ),
		'options'              => array(
			''                 => __( 'fixed', 'woocommerce-bookings-extensions' ),
			'on_the_hour'      => __( 'on the hour', 'woocommerce-bookings-extensions' ),
			'on_the_half_hour' => __( 'on the half hour', 'woocommerce-bookings-extensions' ),
			'on_the_quarter'   => __( 'on the quarter', 'woocommerce-bookings-extensions' ),
		),
		'desc_tip'           => true,
		'class'              => 'select',
	) );
	?>
</div>