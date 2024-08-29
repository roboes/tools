<?php

// WooCommerce - Remove "Calculate shipping" if one or more products added to the cart belong to the $shipping_class_name = 'local-pickup-only'
// Last update: 2024-07-24

if (class_exists('WooCommerce') && WC()) {

    add_filter($hook_name = 'woocommerce_product_needs_shipping', $callback = 'woocommerce_shipping_method_local_pickup_only_remove_calculate_shipping', $priority = 10, $accepted_args = 1);

    function woocommerce_shipping_method_local_pickup_only_remove_calculate_shipping()
    {

        // Setup
        $shipping_class_name = 'local-pickup-only';

        $in_cart = false;
        $calculate_shipping = true;

        foreach (WC()->cart->get_cart_contents() as $key => $values) {
            if ($values['data']->get_shipping_class() === $shipping_class_name) {
                $in_cart = true;
                break;
            }
        }

        if ($in_cart) {
            $calculate_shipping = false;
        }

        return $calculate_shipping;

    }

}
