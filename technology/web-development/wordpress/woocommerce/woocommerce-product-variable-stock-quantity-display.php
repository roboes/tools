<?php

// WooCommerce - Display the total available stock quantity for a variable product before the variations form
// Last update: 2025-10-14

if (class_exists('WooCommerce') && WC()) {

    add_action($hook_name = 'woocommerce_before_variations_form', $callback = 'product_variable_stock_total_display', $priority = 10, $accepted_args = 1);

    function product_variable_stock_total_display()
    {

        if (is_product()) {
            global $product;

            // Settings
            $product_ids = array(17739, 22204, 31437, 31438);
            $messages = [
                'available-appointments' => [
                    'de' => 'Verfügbare Termine',
                    'en' => 'Available Appointments',
                ],
            ];

            // Get current language
            $current_language = (function_exists('pll_current_language') && in_array(pll_current_language('slug'), pll_languages_list(array('fields' => 'slug')))) ? pll_current_language('slug') : 'en';

            // Check if current product is in the target product IDs array
            if ($product && $product->is_type('variable') && in_array($product->get_id(), $product_ids)) {

                // Use the shortcode to get the stock quantity
                if (shortcode_exists('product_variable_stock_quantity')) {
                    $stock_quantity = do_shortcode('[product_variable_stock_quantity id="' . $product->get_id() . '"]');
                } else {
                    $stock_quantity = 0;
                }

                // Display the total stock
                if ($stock_quantity > 0) {
                    echo '<div class="total-stock-totals">';
                    echo '<br>';
                    echo '<strong>' . $messages['available-appointments'][$current_language] . '</strong><br>';
                    echo esc_html($stock_quantity);
                    echo '</div><br>';
                }
            }
        }
    }

}
