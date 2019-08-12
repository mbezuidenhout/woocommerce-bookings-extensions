var calendar;

function isProductCategoryShown( categories ) {
    var shownCategories = $(".manage-column[id]:not(.hidden)").map(function() {
        if ( this.id === "wbe-uncategorized" ) {
            return this.id;
        }
        return Number(this.id.substring(13)); // Remove wbe-category- from string
    }).get();

    if ( 0 === categories.length && -1 < shownCategories.indexOf( "wbe-uncategorized" ) ) {
        return true;
    }

    for ( var i = 0; i < categories.length; i++ ) {
        if ( -1 !== shownCategories.indexOf( categories[ i ] ) ) {
            return true;
        }
    }

    return false;
}

function calendarRemoveHiddenResources() {
    calendar.refetchResources();

    var calendarResources = calendar.getResources();

    for ( var i = 0; i < calendarResources.length; i++ ) {
        var categories = [];
        if ( calendarResources[i].extendedProps.hasOwnProperty( "categories" ) ) {
            categories = calendarResources[i].extendedProps.categories;
        }
        if ( ! isProductCategoryShown( categories ) ) {
            calendarResources[i].remove();
        }
    }
}

var oldSaveManageColumnsState = window.columns.saveManageColumnsState;
window.columns.saveManageColumnsState = function( ) {
    calendarRemoveHiddenResources();
    oldSaveManageColumnsState.call(window.columns);
}

document.addEventListener("DOMContentLoaded", function() {
    var calendarEl = document.getElementById("calendar");
    var xhr = [];

    function eventMove ( info ) {
        if (!confirm(fullcalendarOptions.confirmMoveMessage)) {
            info.revert();
        } else {
            var eventEnd = info.event.end;
            if (null === eventEnd && false === info.event.allDay) {
                eventEnd = new Date(info.event.start.getTime() + 3600000);
            }
            var params = {
                "_ajax_nonce": fullcalendarOptions.events.nonce,
                "id": info.event.id,
                "start": info.event.start !== null ? info.event.start.toISOString() : null,
                "end": eventEnd !== null ? eventEnd.toISOString() : null,
                "allDay": info.event.allDay,
            };
            if ( info.hasOwnProperty("newResource") && info.newResource !== null ) {
                params.resource = info.newResource.id;
            }
            xhr["booking"] = $.ajax({
                type: "POST",
                url: fullcalendarOptions.events.wctargetUrl,
                data: params,
                success: function (data) {
                    calendar.refetchEvents();
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

    calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: [ "interaction", "resourceDayGrid", "resourceTimeGrid", "list" ],
        defaultView: fullcalendarOptions.defaultView,
        defaultDate: fullcalendarOptions.defaultDate,
        schedulerLicenseKey: fullcalendarOptions.schedulerLicenseKey,
        allDaySlot: true,
        editable: true,
        selectable: true,
        eventLimit: true, // allow "more" link when too many events
        //minTime: "08:00:00", // Start at 8am
        //maxTime: "17:00:00", // End at 6pm
        nowIndicator: true,
        navLinks: true,
        contentHeight: "auto",
        businessHours: [ // specify an array instead
            {
                daysOfWeek: [ 1, 2, 3, 4, 5 ], // Monday, Tuesday, Wednesday, Thursday, Friday
                startTime: "08:00", // 8am
                endTime: "18:00" // 6pm
            },
            {
                daysOfWeek: [ 6 ], // Saturday
                startTime: "10:00", // 10am
                endTime: "16:00" // 4pm
            },
            {
                daysOfWeek: [ 0 ], // Sunday
                startTime: "13:00", // 1pm
                endTime: "20:00", // 10pm
            }

        ],
        customButtons: {
            addButton: {
                icon: "fc-icon-plus-square",
                click: function() {
                    tb_show( fullcalendarOptions.createEventTitle, fullcalendarOptions.events.eventPageUrl  + "&" + $.param({_wpnonce: fullcalendarOptions.events.nonce}) );
                },
            }
        },
        header: {
            left: "addButton, prev,next today",
            center: "title",
            right: "resourceTimeGridDay,resourceTimeGridTwoDay,timeGridWeek,dayGridMonth,listWeek"
        },
        views: {
            resourceTimeGridTwoDay: {
                type: "resourceTimeGrid",
                duration: { days: 2 },
                buttonText: "2 days",
            },
            listWeek: {
                buttonText: "list",
            }
        },

        resources: fullcalendarOptions.resources,
        eventSources: [
            {
                url: fullcalendarOptions.events.sourceUrl,
                method: "POST",
                extraParams: {
                    _ajax_nonce: fullcalendarOptions.events.nonce
                },
            }
        ],
        eventRender: function( info ) {
            $(info.el).on(
                "click",
                function( event ) {
                    if( $(this).attr("id").length && $(this).attr( "id" ).substring(0, 10) === "wbe-event-" ) {
                        var params = {
                            _wpnonce: fullcalendarOptions.events.nonce,
                            "id": $(this).attr("id").substring(10),
                        };
                        tb_show( fullcalendarOptions.updateEventTitle, fullcalendarOptions.events.eventPageUrl + "&" + $.param(params) );
                    }
                    event.preventDefault();
                }
            );
            if( info.event.id.length ) {
                if( info.event.extendedProps.hasOwnProperty( "isExternal" ) && info.event.extendedProps.isExternal ) {
                    $(info.el).attr("id", "ext-event-" + info.event.id);
                } else {
                    $(info.el).attr("id", "wbe-event-" + info.event.id);
                }
            }
            if( info.event.extendedProps.hasOwnProperty( "resourceCategories" ) && ! isProductCategoryShown( info.event.extendedProps.resourceCategories ) ) {
                $(info.el).addClass( "hidden" );
            } else {
                $(info.el).removeClass( "hidden" );
            }
            var domElementType = "div";
            if(info.view.constructor.name === "DayGridView") {
                domElementType = "span";
            } else if (info.view.constructor.name === "ResourceTimeGridView") {
                // Remove title if in resource view.
                $(info.el).find(".fc-title").remove();
            }
            if ( ! info.event.extendedProps.hasOwnProperty("isExternal") || ! info.event.extendedProps.isExternal ) {
                $(info.el).find(".fc-title").first().before("<" + domElementType + " class=\"wbe-booking-id\">#" + info.event.id + "</" + domElementType + ">");
            }
            if (info.event.extendedProps.hasOwnProperty("bookedBy")) {
                $(info.el).find(".fc-content").first().append("<" + domElementType + " class=\"wbe-booked-by\">Booked by " + info.event.extendedProps.bookedBy + "</" + domElementType + ">");
            }
            if (info.event.extendedProps.hasOwnProperty("bookedFor")) {
                $(info.el).find(".fc-content").first().append("<" + domElementType + " class=\"wbe-booked-for\">Booked for " + info.event.extendedProps.bookedFor + "</" + domElementType + ">");
            }
            if (info.event.extendedProps.hasOwnProperty("persons")) {
                $(info.el).find(".fc-content").first().append("<" + domElementType + "n class=\"wbe-pax\">(" + info.event.extendedProps.persons + " pax)</" + domElementType + ">");
            }
        },
        eventResize: eventMove,
        eventDrop: eventMove,
        select: function( info ) {
            // if( info.resource && confirm(fullcalendarOptions.confirmAddMessage) ) {
            //     // Show add add event pop-over.
            // } else {
            //     calendar.unselect();
            // }
            var params = {
                _wpnonce: fullcalendarOptions.events.nonce,
                "start": info.start !== null ? info.start.toISOString() : null,
                "end": info.end !== null ? info.end.toISOString() : null,
                "allDay": info.allDay,
                "resource": info.hasOwnProperty("resource") && info.resource !== null ? info.resource.id : null,
            };
            tb_show( fullcalendarOptions.createEventTitle, fullcalendarOptions.events.eventPageUrl + "&" + $.param( params ) );
        },
        dateClick: function(arg) {
            console.log(
                "dateClick",
                arg.date,
                arg.resource ? arg.resource.id : "(no resource)"
            );
        },
    });

    calendarRemoveHiddenResources();

    calendar.render();

});