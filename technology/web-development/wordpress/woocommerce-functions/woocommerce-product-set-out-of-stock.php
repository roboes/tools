<?php

// WooCommerce - Set all products belonging to a specific list of category names to "Out of Stock"
// Last update: 2024-06-21


function function_product_set_out_of_stock_run()
{

    // Settings
    $product_categories = ['Specialty Coffees', 'Spezialitätenkaffees'];


    $products = new WP_Query(['post_type' => 'product', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'name', 'terms' => $product_categories]]]);

    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            $product = wc_get_product(get_the_ID());
            if ($product) {
                $product->set_stock_status('outofstock');
                $product->save();
            }
        }
        wp_reset_postdata();
    }
}


function_product_set_out_of_stock_run();
