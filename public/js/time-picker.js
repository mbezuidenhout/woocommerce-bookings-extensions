jQuery(document).ready(function($) {
	var local_timezone = moment.tz.guess() || booking_form_params.server_timezone;

	if ( booking_form_params.timezone_conversion ) {
		$( '.wc-bookings-date-picker-timezone' ).text( local_timezone.replace( '_', ' ' ) );
		$( '[name="wc_bookings_field_start_date_local_timezone"]' ).val( local_timezone );
	}

	$('.block-picker').on( 'click', 'a', function() {
		var value        = $(this).data( 'value' );
		var block_picker = $(this).closest( 'ul' );

		set_selected_time( block_picker, value );
		return false;
	});

	function set_selected_time( block_picker, value ) {
		var submit_button = block_picker.closest( 'form' ).find( '.wc-bookings-booking-form-button' );
		if ( undefined === value ) {
			submit_button.addClass( 'disabled' );
			return;
		}

		var selected_block = block_picker.find( '[data-value="' + value + '"]' );

		if ( undefined === selected_block.data( 'value' ) ) {
			submit_button.addClass( 'disabled' );
			return;
		}

		var target = block_picker.closest( 'div' ).find( 'input' );

		target.val( value ).change();
		block_picker.closest( 'ul' ).find( 'a' ).removeClass( 'selected' );
		selected_block.addClass( 'selected' );
		submit_button.removeClass( 'disabled' );
	}

	$('#wc_bookings_field_resource, select.wc_bookings_field_duration').change( function() {
		show_available_time_blocks( this );
	});
	$('.wc-bookings-booking-form fieldset').on( 'date-selected', function() {
		show_available_time_blocks( this );
	});

	var xhr;

	function show_available_time_blocks( element ) {
		var $form               = $(element).closest( 'form' );
		var fieldset            = $(element).closest( 'div' ).find( 'fieldset' )
		var block_picker        = $(element).closest( 'div' ).find( '.block-picker' );
		var selected_block      = block_picker.find( '.selected' );

		var year_str = fieldset.find( 'input.booking_date_year' ).val();
		var year  = parseInt( year_str, 10 );
		var month_str = fieldset.find( 'input.booking_date_month' ).val();

		var month  = parseInt( month_str, 10 );
		var day_str = fieldset.find( 'input.booking_date_day' ).val();
		var day  = parseInt( day_str, 10 );

		var date_str =  year_str + '-' + month_str + '-' + day_str;

		if ( ! year || ! month || ! day ) {
			return;
		}

		// clear blocks
		block_picker.closest( 'div' ).find( 'input' ).val( '' ).change();
		block_picker.closest( 'div' ).block( {message: null, overlayCSS: { background: '#fff', backgroundSize: '16px 16px', opacity: 0.6 }} ).show();

		// Get blocks via ajax
		if ( xhr ) xhr.abort();

		var form_val = $form.serialize();

		/*
		 * Get previous/next day in addition to current day based on server/client timezone offset.
		 * This will give the client enough blocks to fill out 24 hours of blocks in its timezone.
		 */
		var server_offset  = get_client_server_timezone_offset_hrs( date_str );
		if ( server_offset < 0 ) {
			form_val += '&get_next_day=true';
		} else if ( server_offset > 0 ) {
			form_val += '&get_prev_day=true';
		}

		xhr = $.ajax({
			type: 		'POST',
			url: 		booking_form_params.ajax_url,
			data: 		{
				action: 'wc_bookings_get_blocks',
				form:   form_val
			},
			success: function( code ) {
				block_picker.html( code );
				resize_blocks();
				offset_block_times( date_str );
				block_picker.closest( 'div' ).unblock();
				set_selected_time( block_picker, selected_block.data( 'value' ) );
			},
			dataType: 	"html"
		});
	}

	function resize_blocks() {
		var max_width  = 0;
		var max_height = 0;

		$('.block-picker a').each( function() {
			var width  = $(this).width();
			var height = $(this).height();
			if ( width > max_width ) {
				max_width = width;
			}
			if ( height > max_height ) {
				max_height = height;
			}
		});

		$('.block-picker a').width( max_width );
		$('.block-picker a').height( max_height );
	}

	function offset_block_times( date_str ) {
		if ( ! booking_form_params.timezone_conversion ) {
			return;
		}

		var from = moment.tz( date_str, local_timezone );
		var to = moment( from );
		to.add( 1, 'days' );

		$( '.block-picker .block a' ).each( function() {
			var block_time  = $( this ).attr( 'data-value' ); // iso8061 format time string
			var server_offset = get_client_server_timezone_offset_hrs( date_str );
			var server_local_time = moment.tz( block_time, booking_form_params.server_timezone );
			var client_local_time = moment.tz( block_time, booking_form_params.server_timezone );
			client_local_time.add( server_offset, 'hours' );

			if ( server_local_time.isBefore( from ) || to.isBefore( server_local_time ) ) {
				// Delete any blocks outside of today
				$( this ).parent().remove();
			} else {
				var local_time_str = client_local_time.format( booking_form_params.server_time_format );
				$( this ).attr( 'title', 'Store server time: ' + server_local_time.format( 'YYYY-MM-DD h:mm A' ) );
				$( this ).text( client_local_time.format( booking_form_params.server_time_format ) );
			}
		});

		var server_offset  = get_client_server_timezone_offset_hrs( date_str );

	}
});
