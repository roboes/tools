<?php

// WooCommerce - Product related products
// Last update: 2026-02-07


if (function_exists('WC') && !is_admin()) {

    add_shortcode(tag: 'product_related_products_count', callback: function () {
        $product = wc_get_product(get_the_ID());

        if (!$product instanceof WC_Product) {
            return '0';
        }

        // Get related product
        $product_related = wc_get_related_products($product->get_id(), -1);

        // Return the count of the array
        return (string) count($product_related);
    });

}
