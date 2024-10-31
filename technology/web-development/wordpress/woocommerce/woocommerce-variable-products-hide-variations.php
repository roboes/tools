<?php

// WooCommerce - Variable products hide variations with past dates and zero-stock variations

// Note: Attributes' Terms need to start with the following format in order to work: "DD.MM.YYYY" (e.g. "13.01.2024 - 14:30 Uhr")
// Last update: 2024-10-16


if (class_exists('WooCommerce') && WC()) {

    // Modify available variations (handles large variation sets)
    add_filter($hook_name = 'woocommerce_available_variation', $callback = 'hide_unavailable_variations', $priority = 10, $accepted_args = 3);

    function hide_unavailable_variations($variation_data, $product, $variation)
    {

        if (is_product()) {

            $time_zone = get_option('timezone_string');
            $current_datetime = new DateTime('now', new DateTimeZone($time_zone));

            // Setup: Check for 'Termin' attribute
            $attribute_name = 'Termin';
            $attribute_value = $variation->get_attribute($attribute_name);

            // Check if the variation has the 'Termin' attribute and it's valid
            if ($attribute_value) {
                $term_date = substr($attribute_value, 0, 10); // Extract the date from the attribute (DD.MM.YYYY)
                if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $term_date)) {
                    $term_date = DateTime::createFromFormat('d.m.Y', $term_date, new DateTimeZone($time_zone));

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
