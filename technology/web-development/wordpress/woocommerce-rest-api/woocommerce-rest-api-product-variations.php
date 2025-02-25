<?php

// WooCommerce REST API - Custom endpoint to fetch WooCommerce products variations by 'sku' and/or 'modified_after'
// Last update: 2025-02-25


add_action($hook_name = 'rest_api_init', $callback = function () {
    register_rest_route('wc/v3', '/product-variations', array('methods' => 'GET', 'callback' => 'custom_get_product_variations_by_sku',
    'permission_callback' => function () {return current_user_can('read'); }, 'args' => array('sku' => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field', ), 'modified_after' => array('required' => false, 'sanitize_callback' => 'sanitize_text_field', ),),));
}, $priority = 10, $accepted_args = 1);

function custom_get_product_variations_by_sku(WP_REST_Request $request)
{
    $sku = $request->get_param('sku');
    $modified_after = $request->get_param('modified_after');

    $args = array(
        'post_type' => 'product_variation',
        'posts_per_page' => -1,
    );

    // Apply SKU filter only if provided
    if (! empty($sku)) {
        $args['meta_query'] = array(
            array(
                'key' => '_sku',
                'value' => $sku,
                'compare' => '=',
            ),
        );
    }

    // Apply modified_after filter only if provided
    if (! empty($modified_after)) {
        $args['date_query'] = array(
            array(
                'column' => 'post_modified_gmt',
                'after' => $modified_after,
                'inclusive' => true,
            ),
        );
    }

    $query = new WP_Query($args);
    $variations = array();

    if ($query->have_posts()) {
        foreach ($query->posts as $post) {
            $product_variation = new WC_Product_Variation($post->ID);
            $variations[] = $product_variation->get_data();
        }
    }

    return rest_ensure_response($variations);
}
