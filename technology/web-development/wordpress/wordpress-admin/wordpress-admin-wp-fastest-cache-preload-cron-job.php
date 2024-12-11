<?php

// WordPress Admin - Run WP Fastest Cache Preload update twice daily (cron job)
// Last update: 2024-11-23


// Unschedule all events attached to a given hook
// wp_clear_scheduled_hook($hook='wp_fastest_cache_preload_cron_job', $args=array(), $wp_error=false);


// Run action once (run on WP Console)
// do_action($hook_name='wp_fastest_cache_preload_cron_job');



if (class_exists('WpFastestCache')) {

    // Schedule cron job if not already scheduled
    add_action($hook_name = 'wp_loaded', $callback = function () {

        if (!wp_next_scheduled($hook = 'wp_fastest_cache_preload_cron_job', $args = array())) {

            // Settings
            $start_datetime = '2024-11-23 14:30:00'; // Time is the same as the WordPress defined get_option('timezone_string');

            $start_datetime = new DateTime($start_datetime);
            $start_timestamp = $start_datetime->getTimestamp();

            // Schedule the cron job to run twice daily (every 12 hours)
            wp_schedule_event($timestamp = $start_timestamp, $recurrence = 'twicedaily', $hook = 'wp_fastest_cache_preload_cron_job');
        }
    }, $priority = 10, $accepted_args = 1);


    add_action($hook_name = 'wp_fastest_cache_preload_cron_job', $callback = function () {(new WpFastestCache())->create_preload_cache();}, $priority = 10, $accepted_args = 1);

}
