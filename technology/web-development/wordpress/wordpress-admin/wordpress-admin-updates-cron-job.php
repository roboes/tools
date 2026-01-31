<?php

// WordPress Admin - Cron job updates
// Last update: 2026-01-15


// Unschedule all events attached to a given hook
// wp_clear_scheduled_hook(hook: 'cron_job_schedule_slugs_update', args: [], wp_error: false);


// Run action once (run on WP Console)
// do_action(hook_name: 'cron_job_schedule_slugs_update');


// Schedule cron job if not already scheduled
add_action(hook_name: 'init', callback: function (): void {

    if (!wp_next_scheduled(hook: 'cron_job_schedule_slugs_update', args: [])) {

        // Settings
        $start_datetime = new DateTimeImmutable(datetime: 'next sunday 02:00:00', timezone: wp_timezone());

        wp_schedule_event(timestamp: $start_datetime->getTimestamp(), recurrence: 'weekly', hook: 'cron_job_schedule_slugs_update', args: [], wp_error: false);
    }

}, priority: 10, accepted_args: 0);


add_action(hook_name: 'cron_job_schedule_slugs_update', callback: 'cron_job_run_slugs_update', priority: 10, accepted_args: 1);

function cron_job_run_slugs_update(): void
{

    // Custom Field 'product_shipping_class'
    if (function_exists('WC')) {

        $product_ids = wc_get_products(['limit' => -1, 'return' => 'ids']);

        if (!empty($product_ids)) {

            foreach ($product_ids as $id) {
                $product = wc_get_product($id);
                if (!$product instanceof WC_Product) {
                    continue;
                }
                update_post_meta(post_id: $product->get_id(), meta_key: 'product_shipping_class', meta_value: $product->get_shipping_class(), prev_value: '');
            }
        }


        // Regenerate attribute labels on custom fields for products

        // Settings
        $attribute_custom_field_pairs = [
            ['attribute_id' => 'pa_coffee-type', 'custom_field_id' => 'product_type', 'categories' => ['Specialty Coffees', 'Spezialitätenkaffees']],
            ['attribute_id' => 'pa_coffee-processing', 'custom_field_id' => 'product_coffee_selection', 'categories' => ['Specialty Coffees', 'Spezialitätenkaffees']],
            ['attribute_id' => 'pa_weight', 'custom_field_id' => 'product_coffee_weight', 'categories' => ['Specialty Coffees', 'Spezialitätenkaffees']],
        ];
        $product_ids_exempt = [19419, 31533];

        $posts_ids = get_posts(['post_type' => 'product', 'posts_per_page' => -1, 'fields' => 'ids']);

        if (!empty($posts_ids)) {
            foreach ($posts_ids as $post_id) {
                // Get the post object inside the loop
                $post = get_post($post_id);

                // Get product language (Polylang/WPML)
                $product_language = apply_filters('wpml_element_language_code', null, ['element_id' => $post->ID, 'element_type' => 'post']) ?: 'en';

                foreach ($attribute_custom_field_pairs as $pair) {
                    $attribute_id = $pair['attribute_id'];
                    $custom_field_id = $pair['custom_field_id'];
                    $categories = $pair['categories'];

                    // Check if the product ID is in the exempt list
                    if (in_array(needle: $post->ID, haystack: $product_ids_exempt, strict: true)) {
                        echo 'Product skipped: ' . $post->ID . ' - ' . $post->post_title . ' (' . $product_language . ')<br>';
                        continue;
                    }

                    // Check if the product is in the specified categories, if categories array is not empty
                    if (!empty($categories)) {
                        $product_categories = wp_get_post_terms($post->ID, 'product_cat', ['fields' => 'names']);
                        if (empty(array_intersect($categories, $product_categories))) {
                            continue;
                        }
                    }

                    // Get WooCommerce product object
                    $wc_product = wc_get_product($post->ID);
                    if (!$wc_product instanceof WC_Product) {
                        continue;
                    }

                    // Get the attribute values
                    $attributes = $wc_product->get_attributes();

                    if (isset($attributes[$attribute_id])) {
                        $processing_attribute = $attributes[$attribute_id];
                        if ($processing_attribute->is_taxonomy()) {
                            // Get terms associated with this attribute in the product's language
                            $terms = wp_get_post_terms($post->ID, $attribute_id, ['language' => $product_language]);

                            // Prepare labelled values
                            $labelled_values = '';
                            foreach ($terms as $term) {
                                $labelled_values .= '<label>' . esc_html($term->name) . '</label>';
                            }

                            // Get the current value of the custom field
                            $current_value = $wc_product->get_meta($custom_field_id, true);

                            // Update the custom field only if the new value is different
                            if ($current_value !== $labelled_values) {
                                update_post_meta($post->ID, $custom_field_id, $labelled_values);

                                echo 'Product processed: ' . $post->ID . ' - ' . $post->post_title . ' (' . $product_language . ')<br>';
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
    $post_id_exempt = [20766, 30721];

    $posts_ids = get_posts(['numberposts' => -1, 'post_type' => 'page', 'fields' => 'ids']);


    if (!empty($posts_ids)) {

        foreach ($posts_ids as $post_id) {
            $post = get_post($post_id);

            // Check if the current post ID is in the exclusion list
            if (in_array(needle: $post->ID, haystack: $post_id_exempt, strict: true)) {
                echo 'Page skipped: ' . $post->ID . ' - ' . $post->post_title . ' (' . $post->post_name . ')<br>';
                continue;
            }

            // Get the current slug before sanitizing
            $old_slug = $post->post_name;

            // Check the slug and run an update if necessary
            $new_slug = sanitize_title(title: $post->post_title);

            if ($old_slug !== $new_slug) {
                wp_update_post(postarr: [
                    'ID' => $post->ID,
                    'post_name' => $new_slug
                ], wp_error: false, fire_after_hooks: true);

                echo 'Page renamed: ' . $post->ID . ' - ' . $post->post_title . ' (' . $old_slug . ' -> ' . $new_slug . ')<br>';
            }
        }
    }


    // Regenerate slugs for products
    if (function_exists('WC')) {

        // Settings
        $post_id_exempt = [18215, 18373, 20116, 27123, 31441, 31459, 31488, 31538];

        $posts_ids = get_posts(['numberposts' => -1, 'post_type' => 'product', 'fields' => 'ids']);

        if (!empty($posts_ids)) {

            foreach ($posts_ids as $post_id) {
                $post = get_post($post_id);

                // Check if the current post ID is in the exclusion list
                if (in_array(needle: $post->ID, haystack: $post_id_exempt, strict: true)) {
                    echo 'Product skipped: ' . $post->ID . ' - ' . $post->post_title . ' (' . $post->post_name . ')<br>';
                    continue;
                }

                // Get the current slug before sanitizing
                $old_slug = $post->post_name;

                // Check the slug and run an update if necessary
                $new_slug = sanitize_title(title: $post->post_title);

                // Example of additional slug modification logic (uncomment if needed)
                // $new_slug = str_replace(['(', ')'], '', $new_slug);

                if ($old_slug !== $new_slug) {
                    wp_update_post(postarr: [
                        'ID' => $post->ID,
                        'post_name' => $new_slug
                    ], wp_error: false, fire_after_hooks: true);

                    echo 'Product renamed: ' . $post->ID . ' - ' . $post->post_title . ' (' . $old_slug . ' -> ' . $new_slug . ')<br>';
                }
            }

        }
    }


    // Regenerate slugs for attachments

    // Settings
    $attachment_id_exempt = [];

    // Query attachments that are not attached to any post ('post_parent' => null)
    $attachment_ids = get_posts(['post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => null, 'fields' => 'ids']);

    if (!empty($attachment_ids)) {

        // Rename title given file name
        foreach ($attachment_ids as $attachment_id) {
            $attachment = get_post($attachment_id);

            // Check if the current attachment ID is in the exclusion list
            if (in_array(needle: $attachment->ID, haystack: $attachment_id_exempt, strict: true)) {
                echo 'Attachment skipped: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $attachment->post_name . ')<br>';
                continue;
            }

            // Get the current file name
            $file_path = get_attached_file($attachment->ID);
            $file_name = basename($file_path);
            $file_name_without_extension = pathinfo(path: $file_name, flags: PATHINFO_FILENAME);

            // Update attachment title and slug
            if ($attachment->post_title !== $file_name_without_extension) {
                wp_update_post(postarr: [
                    'ID' => $attachment->ID,
                    'post_title' => $file_name_without_extension,
                    'post_name' => sanitize_title($file_name_without_extension)
                ], wp_error: false, fire_after_hooks: true);

                echo 'Attachment renamed: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $attachment->post_name . ' -> ' . sanitize_title($file_name_without_extension) . ')<br>';
            }
        }


        // Regenerate slugs
        foreach ($attachment_ids as $attachment_id) {
            $attachment = get_post($attachment_id);

            // Check if the current attachment ID is in the exclusion list
            if (in_array(needle: $attachment->ID, haystack: $attachment_id_exempt, strict: true)) {
                echo 'Attachment skipped: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $attachment->post_name . ')<br>';
                continue;
            }

            // Get the current slug before sanitizing
            $old_slug = $attachment->post_name;

            // Check the slug and run an update if necessary
            $new_slug = sanitize_title(title: $attachment->post_title);

            if ($old_slug !== $new_slug) {
                wp_update_post(postarr: [
                    'ID' => $attachment->ID,
                    'post_name' => $new_slug
                ], wp_error: false, fire_after_hooks: true);

                echo 'Attachment renamed: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $old_slug . ' -> ' . $new_slug . ')<br>';
            }
        }
    }

}
