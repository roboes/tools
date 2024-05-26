<?php
// WooCommerce - Display product stock status icons as a shortcode

add_shortcode( $tag='woocommerce_product_stock_status_icons', $callback='display_custom_availability_icons' );

function display_custom_availability_icons() {
	global $product;

	if ( WC() && $product ) {

		// In stock
		if ( $product->is_in_stock() ) {
			$availability['availability'] = '<i class="fa-solid fa-circle" style="color: #50C878;"></i>';
			$availability['class'] = 'in_stock';
		}

		// Out of stock
		if ( ! $product->is_in_stock() ) {
			$availability['availability'] = '<i class="fa-solid fa-circle" style="color: #b20000;"></i>';
			$availability['class'] = 'out_of_stock';
		}

		return $availability['availability'];
	}
}
