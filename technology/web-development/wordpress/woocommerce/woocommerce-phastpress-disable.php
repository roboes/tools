<?php

// WooCommerce - Disable PhastPress on WooCommerce checkout and cart pages
// Last update: 2025-06-11

if (class_exists('WooCommerce') && WC() &&  class_exists('\Kibo\PhastPlugins\PhastPress\WordPress')) {

    add_filter($hook_name = 'phastpress_disable', $callback = function ($disable) {
        return $disable || is_cart() || is_checkout();
    }, $priority = 10, $accepted_args = 1);

}
