<?php

// WooCommerce - Set a maximum weight per cart
// Last update: 2026-01-16

if (function_exists('WC') && !is_admin()) {

    // Settings
    $weight_limit = 30000;

    // Get current language
    $current_language = 'en';
    if (function_exists('pll_current_language')) {
        if (pll_current_language('slug') && in_array(needle: pll_current_language('slug'), haystack: pll_languages_list(['fields' => 'slug']), strict: true)) {
            $current_language = pll_current_language('slug');
        }
    }

    function get_weight_error_message(int $weight_limit, string $language): string
    {
        if ($language === 'de') {
            return sprintf(__('Ein Warenkorb kann maximal %d kg wiegen. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, kannst du uns gerne kontaktieren.', 'woocommerce'), $weight_limit / 1000);
        }

        return sprintf(__('A cart can weigh a maximum of %d kg. If you have any special requests that are not listed in our online shop, please feel free to contact us.', 'woocommerce'), $weight_limit / 1000);
    }

    // Validate on "Add to Cart"
    add_filter(hook_name: 'woocommerce_add_to_cart_validation', priority: 10, accepted_args: 5, callback: function (bool $passed, int $product_id, int $quantity, $variation_id = '', $variations = '') use ($weight_limit, $current_language): bool {
        if (!WC()->cart) {
            return $passed;
        }

        $product = wc_get_product($variation_id ?: $product_id);
        if (!$product instanceof WC_Product) {
            return $passed;
        }

        $total_weight = (float) WC()->cart->get_cart_contents_weight() + ((float) $product->get_weight() * $quantity);

        if ($total_weight > $weight_limit) {
            wc_add_notice(get_weight_error_message($weight_limit, $current_language), 'error');
            return false;
        }

        return $passed;
    });

    // Validate on Cart Update
    add_filter(hook_name: 'woocommerce_update_cart_validation', priority: 10, accepted_args: 4, callback: function (bool $passed, string $cart_item_key, array $values, int $quantity) use ($weight_limit, $current_language): bool {
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
            wc_add_notice(get_weight_error_message($weight_limit, $current_language), 'error');
            return false;
        }

        return $passed;
    });
}
