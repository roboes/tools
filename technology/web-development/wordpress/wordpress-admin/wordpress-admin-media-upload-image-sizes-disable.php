<?php

// WordPress Admin - Disable WordPress from automatically generating intermediate image sizes
// Last update: 2024-11-14


add_filter($hook_name = 'intermediate_image_sizes_advanced', $callback = '__return_empty_array', $priority = 10, $accepted_args = 1);
add_filter($hook_name = 'big_image_size_threshold', $callback = '__return_false', $priority = 10, $accepted_args = 1);


// if (class_exists('WooCommerce') && WC()) {

// // Use original images for WooCommerce product display
// add_filter($hook_name = 'woocommerce_get_image_size_single', $callback = 'use_original_image_size', $priority = 10, $accepted_args = 1);
// add_filter($hook_name = 'woocommerce_get_image_size_thumbnail', $callback = 'use_original_image_size', $priority = 10, $accepted_args = 1);
// add_filter($hook_name = 'woocommerce_get_image_size_gallery_thumbnail', $callback = 'use_original_image_size', $priority = 10, $accepted_args = 1);

// function use_original_image_size($size)
// {
// return ['width'  => null, 'height' => null, 'crop'   => 0];
// }

// }
