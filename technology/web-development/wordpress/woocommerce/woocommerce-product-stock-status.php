<?php

// WooCommerce - Product stock status
// Last update: 2024-05-29

// Notes: Elementor's "Product Stock" widget only works with "Stock management" (i.e. for products where "Track stock quantity for this product" is activated)

add_shortcode($tag = 'woocommerce_product_stock_status', $callback = 'product_stock_status');

function product_stock_status()
{
    global $product;

    if (WC() && $product) {
        // Load the translation domain for your plugin
        $plugin_domain = 'woocommerce';

        // Define the path to the languages directory within your plugin
        $languages_dir = dirname(plugin_basename(__FILE__)) . '/languages';

        // Load the translation files
        load_plugin_textdomain($plugin_domain, false, $languages_dir);

        if ($product->is_in_stock()) {
            $availability = '<span class="product-stock-status-icon" style="margin-right: 6px"><i class="fa-solid fa-circle" style="color: #50C878;"></i></span>' . __('In stock', $plugin_domain);
        } else {
            $availability = '<span class="product-stock-status-icon" style="margin-right: 6px"><i class="fa-solid fa-circle" style="color: #b20000;"></i></span>' . __('Out of stock', $plugin_domain);
        }

        return $availability;
    }
}
