<?php

// WooCommerce - Set a maximum quantity for individual products and/or individual products in specific categories per cart
// Last update: 2025-10-14

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
            'product_ids' => array(41078, 41085, 39398, 39400),
            'max_quantity' => 12,
        ),
    );

    // Get current language
    $current_language = (function_exists('pll_current_language') && in_array(pll_current_language('slug'), pll_languages_list(array('fields' => 'slug')))) ? pll_current_language('slug') : 'en';

    // Function to get error message
    function get_error_message($max_quantity, $language, $product_name)
    {
        if ($language === 'de') {
            return sprintf(__('Ein Warenkorb kann bis zu %d Artikel von "%s" enthalten. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, kannst du uns gerne kontaktieren.', 'woocommerce'), $max_quantity, $product_name);
        } elseif ($language === 'en') {
            return sprintf(__('A cart can contain up to %d items of "%s". If you have any special requests that are not listed in our online shop, please feel free to contact us.', 'woocommerce'), $max_quantity, $product_name);
        } else {
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


    add_filter($hook_name = 'woocommerce_quantity_input_args', $callback = function ($args, $product) use ($product_quantity_rules) {
        $product_id  = $product->get_id();
        $parent_id = $product->get_parent_id();
        $effective_id = $parent_id ? $parent_id : $product_id;

        // ensure you always have at least a min of 1
        $args['min_value'] = 1;

        foreach ($product_quantity_rules as $rule) {
            if ($rule['type'] === 'categories') {
                // product in one of the rule slugs?
                if (has_term($rule['slugs'], 'product_cat', $product_id)) {
                    // but not in the exceptions?
                    if (in_array($product_id, $rule['product_ids_exception'], true)
                      || in_array($parent_id, $rule['product_ids_exception'], true)) {
                        continue;
                    }
                    $args['max_value'] = $rule['max_quantity'];
                    break; // stop once we’ve found a match
                }
            } elseif ($rule['type'] === 'products') {
                if (in_array($effective_id, $rule['product_ids'], true)) {
                    $args['max_value'] = $rule['max_quantity'];
                    break;
                }
            }
        }

        return $args;
    }, $priority = 10, $accepted_args = 2);

}
