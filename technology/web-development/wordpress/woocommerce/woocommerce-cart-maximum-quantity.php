<?php

// WooCommerce - Set a maximum quantity for individual products and/or individual products in specific categories per cart
// Last update: 2025-01-09

if (class_exists('WooCommerce') && WC()) {

    // Settings
    $product_quantity_rules = array(
        array(
            'type' => 'categories',
            'slugs' => array('accessories-de', 'accessories-en'),
            'product_ids_exception' => array(19412, 31399, 11213, 31435, 11211, 31436, 39398, 39400),
            'max_quantity' => 3,
        ),
        array(
            'type' => 'products',
            'product_ids' => array(39398, 39400),
            'max_quantity' => 12,
        ),
    );

    // Get current language
    $current_language = (function_exists('pll_current_language') && in_array(pll_current_language('locale'), pll_languages_list(array('fields' => 'locale')))) ? pll_current_language('locale') : 'en_US';

    // Function to get error message
    function get_error_message($max_quantity, $language, $product_name)
    {
        if ($language === 'de_DE') {
            return sprintf(__('Ein Warenkorb kann bis zu %d Artikel von "%s" enthalten. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, kannst du uns gerne kontaktieren.', 'woocommerce'), $max_quantity, $product_name);
        } elseif ($language === 'de_DE_formal') {
            return sprintf(__('Ein Warenkorb kann bis zu %d Artikel von "%s" enthalten. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, können Sie uns gerne kontaktieren.', 'woocommerce'), $max_quantity, $product_name);
        } else {
            // Add other languages here if needed
            return sprintf(__('A cart can contain up to %d items of "%s". If you have any special requests that are not listed in our online shop, please feel free to contact us.', 'woocommerce'), $max_quantity, $product_name);
        }
    }

    // Add to cart validation
    add_filter($hook_name = 'woocommerce_add_to_cart_validation', $callback = function ($passed, $product_id, $quantity, $variation_id = '', $variations = '') use ($product_quantity_rules, $current_language) {

        $product = wc_get_product($variation_id ? $variation_id : $product_id);
        $parent_id = $product->get_parent_id();
        $product_or_parent_id = $variation_id ? $parent_id : $product_id;

        // Loop through the product quantity rules
        foreach ($product_quantity_rules as $rule) {
            if ($rule['type'] === 'categories' && has_term($rule['slugs'], 'product_cat', $product_id)) {
                if (in_array($product_id, $rule['product_ids_exception']) || in_array($parent_id, $rule['product_ids_exception'])) {
                    continue;
                }

                // Check if the total quantity exceeds the maximum for the category
                $product_cart_quantity = $quantity;
                foreach (WC()->cart->get_cart() as $cart_item) {
                    $_product = $cart_item['data'];
                    $cart_item_id = $_product->get_parent_id() ? $_product->get_parent_id() : $_product->get_id();
                    if ($cart_item_id == $product_or_parent_id) {
                        $product_cart_quantity += $cart_item['quantity'];
                    }
                }

                if ($product_cart_quantity > $rule['max_quantity']) {
                    $passed = false;
                    $product_name = $product->get_name();
                    $message = get_error_message($rule['max_quantity'], $current_language, $product_name);
                    wc_add_notice($message, 'error');
                }
            } elseif ($rule['type'] === 'products' && in_array($product_id, $rule['product_ids'])) {
                // Check if the product is in the specific product list
                $product_cart_quantity = $quantity;
                foreach (WC()->cart->get_cart() as $cart_item) {
                    $_product = $cart_item['data'];
                    $cart_item_id = $_product->get_parent_id() ? $_product->get_parent_id() : $_product->get_id();
                    if ($cart_item_id == $product_or_parent_id) {
                        $product_cart_quantity += $cart_item['quantity'];
                    }
                }

                if ($product_cart_quantity > $rule['max_quantity']) {
                    $passed = false;
                    $product_name = $product->get_name();
                    $message = get_error_message($rule['max_quantity'], $current_language, $product_name);
                    wc_add_notice($message, 'error');
                }
            }
        }

        return $passed;
    }, $priority = 10, $accepted_args = 5);

    // Cart item quantity update validation
    add_filter($hook_name = 'woocommerce_after_cart_item_quantity_update', $callback = function ($cart_item_key, $new_quantity, $old_quantity, $cart) use ($product_quantity_rules, $current_language) {

        $product = $cart->cart_contents[$cart_item_key]['data'];

        // Loop through the product quantity rules
        foreach ($product_quantity_rules as $rule) {
            if ($rule['type'] === 'categories' && has_term($rule['slugs'], 'product_cat', $product->get_id())) {
                if (in_array($product->get_id(), $rule['product_ids_exception']) || in_array($product->get_parent_id(), $rule['product_ids_exception'])) {
                    continue;
                }

                $product_cart_quantity = 0;
                $product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
                foreach ($cart->get_cart() as $item) {
                    $_product = $item['data'];
                    $check_id = $_product->get_parent_id() ? $_product->get_parent_id() : $_product->get_id();
                    if ($check_id === $product_id) {
                        $product_cart_quantity += $item['quantity'];
                    }
                }

                if ($product_cart_quantity > $rule['max_quantity']) {
                    $adjusted_quantity = max(1, $rule['max_quantity'] - ($product_cart_quantity - $new_quantity));
                    $cart->cart_contents[$cart_item_key]['quantity'] = $adjusted_quantity;
                    $product_name = $product->get_name();
                    $message = get_error_message($rule['max_quantity'], $current_language, $product_name);
                    wc_add_notice($message, 'error');
                }
            } elseif ($rule['type'] === 'products' && in_array($product->get_id(), $rule['product_ids'])) {
                $product_cart_quantity = 0;
                $product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
                foreach ($cart->get_cart() as $item) {
                    $_product = $item['data'];
                    $check_id = $_product->get_parent_id() ? $_product->get_parent_id() : $_product->get_id();
                    if ($check_id === $product_id) {
                        $product_cart_quantity += $item['quantity'];
                    }
                }

                if ($product_cart_quantity > $rule['max_quantity']) {
                    $adjusted_quantity = max(1, $rule['max_quantity'] - ($product_cart_quantity - $new_quantity));
                    $cart->cart_contents[$cart_item_key]['quantity'] = $adjusted_quantity;
                    $product_name = $product->get_name();
                    $message = get_error_message($rule['max_quantity'], $current_language, $product_name);
                    wc_add_notice($message, 'error');
                }
            }
        }

    }, $priority = 10, $accepted_args = 4);

}
