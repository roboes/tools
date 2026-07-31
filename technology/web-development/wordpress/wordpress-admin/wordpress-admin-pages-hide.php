<?php

// WordPress Admin - Hide pages from being indexed search engines
// Last update: 2026-07-08

if (!is_admin()) {

    add_action(hook_name: 'wp_head', callback: 'wordpress_pages_hide', priority: 10, accepted_args: 1);

    function wordpress_pages_hide()
    {
        $settings_page_ids_hide = [38606, 38655, 38939, 45911];

        if (is_page($settings_page_ids_hide)) {
            echo '<meta name="robots" content="noindex, nofollow">' . "\n";
        }
    }

}
