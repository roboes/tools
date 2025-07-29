<?php

// WooCommerce Test - Order meta information
// Last update: 2025-07-14


// Settings
$order_id = 1234;
$order = wc_get_order($order_id);


if ($order) {
    $order_meta = $order->get_meta_data();
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
