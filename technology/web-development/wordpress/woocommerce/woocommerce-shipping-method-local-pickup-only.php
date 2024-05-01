// WooCommerce display only "Local pickup" location(s) if one or more products added to the cart belong to the shipping class "Local Pickup Only". Dynamically unsets all shipping methods except those with values starting with "pickup_location:" if any product in the cart belongs to the "local-pickup-only" shipping class.

add_filter($hook_name='woocommerce_package_rates', $callback='woocommerce_shipping_method_local_pickup_only', $priority=10, $accepted_args=2);

function woocommerce_shipping_method_local_pickup_only( $rates, $package ) {
   if ( WC() ) {

	   $shipping_class_name = 'local-pickup-only';
	   $in_cart = false;

	   foreach ( WC()->cart->get_cart_contents() as $key => $values ) {
		  if ( $values['data']->get_shipping_class() === $shipping_class_name ) {
			 $in_cart = true;
			 break;
		  }
	   }

	   if ( $in_cart ) {
		  // Unset all shipping methods except for the ones with value starting with "pickup_location:"
		  foreach ( $rates as $rate_key => $rate ) {
			 if ( strpos( $rate_key, 'pickup_location:' ) !== 0 ) {
				unset( $rates[ $rate_key ] );
			 }
		  }
	   }

	   return $rates;

   }
}
