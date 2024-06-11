<?php

// WordPress Admin - Regenerate attribute labels on custom fields for products
// Last update: 2024-06-11

// Settings
$attribute_custom_field_pairs = array(
    array('attribute_id' => 'pa_coffee-type', 'custom_field_id' => 'product_type'),
    array('attribute_id' => 'pa_coffee-processing', 'custom_field_id' => 'product_coffee_selection'),
    array('attribute_id' => 'pa_weight', 'custom_field_id' => 'product_coffee_weight'),
);
$exempt_product_ids = array(19419);

$args = array('post_type' => 'product', 'posts_per_page' => -1);
$products = get_posts($args);

if (empty($products)) {
    echo 'No products found.';
} else {
    echo 'Products found: ' . count($products) . '<br>';

    foreach ($products as $product) {
        foreach ($attribute_custom_field_pairs as $pair) {
            $attribute_id = $pair['attribute_id'];
            $custom_field_id = $pair['custom_field_id'];

            // Check if the product ID is in the exempt list
            if (in_array($product->ID, $exempt_product_ids)) {

                echo '<br>';
                echo 'Skipping exempted Product ID: ' . $product->ID . ' - ' . $product->post_title . '<br>';

                continue;
            }



            // Get WooCommerce product object
            $wc_product = wc_get_product($product->ID);

            // Get the attribute values
            $attributes = $wc_product->get_attributes();

            if (isset($attributes[$attribute_id])) {
                $processing_attribute = $attributes[$attribute_id];
                if ($processing_attribute->is_taxonomy()) {
                    // Get terms associated with this attribute
                    $terms = wp_get_post_terms($product->ID, $attribute_id);
                    $labelled_values = '';
                    foreach ($terms as $term) {
                        $labelled_values .= '<label>' . esc_html($term->name) . '</label>';
                    }

                    // Update the custom field
                    update_post_meta($product->ID, $custom_field_id, $labelled_values);

                    echo '<br>';
                    echo 'Processing Product ID: ' . $product->ID . ' - ' . $product->post_title . '<br>';
                    echo 'Updated "' . $custom_field_id . '" custom field with: "' . $labelled_values . '"<br>';
                }
            }
        }
    }
}
