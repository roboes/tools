<?php

// WordPress Admin - Remove media attributes: alternative text, caption and description
// Last update: 2024-06-14

function remove_media_attributes()
{

    // Settings
    $media_id_exceptions = array();

    // List of meta keys to clear
    $meta_keys_to_clear = array(
        '_wp_attachment_image_alt' => 'image_alt',
        '_wp_attachment_caption' => 'caption',
        '_wp_attachment_description' => 'description',
    );

    $attachments = get_posts(array('post_type' => 'attachment', 'posts_per_page' => -1, 'post_status' => 'inherit'));

    foreach ($attachments as $attachment) {
        // Check if the current attachment ID is in the exclusion list
        if (in_array($attachment->ID, $media_id_exceptions)) {
            echo 'Attachment skipped: ' . $attachment->ID . ' - ' . $attachment->post_title . ' (' . $attachment->post_name . ')<br>';
            continue;
        }

        // Remove alternative text (image_alt)
        $alt_text_removed = delete_metadata('post', $attachment->ID, '_wp_attachment_image_alt', '', true);
        if ($alt_text_removed) {
            echo 'Attachment attribute image_alt removed: ' . $attachment->ID . ' - ' . $attachment->post_title . '<br>';
        } else {
            echo 'Failed to remove image_alt for attachment ' . $attachment->ID . '.<br>';
        }

        // Remove caption (post excerpt)
        $caption_removed = wp_update_post(array(
            'ID'           => $attachment->ID,
            'post_excerpt' => '',
        ));

        if (is_wp_error($caption_removed)) {
            echo 'Failed to clear caption (post excerpt) for attachment ' . $attachment->ID . ': ' . $caption_removed->get_error_message() . '<br>';
        } else {
            echo 'Caption (post excerpt) cleared successfully for attachment ' . $attachment->ID . '.<br>';
        }

        // Remove description (post content)
        $description_removed = wp_update_post(array(
            'ID'           => $attachment->ID,
            'post_content' => '',
        ));

        if (is_wp_error($description_removed)) {
            echo 'Failed to clear description (post content) for attachment ' . $attachment->ID . ': ' . $description_removed->get_error_message() . '<br>';
        } else {
            echo 'Description (post content) cleared successfully for attachment ' . $attachment->ID . '.<br>';
        }
    }
}

remove_media_attributes();
