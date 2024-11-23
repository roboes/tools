<?php

// WooCommerce Function - Update SKUs given product attribute names
// Last update: 2024-11-19

function safe_slug($string)
{
    // Settings
    $words_exception = ['g', 'kg'];
    $words_replaced = [
        'WithSpecialtyCoffeeAssociationscaCertification' => 'WithSCA',
        'WithoutSpecialtyCoffeeAssociationscaCertification' => 'WithoutSCA',
    ];

    // Remove accents and special characters, and convert to lowercase
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

    // Replace `/` with a space
    $string = str_replace('/', ' ', $string);

    // Capitalize the first letter of each word and keep exceptions in original form
    $words = explode(' ', $string);
    $words = array_map(function ($word) use ($words_exception) {
        if (in_array(strtolower($word), $words_exception)) {
            return $word; // Keep exceptions in original form
        } else {
            return ucfirst(strtolower($word));
        }
    }, $words);
    $string = implode(' ', $words);

    // Remove non-alphanumeric characters
    $string = preg_replace('/[^a-zA-Z0-9_-]/', '', $string);
    $string = str_replace(' ', '', $string);

    // Replace specific words at the end
    foreach ($words_replaced as $original => $replacement) {
        $string = str_replace($original, $replacement, $string);
    }

    return $string;
}


function woocommerce_product_sku_update_given_attribute_names()
{
    // Query to get all products in "Specialty Coffee" category
    $args = array('post_type' => 'product', 'posts_per_page' => -1, 'post_status' => array('publish', 'private'), 'tax_query' => array(array('taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => 'specialty-coffees-en')));
    $products = get_posts($args);

    foreach ($products as $product_post) {
        $product = wc_get_product($product_post->ID);
        $parent_sku = $product->get_sku();

        if ($product->is_type('variable')) {
            // For variable products, update each variation
            $variations = $product->get_children();
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                $attributes = $variation->get_attributes();
                $variation_sku_parts = array($parent_sku);

                // Build the SKU from the attributes, ensuring safe names
                foreach ($attributes as $attribute_name => $attribute_value) {
                    // Get the term name for the attribute
                    $term = get_term_by('slug', $attribute_value, $attribute_name);
                    if ($term) {
                        $safe_value = safe_slug($term->name);
                    } else {
                        $safe_value = safe_slug($attribute_value);
                    }
                    $variation_sku_parts[] = $safe_value;
                }

                // Join the parts to form the new SKU
                $new_sku = implode('-', $variation_sku_parts);
                if ($variation->get_sku() !== $new_sku) {
                    $variation->set_sku($new_sku);
                    $variation->save();
                    echo 'Updated SKU for Variation ID ' . $variation_id . ' to ' . $new_sku . '<br>';
                }
            }
        }
    }
}

// Execute the function
woocommerce_product_sku_update_given_attribute_names();
