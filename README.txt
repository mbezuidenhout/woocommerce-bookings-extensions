=== WooCommerce Bookings Extensions ===
Contributors: mbezuidenhout
Tags: bookings
Requires PHP: 5.6
Requires at least: 3.0.1
Tested up to: 5.0
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds or replaces functionality in the WooCommerce Bookings plugin.

== Description ==

Adds or replaces functionality in the WooCommerce Bookings plugin.

This plugin adds the short code *wcbooking_search* which generates a form with a duration and possibly persons selection field

wcbooking_search duration_unit="{month|day|hour|minute}" duration="<Integer value of unit size>" [method="{include|exclude}" ids="<Comma seperated Llst of product ids>"]

== Installation ==

1. Upload `woocommerce-bookings-extensions.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Why doesn't Adjacent Buffering work? =

By enabling this plugin it disables the adjacent buffering feature.