<?php

// WooCommerce Test - Shipping class information
// Last update: 2026-02-04

// Get all products including variations
$products = wc_get_products(['limit' => -1, 'type' => ['simple', 'variable', 'variation'], 'return' => 'objects']);

$grouped_data = [];

foreach ($products as $product) {
    $type = $product->get_type();
    $shipping_class = $product->get_shipping_class();

    // Handle inheritance for variations
    if ($type === 'variation' && empty($shipping_class)) {
        $parent = wc_get_product($product->get_parent_id());
        $shipping_class = ($parent->get_shipping_class() ?: 'None') . ' (Inherited)';
    }

    // Default label for empty classes
    $shipping_class = $shipping_class ?: 'None';

    // Store product details in the group
    $grouped_data[$shipping_class][] = [
        'id'   => $product->get_id(),
        'name' => $product->get_name(),
        'type' => $type
    ];
}

// Output the grouped results
foreach ($grouped_data as $shipping_class => $items) {
    echo "--- Shipping Class: " . $shipping_class . " (" . count($items) . " items) ---\n";
    foreach ($items as $item) {
        echo "ID: {$item['id']} | Type: {$item['type']} | Name: {$item['name']}\n";
    }
    echo "\n";
}
