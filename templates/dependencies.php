<?php
/**
 * Linked product options.
 *
 * @package WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="options_group show_if_booking">
	<p class="form-field">
		<label for="grouped_products"><?php esc_html_e( 'Dependent products', 'woocommerce' ); ?></label>
		<select class="wc-product-search" multiple="multiple" style="width: 50%;" id="dependent_products" name="dependent_products[]" data-sortable="true" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-exclude="<?php echo intval( $post->ID ); ?>">

		</select> <?php echo wc_help_tip( __( 'This lets you choose which products are dependent on this product.', 'woocommerce' ) ); // WPCS: XSS ok. ?>
	</p>
</div>
