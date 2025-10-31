<?php

// WordPress Admin - Settings
// Last update: 2025-10-09


// Default sort
add_action($hook_name = 'pre_get_posts', $callback = function ($query) {
    global $is_wc_order_screen;

    if (is_admin() && $query->is_main_query() && empty($is_wc_order_screen)) {
        $query->set('orderby', 'title');
        $query->set('order', 'ASC');
    }
}, $priority = 10, $accepted_args = 1);

// Dynamic "Copyright Date" shortcode
add_shortcode($tag = 'current_year', $callback = function () {return date_i18n('Y');});

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
add_filter($hook_name = 'auto_theme_update_send_email', $callback = '__return_false', $priority = 10, $accepted_args = 1);

// Elementor - Disable accordion scroll animation
// add_filter($hook_name = 'pp_advanced_accordion_scroll_animation', $callback = '__return_false', $priority = 10, $accepted_args = 1);
