<?php

// WooCommerce Function - Update SKUs for all products and variations based on GTIN
// Last update: 2024-11-19

function woocommerce_product_sku_update_to_gtin()
{
    // Query to get all products
    $args = array('post_type' => array('product', 'product_variation'), 'posts_per_page' => -1, 'post_status' => array('publish', 'private'));
    $products = get_posts($args);

    foreach ($products as $product_post) {
        $product = wc_get_product($product_post->ID);

        if ($product->is_type('variable')) {
            // For variable products, update each variation
            $variations = $product->get_children();
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                $gtin = get_post_meta($variation_id, '_global_unique_id', true);
                $current_sku = $variation->get_sku();

                // Check if GTIN exists and is different from current SKU
                if ($gtin && $gtin !== $current_sku) {
                    $variation->set_sku($gtin);
                    $variation->save();
                    echo 'Updated SKU for Variation ID ' . $variation_id . ' from ' . $current_sku . ' to ' . $gtin . '<br>';
                }
            }
        } else {
            // For simple products, update the product itself
            $gtin = get_post_meta($product->get_id(), '_global_unique_id', true);
            $current_sku = $product->get_sku();

            // Check if GTIN exists and is different from current SKU
            if ($gtin && $gtin !== $current_sku) {
                $product->set_sku($gtin);
                $product->save();
                echo 'Updated SKU for Product ID ' . $product->get_id() . ' from ' . $current_sku . ' to ' . $gtin . '<br>';
            }
        }
    }
}

// Execute the function
woocommerce_product_sku_update_to_gtin();
