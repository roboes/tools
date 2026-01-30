<?php

// WooCommerce - Display the total available stock quantity for a variable product before the variations form
// Last update: 2026-01-30


if (function_exists('WC') && !is_admin()) {
    add_action(hook_name: 'woocommerce_before_variations_form', callback: 'product_variable_stock_total_display', priority: 10, accepted_args: 0);

    function product_variable_stock_total_display(): void
    {
        if (!is_product()) {
            return;
        }

        // Settings
        $product_ids = [17739, 22204, 31437, 31438];
        $product_variation_ids_exception = [44043, 44044];
        $messages = [
            'available-appointments' => [
                'en' => 'Available Appointments',
                'de' => 'Verfügbare Termine',
            ],
        ];

        $product = wc_get_product(get_the_ID());

        if (!$product instanceof WC_Product_Variable) {
            return;
        }

        if (!in_array($product->get_id(), $product_ids, true)) {
            return;
        }

        // Get current language (Polylang/WPML)
        $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

        // Get stock quantity via shortcode
        $stock_quantity = shortcode_exists('product_variable_stock_quantity')
            ? (int) do_shortcode('[product_variable_stock_quantity product_variable_id="' . $product->get_id() . '" product_variation_ids_exception="' . implode(',', $product_variation_ids_exception) . '"]')
            : 0;

        if ($stock_quantity <= 0) {
            return;
        }


        ?>
        <style>.total-stock-totals { width: 100%; text-align: left; }</style>
        <div class="total-stock-totals">
            <br><strong><?php echo esc_html($messages['available-appointments'][$browsing_language] ?? $messages['available-appointments']['en']); ?></strong>
            <br><?php echo esc_html($stock_quantity); ?>
        </div><br>
        <?php
    }

}
