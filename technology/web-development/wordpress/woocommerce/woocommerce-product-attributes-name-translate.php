<?php

// WooCommerce - Product attributes name translate
// Last update: 2024-11-22


if (class_exists('WooCommerce') && WC()) {

    add_action($hook_name = 'after_setup_theme', $callback = 'translate_attributes_name', $priority = 10, $accepted_args = 1);

    function translate_attributes_name()
    {

        if (function_exists('pll_current_language')) {

            // Setup
            $translations = array(
                'en' => array(
                    'Termin' => 'Appointment',
                    'Auswahl 1' => 'Selection 1',
                    'Auswahl 2' => 'Selection 2',
                    'Auswahl 3' => 'Selection 3',
                    'Auswahl 1 (250 g)' => 'Selection 1 (250 g)',
                    'Auswahl 2 (250 g)' => 'Selection 2 (250 g)',
                    'Auswahl 3 (500 g)' => 'Selection 3 (500 g)',
                    'Zubehör' => 'Accesories',
                    '2x Cappuccino Tasse' => '2x Cappuccino Cup',
                    '2x Espresso Tasse' => '2x Espresso Cup',
                )
            );

            // Hook into the gettext filter
            add_filter($hook_name = 'gettext', $callback = function ($translated, $text, $domain) use ($translations) {
                $current_language = (function_exists('pll_current_language') && in_array(pll_current_language('slug'), pll_languages_list(array('fields' => 'slug')))) ? pll_current_language('slug') : 'en';
                if (isset($translations[$current_language][$text])) {
                    $translated = $translations[$current_language][$text];
                }
                return $translated;

            }, $priority = 10, $accepted_args = 3);
        }
    }

}
