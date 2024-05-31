<?php

// WordPress Admin - Default Sort (for posts, products, etc.)
// Last update: 2024-05-29

add_action($hook_name = 'pre_get_posts', $callback = 'custom_default_sort_order', $priority = 10, $accepted_args = 1);

function custom_default_sort_order($query)
{
    if (is_admin() && $query -> is_main_query()) {
        $query -> set('orderby', 'title');
        $query -> set('order', 'ASC');
    }
}
