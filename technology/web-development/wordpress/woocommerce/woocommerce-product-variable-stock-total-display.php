<?php

// WooCommerce - Display total available stock for a variable product before the variations form
// Last update: 2024-11-04

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
                    'de_DE' => 'Verfügbare Termine',
                    'de_DE_formal' => 'Verfügbare Termine',
                    'en_US' => 'Available Appointments',
                ],
            ];

            // Get current language
            $current_language = (function_exists('pll_current_language') && in_array(pll_current_language('locale'), pll_languages_list(array('fields' => 'locale')))) ? pll_current_language('locale') : 'en_US';

            // Check if current product is in the target product IDs array
            if ($product && $product->is_type('variable') && in_array($product->get_id(), $product_ids)) {
                $variations = $product->get_available_variations();
                $total_stock = 0;

                // Loop through all variations
                foreach ($variations as $variation_data) {
                    $variation = wc_get_product($variation_data['variation_id']);

                    // Check if stock management is enabled for the variation
                    if ($variation->managing_stock()) {
                        // Get the stock quantity for this variation
                        $stock_qty = $variation->get_stock_quantity();

                        // Sum up the total stock
                        $total_stock += $stock_qty;
                    }
                }

                // Display the total stock
                if ($total_stock > 0) {
                    echo '<div class="total-stock-totals">';
                    echo '<br>';
                    echo '<strong>' . $messages['available-appointments'][$current_language] . '</strong><br>';
                    echo esc_html($total_stock);
                    echo '</div><br>';
                }
            }
        }
    }

}
