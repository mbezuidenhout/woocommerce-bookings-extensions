( function( $ ) {
	'use strict';
	$(function() {
		var url = $(location).attr('href');
		if ( -1 !== url.indexOf( 'wc_booking_search_data=' ) ) {
			var end = url.length;
			if (-1 !== url.indexOf('&', url.indexOf('wc_booking_search_data=') + 23)) {
				end = url.indexOf('&', url.indexOf('wc_booking_search_data=') + 23);
			}
			var params = getUrlVars(decodeURIComponent(url.substring(url.indexOf('wc_booking_search_data=') + 23), end));

			$('#wc_bookings_field_duration').val(params.duration);
			$('#wc_bookings_field_persons').val(params.persons);

			//$('#wc-bookings-booking-form .wc-bookings-date-picker .booking_date_year')
			$('.wc-bookings-date-picker .picker').attr('data-default_date', params.year + '-' + params.month.lpad('0', 2) + '-' + params.day.lpad('0', 2));
			//$(".wc-bookings-date-picker").find(".picker:eq(0)").datepicker("setDate", params.year + '-' + params.month + '-' + params.day);

			var i = 0;
			$.blockUI.defaults.onUnblock = function (element, options) {
				if ($(element).hasClass('picker')) {
					var cell = $(element).find('.bookable a').not('.ui-priority-secondary').filter(function () {
						return $(this).text() === params.day;
					});
					if (!cell.hasClass('.ui-state-active') && !cell.hasClass('.ui-datepicker-unselectable')) {
						cell.parent().click();
						if (1 === i) {
							$.blockUI.defaults.onUnblock = null;
						}
						i++;
					}
				}
			};
		}
	});
})( jQuery );

var getUrlVars = function(getStr) {
	var vars = [], hash;
	var hashes = getStr.split('&');
	for(var i = 0; i < hashes.length; i++)
	{
		hash = hashes[i].split('=');
		vars.push(hash[0]);
		vars[hash[0]] = hash[1];
	}
	return vars;
};

String.prototype.lpad = function(padString, length) {
	var str = this;
	while (str.length < length)
		str = padString + str;
	return str;
}