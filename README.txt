=== WooCommerce Bookings Extensions ===
Contributors: mbezuidenhout
Tags: bookings, booking, woocommerce, woo-booking-extensions, accommodation, search, calendar
Requires PHP: 5.6
Requires at least: 4.9
Tested up to: 5.4
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Adds or replaces functionality in the WooCommerce Bookings plugin.

== Description ==

Adds or replaces functionality in the WooCommerce Bookings plugin.

This plugin adds the short code *wcbooking_search* which generates a form with a duration and possibly persons selection field

wcbooking_search duration_unit="{month|day|hour|minute}" duration="<Integer value of unit size>" [method="{include|exclude}" ids="<Comma seperated list of product ids>"]

You can link bookable products together. If you are booking out rooms in a house separately or the whole house.

Use the shortcode [wcbooking_calendar] to add a calendar to the page. If used on a product page the product id is automatically added.
Options:
product_id=[product_id]
class="<space seperated list of css classes to add>"
default_view="[view]"
header_left="<comma seperated list of views>"
header_center="<comma seperated list of views>"
header_right="<comma seperated list of views>"

List of possible views:
* dayGridMonth         - Month view
* timeGridWeek         - Time grid of the week
* timeGridDay          - Time grid of the day
* resourceTimeGridDay  - Day time grid separated by resource
* listWeek             - List of events for the week
* title                - Day/Month or Week title

== Installation ==

1. Upload contents of `woocommerce-bookings-extensions.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 1.8.1 =
* Add: Missing files in previous release.

= 1.8.0 =
* Add: User can now select their own calendar color in the user profile section.

= 1.7.14 =
* Add: Show payment method in All Bookings table.
* Fix: Rendering of background events.
* Fix: Color keys for events list in Full Calendar.

= 1.7.13 =
* Add: Show payment method in bookings list.

= 1.7.12 =
* Add: Show status of booking in full calendar view.
* Fix: Don't add color key item if event is external.
* Fix: Show external events as black text of beige background.

= 1.7.11 =
* Fixed: Loads jquery before full calendar view.

= 1.7.10 =
* Add: Loading indicator for full calendar.
* Add: Color keys shown at top of full calendar to show who make the booking.
* Add: User is now blocked from making double bookings.

= 1.7.9 =
* Bugfix: wcbooking_overview would hang while filling the booked dates.

= 1.7.6 =
* Update: Added new options to wcbooking_calendar shortcode.

= 1.7.4 =
* Update: Moved js files out of assets directory.
* Update: Improved layouts for new bookings.

= 1.7.3 =
* New: You can now hide bookings in the calendar view by category.

= 1.7.2 =
* Fix: Check if ics file was downloaded successfully before saving.

= 1.7.1 =
* New: Added support for external ical file. Useful for adding public holidays.
* New: Added colors to booking states.
* New: Administrators can create or modify their bookings from the calendar view.

= 1.7.0 =
* New: Added interactive calendar. Uses the fullcalendar.io JS libraries.

= 1.6.6 =
* Fix: Check for WooCommerce activation.

= 1.6.5 =
* Fix: Current and upcoming bookings not displaying correctly.

= 1.6.3 =
* Fix: Check that check in date does not fall before an unbookable date.

= 1.6.2 =
* Fix: Passing data from global search broke calendar.

= 1.6.0 =
* New: Transfer options from global search to product page

= 1.5.0 =
* New: Added new admin option for overriding block costs for specific days

= 1.4.2 =
* New: In the admin calendar view for the day show more details than just the order number.

= 1.4.1 =
* Fix: Global search form would be blocked without ajax query being sent

= 1.4.0 =
* New: Added global route to show a page for upcoming bookings

= 1.2.1 =
* Fix: Linked products field in admin section has missing bookable products from options list

== Frequently Asked Questions ==

= Why doesn't Adjacent Buffering work? =

By enabling this plugin it disables the adjacent buffering feature.

= Why doesn't my global search shortcode work? =

Check your parameters passed to the shortcode ex. [wcbooking_search duration_unit="minute" duration="60"]
This will only search through bookings that has a duration unit of 60 minutes.
The search shortcode cannot be used on the same page as a product booking.

= How do I display the booking webpage? =

The current and upcoming booking display page can be accessed by using the following pattern:
https://<server>/wc-bookings/fetch?username=<username>&password=<password>&product_id=<product_id>
Where <server> is the hostname of the server.
<username> and <password> is the urlencoded username and password respectively of a user that has permission to view all bookings.
<product_id> is the ID of the product as can be seen while hovering over the product name in the "All products" list
Eg. https://example.com/fetch?username=user%40domain.com&password=p%4055w0rd%5C%21&product_id=900
