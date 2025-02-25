<?php

// WooCommerce - Sent to all Shop Managers new order email notification, regardless of order status
// Last update: 2025-02-02

add_action($hook_name = 'woocommerce_new_order', $callback = function ($order_id) {
    $order = wc_get_order($order_id);

    // Get all Shop Managers
    $shop_manager_users = get_users(array(
        'role'   => 'shop_manager',
        'fields' => array('user_email')
    ));

    if (!empty($shop_manager_users)) {
        // Extract emails from user objects
        $shop_manager_emails = wp_list_pluck($shop_manager_users, 'user_email');
        $recipients = implode(',', $shop_manager_emails);

        // Load WooCommerce mailer
        $mailer = WC()->mailer();
        $email = $mailer->emails['WC_Email_New_Order'];

        if ($email) {
            // Override recipient to only send to shop managers
            $email->recipient = $recipients;
            $email->trigger($order_id, $order);
        }
    }
}, $priority = 10, $accepted_args = 1);
