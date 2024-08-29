<?php

// Elementor - "Display Conditions" custom fields limit
// Last update: 2024-07-04

if (is_plugin_active('elementor/elementor.php')) {

    add_filter($hook_name = 'elementor_pro/display_conditions/dynamic_tags/custom_fields_meta_limit', $callback = function ($limit) {return 200;}, $priority = 10, $accepted_args = 1);

}
