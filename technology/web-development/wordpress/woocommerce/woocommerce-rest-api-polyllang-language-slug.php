<?php

// WooCommerce - Add Polylang language slug to WooCommerce REST API
// Last update: 2025-02-10

add_action($hook_name = 'rest_api_init', $callback = function () {
    register_rest_field('product', 'language_slug', [
        'get_callback' => function ($object, $field_name, $request) {
            return pll_get_post_language($object['id']);
        },
        'schema' => ['description' => __('Language of the product'), 'type' => 'string'],
    ]);
}, $priority = 10, $accepted_args = 1);
