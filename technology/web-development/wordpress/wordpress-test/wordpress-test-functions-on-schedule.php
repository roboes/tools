<?php

// WordPress Test - Test if a function is scheduled (run it on WP Console)
// Last update: 2024-06-21

// Get the timestamp when the function is scheduled
$scheduled_timestamp = wp_next_scheduled($hook = 'function_slugs_update_daily', $args = array());

if ($scheduled_timestamp !== false) {
    // Convert the timestamp to a readable date and time format
    $scheduled_datetime = date('Y-m-d H:i:s', $scheduled_timestamp);

    // Echo the scheduled datetime
    echo "Function scheduled for: $scheduled_datetime";
} else {
    echo "Function is not scheduled.";
}
