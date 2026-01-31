<?php

// WooCommerce - Product attribute names and attribute term names translate
// Last update: 2026-01-18


if (function_exists('WC') && !is_admin()) {

    // Translate attribute names
    add_filter(hook_name: 'woocommerce_attribute_label', callback: 'translate_attribute_name', priority: 10, accepted_args: 3);

    // Translate attribute term names
    add_filter(hook_name: 'woocommerce_variation_option_name', callback: 'translate_attribute_term_name', priority: 10, accepted_args: 1);


    function translate_attribute_name($label, $name, $product)
    {

        $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'de';

        if ($browsing_language === 'en') {
            $titles = [
                'Termin' => 'Appointment',
                'Gutschein (Termin später wählbar)' => 'Gift Card (Date to be arranged)',
                'Auswahl 1' => 'Selection 1',
                'Auswahl 2' => 'Selection 2',
                'Auswahl 3' => 'Selection 3',
                'Auswahl 1 (250 g)' => 'Selection 1 (250 g)',
                'Auswahl 2 (250 g)' => 'Selection 2 (250 g)',
                'Auswahl 3 (500 g)' => 'Selection 3 (500 g)',
                'Zubehör' => 'Accesories',
                '2x Cappuccino Tasse' => '2x Cappuccino Cup',
                '2x Espresso Tasse' => '2x Espresso Cup',
            ];

            if (isset($titles[$label])) {
                return $titles[$label];
            }
        }
        return $label;
    }

    function translate_attribute_term_name($term_name)
    {

        $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'de';

        if ($browsing_language === 'en') {
            $values = [
                'Gutschein (Termin später wählbar)' => 'Gift Card (Date to be arranged)',
                'Gutschein (Später wählbar)'        => 'Gift Card (Selection later)',
            ];

            if (isset($values[$term_name])) {
                return $values[$term_name];
            }
        }
        return $term_name;
    }

}
