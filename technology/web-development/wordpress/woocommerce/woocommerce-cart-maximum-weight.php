<?php

// WooCommerce - Set a maximum weight per cart
// Last update: 2024-05-29

// Calculate whether an item being added to the cart passes the weight criteria - triggered on add to cart action

if (WC()) {

    add_filter($hook_name = 'woocommerce_add_to_cart_validation', $callback = 'woocommerce_cart_maximum_weight_add_to_cart_validation', $priority = 10, $accepted_args = 5);

    function woocommerce_cart_maximum_weight_add_to_cart_validation($passed, $product_id, $quantity, $variation_id = '', $variations = '')
    {

        // Setup (weight limit dependent on products' Unit - in this case, grams)
        $weight_limit = 30000;

        // Check cart items
        $total_cart_weight = 0;

        // Get current language
        $current_language = function_exists('pll_current_language') ? pll_current_language('slug') : 'en';

        // Calculate the weight of the current cart items
        foreach(WC()->cart->get_cart() as $cart_item) {
            $item_weight = $cart_item['data']->get_weight();
            $total_cart_weight += is_numeric($item_weight) ? $item_weight * $cart_item['quantity'] : 0;
        }

        // Get an instance of the WC_Product object for the new item
        $product = wc_get_product($variation_id ? $variation_id : $product_id);

        // Get the weight of the new item being added
        $new_item_weight = $product->get_weight();
        $new_item_total_weight = is_numeric($new_item_weight) ? $new_item_weight * $quantity : 0;

        // Add the weight of the new item to the total cart weight
        $total_cart_weight += $new_item_total_weight;

        // If the total cart weight exceeds the weight limit
        if ($total_cart_weight > $weight_limit) {
            $passed = false;

            // Custom notice
            if ($current_language === 'de') {
                $message = sprintf(__('Ein Warenkorb kann maximal %d kg wiegen. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, können Sie uns gerne kontaktieren.', 'woocommerce'), $weight_limit / 1000);
            } else {
                $message = sprintf(__('A cart can weigh a maximum of %d kg. If you have any special requests that are not listed in our online shop, please feel free to contact us.', 'woocommerce'), $weight_limit / 1000);
            }

            wc_add_notice($message = $message, $notice_type = 'error');
        }

        return $passed;

    }


    // Calculate whether an item quantity change passes the weight criteria - triggered on cart item quantity change
    add_filter($hook_name = 'woocommerce_after_cart_item_quantity_update', $callback = 'woocommerce_cart_maximum_weight_cart_item_quantity_change_validation', $priority = 10, $accepted_args = 4);

    function woocommerce_cart_maximum_weight_cart_item_quantity_change_validation($cart_item_key, $new_quantity, $old_quantity, $cart)
    {

        // Setup (weight limit dependent on products' Unit - in this case, grams)
        $weight_limit = 30000;

        // Get current language
        $current_language = function_exists('pll_current_language') ? pll_current_language('slug') : 'en';

        // Calculate the total weight of the cart before the quantity change
        $total_cart_weight = 0;
        foreach ($cart->get_cart() as $item_key => $cart_item) {
            $item_weight = $cart_item['data']->get_weight();
            $total_cart_weight += is_numeric($item_weight) ? $item_weight * $cart_item['quantity'] : 0;
        }

        // Subtract the old quantity weight of the item being changed
        $product_weight = $cart->cart_contents[ $cart_item_key ]['data']->get_weight();
        $product_weight = is_numeric($product_weight) ? $product_weight : 0;
        $total_cart_weight -= $product_weight * $old_quantity;

        // Add the new quantity weight of the item being changed
        $total_cart_weight += $product_weight * $new_quantity;

        // If the total cart weight exceeds the weight limit
        if ($total_cart_weight > $weight_limit) {
            // Set the quantity back to the old quantity
            $cart->cart_contents[ $cart_item_key ]['quantity'] = $old_quantity;

            // Custom notice
            if ($current_language === 'de') {
                $message = sprintf(__('Ein Warenkorb kann maximal %d kg wiegen. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, können Sie uns gerne kontaktieren.', 'woocommerce'), $weight_limit / 1000);
            } else {
                $message = sprintf(__('A cart can weigh a maximum of %d kg. If you have any special requests that are not listed in our online shop, please feel free to contact us.', 'woocommerce'), $weight_limit / 1000);
            }

            wc_add_notice($message = $message, $notice_type = 'error');
        }

    }

}
