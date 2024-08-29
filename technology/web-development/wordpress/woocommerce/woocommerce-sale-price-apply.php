<?php

// WooCommerce - Apply sales price to multiple products
// Last update: 2024-07-31


if (class_exists('WooCommerce') && WC()) {

    function woocommerce_sale_price_update($product_id, $start_date, $end_date, $discount_percentage, $round_precision)
    {
        // Set sale price and dates
        update_post_meta($product_id, '_sale_price_dates_from', strtotime($start_date));
        update_post_meta($product_id, '_sale_price_dates_to', strtotime($end_date));

        // Calculate sale price
        $regular_price = get_post_meta($product_id, '_regular_price', true);
        $sale_price = $regular_price * (1 - $discount_percentage);

        // Round sale price to the specified number of decimal places
        $sale_price = round($sale_price, $round_precision);

        // Update sale price
        update_post_meta($product_id, '_sale_price', $sale_price);

        $product = get_post($product_id);
        echo 'Product price updated: ' . $product->ID . ' - ' . $product->post_title . ' (' . $product->post_name . ')<br>';
        echo 'Regular Price: ' . $regular_price . '<br>';
        echo 'Sale Price: ' . $sale_price . '<br>';
        echo 'Sale Start Date: ' . $start_date . '<br>';
        echo 'Sale End Date: ' . $end_date . '<br><br>';
    }




    function woocommerce_sale_price_apply()
    {

        // Settings
        $category_slugs = array('specialty-coffees-de');
        $product_ids_except = array();
        $start_date = '2024-07-19';
        $end_date = '2024-07-22'; # Exclusive
        $discount_percentage = 0.2;
        $round_precision = 1;

        // Convert $category_slugs to IDs
        $category_ids = array();
        foreach ($category_slugs as $slug) {
            $term = get_term_by('slug', $slug, 'product_cat');
            if ($term) {
                $category_ids[] = $term->term_id;
            }
        }

        $products = get_posts(array('post_type' => 'product', 'posts_per_page' => -1, 'tax_query' => array(array('taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $category_ids, 'operator' => 'IN'))));

        foreach ($products as $product) {
            $product_id = $product->ID;

            // Skip if product ID is in the exclusion list
            if (in_array($product_id, $product_ids_except)) {
                echo 'Product skipped: ' . $product->ID . ' - ' . $product->post_title . ' (' . $product->post_name . ')<br>';
                continue;
            }

            // Process product and its variations
            $product_obj = wc_get_product($product_id);

            if ($product_obj->is_type('variable')) {
                $variations = $product_obj->get_children();
                foreach ($variations as $variation_id) {
                    woocommerce_sale_price_update($variation_id, $start_date, $end_date, $discount_percentage, $round_precision);
                }
            } else {
                woocommerce_sale_price_update($product_id, $start_date, $end_date, $discount_percentage, $round_precision);
            }
        }
    }



    // Execute the function
    woocommerce_sale_price_apply();

}
