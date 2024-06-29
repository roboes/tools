<?php

// WordPress Admin - Disable email notifications sent to the WordPress admin when a user resets their password
// Last update: 2024-06-25

remove_action($hook_name = 'after_password_reset', $callback = 'wp_password_change_notification', $priority = 10);
