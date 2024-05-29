<?php

// WooCommerce - Variable products hide variations if term dates are in the past

// Note: Attributes' Terms need to start with the following format in order to work: "DD.MM.YYYY" (e.g. "13.01.2024 - 14:30 Uhr")


add_filter($hook_name = 'woocommerce_variation_is_visible', $callback = 'hide_past_date_attributes', $priority = 10, $accepted_args = 2);

function hide_past_date_attributes($visible, $variation_id)
{
    if (WC()) {
        // Setup
        $attributes_to_check = array( 'Appointment', 'Termin' );
        $time_zone = 'Europe/Berlin';

        // Get current date and time
        $current_datetime = new DateTime('now', new DateTimeZone($time_zone));
        $current_date = $current_datetime->format('Y-m-d');

        $product = wc_get_product($variation_id);

        foreach ($attributes_to_check as $attribute_name) {
            // Check if the product has the attribute
            if ($product->get_attribute($attribute_name)) {
                // Get the term names for the attribute
                $term_names = $product->get_attribute($attribute_name);

                // Explode term names into an array
                $terms = explode(', ', $term_names);

                foreach ($terms as $term) {
                    // Extract date from term name
                    $term_date = substr($term, 0, 10);

                    // Check if date matches the format dd.mm.yyyy
                    if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $term_date)) {
                        $term_date = DateTime::createFromFormat('d.m.Y', $term_date, new DateTimeZone($time_zone));

                        // Check if the date is in the past
                        if ($term_date < $current_datetime) {
                            $visible = false; // Hide the variation
                            break 2; // No need to check other attributes and terms
                        }
                    }
                }
            }
        }

        return $visible;
    }
}
