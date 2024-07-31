<?php

// WordPress Admin - Disable email notifications sent to the WordPress admin when a user resets their password
// Last update: 2024-07-24


// Disable automatic WordPress core update email notification
add_filter($hook_name = 'auto_core_update_send_email', $callback = '__return_false', $priority = 10, $accepted_args = 1);

// Disable automatic WordPress plugin update email notification
add_filter($hook_name = 'auto_plugin_update_send_email', $callback = '__return_false', $priority = 10, $accepted_args = 1);

// Disable automatic WordPress theme update email notification
add_filter($hook_name = 'auto_theme_update_send_email', '__return_false', $priority = 10, $accepted_args = 1);

// Check if WooCommerce is active and disable password change notification email
if (class_exists('WooCommerce') && WC()) {
    add_filter($hook_name = 'woocommerce_disable_password_change_notification', $callback = '__return_true', $priority = 10, $accepted_args = 1);
}
