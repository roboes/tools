<?php

// WooCommerce - Add custom CSS class 'badge-new-product' for products created within the last 6 months
// Last update: 2026-01-15


if (function_exists('WC') && !is_admin()) {

    add_filter(hook_name: 'post_class', callback: 'add_new_product_css_class', priority: 10, accepted_args: 3);

    function add_new_product_css_class(array $classes, string|array $class, int $post_id): array
    {
        if (get_post_type(post: $post_id) !== 'product') {
            return $classes;
        }

        $post_time = (int) get_the_time(format: 'U', post: $post_id);
        $threshold = strtotime(datetime: '-6 months');

        // Check if the product was created within the last 6 months
        if ($post_time > $threshold) {
            // Add 'badge-new-product' CSS class
            $classes[] = 'badge-new-product';
        }

        return $classes;
    }
}
