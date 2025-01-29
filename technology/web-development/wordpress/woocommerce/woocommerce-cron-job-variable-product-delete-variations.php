<?php

// WordPress WooCommerce - Variable product delete variations (cron job)
// Last update: 2025-01-22

// Unschedule all events attached to a given hook
// wp_clear_scheduled_hook($hook='cron_job_schedule_variable_product_delete_variations', $args=array(), $wp_error=false);


// Run action once (run on WP Console)
// do_action($hook_name='cron_job_schedule_variable_product_delete_variations');


// Schedule cron job if not already scheduled
add_action($hook_name = 'wp_loaded', $callback = function () {

    if (!wp_next_scheduled($hook = 'cron_job_schedule_variable_product_delete_variations', $args = array())) {

        // Settings
        $start_datetime = '2025-01-05 03:00:00'; // Time is the same as the WordPress defined get_option('timezone_string');
        $start_datetime = new DateTime($start_datetime);
        $start_timestamp = $start_datetime->getTimestamp();

        wp_schedule_event($timestamp = $start_timestamp, $recurrence = 'weekly', $hook = 'cron_job_schedule_variable_product_delete_variations', $args = array(), $wp_error = false);

    }
}, $priority = 10, $accepted_args = 1);


// Hook the function to the scheduled event
add_action($hook_name = 'cron_job_schedule_variable_product_delete_variations', $callback = 'cron_job_run_variable_product_delete_variations', $priority = 10, $accepted_args = 1);


// Define the function to be hooked
function cron_job_run_variable_product_delete_variations()
{
    variable_product_delete_variations($product_ids = [17739, 22204], $delete = true);
}


function variable_product_delete_variations($product_ids, $delete = false)
{
    $timezone = get_option('timezone_string');
    $current_datetime = new DateTime('now', new DateTimeZone($timezone));

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        $product_name = $product->get_name();  // Get product name

        if ($product->is_type('variable')) {
            $variations = $product->get_children();
            $variations_to_delete = [];

            foreach ($variations as $variation_id) {
                $variation = new WC_Product_Variation($variation_id);
                $variation_name = $variation->get_name();  // Get variation name
                $attributes = $variation->get_variation_attributes(); // Get variation attributes

                // Check for 'Termin' attribute
                $attribute_value = $variation->get_attribute('Termin');
                if ($attribute_value) {
                    $term_date = substr($attribute_value, 0, 10); // Extract date (DD.MM.YYYY)
                    if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $term_date)) {
                        $term_date = DateTime::createFromFormat('d.m.Y', $term_date, new DateTimeZone($timezone));

                        // Check if the date is in the past
                        if ($term_date < $current_datetime) {
                            $variations_to_delete[] = [
                                'product_id' => $product_id,
                                'product_name' => $product_name,
                                'variation_id' => $variation_id,
                                'variation_name' => $variation_name,
                                'attributes' => $attributes
                            ];
                        }
                    }
                }
            }

            // List or delete variations
            if (!empty($variations_to_delete)) {
                if ($delete) {
                    foreach ($variations_to_delete as $variation) {
                        wp_delete_post($variation['variation_id'], true); // Delete variation
                        echo "Deleted variation - Product ID: {$variation['product_id']}, Product Name: {$variation['product_name']}, Variation ID: {$variation['variation_id']}, Variation Name: {$variation['variation_name']}, Attributes: " . json_encode($variation['attributes']) . "\n";
                    }
                } else {
                    foreach ($variations_to_delete as $variation) {
                        echo "Variation to be deleted - Product ID: {$variation['product_id']}, Product Name: {$variation['product_name']}, Variation ID: {$variation['variation_id']}, Variation Name: {$variation['variation_name']}, Attributes: " . json_encode($variation['attributes']) . "\n";
                    }
                }
            } else {
                echo "No variations to delete for product ID $product_id ($product_name).\n";
            }
        } else {
            echo "Product ID $product_id ($product_name) is not a variable product.\n";
        }
    }
}
