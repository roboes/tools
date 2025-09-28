<?php

// WooCommerce - Automatically create product variations for a given variable product by combining a global attribute (using predefined term slugs) with a local attribute (using custom labels), while setting stock, price, and other basic variation data
// Last update: 2025-06-20


function woocommerce_product_variations_creation()
{
    // Settings
    $product_id = 22204;

    // Local attribute - labels
    $attribute_appointment = [
        '23.08.2025 - 14:30',
        '11.10.2025 - 14:30',
        '25.10.2025 - 14:30',
        '08.11.2025 - 14:30',
        '29.11.2025 - 14:30',
    ];

    // Global attribute (defined under Products → Attributes in WooCommerce) - term slugs
    $attribute_training_own_portafilter = ['training-own-coffee-machine-with-de', 'training-own-coffee-machine-without-de'];

    $product = wc_get_product($product_id);
    if (! $product || $product->get_type() !== 'variable') {
        echo "Product {$product_id} is not a variable product.\n";
        return;
    }

    // Check existence of attributes in product
    $required = ['termin', 'pa_training-own-portafilter'];
    foreach ($required as $slug) {
        if (! $product->get_attribute($slug)) {
            echo "Attribute '{$slug}' not found on product {$product_id}.\n";
            return;
        }
    }

    foreach ($attribute_appointment as $appointment) {
        foreach ($attribute_training_own_portafilter as $training_own_portafilter) {

            # Create variation
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);

            // Set attributes
            $variation->set_attributes([
                'termin' => $appointment,
                'pa_training-own-portafilter' => $training_own_portafilter
            ]);

            // Set other properties
            $variation->set_regular_price('99.00');
            $variation->set_manage_stock(true);
            $variation->set_stock_quantity(4);
            $variation->set_stock_status('instock');

            // Save variation
            try {
                $variation_id = $variation->save();
                echo "Created variation #{$variation_id} for Termin {$appointment} + Own Portafilter {$training_own_portafilter}.\n";
            } catch (Exception $error) {
                echo "Error creating variation for Termin {$appointment} + Own Portafilter {$training_own_portafilter}: ". $error->getMessage() . "\n";
                return;
            }

        }
    }
}


woocommerce_product_variations_creation();


// Update SKUs given product attribute names
if (function_exists('woocommerce_product_sku_update_given_attribute_names')) {
    woocommerce_product_sku_update_given_attribute_names();
} else {
    echo 'Function woocommerce_product_sku_update_given_attribute_names() is not defined.';
}


// Delete older product variations
if (function_exists('variable_product_delete_variations')) {
    variable_product_delete_variations($product_ids = [17739, 22204], $delete = true);
} else {
    echo 'Function variable_product_delete_variations() is not defined.';
}
