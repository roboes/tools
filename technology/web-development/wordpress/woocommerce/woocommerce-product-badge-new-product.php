<?php

// WooCommerce - Add custom CSS class 'badge-new-product' for products created within the last 6 months
// Last update: 2024-07-24

if (class_exists('WooCommerce') && WC()) {

    add_filter($hook_name = 'post_class', $callback = 'add_new_product_css_class', $priority = 10, $accepted_args = 3);

    function add_new_product_css_class($classes, $class, $post_id)
    {

        // Check if the product was created within the last 6 months
        if (get_the_time('U', $post_id) > strtotime('-6 months')) {
            // Add 'badge-new-product' CSS class
            $classes[] = 'badge-new-product';
        }

        return $classes;

    }

}
