jQuery(document).ready(function($) {

	if ( ! window.console ) {
		window.console = {
			log : function(str) {
				// alert(str);
			}
		};
	}

	var xhr = [];

	$('.wc-bookings-extensions-form')
		.on('change', 'input, select', function( e ) {
			var date = $( "#search-datepicker" ).datepicker("getDate");
			if ( '' === date ) {
				return;
			}

			$form = $(this).closest('form');
			var index = $form.index(this);
			var nonce = $form.attr( "data-nonce" );
			if ( typeof xhr[index] !== 'undefined' && xhr[index].hasOwnProperty( 'abort' ) ) {
				xhr[index].abort();
			}

			if ( null != date ) {
				$form.find('.wc-booking-extensions-search-result').block({message: null, overlayCSS: {background: '#fff', backgroundSize: '16px 16px', opacity: 0.6}}).show();
				xhr[index] = $.ajax({
					type: 'POST',
					url: booking_form_params.ajax_url,
					data: {
						action: booking_form_params.action,
						_ajax_nonce: nonce,
						day: date.getDate(),
						month: date.getMonth() + 1,
						year: date.getFullYear(),
						form: $form.serialize()
					},
					success: function (code) {
						if (code.charAt(0) !== '{') {
							console.log(code);
							code = '{' + code.split(/\{(.+)?/)[1];
						}

						result = $.parseJSON(code);

						if ( -1 !== $.inArray(result.result, [ 'SUCCESS', 'ERROR' ] ) ) {
							$form.find('.wc-booking-extensions-result-list').html(result.html);
							$form.find('.wc-booking-extensions-search-result').unblock();
							$form.find('.wc-booking-extensions-result-list a').each( $.proxy(function(key, value) {
								var href = $(value).attr('href');
								if( -1 === href.indexOf('#') ) {
									var params = Object.assign(getUrlVars(this.data), getUrlVars(decodeURIComponent(getUrlVars(this.data).form)));
									var str = encodeURIComponent($.param({
										year: params.year,
										month: params.month,
										day: params.day,
										duration: params.wc_bookings_field_duration,
										persons: params.wc_bookings_field_persons,
									}));
									$(value).attr('href', $(value).attr('href') + '?wc_booking_search_data=' + str);
								}
							}, this) );
						} else {
							$form.find('.wc-booking-extensions-search-result').hide();
							console.log(code);
						}

						$(document.body).trigger('wc_booking_form_changed');
					},
					error: function () {
						$form.find('.wc-booking-extensions-search-result').hide();
						$form.find('.wc-booking-extensions-search-result').unblock();
					},
					dataType: "html"
				});
			}
		})
		.each(function(){
			var button = $(this).closest('form').find('.single_add_to_cart_button');

			button.addClass('disabled');
		});

	if ( 'true' === booking_form_params.pao_pre_30 ) {
		$( '.wc-bookings-extensions-form' ).parent()
			.on( 'updated_addons', function() {
				$( '.wc-bookings-booking-form' ).find( 'input' ).first().trigger( 'change' );
			} );
	}

	$('.wc-bookings-extensions-form, .wc-bookings-booking-form-button').show().removeAttr( 'disabled' );

});

function get_client_server_timezone_offset_hrs( date ) {
	if ( ! booking_form_params.timezone_conversion ) {
		return 0;
	}

	var reference_time = moment( date );
	var client_offset = reference_time.utcOffset();
	reference_time.tz( booking_form_params.server_timezone );
	var server_offset = reference_time.utcOffset();

	return (client_offset - server_offset)/60;
}

var getUrlVars = function (getStr) {
	var vars = [], hash;
	var hashes = getStr.split('&');
	for(var i = 0; i < hashes.length; i++)
	{
		hash = hashes[i].split('=');
		vars.push(hash[0]);
		vars[hash[0]] = hash[1];
	}
	return vars;
}
