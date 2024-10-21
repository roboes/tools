<?php

// WooCommerce - Order email notifications language set based on order using Polylang
// Last update: 2024-10-18


// To prevent issues where simultaneous purchases by different users in different languages might result in email notifications being sent in the wrong language, the code avoids using global variables. Instead, it handles locale switching in an isolated manner for each request.


if (class_exists('WooCommerce') && WC() && class_exists('Polylang')) {

    class WC_Email_Locale_Handler
    {
        public function __construct()
        {
            // Hook into the email header to switch locale before email content
            add_action($hook_name = 'woocommerce_email_header', $callback = [$this, 'set_email_locale_based_on_order'], $priority = 10, $accepted_args = 2);
            add_action($hook_name = 'woocommerce_email_before_order_table', $callback = [$this, 'set_email_locale_based_on_order'], $priority = 10, $accepted_args = 2);

            // Hook into the email footer to restore the original locale after email content
            add_action($hook_name = 'woocommerce_email_footer', $callback = [$this, 'restore_email_locale'], $priority = 10, $accepted_args = 1);

            // Hook into resending emails to handle locale switching
            add_action($hook_name = 'woocommerce_before_resend_order_emails', $callback = [$this, 'set_locale_on_woocommerce_before_resend_order_emails'], $priority = 10, $accepted_args = 1);

            // Hook into email recipient filter to switch locale before generating the subject
            add_filter($hook_name = 'woocommerce_email_recipient_new_order', $callback = [$this, 'set_locale_on_woocommerce_email_recipient_new_order'], $priority = 10, $accepted_args = 2);
        }


        public function set_email_locale_based_on_order($email_heading, $email)
        {
            if (isset($email->object)) {
                // Get the language locale of the order
                $order_locale = $this->get_locale_from_object($email->object);

                if ($order_locale) {
                    // Switch to the order's locale
                    switch_to_locale($order_locale);
                }
            }
        }


        public function restore_email_locale()
        {
            restore_previous_locale();
        }


        public function set_locale_on_woocommerce_before_resend_order_emails($order)
        {
            if ($order) {
                // Get the language locale of the order
                $order_locale = $this->get_locale_from_object($order);

                if ($order_locale) {
                    // Switch to the order's locale
                    switch_to_locale($order_locale);
                }
            }
        }


        public function set_locale_on_woocommerce_email_recipient_new_order($to, $order)
        {
            // Get the order locale
            $order_locale = $this->get_locale_from_object($order);

            if ($order_locale) {
                // Switch to the order's locale before generating the subject
                switch_to_locale($order_locale);
            }

            return $to;
        }


        private function get_locale_from_object($object)
        {
            $order = null;

            // Check if the object is a WC_Order or if the object is a Shipment/Invoice from "Germanized for WooCommerce" plugin
            if (is_a($object, 'WC_Order')) {
                $order = $object;
            } elseif (is_a($object, 'Vendidero\Germanized\Shipments\Shipment') || is_a($object, 'Vendidero\StoreaBill\Invoice\Invoice')) {
                $order = $object->get_order();
            }

            if ($order && function_exists('pll_get_post_language')) {
                // Get the language locale of the order
                $order_language = (function_exists('pll_get_post_language') && in_array(pll_get_post_language($order->get_id(), 'locale'), pll_languages_list(array('fields' => 'locale')))) ? pll_get_post_language($order->get_id(), 'locale') : 'en_US';

                // Map the language slug to a locale code
                return $order_language;
            }

            return null;
        }
    }


    // Initialize the handler class
    new WC_Email_Locale_Handler();

}
