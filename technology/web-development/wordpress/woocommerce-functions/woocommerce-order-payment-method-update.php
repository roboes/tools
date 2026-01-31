<?php

// WooCommerce - Order payment method update
// Last update: 2025-07-29

function woocommerce_payment_methods_get()
{

    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    echo '<pre>';
    foreach ($gateways as $gateway_id => $gateway) {
        echo "ID: {$gateway_id} - Title: {$gateway->get_title()}\n";
    }
    echo '</pre>';

}


function woocommerce_order_payment_method_update($order_id, $payment_method_id_new)
{

    $order = wc_get_order($order_id);
    if ($order) {
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (isset($available_gateways[$payment_method_id_new])) {
            $order->set_payment_method($available_gateways[$payment_method_id_new]);
            $order->save();
            echo "Payment method changed to: " . $available_gateways[$payment_method_id_new]->get_title();
        } else {
            echo "Payment method ID '{$payment_method_id_new}' not available.";
        }
    } else {
        echo "Order ID {$order_id} not found.";
    }
}


woocommerce_payment_methods_get();

// woocommerce_order_payment_method_update(order_id: 10000, payment_method_id_new: 'bacs');

// Tests
$order_id = 10000;
echo (float) wc_get_order($order_id)->get_meta('PayPal Transaction Fee', true);
