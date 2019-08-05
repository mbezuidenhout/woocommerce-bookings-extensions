document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById( fullcalendarOptions.elementId );

    var calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: [ 'dayGrid', 'timeGrid' ],
        defaultDate: fullcalendarOptions.defaultDate,
        schedulerLicenseKey: fullcalendarOptions.schedulerLicenseKey,
        allDaySlot: true,
        eventLimit: true, // allow "more" link when too many events
        nowIndicator: true,
        navLinks: true,
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
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridDay'
        },

        eventSources: [
            {
                url: fullcalendarOptions.events.sourceUrl,
                method: 'POST',
                extraParams: {
                    _ajax_nonce: fullcalendarOptions.events.nonce,
                    product_id: fullcalendarOptions.events.productId,
                },
            }
        ],
    });

    calendar.render();
});