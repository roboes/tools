<?php

// WordPress Admin - Regenerate slugs for attachments, pages and products
// Last update: 2024-06-13


// Pages

// Settings
$post_id_exempt = array(20766);

$posts = get_posts(array('numberposts' => -1, 'post_type' => 'page'));

foreach ($posts as $post) {
    // Check if the current post ID is in the exclusion list
    if (in_array($post->ID, $post_id_exempt)) {
        echo 'Page skipped: ' . $post->ID . ' - ' . $post->post_title . ' (' . $post->post_name . ')<br>';
        continue;
    }

    // Get the current slug before sanitizing
    $old_slug = $post->post_name;

    // Check the slug and run an update if necessary
    $new_slug = sanitize_title($title = $post->post_title);

    // Example of additional slug modification logic (uncomment if needed)
    // $new_slug = str_replace(['(', ')'], '', $new_slug);

    if ($old_slug != $new_slug) {
        wp_update_post(array(
            'ID' => $post->ID,
            'post_name' => $new_slug
        ), $wp_error = false, $fire_after_hooks = true);

        echo 'Page renamed: ' . $post->ID . ' - ' . $post->post_title . ' (' . $old_slug . ' -> ' . $new_slug . ')<br>';
    }
}



// Products

// Settings
$post_id_exempt = array(18215, 18373, 20116, 27123);

$posts = get_posts(array('numberposts' => -1, 'post_type' => 'product'));

foreach ($posts as $post) {
    // Check if the current post ID is in the exclusion list
    if (in_array($post->ID, $post_id_exempt)) {
        echo 'Product skipped: ' . $post->ID . ' - ' . $post->post_title . ' (' . $post->post_name . ')<br>';
        continue;
    }

    // Get the current slug before sanitizing
    $old_slug = $post->post_name;

    // Check the slug and run an update if necessary
    $new_slug = sanitize_title($title = $post->post_title);

    // Example of additional slug modification logic (uncomment if needed)
    // $new_slug = str_replace(['(', ')'], '', $new_slug);

    if ($old_slug != $new_slug) {
        wp_update_post(array(
            'ID' => $post->ID,
            'post_name' => $new_slug
        ), $wp_error = false, $fire_after_hooks = true);

        echo 'Product renamed: ' . $post->ID . ' - ' . $post->post_title . ' (' . $old_slug . ' -> ' . $new_slug . ')<br>';
    }
}



// Attachments

// Settings
$attachment_id_exempt = array(); //

// Query attachments that are not attached to any post ('post_parent' => null)
$attachments = get_posts(array('post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => null));

// Rename title given file name
foreach ($attachments as $attachment) {
    // Check if the current attachment ID is in the exclusion list
    if (in_array($attachment->ID, $attachment_id_exempt)) {
        echo 'Attachment skipped: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $attachment->post_name . ')<br>';
        continue;
    }

    // Get the current file name
    $file_path = get_attached_file($attachment->ID);
    $file_name = basename($file_path);
    $file_name_without_extension = pathinfo($file_name, PATHINFO_FILENAME);

    // Update attachment title and slug
    if ($attachment->post_title != $file_name_without_extension) {
        wp_update_post(array(
            'ID' => $attachment->ID,
            'post_title' => $file_name_without_extension,
            'post_name' => sanitize_title($file_name_without_extension)
        ), $wp_error = false, $fire_after_hooks = true);

        echo 'Attachment renamed: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $attachment->post_name . ' -> ' . sanitize_title($file_name_without_extension) . ')<br>';
    }
}

// Regenerate slugs
foreach ($attachments as $attachment) {
    // Check if the current attachment ID is in the exclusion list
    if (in_array($attachment->ID, $attachment_id_exempt)) {
        echo 'Attachment skipped: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $attachment->post_name . ')<br>';
        continue;
    }

    // Get the current slug before sanitizing
    $old_slug = $attachment->post_name;

    // Check the slug and run an update if necessary
    $new_slug = sanitize_title($attachment->post_title);

    if ($old_slug != $new_slug) {
        wp_update_post(array(
            'ID' => $attachment->ID,
            'post_name' => $new_slug
        ), $wp_error = false, $fire_after_hooks = true);

        echo 'Attachment renamed: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $old_slug . ' -> ' . $new_slug . ')<br>';
    }
}
