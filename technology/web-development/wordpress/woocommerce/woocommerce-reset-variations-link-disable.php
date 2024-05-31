<?php

// WooCommerce - Disable reset variations link

add_filter($hook_name = 'woocommerce_reset_variations_link', $callback = '__return_empty_string', $priority = 10, $accepted_args = 1);
