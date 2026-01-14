<?php

// WooCommerce - Set a maximum weight per cart
// Last update: 2026-01-14

if (function_exists('WC') && !is_admin()) {

    add_filter(hook_name: 'woocommerce_add_to_cart_validation', callback: 'woocommerce_cart_maximum_weight_add_to_cart_validation', priority: 10, accepted_args: 5);

    function woocommerce_cart_maximum_weight_add_to_cart_validation(bool $passed, int $product_id, int $quantity, $variation_id = '', $variations = ''): bool
    {
        if (!WC()->cart) {
            return $passed;
        }

        // Settings
        $weight_limit = 30000;

        // Get current language
        $current_language = 'en';
        if (function_exists('pll_current_language')) {
            if (pll_current_language('slug') && in_array(pll_current_language('slug'), pll_languages_list(['fields' => 'slug']), true)) {
                $current_language = pll_current_language('slug');
            }
        }

        $total_cart_weight = (float) WC()->cart->get_cart_contents_weight();

        $product = wc_get_product($variation_id ?: $product_id);
        if (!$product instanceof WC_Product) {
            return $passed;
        }

        $new_item_weight = (float) $product->get_weight();
        $total_cart_weight += ($new_item_weight * $quantity);

        if ($total_cart_weight > $weight_limit) {
            $passed = false;

            if ($current_language === 'de') {
                $message = sprintf(__('Ein Warenkorb kann maximal %d kg wiegen. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, kannst du uns gerne kontaktieren.'), $weight_limit / 1000);
            } else {
                $message = sprintf(__('A cart can weigh a maximum of %d kg. If you have any special requests that are not listed in our online shop, please feel free to contact us.'), $weight_limit / 1000);
            }

            wc_add_notice(message: $message, notice_type: 'error');
        }

        return $passed;
    }

    add_action(hook_name: 'woocommerce_after_cart_item_quantity_update', callback: 'woocommerce_cart_maximum_weight_cart_item_quantity_change_validation', priority: 10, accepted_args: 4);

    function woocommerce_cart_maximum_weight_cart_item_quantity_change_validation(string $cart_item_key, int $new_quantity, int $old_quantity, WC_Cart $cart): void
    {
        if (empty($cart->cart_contents[$cart_item_key])) {
            return;
        }

        // Settings
        $weight_limit = 30000;

        // Get current language
        $current_language = 'en';
        if (function_exists('pll_current_language')) {
            if (pll_current_language('slug') && in_array(pll_current_language('slug'), pll_languages_list(['fields' => 'slug']), true)) {
                $current_language = pll_current_language('slug');
            }
        }

        $product_weight = (float) $cart->cart_contents[$cart_item_key]['data']->get_weight();
        $total_cart_weight = (float) $cart->get_cart_contents_weight();

        if ($total_cart_weight > $weight_limit) {
            $cart->cart_contents[$cart_item_key]['quantity'] = $old_quantity;

            if ($current_language === 'de') {
                $message = sprintf(__('Ein Warenkorb kann maximal %d kg wiegen. Bei besonderen Anfragen, die in unserem Online-Shop nicht aufgeführt sind, kannst du uns gerne kontaktieren.'), $weight_limit / 1000);
            } else {
                $message = sprintf(__('A cart can weigh a maximum of %d kg. If you have any special requests that are not listed in our online shop, please feel free to contact us.'), $weight_limit / 1000);
            }

            wc_add_notice(message: $message, notice_type: 'error');
        }
    }
}
