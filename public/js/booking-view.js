/* globals: jQuery, booking_view_params */
jQuery( function( $ ) {
	var localTime = new Date();
	var serverTime = new Date( booking_view_params.server_unix_time * 1000 );
	var timeDiff = serverTime - localTime;
	var xhr = [];

	var updateTime = function( firstRun ) {
		var currentTime = new Date();
		currentTime.setTime( currentTime.getTime() + timeDiff );
		$('#current-time').text( moment( currentTime.getTime() ).format( booking_view_params.time_format ) );
		$('#current-date').text( moment( serverTime.getTime() ).format( booking_view_params.date_format ) );
		if( firstRun ) {
			setInterval( updateTime, 60000 ); // Run every subsequent call on the minute basis
		}
	};
	var updateBooking = function() {
		if ( typeof xhr['booking'] !== 'undefined' && xhr['booking'].hasOwnProperty( 'abort' ) ) {
			xhr['booking'].abort();
		}
		xhr['booking'] = $.ajax({
			type: 'GET',
			url: booking_view_params.ajax_url,
			data: {
				'product_id': booking_view_params.product_id,
				'username': booking_view_params.username,
				'password': booking_view_params.password,
				'range': 'now-next',
			},
			success: function ( data ) {
				if ( data.hasOwnProperty( booking_view_params.product_id ) ) {
					var booking = data[booking_view_params.product_id];
					if ( booking.hasOwnProperty( 'now' ) && booking.now.hasOwnProperty( 'organizer' ) ) {
						$('#current-status').text( booking_view_params.text.in_use );
						$('#current-booking-title').text( booking.now.organizer );
						$('#current-booking-end').text( moment.utc( booking.now.unix_end_time * 1000 ).format( booking_view_params.time_format ) );
					} else {
						$('#current-status').text( booking_view_params.text.available );
						$('#current-booking-title').text('');
						$('#current-booking-end').text('');
					}
					if ( booking.hasOwnProperty( 'next' ) && booking.next.hasOwnProperty( 'unix_start_time' ) ) {
						$('#next-booking-date').text( moment.utc( booking.next.unix_start_time * 1000 ).format( booking_view_params.date_format ) );
						$('#next-booking-time').text( moment.utc( booking.next.unix_start_time * 1000 ).format( booking_view_params.time_format ) );
					} else {
						$('#next-booking-date').text('');
						$('#next-booking-time').text('');
					}

				}
			},
			error: function () {

			},
			dataType: "json"
		});
	};

	$.fn.disableSelection = function() {
		return this
			.attr('unselectable', 'on')
			.css({'-moz-user-select':'-moz-none',
				'-moz-user-select':'none',
				'-o-user-select':'none',
				'-khtml-user-select':'none',
				'-webkit-user-select':'none',
				'-ms-user-select':'none',
				'user-select':'none'
			})
			.on('selectstart', false);
	};

	$('.unselectable').disableSelection();

	$('#current-time').text( moment( serverTime.getTime() ).format( booking_view_params.time_format ) );
	$('#current-date').text( moment( serverTime.getTime() ).format( booking_view_params.date_format ) );
	setTimeout( updateTime, (60 - serverTime.getSeconds()) * 1000, true ); // First run on the minute
	setInterval( updateBooking, 60000);
});
