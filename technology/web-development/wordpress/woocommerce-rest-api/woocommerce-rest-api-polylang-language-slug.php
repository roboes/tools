<?php

// WooCommerce - Add Polylang language slug to WooCommerce Rest API
// Last update: 2025-02-25

if (class_exists('WooCommerce') && WC() && class_exists('Polylang')) {

    add_filter($hook_name='woocommerce_rest_product_object_query', $callback = function ($args, $request) {
        if ($request->get_param('lang')) {
            $args['lang'] = sanitize_text_field($request->get_param('lang'));
        }
        return $args;
    }, priority = 10, $accepted_args = 2);

    add_action($hook_name = 'rest_api_init', $callback = function () {
        register_rest_field('product', 'lang', [
            'get_callback' => function ($object, $field_name, $request) {
                return pll_get_post_language($object['id']);
            },
            'schema' => ['description' => __('Language of the product'), 'type' => 'string'],
        ]);
    }, $priority = 10, $accepted_args = 1);

}
