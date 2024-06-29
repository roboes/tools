<?php

// WooCommerce - Set a maximum quantity for individual products belonging to specific category per cart
// Last update: 2024-06-07

// Calculate whether an item being added to the cart passes the quantity criteria - triggered on add to cart action

if (WC()) {

    add_filter($hook_name = 'woocommerce_add_to_cart_validation', $callback = 'woocommerce_cart_maximum_quantity_add_to_cart_validation', $priority = 10, $accepted_args = 5);

    function woocommerce_cart_maximum_quantity_add_to_cart_validation($passed, $product_id, $quantity, $variation_id = '', $variations = '')
    {

        // Setup
        $product_cats = array('Accessories', 'Zubehör');
        $max_quantity = 3;
        $product_ids_exception = array(19412, 11213, 11211);

        // Get current language
        $current_language = function_exists('pll_current_language') ? pll_current_language('slug') : 'en';

        // Get an instance of the WC_Product object
        $product = wc_get_product($variation_id ? $variation_id : $product_id);
        $parent_id = $product->get_parent_id();

        // Check if the product or its parent belongs to any of the specified categories
        if (has_term($product_cats, 'product_cat', $product_id) || ($parent_id && has_term($product_cats, 'product_cat', $parent_id))) {
            // Check if the product or its parent is in the exception list
            if (in_array($product_id, $product_ids_exception) || in_array($parent_id, $product_ids_exception)) {
                return $passed;
            }

            // Calculate the total quantity for this product and its variations in the cart
            $product_cart_quantity = $quantity;
            $product_or_parent_id = $variation_id ? $parent_id : $product_id;

            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $_product = $cart_item['data'];
                $cart_item_id = $_product->get_parent_id() ? $_product->get_parent_id() : $_product->get_id();
                if ($cart_item_id == $product_or_parent_id) {
                    $product_cart_quantity += $cart_item['quantity'];
                }
            }

            // If the total exceeds the maximum allowed quantity, do not pass validation
            if ($product_cart_quantity > $max_quantity) {
                $passed = false;

                // Custom notice
                if ($current_language === 'de') {
                    $message = sprintf(__('Ein Warenkorb kann bis zu %d Artikel pro einzelnem Zubehörprodukt enthalten. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, können Sie uns gerne kontaktieren.', 'woocommerce'), $max_quantity);
                } else {
                    $message = sprintf(__('A cart can contain up to %d items per individual accessory product. If you have any special requests that are not listed in our online shop, please feel free to contact us.', 'woocommerce'), $max_quantity);
                }

                wc_add_notice($message = $message, $notice_type = 'error');
            }
        }

        return $passed;
    }


    // Calculate whether an item quantity change passes the quantity criteria - triggered on cart item quantity change
    add_filter($hook_name = 'woocommerce_after_cart_item_quantity_update', $callback = 'woocommerce_cart_maximum_quantity_cart_item_quantity_change_validation', $priority = 10, $accepted_args = 4);

    function woocommerce_cart_maximum_quantity_cart_item_quantity_change_validation($cart_item_key, $new_quantity, $old_quantity, $cart)
    {

        // Setup
        $product_cats = array('Accessories', 'Zubehör');
        $max_quantity = 3;
        $product_ids_exception = array(19412, 11213, 11211);

        // Get current language
        $current_language = function_exists('pll_current_language') ? pll_current_language('slug') : 'en';

        // Get an instance of the WC_Product object
        $product = $cart->cart_contents[$cart_item_key]['data'];

        // Check if the product belongs to any of the specified categories
        if (has_term($product_cats, 'product_cat', $product->get_id()) || has_term($product_cats, 'product_cat', $product->get_parent_id())) {

            // Check if the product or its parent is in the exception list
            if (in_array($product->get_id(), $product_ids_exception) || in_array($product->get_parent_id(), $product_ids_exception)) {
                return;
            }

            // Calculate the total quantity for this product and its variations in the cart
            $product_cart_quantity = 0;
            $product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();

            foreach ($cart->get_cart() as $item_key => $item) {
                $_product = $item['data'];
                $check_id = $_product->get_parent_id() ? $_product->get_parent_id() : $_product->get_id();
                if ($check_id === $product_id) {
                    $product_cart_quantity += $item['quantity'];
                }
            }

            // If the total exceeds the maximum allowed quantity, adjust the quantity and add a notice
            if ($product_cart_quantity > $max_quantity) {
                $adjusted_quantity = $max_quantity - ($product_cart_quantity - $new_quantity);
                $cart->cart_contents[$cart_item_key]['quantity'] = $adjusted_quantity > 0 ? $adjusted_quantity : 1;

                // Custom notice
                if ($current_language === 'de') {
                    $message = sprintf(__('Ein Warenkorb kann bis zu %d Artikel pro einzelnem Zubehörprodukt enthalten. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, können Sie uns gerne kontaktieren.', 'woocommerce'), $max_quantity);
                } else {
                    $message = sprintf(__('A cart can contain up to %d items per individual accessory product. If you have any special requests that are not listed in our online shop, please feel free to contact us.', 'woocommerce'), $max_quantity);
                }

                wc_add_notice($message = $message, $notice_type = 'error');
            }
        }
    }

}
