<?php

// Elementor - Deactivate Google Fonts (https://elementor.com/help/speed-up-a-slow-site/#external-fonts)
// Last update: 2024-06-18

if (is_plugin_active('elementor/elementor.php')) {

    add_filter($hook_name =  'elementor/frontend/print_google_fonts', $callback = '__return_false', $priority = 10, $accepted_args = 1);

}
