<?php

// WooCommerce Test - Order meta information
// Last update: 2024-08-27


// Settings
$order_id = 1234;
$order = wc_get_order($order_id);


if ($order) {
    $order_meta = get_post_meta($order_id);
    echo '<pre>';
    print_r($order_meta);
    echo '</pre>';
} else {
    echo 'Order not found.';
}


if ($order) {
    $order_data = $order->get_data();
    echo '<pre>';
    print_r($order_data);
    echo '</pre>';
} else {
    echo 'Order not found.';
}


# Accessing Order Meta Data with High-Performance Order Storage (HPOS)
if ($order) {
    $order_meta = $order->get_meta_data();
    echo '<pre>';
    print_r($order_meta);
    echo '</pre>';
} else {
    echo 'Order not found.';
}
