<?php

// WordPress Admin - Settings
// Last update: 2025-10-08

// Disable WordPress from automatically generating intermediate image sizes
add_filter($hook_name = 'intermediate_image_sizes_advanced', $callback = '__return_empty_array', $priority = 10, $accepted_args = 1);
add_filter($hook_name = 'big_image_size_threshold', $callback = '__return_false', $priority = 10, $accepted_args = 1);

// Load "Font Awesome" locally
add_action($hook_name = 'wp_enqueue_scripts', $callback = function () {
    wp_enqueue_style($handle = 'font-awesome-local', $src = content_url('/fonts/fontawesome/css/all.min.css'));
}, $priority = 10, $accepted_args = 1);

// Email - Disable automatic WordPress core update email notification
add_filter($hook_name = 'auto_core_update_send_email', $callback = '__return_false', $priority = 10, $accepted_args = 1);

// Email - Disable automatic WordPress plugin update email notification
add_filter($hook_name = 'auto_plugin_update_send_email', $callback = '__return_false', $priority = 10, $accepted_args = 1);

// Email - Disable automatic WordPress theme update email notification
add_filter($hook_name = 'auto_theme_update_send_email', '__return_false', $priority = 10, $accepted_args = 1);
