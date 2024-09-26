<?php

// WooCommerce - Remove WooCommerce "Process your orders on the go. Get the app." message
// Last update: 2024-09-05

if (class_exists('WooCommerce') && WC()) {

    add_action($hook_name = 'woocommerce_email_footer', $callback = 'woocommerce_get_the_app_message_removal', $priority = 8, $accepted_args = 1);

    function woocommerce_get_the_app_message_removal()
    {
        $mailer = WC()->mailer()->get_emails();
        $object = $mailer['WC_Email_New_Order'];
        remove_action($hook_name = 'woocommerce_email_footer', $callback = array($object, 'mobile_messaging'), $priority = 9);
    }

}
