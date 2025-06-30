<?php

// WooCommerce - Hide the out-of-stock notice for specific products
// Last update: 2025-06-25


if (class_exists('WooCommerce') && WC()) {

    add_action($hook_name = 'wp_head', $callback = function () {

        if (is_product()) {

            // Settings
            $product_ids = [12671, 31374, 12693, 31375, 12731, 31382, 19746, 31389];

            // Current product ID
            $product_id = get_queried_object_id();

            if (in_array($product_id, $product_ids, true)) {
                printf(
                    '<style>
                        .variations_form.cart[data-product_id="%1$d"] .stock.out-of-stock {
                            display: none !important;
                        }
                    </style>',
                    intval($product_id)
                );
            }

        }

    }, $priority = 10, $accepted_args = 1);

};
