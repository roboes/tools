<?php

// WordPress test if a function is scheduled (run it on WP Console)
// Last update: 2024-05-29

// Get the timestamp when the function is scheduled
$scheduled_timestamp = wp_next_scheduled($hook = 'custom_field_product_shipping_class', $args = array());

if ($scheduled_timestamp !== false) {
    // Convert the timestamp to a readable date and time format
    $scheduled_datetime = date('Y-m-d H:i:s', $scheduled_timestamp);

    // Echo the scheduled datetime
    echo "Function scheduled for: $scheduled_datetime";
} else {
    echo "Function is not scheduled.";
}
