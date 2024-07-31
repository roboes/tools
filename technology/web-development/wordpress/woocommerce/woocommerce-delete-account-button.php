<?php

// WooCommerce - Add "Delete Account" button to "Account Details" page
// Last update: 2024-07-24

if (class_exists('WooCommerce') && WC()) {

    // Function to get the message
    function get_message($type, $language = 'en')
    {
        $messages = [
            'account_delete_button' => [
                'de' => 'Account Löschen',
                'en' => 'Delete Account',
            ],
            'account_delete_error' => [
                'de' => 'Sie können Ihr Konto derzeit nicht löschen. Stellen Sie sicher, dass alle Bestellungen abgeschlossen sind und die letzte Bestellung mindestens 14 Tage alt ist.',
                'en' => 'You cannot delete your account at this time. Make sure all orders are completed and the last order is at least 14 days old.',
            ],
        ];
        return isset($messages[$type][$language]) ? $messages[$type][$language] : $messages[$type]['en'];
    }

    // Add "Delete Account" button to the "Account Details" page
    add_action($hook_name = 'woocommerce_account_edit-account_endpoint', $callback = 'delete_account_button_adder', $priority = 10, $accepted_args = 1);

    function delete_account_button_adder()
    {
        $current_language = function_exists('pll_current_language') ? pll_current_language('slug') : 'en';
        $button_text = esc_html(get_message('account_delete_button', $current_language));
        echo '<br>
		<p>
			<form method="post" action="' . esc_url(wp_nonce_url(get_permalink() . 'delete-account', 'delete_account_nonce')) . '">
				<button type="submit" name="delete-account" class="elementor-widget-button">' . $button_text . '</button>
			</form>
		</p>';
    }

    // Handle account deletion
    add_action($hook_name = 'template_redirect', $callback = 'account_deletion_handler', $priority = 10, $accepted_args = 1);

    function account_deletion_handler()
    {
        if (is_user_logged_in() && isset($_POST['delete-account']) && check_admin_referer('delete_account_nonce')) {
            $user_id = get_current_user_id();
            if (!current_user_can($capability = 'administrator') && account_deletion_verifier($user_id)) {
                // Delete user and redirect
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                wp_delete_user($user_id);
                wp_redirect(home_url());
                exit;
            } else {
                $current_language = function_exists('pll_current_language') ? pll_current_language('slug') : 'en';
                wc_add_notice(get_message('account_delete_error', $current_language), 'error');
                wp_redirect(wc_get_account_endpoint_url('edit-account'));
                exit;
            }
        }
    }


    // Check if the user can delete their account
    function account_deletion_verifier($user_id)
    {
        // Settings
        $allowed_roles = array('customer');

        $user = get_user_by('ID', $user_id);

        // Check if the user role is allowed to delete account
        if (in_array($user->roles[0], $allowed_roles)) {
            // Get completed orders
            $orders = wc_get_orders(array('customer_id' => $user_id, 'status' => 'completed', 'limit' => -1));

            // Check if there are completed orders
            if (!empty($orders)) {
                $last_order = end($orders);
                $last_completed_date = $last_order->get_date_completed();

                // Check if the last completed order is at least 14 days old
                if ($last_completed_date && strtotime($last_completed_date) >= strtotime('-14 days')) {
                    return false; // Cannot delete account if last order is not old enough
                }
            }

            // Allow account deletion if no orders or all orders are old enough
            return true;
        }

        return false; // Default to disallow deletion for other roles
    }

}
