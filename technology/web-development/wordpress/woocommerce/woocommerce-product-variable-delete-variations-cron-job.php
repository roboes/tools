<?php

// WordPress WooCommerce - Variable product delete variations (cron job)
// Last update: 2026-01-18


// Unschedule all events attached to a given hook
// wp_clear_scheduled_hook(hook: 'cron_job_schedule_variable_product_delete_variations', args: [], wp_error: false);


// Run action once (run on WP Console)
// do_action(hook_name: 'cron_job_schedule_variable_product_delete_variations');


// Schedule cron job if not already scheduled
add_action(hook_name: 'init', callback: function (): void {

    if (!wp_next_scheduled(hook: 'cron_job_schedule_variable_product_delete_variations', args: [])) {

        // Settings
        $start_datetime = new DateTimeImmutable(datetime: 'next sunday 03:00:00', timezone: wp_timezone());

        wp_schedule_event(timestamp: $start_datetime->getTimestamp(), recurrence: 'weekly', hook: 'cron_job_schedule_variable_product_delete_variations', args: [], wp_error: false);
    }

}, priority: 10, accepted_args: 0);


add_action(hook_name: 'cron_job_schedule_variable_product_delete_variations', callback: 'product_variable_delete_variations', priority: 10, accepted_args: 0);


function product_variable_delete_variations(): void
{
    if (!function_exists('WC')) {
        return;
    }

    // Settings
    $product_ids = [17739, 22204];
    $delete = true;

    $current_datetime = new DateTimeImmutable(datetime: 'now', timezone: wp_timezone());

    $product_ids_to_process = [];

    foreach ($product_ids as $product_id) {
        // Handle Polylang/WPML translations
        $translations = apply_filters('wpml_get_element_translations', null, $product_id, 'post_product');

        if (!empty($translations) && is_array($translations)) {
            $product_ids_to_process = array_merge($product_ids_to_process, array_values(array_filter(array_column($translations, 'element_id'))));
        } else {
            $product_ids_to_process[] = (int) $product_id;
        }
    }

    $product_ids_to_process = array_unique($product_ids_to_process);

    // Now loop through all product IDs (original + translations)
    foreach ($product_ids_to_process as $product_id_current) {

        $product = wc_get_product($product_id_current);

        if (!$product instanceof WC_Product) {
            echo "Product ID {$product_id_current} not found.";
            continue;
        }

        $product_name = $product->get_name();  // Get product name

        if ($product->is_type(type: 'variable')) {
            $variations = $product->get_children();
            $variations_to_delete = [];

            foreach ($variations as $variation_id) {
                $variation = new WC_Product_Variation($variation_id);
                if (!$variation->get_id()) {
                    continue;
                }

                $variation_name = $variation->get_name();  // Get variation name
                $attributes = $variation->get_variation_attributes(); // Get variation attributes

                // Check for 'Termin' attribute
                $attribute_value = $variation->get_attribute(attribute: 'Termin');

                // Explicitly ignore gift cards so they are never deleted
                if (stripos($attribute_value, 'Gutschein') !== false || stripos($attribute_value, 'Gift Card') !== false) {
                    continue;
                }

                if ($attribute_value) {
                    $term_date_prefix = substr(string: $attribute_value, offset: 0, length: 10); // Extract date (DD.MM.YYYY)
                    if (preg_match(pattern: '/^\d{2}\.\d{2}\.\d{4}$/', subject: $term_date_prefix)) {
                        $term_date = DateTimeImmutable::createFromFormat(format: 'd.m.Y - H:i', datetime: $attribute_value, timezone: wp_timezone());

                        # Check if $term_date is false
                        if (!$term_date) {
                            echo "Warning: Could not parse date string '$attribute_value' for variation ID {$variation_id}.\n";
                            continue;
                        }

                        // Check if the date is in the past
                        if ($term_date < $current_datetime) {
                            $variations_to_delete[] = [
                                'product_id_current' => $product_id_current,
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
                    // Clear caches before deletion
                    wc_delete_product_transients(product_id: $product_id_current);
                    delete_transient(transient: 'wc_var_prices_' . $product_id_current);

                    foreach ($variations_to_delete as $variation_item) {
                        // Delete variation (force delete)
                        wp_delete_post(post_id: $variation_item['variation_id'], force_delete: true);

                        // Clear variation-specific caches
                        wc_delete_product_transients(product_id: $variation_item['variation_id']);

                        echo "Deleted variation - Product ID: {$variation_item['product_id_current']}, Product Name: {$variation_item['product_name']}, Variation ID: {$variation_item['variation_id']}, Variation Name: {$variation_item['variation_name']}, Attributes: " . wp_json_encode(data: $variation_item['attributes']) . "\n";
                    }

                    // Refresh the product object to get updated children list
                    $product = wc_get_product($product_id_current);

                    if ($product instanceof WC_Product_Variable) {
                        // Sync the parent product to update available variations
                        WC_Product_Variable::sync(product_id: $product_id_current);
                        $product->save();
                    }

                    // Clear all caches again after save
                    wc_delete_product_transients(product_id: $product_id_current);
                    delete_transient(transient: 'wc_var_prices_' . $product_id_current);
                    wp_cache_delete(key: 'product-' . $product_id_current, group: 'products');
                    clean_post_cache(post: $product_id_current);

                    // If using Redis/Object Cache, clear product-specific cache
                    wp_cache_delete(key: $product_id_current, group: 'post_meta');
                    wp_cache_delete(key: $product_id_current, group: 'posts');

                    echo "Synced and saved product ID $product_id_current.\n";

                } else {
                    foreach ($variations_to_delete as $variation_item) {
                        echo "Variation to be deleted - Product ID: {$variation_item['product_id_current']}, Product Name: {$variation_item['product_name']}, Variation ID: {$variation_item['variation_id']}, Variation Name: {$variation_item['variation_name']}, Attributes: " . wp_json_encode(data: $variation_item['attributes']) . "\n";
                    }
                }
            } else {
                echo "No variations to delete for product ID $product_id_current ($product_name).\n";
            }
        } else {
            echo "Product ID $product_id_current ($product_name) is not a variable product.\n";
        }
    }
}
