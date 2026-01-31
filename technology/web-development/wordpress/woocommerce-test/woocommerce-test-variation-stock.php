<?php

// WooCommerce - Variations Stock
// Last update: 2025-07-04

function get_all_variation_stock($product_ids)
{
    $variation_stock_data = [];

    foreach ($product_ids as $product_id) {
        // Get the product object
        $product = wc_get_product($product_id);

        // Check if the product has variations
        if ($product->is_type('variable')) {
            // Get all variation IDs
            $variation_ids = $product->get_children();

            foreach ($variation_ids as $variation_id) {
                // Get the variation product object
                $variation_product = wc_get_product($variation_id);

                if (!$variation_product) {
                    continue;
                }

                // Prepare the variation name
                $attribute_names = [];
                $attributes = $variation_product->get_attributes();

                foreach ($attributes as $attribute => $value) {
                    $taxonomy = str_replace('attribute_', '', $attribute);
                    $term = get_term_by('slug', $value, $taxonomy);

                    if ($term) {
                        $attribute_names[] = $term->name;
                    } else {
                        $attribute_names[] = $value;
                    }
                }
                $variation_name = implode(' - ', $attribute_names);

                // Get the stock quantity for the variation
                $stock_quantity = $variation_product->get_stock_quantity();

                // Save the variation stock data
                $variation_stock_data[$product_id][$variation_id] = [
                    'name' => $variation_name,
                    'stock' => $stock_quantity
                ];
            }
        }
    }

    return $variation_stock_data;
}


$variation_stock = get_all_variation_stock(product_ids: [22204, 31437]);

// Output the variation stock data
echo '<pre>';
print_r($variation_stock);
echo '</pre>';
