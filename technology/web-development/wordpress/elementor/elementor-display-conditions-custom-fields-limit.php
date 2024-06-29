<?php

// Elementor - "Display Conditions" custom fields limit
// Last update: 2024-05-29

if (is_plugin_active('elementor/elementor.php')) {

    add_filter($hook_name = 'elementor_pro/display_conditions/dynamic_tags/custom_fields_meta_limit', $callback = 'custom_custom_fields_meta_limit', $priority = 10, $accepted_args = 1);

    function custom_custom_fields_meta_limit($limit)
    {
        $new_limit = 100;
        return $new_limit;
    }

}
