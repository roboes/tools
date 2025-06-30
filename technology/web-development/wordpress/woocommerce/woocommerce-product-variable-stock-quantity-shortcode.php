<?php

// WooCommerce - Calculate the total available stock quantity for a variable product shortcode
// Last update: 2025-06-20


if (class_exists('WooCommerce') && WC()) {

    add_shortcode($tag = 'product_variable_stock_quantity', $callback = 'product_variable_stock_quantity_shortcode');

    function product_variable_stock_total_calculate($product_id)
    {

        $stock_quantity = 0;

        $product = wc_get_product($product_id);

        if ($product && $product->is_type('variable')) {

            foreach ($product->get_children() as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation && $variation->managing_stock()) {
                    $stock_quantity += max(0, (int) $variation->get_stock_quantity()); // Treat < 0 as 0
                }
            }

        }

        return $stock_quantity;
    }

    function product_variable_stock_quantity_shortcode($atts)
    {
        // Get product ID from shortcode attribute
        $product_id = isset($atts['id']) ? (int)$atts['id'] : 0;

        // Calculate stock for specific product
        $stock_quantity = product_variable_stock_total_calculate($product_id);

        return $stock_quantity;
    }

}
