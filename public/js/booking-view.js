/* globals: jQuery, booking_view_params */
jQuery( function( $ ) {
	var localTime = new Date();
	var serverTime = new Date( booking_view_params.server_unix_time * 1000 );
	var timeDiff = serverTime - localTime;
	var xhr = [];

	var reloadLocation = function( ) {
		location.reload();
	};
	var updateClock = function( setRunInterval ) {
		var currentTime = new Date();
		currentTime.setTime( currentTime.getTime() + timeDiff );
		$('#current-time').text( moment( currentTime.getTime() ).format( booking_view_params.time_format ) );
		$('#current-date').text( moment( currentTime.getTime() ).format( booking_view_params.date_format ) );

		if ( currentTime.getHours() === 0 && currentTime.getMinutes() === 0 ) {
			setTimeout( reloadLocation, 1000 ); // Reload the window at midnight
		}

		if ( setRunInterval ) {
			setInterval( updateClock, 60000 ); // Run every subsequent call on the minute basis
		}
	};

	var updateDisplay = function( displayObject ) {
		if( false === displayObject.next_booking_container ) {
			$('#next-booking-container').hide();
		} else {
			$('#next-booking-container').show();
		}
		delete displayObject.next_booking_container;
		for( var property in displayObject ) {
			var domId = '#' + property.replace(/_/g,'-');
			var currentValue = $(domId).text();
			if(currentValue !== displayObject[property]) {
				$(domId).text( displayObject[property].toString() );
			}
		}
	};

	var updateBooking = function() {
		var now = new Date();
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
				'version': 2,
				'from': serverTime / 1000 | 0,
				'to': serverTime / 1000 | 0 + 86400, // Get a 24 hour time range
			},
			success: function ( data ) {
				$('#error').hide();
				if( data.hasOwnProperty( 'options' )) {
					localTime = new Date();
					serverTime = new Date( data.options.server_unix_time * 1000 );
					timeDiff = serverTime - localTime;

					console.log( 'Query time is: ' +  (Date.now() - now) / 1000 + ' seconds');

					booking_view_params.date_format = data.options.date_format;
					booking_view_params.time_format = data.options.time_format;
				}
				setTimeout( updateClock, (60 - serverTime.getSeconds()) * 1000, true ); // First run on the minute

				if ( data.hasOwnProperty( 'bookings' ) ) {
					var displayObject = {
						'product_title': '',
						'current_booking_title': '',
						'current_booking_end': '',
						'current_status': booking_view_params.text.available,
						'next_booking_container': false,
						'next_booking_title': '',
						'next_booking_time': '',
						'next_booking_date': '',
					};
					var unix_now_time = serverTime / 1000 | 0;
					for ( var i = 0; i < data.bookings.length; i++ ) {
						var order = data.bookings[i].order;
						var customer = data.bookings[i].customer;

						if ( data.bookings[i].unix_start_time < unix_now_time && data.bookings[i].unix_end_time > unix_now_time ) {
							if(order !== null && order.hasOwnProperty('billing_company') && order.billing_company.length > 0) {
								displayObject.current_booking_title = order.billing_company;
							} else {
								displayObject.current_booking_title = customer.display_name
							}
							displayObject.current_status = booking_view_params.text.in_use;
							displayObject.product_title = data.bookings[i].product_name;
							displayObject.current_booking_end = moment.utc(data.bookings[i].unix_end_time * 1000).format(booking_view_params.time_format);
						} else if ( data.bookings[i].unix_start_time > unix_now_time ) {
							displayObject.next_booking_container = true;
							if(order !== null && order.hasOwnProperty('billing_company') && order.billing_company.length > 0) {
								displayObject.next_booking_title = order.billing_company;
							} else {
								displayObject.next_booking_title = customer.display_name
							}
							var nextBookingTime = moment.utc(data.bookings[i].unix_start_time * 1000);
							displayObject.next_booking_date = nextBookingTime.format( booking_view_params.date_format );
							displayObject.next_booking_time = nextBookingTime.format( booking_view_params.time_format );
							break;
						}
					}

					updateDisplay( displayObject );

				}
			},
			error: function () {
				$('#error').show();
			},
			dataType: "json"
		});
	};

	$.fn.disableSelection = function() {
		return this
			.attr('unselectable', 'on')
			.css({
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

	updateClock( false );
	updateBooking();

	setInterval( updateBooking, 60000);
});
