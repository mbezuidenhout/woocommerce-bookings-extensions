(function( $ ) {
    'use strict';

    $(function() {
        var bookingsPricing = $('#bookings_pricing');
        $('<th><a class="tips" data-tip="' + wc_bookings_extensions_product_data.data_tip + '">[?]</a></th>').insertAfter(bookingsPricing.find('table thead tr th:nth-child(7)'));
        $( '.tips' ).tipTip({
            'attribute': 'data-tip',
            'fadeIn': 50,
            'fadeOut': 50,
            'delay': 200
        });

        var updateTable = function() {
            var bookingsPricing = $('#bookings_pricing');
            if ('undefined' !== bookingsPricing) {
                bookingsPricing.find('table tbody tr').each(function () {
                    var columns = $(this).find('td').length;
                    if ( 9 > columns ) {
                        var ruleType = $(this).find('select[name^="wc_booking_pricing_type"]');
                        var ruleNum = ruleType[0].name.slice(ruleType[0].name.indexOf('[') + 1, ruleType[0].name.indexOf(']'));
                        $('<td><input type="checkbox" name="wc_booking_ext_pricing_override[' + ruleNum + ']"></td>').insertAfter($(this).find('td:nth-child(7)'));

                        if ( 0 === $.inArray( parseInt( ruleNum ), wc_bookings_extensions_product_data.ext_override ) ) {
                            $('input[name="wc_booking_ext_pricing_override[' + ruleNum + ']"]').prop('checked', true);
                        }

                        if ('days' == ruleType.val()) {
                            $('input[name="wc_booking_ext_pricing_override[' + ruleNum + ']"]').show();
                        } else {
                            $('input[name="wc_booking_ext_pricing_override[' + ruleNum + ']"]').hide();
                        }

                        ruleType.on('change', function() {
                            if( 'days' == $(this).val()) {
                                $('input[name="wc_booking_ext_pricing_override[' + ruleNum + ']"]').show();
                            } else {
                                $('input[name="wc_booking_ext_pricing_override[' + ruleNum + ']"]').hide();
                            }
                        });
                    }
                });

            }
        }
        updateTable();
        $('#pricing_rows').on( 'change', updateTable );
    });

})( jQuery );