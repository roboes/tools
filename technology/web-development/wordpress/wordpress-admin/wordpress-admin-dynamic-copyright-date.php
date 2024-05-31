<?php

// WordPress Admin - Dynamic "Copyright Date"
// Last update: 2024-05-29

add_shortcode($tag = 'current_year', $callback = 'get_year');

function get_year()
{
    $year = date_i18n('Y');
    return $year;
}
