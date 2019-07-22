<?php
/**
 * Create event or event details.
 *
 * @global \DateTime $start
 * @global \DateTime $end
 * @global WC_Product_Booking $product
 * @global string $all_day
 */

$bookable_products = array( '' => __( 'N/A', 'woocommerce-bookings' ) );

$statuses = array_unique( array_merge( get_wc_booking_statuses( null, true ), get_wc_booking_statuses( 'user', true ), get_wc_booking_statuses( 'cancel', true ) ) );

foreach ( WC_Bookings_Admin::get_booking_products() as $bookable_product ) {
	$bookable_products[ $bookable_product->get_id() ] = $bookable_product->get_name();

	$resources = $bookable_product->get_resources();

	foreach ( $resources as $resource ) {
		$bookable_products[ $bookable_product->get_id() . '=>' . $resource->get_id() ] = '&nbsp;&nbsp;&nbsp;' . $resource->get_name();
	}
}
?>
<form name="post" action="" method="post" id="post">
    <div class="panel-wrap woocommerce">
        <div id="booking_data" class="panel">
            <div class="booking_data_column">
                <h4><?php _e( 'General details', 'woocommerce-bookings' ); ?></h4>
                <p class="form-field form-field-wide">
                    <label for="_booking_status"><?php _e( 'Booking status:', 'woocommerce-bookings' ); ?></label>
                    <select id="_booking_status" name="_booking_status" class="wc-enhanced-select">
						<?php
						foreach ( $statuses as $key => $value ) {
							echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
						}
						?>
                    </select>
                </p>
            </div>
            <div class="booking_data_column">
                <h4><?php _e( 'Booking specification', 'woocommerce-bookings' ); ?></h4>

				<?php
				woocommerce_wp_select( array(
					'id'            => 'product_or_resource_id',
					'class'         => 'wc-enhanced-select',
					'wrapper_class' => 'form-field form-field-wide',
					'label'         => __( 'Booked product:', 'woocommerce-bookings' ),
					'options'       => $bookable_products,
					'value'         => ! empty( $product ) ? $product->get_id() : null,
				) );
				?>

            </div>
            <div class="booking_data_column">
                <h4><?php _e( 'Booking date &amp; time', 'woocommerce-bookings' ); ?></h4>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => 'booking_start_date',
						'label'       => __( 'Start date:', 'woocommerce-bookings' ),
						'placeholder' => 'yyyy-mm-dd',
						'value'       => $start->format( 'Y-m-d' ),
						'class'       => 'date-picker-field',
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => 'booking_end_date',
						'label'       => __( 'End date:', 'woocommerce-bookings' ),
						'placeholder' => 'yyyy-mm-dd',
						'value'       => $end->format( 'Y-m-d' ),
						'class'       => 'date-picker-field',
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_booking_all_day',
						'label'       => __( 'All day booking:', 'woocommerce-bookings' ),
						'description' => __( 'Check this box if the booking is for all day.', 'woocommerce-bookings' ),
						'value'       => $all_day,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => 'booking_start_time',
						'label'       => __( 'Start time:', 'woocommerce-bookings' ),
						'placeholder' => 'hh:mm',
						'value'       => $start->format( 'H:i' ),
						'type'        => 'time',
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => 'booking_end_time',
						'label'       => __( 'End time:', 'woocommerce-bookings' ),
						'placeholder' => 'hh:mm',
						'value'       => $end->format( 'H:i' ),
						'type'        => 'time',
					)
				);

				?>
            </div>
        </div>
        <div class="clear"></div>
        <input type="submit" class="button save_order button-primary tips" name="save"
               value="<?php esc_html_e( 'Save Booking', 'woocommerce-bookings' ); ?>"
               data-tip="<?php esc_html_e( 'Save/update the booking', 'woocommerce-bookings' ); ?>"/>
    </div>
</form>

<script type='text/javascript'>
/* <![CDATA[ */
(function( $ ) {
    'use strict';

    $(function () {
        $('#post').submit(function( event ) {
            debugger;
            tb_remove();
            calendar.refetchEvents();
            event.preventDefault();
            });
    });
})( jQuery );
/* ]]> */
</script>
