<?php

// WooCommerce - Force country display even if the order billing and shipping addresses match the store address
// Last update: 2025-01-17

add_filter($hook_name = 'woocommerce_formatted_address_force_country_display', $callback = '__return_true', $priority = 10, $accepted_args = 1);
