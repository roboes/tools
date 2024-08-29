<?php

// Two-Factor - Ensure email-based Two-Factor Authentication (2FA) by default and as primary for all WooCommerce "customer" users daily
// Requirements: "Two-Factor" plugin (https://wordpress.org/plugins/two-factor/ / https://github.com/WordPress/two-factor)
// Last update: 2024-07-05


// Unschedule all events attached to a given hook
// wp_clear_scheduled_hook($hook='function_two_factor_authentication_update_daily', $args=array(), $wp_error=false);


// Run action once (run on WP Console)
// do_action($hook_name='function_two_factor_authentication_update_daily');


if (class_exists('Two_Factor_Core')) {

    // Schedule cron job if not already scheduled
    add_action($hook_name = 'wp_loaded', $callback = 'schedule_custom_cron_job_function_two_factor_authentication_update_daily', $priority = 10, $accepted_args = 1);

    function schedule_custom_cron_job_function_two_factor_authentication_update_daily()
    {
        if (! wp_next_scheduled($hook = 'function_two_factor_authentication_update_daily', $args = array())) {

            // Settings
            $start_datetime = '2024-07-05 00:30:00'; // Time is the same as the WordPress defined get_option('timezone_string');

            $start_datetime = new DateTime($start_datetime);
            $start_timestamp = $start_datetime->getTimestamp();

            wp_schedule_event($timestamp = $start_timestamp, $recurrence = 'daily', $hook = 'function_two_factor_authentication_update_daily', $args = array(), $wp_error = false);
        }
    }

    add_action($hook_name = 'function_two_factor_authentication_update_daily', $callback = 'function_two_factor_authentication_update_daily_run', $priority = 10, $accepted_args = 1);

    function function_two_factor_authentication_update_daily_run()
    {
        // Settings
        $two_factor_roles = array('customer');

        // Get all users with the specified roles
        $users = get_users(array('role__in' => $two_factor_roles, 'fields' => 'ID'));

        // Loop through each user
        foreach ($users as $user_id) {
            // Check if the user is using Two-Factor authentication
            if (!Two_Factor_Core::is_user_using_two_factor($user_id)) {
                // Enable email Two-Factor authentication for the user
                $enabled_providers = get_user_meta($user_id, '_two_factor_enabled_providers', true);
                if (empty($enabled_providers)) {
                    $enabled_providers = array();
                }
                if (!in_array('Two_Factor_Email', $enabled_providers)) {
                    $enabled_providers[] = 'Two_Factor_Email';
                }
                update_user_meta($user_id, '_two_factor_enabled_providers', $enabled_providers);

                // Set email as the primary Two-Factor authentication method
                update_user_meta($user_id, '_two_factor_primary_provider', 'Two_Factor_Email');
            }
        }
    }

}
