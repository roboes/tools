<?php

// WooCommerce - Remove stock inventory count
// Last update: 2024-05-29

add_filter($hook_name = 'woocommerce_get_stock_html', $callback = 'woocommerce_product_stock_remove_inventory_count', $priority = 10, $accepted_args = 1);

function woocommerce_product_stock_remove_inventory_count()
{
    if (WC()) {
        return '';
    }
}
