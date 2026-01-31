<?php

// WooCommerce - Variable products hide variations with past dates and zero-stock variations
// Last update: 2026-01-15

// Notes:
//- Attributes' Terms need to start with the following format in order to work: "DD.MM.YYYY" (e.g. "13.01.2025 - 14:30")
//- The "woocommerce_ajax_variation_threshold" setting determines the maximum number of variations a product can have before WooCommerce switches to using Ajax to load them. The default value is 30 variations (see https://woocommerce.com/document/change-limit-on-number-of-variations-for-dynamic-variable-product-dropdowns/)


if (function_exists('WC') && !is_admin()) {

    // Change the default "woocommerce_ajax_variation_threshold" setting to increase variable product variation threshold
    add_filter(hook_name: 'woocommerce_ajax_variation_threshold', callback: static function (int $qty, WC_Product $product): int {
        return 60;
    }, priority: 10, accepted_args: 2);

    // Modify available variations (handles large variation sets)
    add_filter(hook_name: 'woocommerce_available_variation', callback: 'hide_unavailable_variations', priority: 10, accepted_args: 3);

    function hide_unavailable_variations(array|false $variation_data, WC_Product_Variable $product, WC_Product_Variation $variation): array|false
    {

        if (!is_product()) {
            return $variation_data;
        }

        // Completely hide variation if stock is zero
        if ($variation->get_stock_quantity() === 0 || !$variation->is_in_stock()) {
            return false;
        }

        // Setup: Check for 'Termin' attribute
        $attribute_value = $variation->get_attribute(attribute: 'Termin');

        // Check if the variation has the 'Termin' attribute and it's valid
        if ($attribute_value) {
            $term_date_string = substr(string: $attribute_value, offset: 0, length: 10); // Extract the date from the attribute (DD.MM.YYYY)

            if (preg_match(pattern: '/^\d{2}\.\d{2}\.\d{4}$/', subject: $term_date_string)) {
                $term_date = DateTimeImmutable::createFromFormat(format: 'd.m.Y', datetime: $term_date_string, timezone: wp_timezone());
                $current_datetime = new DateTimeImmutable(datetime: 'now', timezone: wp_timezone());
                // Completely hide variation if the date is in the past
                if ($term_date && $term_date < $current_datetime) {
                    return false;
                }
            }
        }
        return $variation_data;
    }

}
