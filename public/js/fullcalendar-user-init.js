document.addEventListener('DOMContentLoaded', function() {
    function initCalendar( attrs ) {
        var calendarEl = document.getElementById( attrs.elementId );

        var calendar = new FullCalendar.Calendar(calendarEl, {
            plugins: [ 'resourceDayGrid', 'resourceTimeGrid', 'list' ],
            defaultDate: fullcalendarOptions.defaultDate,
            schedulerLicenseKey: fullcalendarOptions.schedulerLicenseKey,
            allDaySlot: true,
            eventLimit: true, // allow "more" link when too many events
            nowIndicator: true,
            navLinks: false,
            defaultView: attrs.defaultView,
            businessHours: [ // specify an array instead
                {
                    daysOfWeek: [ 1, 2, 3, 4, 5 ], // Monday, Tuesday, Wednesday, Thursday, Friday
                    startTime: '08:00', // 8am
                    endTime: '18:00' // 6pm
                },
                {
                    daysOfWeek: [ 6 ], // Saturday
                    startTime: '10:00', // 10am
                    endTime: '16:00' // 4pm
                },
                {
                    daysOfWeek: [ 0 ], // Sunday
                    startTime: '13:00', // 1pm
                    endTime: '20:00', // 10pm
                }

            ],
            header: {
                left: attrs.headerLeft,
                center: attrs.headerCenter,
                right: attrs.headerRight,
            },
            resources: attrs.resources,
            eventSources: [
                {
                    url: fullcalendarOptions.events.sourceUrl,
                    method: 'POST',
                    extraParams: {
                        _ajax_nonce: fullcalendarOptions.events.nonce,
                        product_id: attrs.productId,
                    },
                }
            ],
            eventRender: function( info ) {
                //info.el.getElementById('fc-time').remove();
                jQuery(info.el).find('.fc-time').remove();
            },
        });

        calendar.render();
    }
    for( var i = 0; i < fullcalendarOptions.calendars.length; i++ ) {
        if( null !== document.getElementById(fullcalendarOptions.calendars[i].elementId) ) {
            initCalendar( fullcalendarOptions.calendars[i] );
        }
    }
});