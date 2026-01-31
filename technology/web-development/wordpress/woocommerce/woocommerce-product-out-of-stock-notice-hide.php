<?php

// WooCommerce - Hide the out-of-stock notice for specific products
// Last update: 2026-01-15


if (function_exists(function_name: 'WC') && !is_admin()) {
    add_action(hook_name: 'wp_head', callback: function (): void {
        if (!is_product()) {
            return;
        }

        // Settings
        $product_ids = [12671, 31374, 12693, 31375, 12731, 31382, 19746, 31389];

        // Current product ID
        $product_id = (int) get_queried_object_id();

        if (in_array(needle: $product_id, haystack: $product_ids, strict: true)) {
            printf(
                '<style>
                    .variations_form.cart[data-product_id="%1$d"] .stock.out-of-stock {
                        display: none !important;
                    }
                </style>',
                $product_id
            );
        }
    }, priority: 10, accepted_args: 0);
}
