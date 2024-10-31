<?php

// WordPress Admin - Load "Font Awesome" locally
// Last update: 2024-10-31

add_action($hook_name = 'wp_enqueue_scripts', $callback = 'enqueue_font_awesome', $priority = 10, $accepted_args = 1);

function enqueue_font_awesome()
{
    wp_enqueue_style($handle = 'font-awesome-local', $src = content_url('/fonts/fontawesome/css/all.min.css'));
}
