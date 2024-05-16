// WooCommerce add custom CSS class 'badge-new-product' for products created within the last 6 months

add_filter( $hook_name='post_class', $callback='add_new_product_css_class', $priority=10, $accepted_args=1 );

function add_new_product_css_class( $classes ) {
	if ( WC() ) {
		// Check if the product was created within the last 6 months
		if ( get_the_time( 'U', $product_id ) > strtotime( '-6 months' ) ) {
			// Add 'badge-new-product' CSS class
			$classes[] = 'badge-new-product';
		}

		return $classes;
	}
}
