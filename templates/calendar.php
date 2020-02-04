<div class="wrap woocommerce">
    <h2><?php _e( 'New Calendar', 'woocommerce-booking-extensions' ); ?></h2>
    <div id="wbe-calendar-legend">
        <ul></ul>
    </div>
    <div id="calendar"></div>
</div>
<div class="hidden">
	<?php
	/**
	 * Get list of product categories in WooCommerce
	 *
	 * @var WP_Term[] $product_categories
	 */
	$product_categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );

	$data_store = WC_Data_Store::load( 'product-booking' );
	/**
	 * Array of WC_Product_Booking.
	 *
	 * @var WC_Product_Booking[] $products Array of WC_Product_Booking.
	 */
	$products = $data_store->get_products(
		array(
			'status' => array( 'publish', 'private' ),
			'limit'  => - 1,
		)
	);

	$screen = get_current_screen();

	$hidden = get_user_option( 'manage' . $screen->id .  'columnshidden' );

	$term_ids = array();

	foreach ( $product_categories as $category ) {
		$term_ids[] = $category->term_id;
		$column_id  = 'wbe-category-' . esc_attr( $category->term_id );
		echo '<div class="manage-column column-' . esc_attr( $column_id ) . ( in_array( $column_id, $hidden, true ) ? ' hidden' : '' ) . '" id="' . $column_id . '">';
		echo '</div>';
	}

	$column_id = 'wbe-uncategorized';
	echo '<div class="manage-column column-' . $column_id . ( in_array( $column_id, $hidden, true ) ? ' hidden' : '' ) . '" id="' . $column_id . '">';
	echo '</div>';

	?>
</div>