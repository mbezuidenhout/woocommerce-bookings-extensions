var calendar;
var isCalendarInit = true;

function luminance( r, g, b ) {
	var rgb = [ r, g, b ];
	for ( var i = 0; i < 3; i++ ) {
		rgb[i] = rgb[i] / 255.0;
		if (rgb[i] <= 0.03928) {
			rgb[i] = rgb[i] / 12.92;
		} else {
			rgb[i] = Math.pow( ( ( rgb[i] + 0.055 ) / 1.055), 2.4 );
		}
	}
	return 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2];
}

function hexToRgb(hex) {
	// Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
	var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
	hex = hex.replace(shorthandRegex, function(m, r, g, b) {
		return r + r + g + g + b + b;
	});

	var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
	return result ? {
		r: parseInt(result[1], 16),
		g: parseInt(result[2], 16),
		b: parseInt(result[3], 16)
	} : null;
}

function isProductCategoryShown( categories ) {
	var shownCategories = jQuery( ".manage-column[id]:not(.hidden)" ).map(
		function() {
			if ( this.id === "wbe-uncategorized" ) {
				return this.id;
			}
			return Number( this.id.substring( 13 ) ); // Remove wbe-category- from string.
		}
	).get();

	if ( 0 === categories.length && -1 < shownCategories.indexOf( "wbe-uncategorized" ) ) {
		return true;
	}

	var len = categories.length;
	for ( var i = 0; i < len; i++ ) {
		if ( -1 !== shownCategories.indexOf( categories[ i ] ) ) {
			return true;
		}
	}

	return false;
}

function calendarRemoveHiddenResources() {
	calendar.refetchResources();

	var calendarResources = calendar.getResources();

	var len = calendarResources.length;
	for ( var i = 0; i < len; i++ ) {
		var categories = [];
		if ( calendarResources[i].extendedProps.hasOwnProperty( "categories" ) ) {
			categories = calendarResources[i].extendedProps.categories;
		}
		if ( ! isProductCategoryShown( categories ) ) {
			calendarResources[i].remove();
		}
	}
}

var oldSaveManageColumnsState         = window.columns.saveManageColumnsState;
window.columns.saveManageColumnsState = function( ) {
	calendarRemoveHiddenResources();
	oldSaveManageColumnsState.call( window.columns );
}

document.addEventListener("DOMContentLoaded", function() {
	var calendarEl = document.getElementById( "calendar" );
	var xhr        = [];

	function eventMove ( info ) {
		if ( ! confirm( fullcalendarOptions.confirmMoveMessage ) ) {
			info.revert();
		} else {
			var eventEnd = info.event.end;
			if (null === eventEnd && false === info.event.allDay) {
				eventEnd = new Date( info.event.start.getTime() + 3600000 );
			}
			var params = {
				"_ajax_nonce": fullcalendarOptions.events.nonce,
				"id": info.event.id,
				"start": info.event.start !== null ? info.event.start.toISOString() : null,
				"end": eventEnd !== null ? eventEnd.toISOString() : null,
				"allDay": info.event.allDay,
			};
			if ( info.hasOwnProperty( "newResource" ) && info.newResource !== null ) {
				params.resource = info.newResource.id;
			}
			xhr["booking"] = jQuery.ajax({
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
		eventLimit: true, // allow "more" link when too many events.
		//minTime: "08:00:00", // Start at 8am.
		//maxTime: "17:00:00", // End at 6pm.
		nowIndicator: true,
		navLinks: true,
		contentHeight: "auto",
		businessHours: [ // specify an array instead.
		{
			daysOfWeek: [ 1, 2, 3, 4, 5 ], // Monday, Tuesday, Wednesday, Thursday, Friday.
			startTime: "08:00", // 8am
			endTime: "17:00" // 5pm
		},
			{
				daysOfWeek: [ 6 ], // Saturday.
				startTime: "13:00", // 1pm.
				endTime: "17:00" // 5pm.
			},
//            {
//                daysOfWeek: [ 0 ], // Sunday.
//                startTime: "13:00", // 1pm.
//                endTime: "20:00", // 10pm.
//            }

		],
		customButtons: {
			addButton: {
				icon: "fc-icon-plus-square",
				click: function() {
					tb_show( fullcalendarOptions.createEventTitle, fullcalendarOptions.events.eventPageUrl + "&" + jQuery.param( {_wpnonce: fullcalendarOptions.events.nonce} ) );
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
			jQuery( info.el ).on(
				"click",
				function( event ) {
					if ( jQuery( this ).attr( "id" ).length && jQuery( this ).attr( "id" ).substring( 0, 10 ) === "wbe-event-" ) {
						var params = {
							_wpnonce: fullcalendarOptions.events.nonce,
							"id": jQuery( this ).attr( "id" ).substring( 10 ),
						};
						tb_show( fullcalendarOptions.updateEventTitle, fullcalendarOptions.events.eventPageUrl + "&" + jQuery.param( params ) );
					}
					event.preventDefault();
				}
			);

			if ( info.event.rendering === "" &&
				! ( info.event.extendedProps.hasOwnProperty( "isExternal" ) && info.event.extendedProps.isExternal === true ) ) {
				var legend = jQuery( "#wbe-calendar-legend ul" );

				var legendItemClass = 'wbe-legend-item';
				var createdBy       = info.event.extendedProps.createdBy;
				if ( info.event.extendedProps.createdById === fullcalendarOptions.loggedInUserId ) {
					createdBy += " (You)";
				}

				// With ES6 this can be changed to a string literal like:
				// `<li id="wbe-legend-admin-jQuery{info.event.extendedProps.createdById">jQuery{info.event.extendedPropts.createdBy}</li>`
				if ( jQuery( "#wbe-legend-admin-" + info.event.extendedProps.createdById ).length === 0 ) {
					legend.append( '<li id="wbe-legend-admin-' + info.event.extendedProps.createdById + '" class="' + legendItemClass + '"><span style="background-color:' + info.event.backgroundColor + '">&nbsp;</span>' + createdBy + "</li>" );
				}

				var rgb         = hexToRgb( info.event.backgroundColor );
				var bgLuminance = luminance( rgb.r, rgb.g, rgb.b );
				if ( bgLuminance > 0.179) {
					jQuery( info.el ).find( ".fc-content" ).css( {'color': '#000'} );
				} else {
					jQuery( info.el ).find( ".fc-content" ).css( {'color': '#fff'} );
				}

			}

			if ( info.event.id.length ) {
				if ( info.event.extendedProps.hasOwnProperty( "isExternal" ) && info.event.extendedProps.isExternal ) {
					jQuery( info.el ).attr( "id", "ext-event-" + info.event.id );
				} else {
					jQuery( info.el ).attr( "id", "wbe-event-" + info.event.id );
				}
			}
			if ( info.event.extendedProps.hasOwnProperty( "resourceCategories" ) && ! isProductCategoryShown( info.event.extendedProps.resourceCategories ) ) {
				jQuery( info.el ).addClass( "hidden" );
			} else {
				jQuery( info.el ).removeClass( "hidden" );
			}
			var domElementType = "div";
			if ( info.view.constructor.name === "DayGridView" ) {
				domElementType = "span";
			} else if (info.view.constructor.name === "ResourceTimeGridView") {
				// Remove title if in resource view.
				jQuery( info.el ).find( ".fc-title" ).remove();
			}
			if ( ! info.event.extendedProps.hasOwnProperty( "isExternal" ) || ! info.event.extendedProps.isExternal ) {
				jQuery( info.el ).find( ".fc-title" ).first().before( "<" + domElementType + " class=\"wbe-booking-id\">#" + info.event.id + "</" + domElementType + ">" );
			}
			if ( info.event.extendedProps.hasOwnProperty( "bookedBy" ) ) {
				jQuery( info.el ).find( ".fc-content" ).first().append( "<" + domElementType + " class=\"wbe-booked-by\">Booked by " + info.event.extendedProps.bookedBy + "</" + domElementType + ">" );
			}
			if ( info.event.extendedProps.hasOwnProperty( "bookedFor" ) ) {
				jQuery( info.el ).find( ".fc-content" ).first().append( "<" + domElementType + " class=\"wbe-booked-for\">Booked for " + info.event.extendedProps.bookedFor + "</" + domElementType + ">" );
			}
			if ( info.event.extendedProps.hasOwnProperty( "persons" ) && info.event.extendedProps.persons.length > 0 ) {
				jQuery( info.el ).find( ".fc-content" ).first().append( "<" + domElementType + " class=\"wbe-pax\">(" + info.event.extendedProps.persons + " pax)</" + domElementType + ">" );
			}
			if ( info.event.extendedProps.hasOwnProperty( "status" ) && info.event.extendedProps.status.length > 0 ) {
				jQuery( info.el ).find( ".fc-content" ).first().append( "<" + domElementType + " class=\"wbe-status\">" + info.event.extendedProps.status + "</" + domElementType + ">" );
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
				"resource": info.hasOwnProperty( "resource" ) && info.resource !== null ? info.resource.id : null,
			};
			tb_show( fullcalendarOptions.createEventTitle, fullcalendarOptions.events.eventPageUrl + "&" + jQuery.param( params ) );
		},
		dateClick: function( arg ) {
			console.log(
				"dateClick",
				arg.date,
				arg.resource ? arg.resource.id : "(no resource)"
			);
		},
		// This function gets called before the calendar dom element is put on the page.
		viewSkeletonRender: function( info ) {
			// Add overlay for loading.
			var overlay = document.createElement( "div" );
			overlay.setAttribute( "id", "loading-overlay" );
			if ( isCalendarInit ) {
				overlay.setAttribute( "class", "loading" );
				isCalendarInit = false;
			}
			info.el.appendChild( overlay );
		},
		// This function gets called each time the calender is loading or completed loading data.
		loading: function( isLoading, view ) {
			if ( isLoading ) {
				jQuery( "#loading-overlay" ).addClass( "loading" );
			} else {
				jQuery( "#loading-overlay" ).removeClass( "loading" );
			}
		},
	});

	calendarRemoveHiddenResources();

	calendar.render();

});