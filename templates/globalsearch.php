<?php
/**
 * Bookings search.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-bookings-extensions/globalsearch.php
 *
 * @version 1.0.0
 * @since   1.2.0
 *
 * @see WC_Bookings_Extensions_Public::global_search_shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$nonce = wp_create_nonce( 'search-bookings' );

/** @var WC_Bookings_Extensions_Bookings_Search $bookings_search_form */
?>
<form class="cart" method="post" enctype='multipart/form-data' data-nonce="<?php echo esc_attr( $nonce ); ?>">

	<div id="wc-bookings-booking-form" class="wc-bookings-booking-form wc-bookings-search">

		<?php $bookings_search_form->output(); ?>

		<div class="wc-booking-extensions-search-result" style="display:none">
			<ul class="wc-booking-extensions-result-list">

			</ul>
		</div>
	</div>
</form>
