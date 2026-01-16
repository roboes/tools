<?php

// WooCommerce - Product attributes name translate
// Last update: 2026-01-15


// Note: Manual translation for product attributes not registered in WooCommerce > Attributes. Standard global attributes should be translated via Polylang's "Translations" settings instead


if (function_exists('WC') && !is_admin()) {
    add_action(hook_name: 'init', callback: 'translate_attributes_name', priority: 10, accepted_args: 0);

    function translate_attributes_name(): void
    {

        if (function_exists('pll_current_language')) {

            // Setup
            $translations = [
                'en' => [
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
                ]
            ];

            // Hook into the gettext filter
            add_filter(hook_name: 'gettext', callback: function (string $translated, string $text, string $domain) use ($translations): string {
                // Get current language
                $current_language = 'en';
                if (function_exists('pll_current_language')) {
                    if (pll_current_language('slug') && in_array(needle: pll_current_language('slug'), haystack: pll_languages_list(['fields' => 'slug']), strict: true)) {
                        $current_language = pll_current_language('slug');
                    }
                }

                if (isset($translations[$current_language][$text])) {
                    $translated = $translations[$current_language][$text];
                }

                return $translated;
            }, priority: 10, accepted_args: 3);
        }
    }
}
