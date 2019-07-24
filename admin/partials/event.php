<?php
/**
 * Create event or event details.
 *
 * @global \WC_Booking $booking
 */

//phpcs:disable Generic.WhiteSpace.DisallowSpaceIndent

$bookable_products = array( '' => __( 'N/A', 'woocommerce-bookings' ) );

$statuses   = array_unique( array_merge( get_wc_booking_statuses( null, true ), get_wc_booking_statuses( 'user', true ), get_wc_booking_statuses( 'cancel', true ) ) );
$order      = $booking->get_order();
$customer   = $booking->get_customer();
$product_id = $booking->get_product_id( 'edit' );
$product    = $booking->get_product( $product_id );

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
	    <?php
	    do_action( 'woo_booking_extensions_event_page_before', $booking );
	    ?>
        <div id="booking_data" class="panel wbe-booking-data-thickbox">
            <div class="booking_data_column">
                <h4><?php esc_html_e( 'General details', 'woocommerce-bookings' ); ?></h4>

                <p class="form-field form-field-wide">
                    <label for="_booking_order_id"><?php esc_html_e( 'Order ID:', 'woocommerce-bookings' ); ?></label>
                    <select name="_booking_order_id" id="_booking_order_id"
                            data-placeholder="<?php esc_html_e( 'N/A', 'woocommerce-bookings' ); ?>" data-allow_clear="true">
			            <?php if ( $booking->get_order_id() && $order ) : ?>
                            <option selected="selected"
                                    value="<?php echo esc_attr( $booking->get_order_id() ); ?>"><?php echo esc_html( $order->get_order_number() . ' &ndash; ' . date_i18n( wc_date_format(), strtotime( is_callable( array(
							            $order,
							            'get_date_created'
						            ) ) ? $order->get_date_created() : $order->post_date ) ) ); ?></option>
			            <?php endif; ?>
                    </select>
                </p>
                <p class="form-field form-field-wide">
                    <label for="_booking_status"><?php esc_html_e( 'Booking status:', 'woocommerce-bookings' ); ?></label>
                    <select id="_booking_status" name="_booking_status" class="wc-enhanced-select">
						<?php
						foreach ( $statuses as $key => $value ) {
							echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $booking->get_status(), false ) . '>' . esc_html( $value ) . '</option>';
						}
						?>
                    </select>
                </p>

            <p class="form-field form-field-wide">
                <label for="_booking_customer_id"><?php esc_html_e( 'Customer:', 'woocommerce-bookings' ); ?></label>
		        <?php
		        $name              = ! empty( $customer->name ) ? ' &ndash; ' . $customer->name : '';
		        $guest_placeholder = __( 'Guest', 'woocommerce-bookings' );
		        if ( 'Guest' === $name ) {
			        /* translators: 1: guest name */
			        $guest_placeholder = sprintf( _x( 'Guest (%s)', 'Admin booking guest placeholder', 'woocommerce-bookings' ), $name );
		        }

		        if ( $booking->get_customer_id() ) {
			        $user            = get_userdata( $booking->get_customer_id() );
			        $customer_string = sprintf(
			            /* translators: 1: full name 2: user id 3: email */
				        esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce-bookings' ),
				        trim( $user->first_name . ' ' . $user->last_name ),
				        $customer->user_id,
				        $customer->email
			        );
		        } else {
			        $customer_string = '';
		        }
		        ?>
		        <?php if ( version_compare( WC_VERSION, '3.0', '<' ) ) : ?>
                    <input type="hidden" name="_booking_customer_id" id="_booking_customer_id" class="wc-customer-search" value="<?php echo esc_attr( $booking->get_customer_id() ); ?>" data-selected="<?php echo esc_attr( $customer_string ); ?>" data-placeholder="<?php echo esc_attr( $guest_placeholder ); ?>" data-allow_clear="true" />
		        <?php else : ?>
                    <select name="_booking_customer_id" id="_booking_customer_id" class="wc-customer-search" data-placeholder="<?php echo esc_attr( $guest_placeholder ); ?>" data-allow_clear="true">
				        <?php if ( $booking->get_customer_id() ) : ?>
                            <option selected="selected" value="<?php echo esc_attr( $booking->get_customer_id() ); ?>"><?php echo esc_attr( $customer_string ); ?></option>
				        <?php endif; ?>
                    </select>
		        <?php endif; ?>
            </p>
            <p class="form-field form-field-wide">
                <label for="booking_guest_name"><?php esc_html_e( 'Guest name:', 'woocommerce-booking-extensions' ); ?></label>
                <input type="text" style="" name="booking_guest_name" id="booking_guest_name" value="<?php echo esc_attr( $booking->get_meta( 'booking_guest_name' ) ); ?>"
                       placeholder="N/A">
            </p>
        </div>
            <div class="booking_data_column">
                <h4><?php esc_html_e( 'Booking specification', 'woocommerce-bookings' ); ?></h4>

				<?php
				woocommerce_wp_select(
                    array(
					    'id'            => 'product_or_resource_id',
					    'class'         => 'wc-enhanced-select',
					    'wrapper_class' => 'form-field form-field-wide',
					    'label'         => __( 'Booked product:', 'woocommerce-bookings' ),
					    'options'       => $bookable_products,
					    'value'         => $booking->get_product_id(),
				    )
                );
				?>
                <?php
                $person_counts = $booking->get_person_counts();

                echo '<br class="clear" />';
                echo '<h4>' . esc_html__( 'Person(s)', 'woocommerce-bookings' ) . '</h4>';

                $person_types = $product ? $product->get_person_types() : array();

                if ( count( $person_counts ) > 0 || count( $person_types ) > 0 ) {
	                $needs_update = false;

	                foreach ( $person_counts as $person_id => $person_count ) {
		                $person_type = null;

		                try {
			                $person_type = new WC_Product_Booking_Person_Type( $person_id );
		                } catch ( Exception $e ) {
			                // This person type was deleted from the database.
			                unset( $person_counts[ $person_id ] );
			                $needs_update = true;
		                }

		                if ( $person_type ) {
			                woocommerce_wp_text_input(
                                array(
				                    'id'            => '_booking_person_' . $person_id,
				                    'label'         => $person_type->get_name(),
				                    'type'          => 'number',
				                    'placeholder'   => '0',
				                    'value'         => $person_count,
				                    'wrapper_class' => 'booking-person',
			                    )
                            );
		                }
	                }

	                if ( $needs_update ) {
		                $booking->set_person_counts( $person_counts );
		                $booking->save();
	                }

	                $product_booking_diff = array_diff( array_keys( $person_types ), array_keys( $person_counts ) );

	                foreach ( $product_booking_diff as $pid ) {
		                $person_type = $person_types[ $pid ];
		                woocommerce_wp_text_input(
                            array(
                                'id'            => '_booking_person_' . $person_type->get_id(),
			                    'label'         => $person_type->get_name(),
			                    'type'          => 'number',
			                    'placeholder'   => '0',
			                    'value'         => '0',
			                    'wrapper_class' => 'booking-person',
		                    )
                        );
	                }
                } else {
	                $person_counts = $booking->get_person_counts();
	                $person_type   = new WC_Product_Booking_Person_Type( 0 );

	                woocommerce_wp_text_input(
                        array(
		                    'id'            => '_booking_person_0',
		                    'label'         => $person_type->get_name(),
		                    'type'          => 'number',
		                    'placeholder'   => '0',
		                    'value'         => ! empty( $person_counts[0] ) ? $person_counts[0] : 0,
		                    'wrapper_class' => 'booking-person',
	                    )
                    );
                }
                ?>
            </div>
            <div class="booking_data_column">
                <h4><?php esc_html_e( 'Booking date &amp; time', 'woocommerce-bookings' ); ?></h4>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => 'booking_start_date',
						'label'       => __( 'Start date:', 'woocommerce-bookings' ),
						'placeholder' => 'yyyy-mm-dd',
						'value'       => date( 'Y-m-d', $booking->get_start( 'edit' ) ),
						'class'       => 'date-picker-field',
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => 'booking_end_date',
						'label'       => __( 'End date:', 'woocommerce-bookings' ),
						'placeholder' => 'yyyy-mm-dd',
						'value'       => date( 'Y-m-d', $booking->get_end( 'edit' ) ),
						'class'       => 'date-picker-field',
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_booking_all_day',
						'label'       => __( 'All day booking:', 'woocommerce-bookings' ),
						'description' => __( 'Check this box if the booking is for all day.', 'woocommerce-bookings' ),
						'value'       => $booking->get_all_day( 'edit' ) ? 'yes' : 'no',
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => 'booking_start_time',
						'label'       => __( 'Start time:', 'woocommerce-bookings' ),
						'placeholder' => 'hh:mm',
						'value'       => date( 'H:i', $booking->get_start( 'edit' ) ),
						'type'        => 'time',
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => 'booking_end_time',
						'label'       => __( 'End time:', 'woocommerce-bookings' ),
						'placeholder' => 'hh:mm',
						'value'       => date( 'H:i', $booking->get_end( 'edit' ) ),
						'type'        => 'time',
					)
				);

				?>
            </div>
        </div>
        <div class="clear"></div>
        <?php
        do_action( 'woo_booking_extensions_event_page_after', $booking );
        ?>
        <div>
            <input type="hidden" name="_booking_id" id="_booking_id" value="<?php echo esc_attr( $booking->get_id() ); ?>"/>
            <input type="submit" class="button save_order button-primary tips" name="save"
                   value="<?php esc_html_e( 'Save Booking', 'woocommerce-bookings' ); ?>"
                   data-tip="<?php esc_html_e( 'Save/update the booking', 'woocommerce-bookings' ); ?>"/>
        </div>
        <div class="clear"></div>
        <div class="booking-changed-data">
            <?php
            if ( ! empty( $booking->get_date_created() ) ) :
                ?>
            <div class="booking-changed-column">
                <p>
                    <?php esc_html_e( 'Date created:', 'woocommerce-bookings-extensions' ); ?><br>
                    <?php echo esc_html( date_i18n( wc_date_format() . ' ' . wc_time_format(), $booking->get_date_created() ) ); ?>
                </p>
            </div>
                <?php
            endif;
            if ( ! empty( $booking->get_date_modified() ) ) :
                ?>
            <div class="booking-changed-column">
                <p>
                    <?php esc_html_e( 'Date modified:', 'woocommerce-bookings-extensions' ); ?><br>
                    <?php echo esc_html( date_i18n( wc_date_format() . ' ' . wc_time_format(), $booking->get_date_modified() ) ); ?>
                </p>
            </div>
                <?php
            endif;
            if ( ! empty( $booking->get_meta( '_booking_created_user_id' ) ) ) :
                $created_by = get_userdata( $booking->get_meta( '_booking_created_user_id' ) );
                if ( $created_by ) :
	                ?>
            <div class="booking-changed-column">
                <p>
	                <?php esc_html_e( 'User created:', 'woocommerce-bookings-extensions' ); ?><br>
	                <?php echo esc_html( $created_by->first_name . ' ' . $created_by->last_name ); ?>
                </p>
            </div>
                    <?php
                endif;
            endif;
            if ( ! empty( $booking->get_meta( '_booking_modified_user_id' ) ) ) :
                $modified_by = get_userdata( $booking->get_meta( '_booking_modified_user_id' ) );
                if ( $modified_by ) :
                    ?>
            <div class="booking-changed-column">
                <p>
		            <?php esc_html_e( 'User modified:', 'woocommerce-bookings-extensions' ); ?><br>
                    <?php echo esc_html( $modified_by->first_name . ' ' . $modified_by->last_name ); ?>
                </p>
            </div>
                    <?php
                endif;
            endif;
            ?>
        </div>
    </div>
</form>

<script type='text/javascript'>
/* <![CDATA[ */
(function( $ ) {
    'use strict';

    $(function () {
        $( document.body ).trigger('wc-enhanced-select-init');

        $('#post').submit(function( event ) {
            var d = new Date();
            if( $(this).find('input[name=_booking_all_day]').is(':checked') ) {
                $(this).find('input[name=booking_start_time]').val('00:00');
                $(this).find('input[name=booking_end_time]').val('00:00');
            }
            var start = new Date( $(this).find('input[name=booking_start_date]').val() + 'T' + $(this).find('input[name=booking_start_time]').val() + ':00Z' );
            var end = new Date( $(this).find('input[name=booking_end_date]').val() + 'T' + $(this).find('input[name=booking_end_time]').val() + ':00Z' );
            start.setTime( start.getTime() + d.getTimezoneOffset() * 60000 );
            end.setTime( end.getTime() + d.getTimezoneOffset() * 60000 );
            var formdata = $(this).serializeArray();
            var data = {};
            $(formdata ).each(function(index, obj){
                data[obj.name] = obj.value;
            });
            var extendedData = {
                '_ajax_nonce': fullcalendarOptions.events.nonce,
                'order_id': $(this).find('select[name=_booking_order_id]').val(),
                'customer_id': $(this).find('select[name=_booking_customer_id]').val(),
                'start': start.toISOString(),
                'end': end.toISOString(),
                'allDay': $(this).find('input[name=_booking_all_day]').is(':checked'),
                'resource': $(this).find('select[name=product_or_resource_id]').val(),
                'persons': $(this).find('input[name=_booking_person_0]').val(),
                'guest_name': $(this).find('input[name=booking_guest_name]').val(),
                'booking_status': $(this).find('select[name=_booking_status]').val(),
                'id': $(this).find('input[name=_booking_id]').val(),
            }
            $.extend( data, extendedData );
            $.ajax({
                type: 'POST',
                url: fullcalendarOptions.events.wptargetUrl,
                data: data,
                success: function (data) {
                    calendar.refetchEvents();
                    tb_remove();
                },
                error: function (jqXHR, textStatus, errorThrown) {

                },
                complete: function() {
                }
            });
            event.preventDefault();
        });

        $( '#_booking_all_day' ).change( function () {
            if ( $( this ).is( ':checked' ) ) {
                $( '#booking_start_time, #booking_end_time' ).closest( 'p' ).hide();
            } else {
                $( '#booking_start_time, #booking_end_time' ).closest( 'p' ).show();
            }
        }).change();

        $( '.date-picker-field' ).datepicker({
            dateFormat: 'yy-mm-dd',
            firstDay: ". get_option( 'start_of_week' ) .",
            numberOfMonths: 1,
            showButtonPanel: true,
        });

        $( '#_booking_order_id' ).filter( ':not(.enhanced)' ).each( function() {
            var select2_args = {
                allowClear:  true,
                placeholder: $( this ).data( 'placeholder' ),
                minimumInputLength: 1,
                escapeMarkup: function( m ) {
                    return m;
                },
                ajax: {
                    url:         '<?php echo admin_url( 'admin-ajax.php' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>',
                    dataType:    'json',
                    quietMillis: 250,
                    data: function( params ) {
                        return {
                            term:     params.term,
                            action:   'wc_bookings_json_search_order',
                            security: '<?php echo wp_create_nonce( 'search-booking-order' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>'
                        };
                    },
                    processResults: function( data ) {
                        var terms = [];
                        if ( data ) {
                            $.each( data, function( id, text ) {
                                terms.push({
                                    id: id,
                                    text: text
                                });
                            });
                        }
                        return {
                            results: terms
                        };
                    },
                    cache: true
                },
                multiple: false
            };
            $( this ).select2( select2_args ).addClass( 'enhanced' );
        });
    });
})( jQuery );
/* ]]> */
</script>
