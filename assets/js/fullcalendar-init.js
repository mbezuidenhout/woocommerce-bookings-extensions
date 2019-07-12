document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: [ 'interaction', 'resourceDayGrid', 'resourceTimeGrid' ],
        defaultView: fullcalendarOptions.defaultView,
        defaultDate: fullcalendarOptions.defaultDate,
        schedulerLicenseKey: fullcalendarOptions.schedulerLicenseKey,
        allDaySlot: true,
        editable: true,
        selectable: true,
        eventLimit: true, // allow "more" link when too many events
        //minTime: '08:00:00', // Start at 8am
        //maxTime: '17:00:00', // End at 6pm
        nowIndicator: true,
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
            right: 'resourceTimeGridDay,resourceTimeGridTwoDay,timeGridWeek,dayGridMonth'
        },
        views: {
            resourceTimeGridTwoDay: {
                type: 'resourceTimeGrid',
                duration: { days: 2 },
                buttonText: '2 days',
            }
        },

        //// uncomment this line to hide the all-day slot
        //allDaySlot: false,

        resources: fullcalendarOptions.resources,
        eventSources: [
            {
                url: fullcalendarOptions.events.sourceUrl,
                method: 'POST',
                extraParams: {
                    _ajax_nonce: fullcalendarOptions.events.nonce
                },
            }
        ],

        select: function(arg) {
            console.log(
                'select',
                arg.startStr,
                arg.endStr,
                arg.resource ? arg.resource.id : '(no resource)'
            );
        },
        dateClick: function(arg) {
            console.log(
                'dateClick',
                arg.date,
                arg.resource ? arg.resource.id : '(no resource)'
            );
        }
    });

    calendar.render();
});