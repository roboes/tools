<?php

// WooCommerce - Variations Stock
// Last update: 2024-09-02

function get_all_variation_stock($product_ids)
{
    $variation_stock_data = array();

    foreach ($product_ids as $product_id) {
        // Get the product object
        $product = wc_get_product($product_id);

        // Check if the product has variations
        if ($product->is_type('variable')) {
            // Get the product's variations
            $available_variations = $product->get_available_variations();

            foreach ($available_variations as $variation) {
                $variation_id = $variation['variation_id'];

                // Prepare the variation name
                $attribute_names = array();
                foreach ($variation['attributes'] as $attribute => $value) {
                    $taxonomy = str_replace('attribute_', '', $attribute);
                    $term = get_term_by('slug', $value, $taxonomy);

                    if ($term) {
                        $attribute_names[] = $term->name;
                    } else {
                        $attribute_names[] = $value;
                    }
                }
                $variation_name = implode(' - ', $attribute_names);

                // Get the variation product object
                $variation_product = wc_get_product($variation_id);

                // Get the stock quantity for the variation
                $stock_quantity = $variation_product->get_stock_quantity();

                // Save the variation stock data
                $variation_stock_data[$product_id][$variation_id] = array(
                    'name' => $variation_name,
                    'stock' => $stock_quantity
                );
            }
        }
    }

    return $variation_stock_data;
}


$variation_stock = get_all_variation_stock($product_ids = array(22204));

// Output the variation stock data
echo '<pre>';
print_r($variation_stock);
echo '</pre>';
