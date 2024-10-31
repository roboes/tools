<?php

// WooCommerce - Set all products belonging to a specific list of category names to "Out of Stock"
// Last update: 2024-06-21


// Unschedule all events attached to a given hook
// wp_clear_scheduled_hook( $hook='function_product_set_out_of_stock', $args=array(), $wp_error=false );


// Schedule cron job on a specific date and time if not already scheduled
add_action($hook_name = 'wp_loaded', $callback = 'schedule_custom_cron_job_function_product_set_out_of_stock', $priority = 10, $accepted_args = 1);

function schedule_custom_cron_job_function_product_set_out_of_stock()
{
    if (! wp_next_scheduled($hook = 'function_product_set_out_of_stock', $args = array())) {

        // Settings
        $start_datetime = '2024-06-22 00:00:00'; // Time is the same as the WordPress defined get_option('timezone_string');

        $start_datetime = new DateTime($start_datetime);
        $start_timestamp = $start_datetime->getTimestamp();

        wp_schedule_single_event($timestamp = $start_timestamp, $hook = 'function_product_set_out_of_stock', $args = array(), $wp_error = false);
    }
}


// Update stock status based on categories
add_action('function_product_set_out_of_stock', 'function_product_set_out_of_stock_run');

function function_product_set_out_of_stock_run()
{

    // Settings
    $product_cats = array('Specialty Coffees', 'Spezialitätenkaffees');


    $products = new WP_Query($args = array('post_type' => 'product', 'posts_per_page' => -1, 'tax_query' => array(array('taxonomy' => 'product_cat', 'field' => 'name', 'terms' => $product_cats))));

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
