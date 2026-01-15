<?php

// WooCommerce - Add "Delete Account" button to "Account Details" page
// Last update: 2026-01-14

if (function_exists('WC') && !is_admin()) {

    // Add "Delete Account" button to the "Account Details" page
    add_action(hook_name: 'woocommerce_account_edit-account_endpoint', callback: 'delete_account_button_adder', priority: 10, accepted_args: 1);

    function delete_account_button_adder(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        // Settings
        $messages = [
            'account_delete_button' => [
                'de' => 'Account Löschen',
                'en' => 'Delete Account',
            ],
            'confirm_deletion' => [
                'de' => 'Bist du sicher, dass du dein Konto dauerhaft löschen möchtest? Dies kann nicht rückgängig gemacht werden.',
                'en' => 'Are you sure you want to permanently delete your account? This action cannot be undone.',
            ],
        ];

        // Get current language
        $current_language = 'en';
        if (function_exists('pll_current_language')) {
            if (pll_current_language('slug') && in_array(needle: pll_current_language('slug'), haystack: pll_languages_list(['fields' => 'slug']), strict: true)) {
                $current_language = pll_current_language('slug');
            }
        }

        $button_text = esc_html($messages['account_delete_button'][$current_language]);
        $confirm_text = esc_js($messages['confirm_deletion'][$current_language]);

        echo '<br>
        <p>
            <form method="post" action="' . esc_url(get_permalink()) . '" onsubmit="return confirm(\'' . $confirm_text . '\');">
                ' . wp_nonce_field('delete_account_nonce', 'delete_account_nonce_field', true, false) . '
                <button type="submit" name="delete-account" class="elementor-widget-button" style="background-color: #d9534f; color: white; border: none; padding: 10px 20px; cursor: pointer;">' . $button_text . '</button>
            </form>
        </p>';
    }


    // Handle account deletion
    add_action(hook_name: 'template_redirect', callback: 'account_deletion_handler', priority: 10, accepted_args: 1);

    function account_deletion_handler(): void
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        if (!is_user_logged_in() || !isset($_POST['delete-account'])) {
            return;
        }

        // Settings
        $messages = [
            'account_delete_error' => [
                'de' => 'Du kannst dein Konto derzeit nicht löschen. Bitte stelle sicher, dass alle Bestellungen abgeschlossen sind und deine letzte Bestellung mindestens 14 Tage alt ist.',
                'en' => 'You cannot delete your account at this time. Make sure all orders are completed and the last order is at least 14 days old.',
            ],
        ];

        if (check_admin_referer('delete_account_nonce', 'delete_account_nonce_field')) {
            $user_id = (int) get_current_user_id();

            if (!current_user_can('administrator') && account_deletion_verifier($user_id)) {
                // Delete user and redirect
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                wp_delete_user($user_id);
                wp_redirect(home_url());
                exit;
            } else {
                // Get current language
                $current_language = 'en';
                if (function_exists('pll_current_language')) {
                    if (pll_current_language('slug') && in_array(needle: pll_current_language('slug'), haystack: pll_languages_list(['fields' => 'slug']), strict: true)) {
                        $current_language = pll_current_language('slug');
                    }
                }

                wc_add_notice($messages['account_delete_error'][$current_language], 'error');
                wp_redirect(wc_get_account_endpoint_url('edit-account'));
                exit;
            }
        }
    }


    // Check if the user can delete their account
    function account_deletion_verifier(int $user_id): bool
    {
        if ($user_id <= 0) {
            return false;
        }

        // Settings
        $allowed_roles = ['customer'];

        $user = get_user_by('ID', $user_id);
        if (!$user instanceof WP_User) {
            return false;
        }

        // Check if the user role is allowed to delete account
        if (!empty($user->roles) && in_array(array_first($user->roles), $allowed_roles, true)) {
            // Get completed orders
            $orders = wc_get_orders(['customer_id' => $user_id, 'status' => 'completed', 'limit' => -1]);

            // Check if there are completed orders
            if (!empty($orders)) {
                $last_order = array_last($orders);
                $last_completed_date = $last_order->get_date_completed();

                // Check if the last completed order is at least 14 days old
                if ($last_completed_date && $last_completed_date->getTimestamp() > strtotime('-14 days')) {
                    return false; // Cannot delete account if last order is not old enough
                }
            }

            // Allow account deletion if no orders or all orders are old enough
            return true;
        }

        return false; // Default to disallow deletion for other roles
    }

}
