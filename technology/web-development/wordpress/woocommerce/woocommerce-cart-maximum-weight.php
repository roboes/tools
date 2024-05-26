<?php
// WooCommerce - Set a maximum weight per cart

// Calculate whether an item being added to the cart passes weight criteria - triggered on add to cart action
add_filter( $hook_name='woocommerce_add_to_cart_validation', $callback='woocommerce_cart_add_to_cart_validation', $priority=10, $accepted_args=5 );

function woocommerce_cart_add_to_cart_validation( $passed, $product_id, $quantity, $variation_id = '', $variations = '' ) {

	if ( WC() ) {

		// Setup (weight limit dependent on products' Unit - in this case, grams)
		$weight_limit = 30000;

		$total_item_weight = 0;

		// Get current language from Polylang
		if ( function_exists( 'pll_current_language' ) ) {
			$current_language = pll_current_language( $value='slug' );
		} else {
			$current_language = 'en';
		}

		// Check cart items
		foreach( WC()->cart->get_cart() as $cart_item ) {
			$item_product_id = empty($variation_id) ? $product_id : $variation_id;

			// If the product is already in cart
			if( $item_product_id == $cart_item['data']->get_id() ){
				// Get total cart item weight
				$total_item_weight += $cart_item['data']->get_weight() * $cart_item['quantity'];
			}
		}

		// Get an instance of the WC_Product object
		$product = empty($variation_id) ? wc_get_product($product_id) : wc_get_product($variation_id);

		// Get total item weight
		$total_item_weight += $product->get_weight() * $quantity;

		if( $total_item_weight > $weight_limit ){
			$passed = false ;

			if ($current_language === 'en') {
				$message = __( 'A purchase can weigh a maximum of ' . $weight_limit/1000 . ' kg', 'woocommerce' );
			} elseif ($current_language === 'de') {
				$message = __( 'Ein Einkauf darf maximal ' . $weight_limit/1000 . ' kg wiegen', 'woocommerce' );
			} else {
				$message = __( 'A purchase can weigh a maximum of ' . $weight_limit/1000 . ' kg', 'woocommerce' );
			}

			wc_add_notice( $message=$message, $notice_type='error' );
		}

		return $passed;
	}

}


// Calculate whether an item quantity change passes weight criteria - triggered on cart item quantity change
add_filter( $hook_name='woocommerce_after_cart_item_quantity_update', $callback='woocommerce_cart_item_quantity_change', $priority=10, $accepted_args=4 );

function woocommerce_cart_item_quantity_change( $cart_item_key, $new_quantity, $old_quantity, $cart ) {

	if ( WC() ) {

		// Setup (weight limit dependent on products' Unit - in this case, grams)
		$weight_limit = 30000;

		// Get current language from Polylang
		if ( function_exists( 'pll_current_language' ) ) {
			$current_language = pll_current_language( $value='slug' );
		} else {
			$current_language = 'en';
		}

		// Get an instance of the WC_Product object
		$product = $cart->cart_contents[ $cart_item_key ]['data'];

		$product_weight = $product->get_weight(); // The product weight

		// Calculate the limit allowed max quantity from allowed weight limit
		$max_quantity = floor( $weight_limit / $product_weight );

		// If the new quantity exceed the weight limit
		if( ( $new_quantity * $product_weight ) > $weight_limit ){

			// Change the quantity to the limit allowed max quantity
			$cart->cart_contents[ $cart_item_key ]['quantity'] = $max_quantity;

			// Add a custom notice
			if ($current_language === 'en') {
				$message = __( 'A purchase can weigh a maximum of ' . $weight_limit/1000 . ' kg', 'woocommerce' );
			} elseif ($current_language === 'de') {
				$message = __( 'Ein Einkauf darf maximal ' . $weight_limit/1000 . ' kg wiegen', 'woocommerce' );
			} else {
				$message = __( 'A purchase can weigh a maximum of ' . $weight_limit/1000 . ' kg', 'woocommerce' );
			}

			wc_add_notice( $message=$message, $notice_type='error' );
		}
	}
}
