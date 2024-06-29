<?php

// WordPress Test - Export WooCommerce products to .csv (run it on WP Console)
// Last update: 2024-06-13

function export_woocommerce_products_to_csv()
{
    // Check if the user is an admin
    if (!current_user_can('manage_options')) {
        return;
    }

    // Define the file path
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/woocommerce_products.csv';

    // Open the file for writing
    $output = fopen($file_path, 'w');

    if (!$output) {
        return;
    }

    // Write the BOM to the file to ensure UTF-8 encoding
    fwrite($output, "\xEF\xBB\xBF");

    // Query WooCommerce products to get all attributes
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
    );
    $loop = new WP_Query($args);

    // Collect all unique attribute names
    $attribute_names = array();
    while ($loop->have_posts()) {
        $loop->the_post();
        $product = wc_get_product(get_the_ID());

        if ($product->is_type('variable')) {
            $variations = $product->get_available_variations();
            foreach ($variations as $variation) {
                $variation_obj = wc_get_product($variation['variation_id']);
                $variation_attributes = $variation_obj->get_attributes();
                foreach ($variation_attributes as $attr_name => $attr_value) {
                    $attr_name = 'Attribute_' . wc_attribute_label($attr_name);
                    if (!in_array($attr_name, $attribute_names)) {
                        $attribute_names[] = $attr_name;
                    }
                }
            }
        }
    }
    // Reset post data
    wp_reset_postdata();

    // Output the column headings
    $headers = array_merge(
        array('Category', 'Type', 'ID', 'SKU', 'Name', 'Price', 'Stock'),
        $attribute_names
    );
    fputcsv($output, $headers);

    // Query WooCommerce products again to output data
    $loop = new WP_Query($args);

    // Loop through the products
    while ($loop->have_posts()) {
        $loop->the_post();
        $product = wc_get_product(get_the_ID());

        // Get product details
        $product_id = $product->get_id();
        $product_name = $product->get_name();
        $product_sku = $product->get_sku();
        $product_price = $product->get_price();
        $product_stock = $product->get_stock_quantity();
        $product_categories = strip_tags(wc_get_product_category_list($product_id));
        $product_type = $product->get_type();

        // Initialize row data
        $row = array(
            $product_categories, $product_type, $product_id, $product_sku,
            $product_name, $product_price, $product_stock
        );

        // Fill attributes columns with empty values initially
        $attribute_values = array_fill(0, count($attribute_names), '');

        // Handle simple products
        if ($product_type == 'simple') {
            $row = array_merge($row, $attribute_values);
            fputcsv($output, $row);
        } elseif ($product_type == 'variable') {
            // Output variable product data
            $row = array_merge($row, $attribute_values);
            fputcsv($output, $row);

            // Get product variations
            $variations = $product->get_available_variations();
            foreach ($variations as $variation) {
                $variation_id = $variation['variation_id'];
                $variation_obj = wc_get_product($variation_id);
                $variation_sku = $variation_obj->get_sku();
                $variation_price = $variation_obj->get_price();
                $variation_stock = $variation_obj->get_stock_quantity();
                $variation_attributes = $variation_obj->get_attributes();

                // Initialize variation row
                $variation_row = array(
                    $product_categories, 'variation', $variation_id, $variation_sku,
                    $product_name . ' - ' . wc_get_formatted_variation($variation_obj, true),
                    $variation_price, $variation_stock
                );

                // Fill variation attributes
                $variation_attribute_values = array_fill(0, count($attribute_names), '');
                foreach ($variation_attributes as $attr_name => $attr_value) {
                    $attr_name_label = 'Attribute_' . wc_attribute_label($attr_name);
                    $index = array_search($attr_name_label, $attribute_names);
                    if ($index !== false) {
                        $variation_attribute_values[$index] = $attr_value;
                    }
                }

                $variation_row = array_merge($variation_row, $variation_attribute_values);
                fputcsv($output, $variation_row);
            }
        }
    }

    // Reset post data
    wp_reset_postdata();

    // Close the file
    fclose($output);

    echo "CSV file has been created at: " . $file_path;
}

// Run the function (for testing purpose in WP-Console or WP-CLI)
export_woocommerce_products_to_csv();
