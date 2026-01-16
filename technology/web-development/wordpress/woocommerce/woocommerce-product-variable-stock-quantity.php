<?php

// WooCommerce - Calculate the total available stock quantity for a variable product
// Last update: 2026-01-15


// Notes: Returns total stock for all variations; used in Dynamic Conditions to hide/show sections based on availability
// Usage: [product_variable_stock_quantity product_variable_id="22204"]


if (function_exists('WC')) {

    // Clear cache when stock is updated
    add_action(hook_name: 'woocommerce_variation_set_stock', callback: static function (WC_Product_Variation $variation): void {
        $parent_id = $variation->get_parent_id();
        if ($parent_id) {
            delete_transient('wc_var_stock_' . $parent_id);
        }
    }, priority: 10, accepted_args: 1);

    if (!is_admin()) {
        add_shortcode(tag: 'product_variable_stock_quantity', callback: 'product_variable_stock_quantity');

        function product_variable_stock_total_calculate(int $product_variable_id): int
        {
            $product_variable = wc_get_product($product_variable_id);

            if (!$product_variable instanceof WC_Product_Variable) {
                return 0;
            }

            $transient_key = 'wc_var_stock_' . $product_variable_id;
            $cached_quantity = get_transient($transient_key);

            if ($cached_quantity !== false) {
                return (int) $cached_quantity;
            }

            $stock_quantity = 0;
            foreach ($product_variable->get_children() as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation instanceof WC_Product && $variation->managing_stock()) {
                    $stock_quantity += max(0, (int) $variation->get_stock_quantity());
                }
            }

            set_transient(transient: $transient_key, value: $stock_quantity, expiration: HOUR_IN_SECONDS);

            return $stock_quantity;
        }

        function product_variable_stock_quantity(array|string $atts): string
        {
            $atts = shortcode_atts(pairs: ['product_variable_id' => 0], atts: $atts, shortcode: 'product_variable_stock_quantity');
            return (string) product_variable_stock_total_calculate(product_variable_id: (int) $atts['product_variable_id']);
        }
    }
}
