=== WooCommerce Bookings Extensions ===
Contributors: mbezuidenhout
Tags: bookings
Requires PHP: 5.6
Requires at least: 4.9
Tested up to: 5.1
Stable tag: 1.6.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds or replaces functionality in the WooCommerce Bookings plugin.

== Description ==

Adds or replaces functionality in the WooCommerce Bookings plugin.

This plugin adds the short code *wcbooking_search* which generates a form with a duration and possibly persons selection field

wcbooking_search duration_unit="{month|day|hour|minute}" duration="<Integer value of unit size>" [method="{include|exclude}" ids="<Comma seperated Llst of product ids>"]

== Installation ==

1. Upload contents of `woocommerce-bookings-extensions.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 1.2.1 =
* Fix: Linked products field in admin section has missing bookable products from options list

= 1.4.0 =
* New: Added global route to show a page for upcoming bookings

= 1.4.1 =
* Fix: Global search form would be blocked without ajax query being sent

= 1.4.2 =
* New: In the admin calendar view for the day show more details than just the order number.

= 1.5.0 =
* New: Added new admin option for overriding block costs for specific days

= 1.6.0 =
* New: Transfer options from global search to product page

= 1.6.2 =
* Fix: Passing data from global search broke calendar.

= 1.6.3 =
* Fix: Check that check in date does not fall before an unbookable date.

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
