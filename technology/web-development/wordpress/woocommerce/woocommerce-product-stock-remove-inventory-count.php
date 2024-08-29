<?php

// WooCommerce - Remove stock inventory count
// Last update: 2024-07-24

if (class_exists('WooCommerce') && WC()) {

    add_filter($hook_name = 'woocommerce_get_stock_html', $callback = function () {return '';}, $priority = 10, $accepted_args = 1);

}
