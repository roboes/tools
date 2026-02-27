<?php

// WooCommerce - Add Polylang support to Germanized order email notifications
// Last update: 2026-02-15

/*
Notes:
- Fixes StoreaBill (Germanized) invoice emails to use order language instead of site default.
- Adds Polylang compatibility to StoreaBill's email system (which natively only supports WPML).
*/

if (function_exists('WC') && class_exists('WooCommerce_Germanized') && class_exists('Polylang')) {

    class WC_Email_Locale_Handler
    {
        public function __construct()
        {
            // Hook into StoreaBill's email locale system (adds Polylang support)
            add_action(hook_name: 'storeabill_switch_email_locale', callback: [$this, 'setup_storeabill_email_locale'], priority: 10, accepted_args: 2);
            add_action(hook_name: 'storeabill_restore_email_locale', callback: [$this, 'restore_storeabill_email_locale'], priority: 10, accepted_args: 1);
        }

        public function setup_storeabill_email_locale($email, $lang = false)
        {
            try {
                if (!is_object($email)) {
                    return;
                }

                // If explicit language provided, use it
                if ($lang && is_string($lang)) {
                    switch_to_locale($lang);
                    return;
                }

                // Find the document/invoice object using reflection
                $document = null;
                $reflection = new ReflectionObject($email);

                foreach (['object', 'document', 'invoice'] as $prop_name) {
                    if ($reflection->hasProperty($prop_name)) {
                        $property = $reflection->getProperty($prop_name);
                        $value = $property->getValue($email);
                        if (is_object($value) && method_exists($value, 'get_order')) {
                            $document = $value;
                            break;
                        }
                    }
                }

                // Get order and switch to its locale
                if ($document && method_exists($document, 'get_order')) {
                    $order = $document->get_order();
                    if ($order && method_exists($order, 'get_id')) {
                        $order_locale = function_exists('pll_get_post_language') ? (pll_get_post_language($order->get_id(), 'locale') ?: get_locale()) : get_locale();

                        if ($order_locale) {
                            switch_to_locale($order_locale);
                        }
                    }
                }
            } catch (Throwable $error) {
                error_log('WC_Email_Locale_Handler: ' . $error->getMessage());
            }
        }

        public function restore_storeabill_email_locale($email)
        {
            try {
                restore_previous_locale();
            } catch (Throwable $error) {
                error_log('WC_Email_Locale_Handler restore: ' . $error->getMessage());
            }
        }
    }

    new WC_Email_Locale_Handler();

}
