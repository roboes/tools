<?php

// WooCommerce - Product attributes name translate
// Last update: 2024-10-10


if (class_exists('WooCommerce') && WC()) {

    add_action($hook_name = 'after_setup_theme', $callback = 'translate_attributes_name', $priority = 10, $accepted_args = 1);

    function translate_attributes_name()
    {

        if (function_exists('pll_current_language')) {

            // Setup - Define translations for different languages (format: 'Original language attribute name' => 'Translated attribute name')
            $translations = array(
                'en' => array(
                    'Termin' => 'Appointment'
                )
            );

            // Hook into the gettext filter
            add_filter($hook_name = 'gettext', $callback = function ($translated, $text, $domain) use ($translations) {
                $current_language = pll_current_language('slug');
                if (isset($translations[$current_language][$text])) {
                    $translated = $translations[$current_language][$text];
                }
                return $translated;

            }, $priority = 10, $accepted_args = 3);
        }

    }
}
