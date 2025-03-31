<?php

// WooCommerce - Batch update fields for all product variations and simple products in a given product category
// Last update: 2025-03-21


if (class_exists('WooCommerce') && WC()) {

    function batch_update_variation_dimensions($category_slug)
    {
        // Settings
        $products = get_posts(array('post_type' => 'product', 'posts_per_page' => -1, 'post_status' => array('publish', 'private', 'draft', 'pending', 'future'), 'tax_query' => array(array('taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $category_slug))));


        foreach ($products as $product) {
            $product_id = $product->ID;
            $product_obj = wc_get_product($product_id);

            if ($product_obj && $product_obj->is_type('variable')) {
                $variations = $product_obj->get_children();

                foreach ($variations as $variation_id) {
                    update_post_meta($variation_id, '_length', '');
                    update_post_meta($variation_id, '_width', '');
                    update_post_meta($variation_id, '_height', '');
                }
            } elseif ($product_obj && $product_obj->is_type('simple')) {
                // Also update simple products
                update_post_meta($product_id, '_length', '');
                update_post_meta($product_id, '_width', '');
                update_post_meta($product_id, '_height', '');
            }
        }

        return "Batch update completed for category: " . $category_slug;
    }

    // Execute the function
    batch_update_variation_dimensions('specialty-coffees-en');

}
