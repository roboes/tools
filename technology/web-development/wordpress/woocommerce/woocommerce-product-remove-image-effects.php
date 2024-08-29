<?php

// WooCommerce - Product remove image effects from Hello Elementor theme
// Last update: 2024-07-24

if (class_exists('WooCommerce') && WC()) {

    add_action($hook_name = 'wp', $callback = 'remove_woo_image_effects', $priority = 10, $accepted_args = 1);

    function remove_woo_image_effects()
    {

        // Disable gallery slider and lightbox
        remove_theme_support($feature = 'wc-product-gallery-lightbox');
        remove_theme_support($feature = 'wc-product-gallery-slider');

        // Disable the zoom
        remove_theme_support($feature = 'wc-product-gallery-zoom');

    }

}
