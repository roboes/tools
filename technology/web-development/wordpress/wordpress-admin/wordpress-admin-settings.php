<?php

// WordPress Admin - Settings
// Last update: 2026-01-21

// Default sort
add_action(hook_name: 'pre_get_posts', callback: function (WP_Query $query): void {

    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    // Do not apply alphabetical sort if we are on the WooCommerce Orders screen - handles both legacy (edit-shop_order) and HPOS (woocommerce_page_wc-orders)
    $is_order_screen = $screen && in_array(needle: $screen->id, haystack: ['edit-shop_order', 'woocommerce_page_wc-orders'], strict: true);

    if ($is_order_screen) {
        return;
    }

    $post_type = $query->get('post_type');

    if (in_array(needle: $post_type, haystack: ['attachment', 'page', 'post', 'product', 'elementor_library'], strict: true)) {
        $query->set('orderby', 'title');
        $query->set('order', 'ASC');
    }

}, priority: 10, accepted_args: 1);

// Dynamic "Copyright Date" shortcode
add_shortcode(tag: 'current_year', callback: function (): string {
    return date_i18n('Y');
});

// Disable WordPress from automatically generating intermediate image sizes
add_filter(hook_name: 'intermediate_image_sizes_advanced', callback: '__return_empty_array', priority: 10, accepted_args: 0);
add_filter(hook_name: 'big_image_size_threshold', callback: '__return_false', priority: 10, accepted_args: 0);

// Load "Font Awesome" locally
add_action(hook_name: 'wp_enqueue_scripts', callback: function (): void {
    if (is_admin()) {
        return;
    }

    wp_enqueue_style(handle: 'font-awesome-local', src: content_url('/fonts/fontawesome/css/all.min.css'));
}, priority: 10, accepted_args: 0);

// Email - Disable automatic WordPress core update email notification
add_filter(hook_name: 'auto_core_update_send_email', callback: '__return_false', priority: 10, accepted_args: 0);

// Email - Disable automatic WordPress plugin update email notification
add_filter(hook_name: 'auto_plugin_update_send_email', callback: '__return_false', priority: 10, accepted_args: 0);

// Email - Disable automatic WordPress theme update email notification
add_filter(hook_name: 'auto_theme_update_send_email', callback: '__return_false', priority: 10, accepted_args: 0);

// Disable automatic smart punctuation
add_filter(hook_name: 'run_wptexturize', callback: '__return_false', priority: 10, accepted_args: 0);

// Elementor - Disable accordion scroll animation
// add_filter(hook_name: 'pp_advanced_accordion_scroll_animation', callback: '__return_false', priority: 10, accepted_args: 0);
