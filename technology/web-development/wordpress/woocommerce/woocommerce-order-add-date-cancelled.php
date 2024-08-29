<?php

// WooCommerce - Add order date cancelled
// Last update: 2024-08-26

if (class_exists('WooCommerce') && WC()) {

    add_action($hook_name = 'woocommerce_order_status_cancelled', $callback = 'add_order_date_cancelled', $priority = 10, $accepted_args = 2);

    function add_order_date_cancelled($order_id, $order)
    {
        $timezone = new DateTimeZone($order->get_date_created()->getTimezone()->getName()); // Get timezone from order creation date
        $date_cancelled = new WC_DateTime('now', $timezone); // Get current datetime with the timezone from order creation date

        // Add date cancelled as custom metadata and save
        $order->update_meta_data('date_cancelled', $date_cancelled->format(DateTime::ATOM));
        $order->save();
    }

}
