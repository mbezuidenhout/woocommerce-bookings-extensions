document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var xhr = [];

    function eventMove ( info ) {
        if (!confirm(fullcalendarOptions.confirmMessage)) {
            info.revert();
        } else {
            var eventEnd = info.event.end;
            if (null === eventEnd && false === info.event.allDay) {
                eventEnd = new Date(info.event.start.getTime() + 3600000);
            }
            xhr['booking'] = $.ajax({
                type: 'POST',
                url: fullcalendarOptions.events.targetUrl,
                data: {
                    '_ajax_nonce': fullcalendarOptions.events.nonce,
                    'id': info.event.id,
                    'start': info.event.start !== null ? info.event.start.toISOString() : null,
                    'end': eventEnd !== null ? eventEnd.toISOString() : null,
                    'allDay': info.event.allDay,
                    'resource': info.hasOwnProperty("newResource") && info.newResource !== null ? info.newResource.id : null,
                },
                success: function (data) {
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    info.revert();
                },
                complete: function() {
                },
                dataType: "json"
            });
        }
    }

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
        eventRender: function( info ) {
            if(info.view.constructor.name === "DayGridView") {
                $(info.el).find(".fc-title").first().before("<span class=\"wbe-booking-id\">#" + info.event.id + "</span>");
                if (info.event.extendedProps.hasOwnProperty("bookedBy")) {
                    $(info.el).find(".fc-content").first().append("<span class=\"wbe-booked-by\">Booked by " + info.event.extendedProps.bookedBy + "</span>");
                }
                if (info.event.extendedProps.hasOwnProperty("bookedFor")) {
                    $(info.el).find(".fc-content").first().append("<span class=\"wbe-booked-for\">Booked for " + info.event.extendedProps.bookedFor + "</span>");
                }
                if (info.event.extendedProps.hasOwnProperty("persons")) {
                    $(info.el).find(".fc-content").first().append("<span class=\"wbe-pax\">(" + info.event.extendedProps.persons + " pax)</span>");
                }

            } else {
                $(info.el).find(".fc-title").first().before("<div class=\"wbe-booking-id\">#" + info.event.id + "</div>");
                if (info.event.extendedProps.hasOwnProperty("bookedBy")) {
                    $(info.el).find(".fc-content").first().append("<div class=\"wbe-booked-by\">Booked by " + info.event.extendedProps.bookedBy + "</div>");
                }
                if (info.event.extendedProps.hasOwnProperty("bookedFor")) {
                    $(info.el).find(".fc-content").first().append("<div class=\"wbe-booked-for\">Booked for " + info.event.extendedProps.bookedFor + "</div>");
                }
                if (info.event.extendedProps.hasOwnProperty("persons")) {
                    $(info.el).find(".fc-content").first().append("<div class=\"wbe-pax\">(" + info.event.extendedProps.persons + " pax)</div>");
                }
                // Remove title if in resource view.
                if (info.view.constructor.name === "ResourceTimeGridView") {
                    $(info.el).find(".fc-title").remove();
                }
            }
        },
        eventResize: eventMove,
        eventDrop: eventMove,
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