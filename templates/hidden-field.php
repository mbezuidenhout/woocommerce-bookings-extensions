<?php
/**
 * Bookings search.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-bookings-extensions/hidden-field.php
 *
 * @version 1.0.0
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<input type="hidden" name="<?php echo $field['name'] ?>" value="<?php echo $field['value'] ?>">