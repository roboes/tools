<?php

// WooCommerce - Hide delivery shipping methods (keep only Local Pickup) for carts containing "local-pickup-only" items, plus checkout validation backstop
// Last update: 2026-06-25

add_filter(hook_name: 'woocommerce_package_rates', callback: 'shipping_class_hide_delivery_methods', priority: 10, accepted_args: 2);
function shipping_class_hide_delivery_methods($rates, $package)
{

    // Settings
    $settings_class_local_pickup_only = 'local-pickup-only';

    $has_pickup_only_item = false;

    foreach ($package['contents'] as $item) {
        if ($item['data']->get_shipping_class() === $settings_class_local_pickup_only) {
            $has_pickup_only_item = true;
            break;
        }
    }

    if ($has_pickup_only_item) {
        foreach ($rates as $rate_id => $rate) {
            if (strpos($rate_id, 'local_pickup') !== 0) {
                unset($rates[ $rate_id ]);
            }
        }
    }

    return $rates;
}
