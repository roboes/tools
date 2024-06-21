<?php

// WordPress Admin - Default Sort (for posts, products, etc.)
// Last update: 2024-06-18

add_action($hook_name = 'current_screen', $callback = 'set_wc_order_screen_flag', $priority = 10, $accepted_args = 1);


function set_wc_order_screen_flag($current_screen)
{
    global $is_wc_order_screen;
    $is_wc_order_screen = ($current_screen->id === 'edit-shop_order');
}


add_action($hook_name = 'pre_get_posts', $callback = 'custom_default_sort_order', $priority = 10, $accepted_args = 1);

function custom_default_sort_order($query)
{
    global $is_wc_order_screen;

    if (is_admin() && $query->is_main_query() && empty($is_wc_order_screen)) {
        $query->set('orderby', 'title');
        $query->set('order', 'ASC');
    }
}
