<?php

// WordPress Admin - Create custom fields
// Last update: 2024-05-29

// Unschedule all events attached to a given hook
// wp_clear_scheduled_hook( $hook='custom_field_product_shipping_class', $args=array(), $wp_error=false );


// Run action once (run on WP Console)
// do_action( $hook_name='custom_field_product_shipping_class');


// Add custom cron schedules
add_filter($hook_name = 'cron_schedules', $callback = 'custom_cron_schedules', $priority = 10, $accepted_args = 1);

function custom_cron_schedules($schedules)
{
    if(!isset($schedules['every_minute'])) {
        $schedules['every_minute'] = array( 'interval' => 60, 'display' => __('Once every minute') );
    }

    return $schedules;
}


// Schedule cron jobs (UTC time)
add_action($hook_name = 'init', $callback = 'schedule_custom_cron_job', $priority = 10, $accepted_args = 1);

function schedule_custom_cron_job()
{
    if (! wp_next_scheduled($hook = 'custom_field_product_shipping_class', $args = array())) {
        wp_schedule_event($timestamp = time(), $recurrence = 'every_minute', $hook = 'custom_field_product_shipping_class', $args = array(), $wp_error = false);
    }
}


// Custom Field 'product_shipping_class'
add_action($hook_name = 'custom_field_product_shipping_class', $callback = 'add_custom_field_product_shipping_class', $priority = 10, $accepted_args = 1);

function add_custom_field_product_shipping_class()
{
    if (WC()) {
        // Get all products
        $products = wc_get_products(array('limit' => -1));

        foreach ($products as $product) {
            update_post_meta($post_id = $product->get_id(), $meta_key = 'product_shipping_class', $meta_value = $product->get_shipping_class(), $prev_value = '');
        }
    }
}
