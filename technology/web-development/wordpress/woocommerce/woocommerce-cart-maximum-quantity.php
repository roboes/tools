<?php

// WooCommerce - Set a maximum quantity for individual products and/or individual products in specific categories per cart
// Last update: 2026-01-15


if (function_exists('WC') && !is_admin()) {

    // Settings
    $product_quantity_rules = [
        [
            'type' => 'categories',
            'slugs' => ['accessories-de', 'accessories-en'],
            'product_ids_exception' => [19412, 31399, 11213, 31435, 11211, 31436, 39398, 39400],
            'max_quantity' => 3,
        ],
        [
            'type' => 'products',
            'product_ids' => [41078, 41085, 39398, 39400],
            'max_quantity' => 12,
        ],
    ];

    // Get current language (Polylang/WPML)
    $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

    function get_error_message(int $max_quantity, string $language, string $product_name): string
    {
        if ($language === 'de') {
            return sprintf(__('Ein Warenkorb kann bis zu %d Artikel von "%s" enthalten. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, kannst du uns gerne kontaktieren.', 'woocommerce'), $max_quantity, $product_name);
        }

        return sprintf(__('A cart can contain up to %d items of "%s". If you have any special requests that are not listed in our online shop, please feel free to contact us.', 'woocommerce'), $max_quantity, $product_name);
    }

    function get_matching_rule(int $product_id, int $parent_id, array $rules): ?array
    {
        $effective_id = $parent_id ?: $product_id;

        foreach ($rules as $rule) {
            if ($rule['type'] === 'categories' && has_term($rule['slugs'], 'product_cat', $effective_id)) {
                if (in_array($product_id, $rule['product_ids_exception'], true) || in_array($parent_id, $rule['product_ids_exception'], true)) {
                    continue;
                }
                return $rule;
            }

            if ($rule['type'] === 'products' && in_array($effective_id, $rule['product_ids'], true)) {
                return $rule;
            }
        }

        return null;
    }

    // Validate on "Add to Cart"
    add_filter(hook_name: 'woocommerce_add_to_cart_validation', callback: function (bool $passed, int $product_id, int $quantity, $variation_id = '', $variations = '') use ($product_quantity_rules, $browsing_language): bool {
        if (!WC()->cart) {
            return $passed;
        }

        $product = wc_get_product($variation_id ?: $product_id);
        if (!$product instanceof WC_Product) {
            return $passed;
        }

        $parent_id = (int) $product->get_parent_id();
        $rule = get_matching_rule($product_id, $parent_id, $product_quantity_rules);

        if ($rule) {
            $product_or_parent_id = $variation_id ? $parent_id : $product_id;
            $product_cart_quantity = $quantity;

            foreach (WC()->cart->get_cart() as $cart_item) {
                $_product = $cart_item['data'];
                $cart_item_id = (int) ($_product->get_parent_id() ?: $_product->get_id());

                if ($cart_item_id === $product_or_parent_id) {
                    $product_cart_quantity += (int) $cart_item['quantity'];
                }
            }

            if ($product_cart_quantity > (int) $rule['max_quantity']) {
                wc_add_notice(get_error_message((int) $rule['max_quantity'], $browsing_language, $product->get_name()), 'error');
                return false;
            }
        }

        return $passed;
    }, priority: 10, accepted_args: 5);

    // Validate on Cart Update
    add_filter(hook_name: 'woocommerce_update_cart_validation', callback: function (bool $passed, string $cart_item_key, array $values, int $quantity) use ($product_quantity_rules, $browsing_language): bool {
        $product = $values['data'];
        if (!$product instanceof WC_Product) {
            return $passed;
        }

        $product_id = (int) $product->get_id();
        $parent_id = (int) $product->get_parent_id();
        $rule = get_matching_rule($product_id, $parent_id, $product_quantity_rules);

        if ($rule && $quantity > (int) $rule['max_quantity']) {
            wc_add_notice(get_error_message((int) $rule['max_quantity'], $browsing_language, $product->get_name()), 'error');
            return false;
        }

        return $passed;
    }, priority: 10, accepted_args: 4);

    // Set max quantity input
    add_filter(hook_name: 'woocommerce_quantity_input_args', callback: function (array $args, WC_Product $product) use ($product_quantity_rules): array {
        $product_id = (int) $product->get_id();
        $parent_id = (int) $product->get_parent_id();
        $rule = get_matching_rule($product_id, $parent_id, $product_quantity_rules);

        $args['min_value'] = 1;
        if ($rule) {
            $args['max_value'] = (int) $rule['max_quantity'];
        }

        return $args;
    }, priority: 10, accepted_args: 2);
}
