<?php

// WordPress Admin - Disable WordPress from automatically generating intermediate image sizes
// Last update: 2024-07-10


add_filter($hook_name = 'intermediate_image_sizes_advanced', $callback = '__return_empty_array', $priority = 10, $accepted_args = 1);
add_filter($hook_name = 'big_image_size_threshold', $callback = '__return_false', $priority = 10, $accepted_args = 1);
