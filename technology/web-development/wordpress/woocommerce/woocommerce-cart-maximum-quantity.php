<?php

// WooCommerce - Set a maximum quantity for individual products and/or individual products in specific categories per cart
// Last update: 2026-01-14

if (function_exists('WC') && !is_admin()) {

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
    $current_language = 'en';
    if (function_exists('pll_current_language')) {
        if (pll_current_language('slug') && in_array(pll_current_language('slug'), pll_languages_list(['fields' => 'slug']), true)) {
            $current_language = pll_current_language('slug');
        }
    }

    function get_error_message(int $max_quantity, string $language, string $product_name): string
    {
        if ($language === 'de') {
            return sprintf(__('Ein Warenkorb kann bis zu %d Artikel von "%s" enthalten. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, kannst du uns gerne kontaktieren.', 'woocommerce'), $max_quantity, $product_name);
        }

        return sprintf(__('A cart can contain up to %d items of "%s". If you have any special requests that are not listed in our online shop, please feel free to contact us.', 'woocommerce'), $max_quantity, $product_name);
    }

    add_filter(hook_name: 'woocommerce_add_to_cart_validation', priority: 10, accepted_args: 5, callback: function (bool $passed, int $product_id, int $quantity, $variation_id = '', $variations = '') use ($product_quantity_rules, $current_language): bool {
        if (!WC()->cart) {
            return $passed;
        }

        $product = wc_get_product($variation_id ?: $product_id);
        if (!$product instanceof WC_Product) {
            return $passed;
        }

        $parent_id = (int) $product->get_parent_id();
        $product_or_parent_id = $variation_id ? $parent_id : $product_id;

        foreach ($product_quantity_rules as $rule) {
            $is_category_match = ($rule['type'] === 'categories' && has_term($rule['slugs'], 'product_cat', $product_id));
            $is_product_match = ($rule['type'] === 'products' && in_array($product_id, $rule['product_ids'], true));

            if ($is_category_match) {
                if (in_array($product_id, $rule['product_ids_exception'], true) || in_array($parent_id, $rule['product_ids_exception'], true)) {
                    continue;
                }
            }

            if ($is_category_match || $is_product_match) {
                $product_cart_quantity = $quantity;

                foreach (WC()->cart->get_cart() as $cart_item) {
                    $_product = $cart_item['data'];
                    $cart_item_id = (int) ($_product->get_parent_id() ?: $_product->get_id());

                    if ($cart_item_id === $product_or_parent_id) {
                        $product_cart_quantity += (int) $cart_item['quantity'];
                    }
                }

                if ($product_cart_quantity > (int) $rule['max_quantity']) {
                    $passed = false;
                    $message = get_error_message((int) $rule['max_quantity'], $current_language, $product->get_name());
                    wc_add_notice($message, 'error');
                }
            }
        }

        return $passed;
    });

    add_filter(hook_name: 'woocommerce_quantity_input_args', priority: 10, accepted_args: 2, callback: function (array $args, WC_Product $product) use ($product_quantity_rules): array {
        if (is_admin() && !defined('DOING_AJAX')) {
            return $args;
        }

        $product_id = (int) $product->get_id();
        $parent_id = (int) $product->get_parent_id();
        $effective_id = $parent_id ?: $product_id;

        $args['min_value'] = 1;

        foreach ($product_quantity_rules as $rule) {
            if ($rule['type'] === 'categories') {
                if (has_term($rule['slugs'], 'product_cat', $product_id)) {
                    if (in_array($product_id, $rule['product_ids_exception'], true) || in_array($parent_id, $rule['product_ids_exception'], true)) {
                        continue;
                    }
                    $args['max_value'] = (int) $rule['max_quantity'];
                    break;
                }
            } elseif ($rule['type'] === 'products') {
                if (in_array($effective_id, $rule['product_ids'], true)) {
                    $args['max_value'] = (int) $rule['max_quantity'];
                    break;
                }
            }
        }

        return $args;
    });
}
