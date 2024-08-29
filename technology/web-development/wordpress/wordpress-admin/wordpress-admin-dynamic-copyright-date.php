<?php

// WordPress Admin - Dynamic "Copyright Date"
// Last update: 2024-07-07

add_shortcode($tag = 'current_year', $callback = function () {return date_i18n('Y');});
