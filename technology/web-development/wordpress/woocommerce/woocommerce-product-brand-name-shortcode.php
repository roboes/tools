<?php

// WooCommerce - Product brand name shortcode
// Last update: 2025-01-24

add_shortcode($tag = 'product_brand_name', $callback = function () {

    $product = wc_get_product(get_the_ID());

    // Check if $product is available
    if (! $product) {
        return;
    }

    $brands = implode(', ', wp_get_post_terms($product->get_id(), 'product_brand', ['fields' => 'names']));
    return $brands;

});
