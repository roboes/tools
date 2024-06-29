<?php

// WooCommerce - Delete all products in English
// Last update: 2024-06-15

function delete_all_english_products()
{
    // Fetch all English products
    $english_products = get_posts(array(
        'post_type' => 'product',
        'lang' => 'en',
        'posts_per_page' => -1
    ));

    foreach ($english_products as $english_product) {
        // Delete product
        wp_delete_post($english_product->ID, true); // true to force deletion without moving to trash
    }
}

// Execute the function
delete_all_english_products();
