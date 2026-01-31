<?php

// WooCommerce - Set a maximum weight per cart
// Last update: 2026-01-16

if (function_exists('WC') && !is_admin()) {

    // Settings
    $weight_limit = 30000;

    // Get current language (Polylang/WPML)
    $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

    function get_weight_error_message(int $weight_limit, string $language): string
    {
        if ($language === 'de') {
            return sprintf(__('Ein Warenkorb kann maximal %d kg wiegen. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, kannst du uns gerne kontaktieren.', 'woocommerce'), $weight_limit / 1000);
        }

        return sprintf(__('A cart can weigh a maximum of %d kg. If you have any special requests that are not listed in our online shop, please feel free to contact us.', 'woocommerce'), $weight_limit / 1000);
    }

    // Validate on "Add to Cart"
    add_filter(hook_name: 'woocommerce_add_to_cart_validation', callback: function (bool $passed, int $product_id, int $quantity, $variation_id = '', $variations = '') use ($weight_limit, $browsing_language): bool {
        if (!WC()->cart) {
            return $passed;
        }

        $product = wc_get_product($variation_id ?: $product_id);
        if (!$product instanceof WC_Product) {
            return $passed;
        }

        $total_weight = (float) WC()->cart->get_cart_contents_weight() + ((float) $product->get_weight() * $quantity);

        if ($total_weight > $weight_limit) {
            wc_add_notice(get_weight_error_message($weight_limit, $browsing_language), 'error');
            return false;
        }

        return $passed;
    }, priority: 10, accepted_args: 5);

    // Validate on Cart Update
    add_filter(hook_name: 'woocommerce_update_cart_validation', callback: function (bool $passed, string $cart_item_key, array $values, int $quantity) use ($weight_limit, $browsing_language): bool {
        $product = $values['data'];
        if (!$product instanceof WC_Product) {
            return $passed;
        }

        $current_quantity = (int) $values['quantity'];
        $quantity_diff = $quantity - $current_quantity;

        if ($quantity_diff <= 0) {
            return $passed;
        }

        $total_weight = (float) WC()->cart->get_cart_contents_weight() + ((float) $product->get_weight() * $quantity_diff);

        if ($total_weight > $weight_limit) {
            wc_add_notice(get_weight_error_message($weight_limit, $browsing_language), 'error');
            return false;
        }

        return $passed;
    }, priority: 10, accepted_args: 4);
}
