<?php

// WooCommerce - Disable reset variations link
// Last update: 2024-07-24

if (class_exists('WooCommerce') && WC()) {

    add_filter($hook_name = 'woocommerce_reset_variations_link', $callback = '__return_empty_string', $priority = 10, $accepted_args = 1);

}
