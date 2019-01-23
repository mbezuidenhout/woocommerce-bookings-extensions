=== WooCommerce Bookings Extensions ===
Contributors: mbezuidenhout
Tags: bookings
Requires PHP: 5.6
Requires at least: 3.0.1
Tested up to: 5.0.3
Stable tag: 1.2.1
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

== Frequently Asked Questions ==

= Why doesn't Adjacent Buffering work? =

By enabling this plugin it disables the adjacent buffering feature.

= Why doesn't my global search shortcode work? =

Check your parameters passed to the shortcode ex. [wcbooking_search duration_unit="minute" duration="60"]
This will only search through bookings that has a duration unit of 60 minutes.
The search shortcode cannot be used on the same page as a product booking.