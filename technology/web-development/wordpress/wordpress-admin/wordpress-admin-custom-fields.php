<?php
// WordPress Admin create custom fields


// Unschedule all events attached to a given hook
// wp_clear_scheduled_hook( $hook='custom_field_product_shipping_class', $args=array(), $wp_error=false );


// Run functions on schedule (UTC time)
if ( ! wp_next_scheduled( $hook='custom_field_product_shipping_class', $args=array() ) ) {
	wp_schedule_event( $timestamp=strtotime( '15:18:00' ), $recurrence='daily', $hook='custom_field_product_shipping_class', $args=array(), $wp_error=false );
}



// Custom Field 'product_shipping_class'
add_action( $hook_name='custom_field_product_shipping_class', $callback='add_custom_field_product_shipping_class', $priority=10, $accepted_args=1  );

function add_custom_field_product_shipping_class( $post_id ) {
    if ( WC() ) {

		// Get all products
		$products = wc_get_products( array('limit' => -1) );

        foreach ( $products as $product ) {
            update_post_meta( $post_id=$product->get_id(), $meta_key='product_shipping_class', $meta_value=$product->get_shipping_class(), $prev_value='' );
        }
    }
}
