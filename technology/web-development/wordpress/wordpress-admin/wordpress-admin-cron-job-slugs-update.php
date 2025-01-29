<?php

// WordPress Admin - Run slugs update daily (cron job)
// Last update: 2025-01-24

// Unschedule all events attached to a given hook
// wp_clear_scheduled_hook($hook='cron_job_schedule_slugs_update', $args=array(), $wp_error=false);


// Run action once (run on WP Console)
// do_action($hook_name='cron_job_schedule_slugs_update');


// Add custom cron schedules
/* add_filter($hook_name = 'cron_schedules', $callback = 'custom_cron_schedules', $priority = 10, $accepted_args = 1);

function custom_cron_schedules($schedules)
{
    if(!isset($schedules['every_minute'])) {
        $schedules['every_minute'] = array('interval' => 60, 'display' => __('Once every minute'));
    }

    return $schedules;
} */


// Schedule cron job if not already scheduled
add_action($hook_name = 'wp_loaded', $callback = function () {

    if (!wp_next_scheduled($hook = 'cron_job_schedule_slugs_update', $args = array())) {

        // Settings
        $start_datetime = '2025-01-05 02:00:00'; // Time is the same as the WordPress defined get_option('timezone_string');

        $start_datetime = new DateTime($start_datetime);
        $start_timestamp = $start_datetime->getTimestamp();

        wp_schedule_event($timestamp = $start_timestamp, $recurrence = 'weekly', $hook = 'cron_job_schedule_slugs_update', $args = array(), $wp_error = false);
    }

}, $priority = 10, $accepted_args = 1);


add_action($hook_name = 'cron_job_schedule_slugs_update', $callback = 'cron_job_run_slugs_update', $priority = 10, $accepted_args = 1);

function cron_job_run_slugs_update()
{

    // Custom Field 'product_shipping_class'
    if (class_exists('WooCommerce') && WC()) {

        $products = wc_get_products(array('limit' => -1));

        if (!empty($products)) {

            foreach ($products as $product) {
                update_post_meta($post_id = $product->get_id(), $meta_key = 'product_shipping_class', $meta_value = $product->get_shipping_class(), $prev_value = '');
            }
        }


        // Regenerate attribute labels on custom fields for products

        // Settings
        $attribute_custom_field_pairs = array(
            array('attribute_id' => 'pa_coffee-type', 'custom_field_id' => 'product_type', 'categories' => array('Specialty Coffees', 'Spezialitätenkaffees')),
            array('attribute_id' => 'pa_coffee-processing', 'custom_field_id' => 'product_coffee_selection', 'categories' => array('Specialty Coffees', 'Spezialitätenkaffees')),
            array('attribute_id' => 'pa_weight', 'custom_field_id' => 'product_coffee_weight', 'categories' => array('Specialty Coffees', 'Spezialitätenkaffees')),
        );
        $product_ids_exempt = array(19419, 31533);

        $products = get_posts(array('post_type' => 'product', 'posts_per_page' => -1));

        if (!empty($products)) {
            foreach ($products as $product) {
                // Determine the language slug of the product
                $product_language = (function_exists('pll_get_post_language') && in_array(pll_get_post_language($product->ID, 'slug'), pll_languages_list(array('fields' => 'slug')))) ? pll_get_post_language($product->ID, 'slug') : 'en';

                foreach ($attribute_custom_field_pairs as $pair) {
                    $attribute_id = $pair['attribute_id'];
                    $custom_field_id = $pair['custom_field_id'];
                    $categories = $pair['categories'];

                    // Check if the product ID is in the exempt list
                    if (in_array($product->ID, $product_ids_exempt)) {
                        echo 'Product skipped: ' . $product->ID . ' - ' . $product->post_title . ' (' . $product_language . ')<br>';
                        continue;
                    }

                    // Check if the product is in the specified categories, if categories array is not empty
                    if (!empty($categories)) {
                        $product_categories = wp_get_post_terms($product->ID, 'product_cat', array('fields' => 'names'));
                        if (empty(array_intersect($categories, $product_categories))) {
                            continue;
                        }
                    }

                    // Get WooCommerce product object
                    $wc_product = wc_get_product($product->ID);

                    // Get the attribute values
                    $attributes = $wc_product->get_attributes();

                    if (isset($attributes[$attribute_id])) {
                        $processing_attribute = $attributes[$attribute_id];
                        if ($processing_attribute->is_taxonomy()) {
                            // Get terms associated with this attribute in the product's language
                            $terms = wp_get_post_terms($product->ID, $attribute_id, array('language' => $product_language));

                            // Prepare labelled values
                            $labelled_values = '';
                            foreach ($terms as $term) {
                                $labelled_values .= '<label>' . esc_html($term->name) . '</label>';
                            }

                            // Get the current value of the custom field
                            $current_value = get_post_meta($product->ID, $custom_field_id, true);

                            // Update the custom field only if the new value is different
                            if ($current_value !== $labelled_values) {
                                update_post_meta($product->ID, $custom_field_id, $labelled_values);

                                echo 'Product processed: ' . $product->ID . ' - ' . $product->post_title . ' (' . $product_language . ')<br>';
                                echo 'Updated "' . $custom_field_id . '" custom field with: "' . $labelled_values . '"<br>';
                            }
                        }
                    }
                }
            }
        }
    }


    // Regenerate slugs for pages

    // Settings
    $post_id_exempt = array(20766, 30721);

    $posts = get_posts(array('numberposts' => -1, 'post_type' => 'page'));


    if (!empty($posts)) {

        foreach ($posts as $post) {
            // Check if the current post ID is in the exclusion list
            if (in_array($post->ID, $post_id_exempt)) {
                echo 'Page skipped: ' . $post->ID . ' - ' . $post->post_title . ' (' . $post->post_name . ')<br>';
                continue;
            }

            // Get the current slug before sanitizing
            $old_slug = $post->post_name;

            // Check the slug and run an update if necessary
            $new_slug = sanitize_title($title = $post->post_title);

            // Example of additional slug modification logic (uncomment if needed)
            // $new_slug = str_replace(['(', ')'], '', $new_slug);

            if ($old_slug != $new_slug) {
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_name' => $new_slug
                ), $wp_error = false, $fire_after_hooks = true);

                echo 'Page renamed: ' . $post->ID . ' - ' . $post->post_title . ' (' . $old_slug . ' -> ' . $new_slug . ')<br>';
            }
        }
    }


    // Regenerate slugs for products
    if (class_exists('WooCommerce') && WC()) {

        // Settings
        $post_id_exempt = array(18215, 18373, 20116, 27123, 31441, 31459, 31488, 31538);

        $posts = get_posts(array('numberposts' => -1, 'post_type' => 'product'));

        if (!empty($posts)) {

            foreach ($posts as $post) {
                // Check if the current post ID is in the exclusion list
                if (in_array($post->ID, $post_id_exempt)) {
                    echo 'Product skipped: ' . $post->ID . ' - ' . $post->post_title . ' (' . $post->post_name . ')<br>';
                    continue;
                }

                // Get the current slug before sanitizing
                $old_slug = $post->post_name;

                // Check the slug and run an update if necessary
                $new_slug = sanitize_title($title = $post->post_title);

                // Example of additional slug modification logic (uncomment if needed)
                // $new_slug = str_replace(['(', ')'], '', $new_slug);

                if ($old_slug != $new_slug) {
                    wp_update_post(array(
                        'ID' => $post->ID,
                        'post_name' => $new_slug
                    ), $wp_error = false, $fire_after_hooks = true);

                    echo 'Product renamed: ' . $post->ID . ' - ' . $post->post_title . ' (' . $old_slug . ' -> ' . $new_slug . ')<br>';
                }
            }

        }
    }


    // Regenerate slugs for attachments

    // Settings
    $attachment_id_exempt = array();

    // Query attachments that are not attached to any post ('post_parent' => null)
    $attachments = get_posts(array('post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => null));

    if (!empty($attachments)) {

        // Rename title given file name
        foreach ($attachments as $attachment) {
            // Check if the current attachment ID is in the exclusion list
            if (in_array($attachment->ID, $attachment_id_exempt)) {
                echo 'Attachment skipped: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $attachment->post_name . ')<br>';
                continue;
            }

            // Get the current file name
            $file_path = get_attached_file($attachment->ID);
            $file_name = basename($file_path);
            $file_name_without_extension = pathinfo($file_name, PATHINFO_FILENAME);

            // Update attachment title and slug
            if ($attachment->post_title != $file_name_without_extension) {
                wp_update_post(array(
                    'ID' => $attachment->ID,
                    'post_title' => $file_name_without_extension,
                    'post_name' => sanitize_title($file_name_without_extension)
                ), $wp_error = false, $fire_after_hooks = true);

                echo 'Attachment renamed: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $attachment->post_name . ' -> ' . sanitize_title($file_name_without_extension) . ')<br>';
            }
        }



        // Regenerate slugs
        foreach ($attachments as $attachment) {
            // Check if the current attachment ID is in the exclusion list
            if (in_array($attachment->ID, $attachment_id_exempt)) {
                echo 'Attachment skipped: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $attachment->post_name . ')<br>';
                continue;
            }

            // Get the current slug before sanitizing
            $old_slug = $attachment->post_name;

            // Check the slug and run an update if necessary
            $new_slug = sanitize_title($attachment->post_title);

            if ($old_slug != $new_slug) {
                wp_update_post(array(
                    'ID' => $attachment->ID,
                    'post_name' => $new_slug
                ), $wp_error = false, $fire_after_hooks = true);

                echo 'Attachment renamed: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $old_slug . ' -> ' . $new_slug . ')<br>';
            }
        }
    }

    // Turn product brand name into a custom field
    if (class_exists('WooCommerce') && WC()) {
        // Get all products (you can limit the query if needed)
        $args = array('post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish');

        $query = new WP_Query($args);

        // Loop through each product
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Get the product's brand(s)
            $product = wc_get_product($post_id);
            $brands = implode(', ', wp_get_post_terms($product->get_id(), 'product_brand', [ 'fields' => 'names' ]));

            // Save the brand name(s) as a custom field
            update_post_meta($post_id, 'product_brand_name', $brands);
        }

        // Reset post data
        wp_reset_postdata();
    }

}
