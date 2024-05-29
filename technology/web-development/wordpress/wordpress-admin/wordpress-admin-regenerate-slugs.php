<?php

// WordPress Admin - Regenerate slugs for products
// Last update: 2024-05-29

$posts = get_posts(array( 'numberposts' => -1, 'post_type' => 'product' ));

foreach ($posts as $post) {
    // check the slug and run an update if necessary
    $new_slug = sanitize_title($title = $post->post_title);

    // Remove specific characters the post name
    // $new_slug = str_replace( $search=['(', ')'], $replace='', $subject=$new_slug);

    if ($post->post_name != $new_slug) {
        wp_update_post($postarr = array( 'ID' => $post->ID, 'post_name' => $new_slug ), $wp_error = false, $fire_after_hooks = true);
    }
}
