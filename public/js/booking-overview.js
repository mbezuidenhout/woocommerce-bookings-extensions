function OverviewCalendar( calendarId, product_ids ) {
    var $ = jQuery;
    this.products = product_ids;
    this.calendarId = "#" + calendarId;
    this.date = new Date();
    this.moment = moment( this.date.getTime() );
    this.render = function() {
        $(this.calendarId).find(".fc-toolbar .fc-center h2").text( this.moment.format( 'MMMM Y' ) );
        $(this.calendarId).find("tbody").children().remove();

        var cells = '';
        for( var i = 0; i < this.products.length; i++ ) {
            cells += '<td class="wbe-product-cell-' + this.products[i] + '"></td>';
        }

        var cur = this.moment.startOf('month').clone();
        while( cur.month() == this.moment.month() ) {
            $(this.calendarId).find("tbody").append('<tr id="wbe-day-row-' + cur.date() + '"><td>' + cur.date() + ', ' + cur.format('ddd') + '</td>' + cells + '</tr>');
            cur.add(1, 'd');
        }
        for( var i = 0; i < this.products.length; i++ ) {
            var product = { product_id: this.products[i] };
            $.ajax({
                type: 'POST',
                url: calendarOverview.url,
                data: {
                    _ajax_nonce: calendarOverview.nonce,
                    product_id: this.products[i],
                    start: this.moment.startOf('month').toISOString(),
                    end: this.moment.endOf('month').toISOString(),
                },
                dataType: 'json',
                success: $.proxy(function ( product, data) {
                    for( var k = 0; k < data.length; k++ ) {
                        var start = moment(data[k].start);
                        var end = moment(data[k].end);
                        if( ! data[k].hasOwnProperty( 'isExternal' ) || false === data[k].isExternal ) {
                            var dateCrawl = start.clone();
                            while( dateCrawl.date() <= end.date() ) {
                                var dom = $(this.calendarId).find('#wbe-day-row-' + dateCrawl.date()).find('.wbe-product-cell-' + product.product_id);
                                //dom.text( start.date() + ' - ' + end.date() );
                                dom.addClass( 'wbe-date-booked' );
                                dateCrawl.add(1, 'd');
                            }
                        }
                    }
                }, this, product ),
                error: function (jqXHR, textStatus, errorThrown) {

                },
                complete: function() {
                }
            });
        }
    }
    this.monthAdvance = function() {
        this.date.setMonth( this.date.getMonth() + 1 );
        this.moment = this.moment.add(1, 'M');
        this.render();
    }
    this.monthReverse = function() {
        this.date.setMonth( this.date.getMonth() - 1 );
        this.moment = this.moment.subtract(1, 'M');
        this.render();
    }
}

jQuery( function( $ ) {
    'use strict';
    $(function() {
        var cal = new OverviewCalendar( calendarOverview.calendarId, calendarOverview.products );
        $( '#' + calendarOverview.calendarId ).find('.fc-next-button').on('click', function() {
            cal.monthAdvance();
        });
        $( '#' + calendarOverview.calendarId ).find('.fc-prev-button').on('click', function() {
            cal.monthReverse();
        });
        cal.render();
    });
});