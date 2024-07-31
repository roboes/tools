<?php

// Two-Factor - Settings
// Requirements: "Two-Factor" plugin (https://wordpress.org/plugins/two-factor/ / https://github.com/WordPress/two-factor)
// Last update: 2024-07-13


if (class_exists('Two_Factor_Core')) {

    // Settings
    define('SENDER_EMAIL', 'email@website.com');

    add_filter($hook_name = 'wp_mail_from', $callback = function ($original_email_address) {return SENDER_EMAIL;}, $priority = 10, $accepted_args = 1);

    // Set sender name
    add_filter($hook_name = 'wp_mail_from_name', $callback = function ($original_email_from_name) {return get_option($option = 'blogname', $default_value = false);}, $priority = 10, $accepted_args = 1);

    // Ensure email content is HTML
    add_filter($hook_name = 'wp_mail_content_type', $callback = function ($content_type) {return 'text/html';}, $priority = 10, $accepted_args = 1);

}
