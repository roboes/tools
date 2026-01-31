<?php

// WooCommerce - Delete all products in English
// Last update: 2024-07-31

if (function_exists('WC')) {

    function delete_all_english_products()
    {
        // Fetch all English products
        $products_english = get_posts([
            'post_type' => 'product',
            'lang' => 'en',
            'posts_per_page' => -1
        ]);

        foreach ($products_english as $product_english) {
            // Delete product
            wp_delete_post(post_id: $product_english->ID, force_delete: true); // true to force deletion without moving to trash
        }
    }

    // Execute the function
    delete_all_english_products();

}
