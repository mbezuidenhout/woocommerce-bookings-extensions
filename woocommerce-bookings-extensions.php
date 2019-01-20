<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @since             1.0.0
 * @package           Woocommerce_Bookings_Extensions
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Bookings Extensions
 * Plugin URI:        https://github.com/mbezuidenhout/wocommerce-bookings-extensions
 * Description:       Adds or replaces functionality in the WooCommerce Bookings plugin.
 * Version:           1.2.0
 * Author:            Marius Bezuidenhout
 * Author URI:        https://plus.google.com/+MariusBezuidenhout31337
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       woocommerce-bookings-extensions
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOOCOMMERCE_BOOKINGS_EXTENSIONS_VERSION', '1.2.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wc-bookings-extensions-activator.php
 */
function activate_woocommerce_bookings_extensions() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-bookings-extensions-activator.php';
	WC_Bookings_Extensions_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wc-bookings-extensions-deactivator.php
 */
function deactivate_woocommerce_bookings_extensions() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-bookings-extensions-deactivator.php';
	WC_Bookings_Extensions_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woocommerce_bookings_extensions' );
register_deactivation_hook( __FILE__, 'deactivate_woocommerce_bookings_extensions' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-bookings-extensions.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woocommerce_bookings_extensions() {

	$plugin = new WC_Bookings_Extensions();
	$plugin->run();

}
run_woocommerce_bookings_extensions();
