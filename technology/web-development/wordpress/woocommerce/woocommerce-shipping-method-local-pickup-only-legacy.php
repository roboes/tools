<?php

// WooCommerce - Display only chosen $local_pickup_ids if one or more products added to the cart belong to the $shipping_class_name = 'local-pickup-only', dynamically unsets all shipping methods except for chosen $local_pickup_ids
// Last update: 2024-05-29

add_filter($hook_name = 'woocommerce_package_rates', $callback = 'woocommerce_shipping_method_local_pickup_only', $priority = 10, $accepted_args = 1);

function woocommerce_shipping_method_local_pickup_only($rates)
{
    if (WC()) {

        // Setup
        $shipping_class_name = 'local-pickup-only';
        $local_pickup_ids = array( 'local_pickup:38' );

        $in_cart = false;

        foreach (WC()->cart->get_cart_contents() as $key => $values) {
            if ($values['data']->get_shipping_class() === $shipping_class_name) {
                $in_cart = true;
                break;
            }
        }

        if ($in_cart) {
            // Unset all shipping methods except for $local_pickup_ids
            foreach ($rates as $rate_key => $rate) {
                if (!in_array($rate_key, $local_pickup_ids)) {
                    unset($rates[$rate_key]);
                }
            }
        }

        return $rates;
    }
}
