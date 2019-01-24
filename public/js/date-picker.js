/* globals: jQuery, wc_bookings_booking_form, booking_form_params */
// globally accessible for tests
wc_bookings_extensions_date_picker = {};

jQuery( function( $ ) {
	var wc_bookings_locale			 = window.navigator.userLanguage || window.navigator.language,
		wc_bookings_date_picker_object  = {
			init: function() {
				$( '.wc_bookings_field_date' ).each( function() {
					$( function() {
						$( "#search-datepicker" ).datepicker( wc_bookings_extensions_date_picker_args.datepicker_args );
					} );

				} );
			},
		};

	moment.locale( wc_bookings_locale );

	// Replace jQuery date format with momentjs
	$.datepicker.parseDate = function(format, value) {
		var date = moment(value, format).toDate();
		if( 'Invalid Date' == date )
			return '';
		return date;
	};
	$.datepicker.formatDate = function (format, value) {
		return moment(value).format(format);
	};

	// export globally
	wc_bookings_extensions_date_picker = wc_bookings_date_picker_object;
	wc_bookings_extensions_date_picker.init();
});
