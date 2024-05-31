<?php

// WordPress Admin - Add "Modified Date" column to Posts and Pages
// Last update: 2024-05-29

add_filter($hook_name = 'manage_pages_columns', $callback = 'custom_columns', $priority = 10, $accepted_args = 1);

// Adds a new sortable "last updated" column to posts and pages backend.
function custom_columns($defaults)
{
    $defaults['modified_date'] = __('Modified Date', 'your-textdomain');
    return $defaults;
}


add_action($hook_name = 'manage_pages_custom_column', $callback = 'custom_columns_content', $priority = 10, $accepted_args = 2);

function custom_columns_content($column_name, $post_id)
{
    if (is_admin() && $column_name == 'modified_date') {
        $modified_date = get_the_modified_date(get_option('date_format')); // Retrieve date format from WordPress settings
        echo $modified_date;
    }
}


add_filter($hook_name = 'manage_edit-page_sortable_columns', $callback = 'custom_columns_sortable', $priority = 10, $accepted_args = 1);

function custom_columns_sortable($columns)
{
    $columns['modified_date'] = 'modified_date';
    return $columns;
}


add_action($hook_name = 'pre_get_posts', $callback = 'custom_columns_orderby', $priority = 10, $accepted_args = 1);

function custom_columns_orderby($query)
{
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');

    if ('modified_date' == $orderby) {
        $query->set('orderby', 'modified');
    }
}
