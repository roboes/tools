<?php

// WooCommerce - Variable products hide variations with past dates and zero-stock variations

// Notes:
//- Attributes' Terms need to start with the following format in order to work: "DD.MM.YYYY" (e.g. "13.01.2025 - 14:30")
//- The "woocommerce_ajax_variation_threshold" setting determines the maximum number of variations a product can have before WooCommerce switches to using Ajax to load them. The default value is 30 variations (see https://woocommerce.com/document/change-limit-on-number-of-variations-for-dynamic-variable-product-dropdowns/)
// Last update: 2024-11-04


if (class_exists('WooCommerce') && WC()) {

    // Change the default "woocommerce_ajax_variation_threshold" setting to increase variable product variation threshold
    add_filter($hook_name = 'woocommerce_ajax_variation_threshold', $callback = function ($qty, $product) {
        return 60;
    }, $priority = 10, $accepted_args = 2);


    // Modify available variations (handles large variation sets)
    add_filter($hook_name = 'woocommerce_available_variation', $callback = 'hide_unavailable_variations', $priority = 10, $accepted_args = 3);

    function hide_unavailable_variations($variation_data, $product, $variation)
    {

        if (is_product()) {

            $timezone = get_option('timezone_string');
            $current_datetime = new DateTime('now', new DateTimeZone($timezone));

            // Setup: Check for 'Termin' attribute
            $attribute_value = $variation->get_attribute($attribute = 'Termin');

            // Check if the variation has the 'Termin' attribute and it's valid
            if ($attribute_value) {
                $term_date = substr($attribute_value, 0, 10); // Extract the date from the attribute (DD.MM.YYYY)
                if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $term_date)) {
                    $term_date = DateTime::createFromFormat('d.m.Y', $term_date, new DateTimeZone($timezone));

                    // Completely hide variation if the date is in the past
                    if ($term_date < $current_datetime) {
                        return false; // Hide the variation
                    }
                }
            }

            // Completely hide variation if stock is zero
            if ($variation->get_stock_quantity() === 0 || !$variation->is_in_stock()) {
                return false; // Hide the variation
            }

            return $variation_data;
        }
    }

}
