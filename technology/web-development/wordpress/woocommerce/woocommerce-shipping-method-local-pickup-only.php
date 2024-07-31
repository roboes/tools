<?php

// WooCommerce - Display only "Local pickup" location(s) (from new "WooCommerce Blocks: Local Pickup" - Settings > Shipment > "Local Pickup" tab - https://woocommerce.com/document/woocommerce-blocks-local-pickup/) if one or more products added to the cart belong to the $shipping_class_name = 'local-pickup-only', dynamically unsets all shipping methods except those with values starting with "pickup_location:"
// Last update: 2024-07-24

// Notes: The new "WooCommerce Blocks: Local Pickup" does not work with Elementor as it requires the Checkout block to enable it (as described here https://woocommerce.com/document/using-the-new-block-based-checkout/)

if (class_exists('WooCommerce') && WC()) {

    add_filter($hook_name = 'woocommerce_package_rates', $callback = 'woocommerce_shipping_method_local_pickup_only', $priority = 10, $accepted_args = 1);

    function woocommerce_shipping_method_local_pickup_only($rates)
    {

        // Setup
        $shipping_class_name = 'local-pickup-only';

        $in_cart = false;

        foreach (WC()->cart->get_cart_contents() as $key => $values) {
            if ($values['data']->get_shipping_class() === $shipping_class_name) {
                $in_cart = true;
                break;
            }
        }

        if ($in_cart) {
            // Unset all shipping methods except for the ones with value starting with "pickup_location:"
            foreach ($rates as $rate_key => $rate) {
                if (strpos($rate_key, 'pickup_location:') !== 0) {
                    unset($rates[ $rate_key ]);
                }
            }
        }

        return $rates;

    }

}
