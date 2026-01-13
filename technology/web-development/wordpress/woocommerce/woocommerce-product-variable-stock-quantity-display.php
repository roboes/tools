<?php

// WooCommerce - Display the total available stock quantity for a variable product before the variations form
// Last update: 2026-01-12


if (function_exists('WC')) {
    add_action('woocommerce_before_variations_form', 'product_variable_stock_total_display');

    function product_variable_stock_total_display(): void
    {
        if (!is_product()) {
            return;
        }

        // Settings
        $product_ids = [17739, 22204, 31437, 31438];
        $messages = [
            'available-appointments' => [
                'de' => 'Verfügbare Termine',
                'en' => 'Available Appointments',
            ],
        ];

        $product = wc_get_product(get_the_ID());

        if (!$product instanceof WC_Product_Variable) {
            return;
        }

        if (!in_array($product->get_id(), $product_ids, true)) {
            return;
        }

        // Get current language (Polylang)
        $current_language = 'en';
        if (function_exists('pll_current_language')) {
            $pll_lang = pll_current_language('slug');
            if ($pll_lang && in_array($pll_lang, pll_languages_list(['fields' => 'slug']), true)) {
                $current_language = $pll_lang;
            }
        }

        // Get stock quantity via shortcode
        $stock_quantity = shortcode_exists('product_variable_stock_quantity')
            ? (int) do_shortcode('[product_variable_stock_quantity id="' . $product->get_id() . '"]')
            : 0;

        if ($stock_quantity <= 0) {
            return;
        }

        printf(
            '<div class="total-stock-totals"><br><strong>%s</strong><br>%s</div><br>',
            esc_html($messages['available-appointments'][$current_language] ?? $messages['available-appointments']['en']),
            esc_html($stock_quantity)
        );
    }
}
