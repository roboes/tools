<?php

// WooCommerce - Add "Delete Account" button to "Account Details" page
// Last update: 2026-01-15


if (function_exists('WC') && !is_admin()) {

    // Add "Delete Account" button to the "Account Details" page
    add_action(hook_name: 'woocommerce_account_edit-account_endpoint', callback: 'delete_account_button_adder', priority: 10, accepted_args: 0);

    function delete_account_button_adder(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        // Settings
        $messages = [
            'account_delete_button' => [
                'en' => 'Delete Account',
                'de' => 'Account Löschen',
                'pt' => 'Excluir Conta',
            ],
            'confirm_deletion' => [
                'en' => 'Are you sure you want to permanently delete your account? This action cannot be undone.',
                'de' => 'Bist du sicher, dass du dein Konto dauerhaft löschen möchtest? Dies kann nicht rückgängig gemacht werden.',
                'pt' => 'Tem certeza de que deseja excluir permanentemente sua conta? Esta ação não pode ser desfeita.',
            ],
        ];

        // Get current language (Polylang/WPML)
        $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

        $button_text = esc_html($messages['account_delete_button'][$browsing_language]);
        $confirm_text = esc_js($messages['confirm_deletion'][$browsing_language]);

        echo '<br>
        <p>
            <form method="post" action="' . esc_url(get_permalink()) . '" onsubmit="return confirm(\'' . $confirm_text . '\');">
                ' . wp_nonce_field('delete_account_nonce', 'delete_account_nonce_field', true, false) . '
                <button type="submit" name="delete-account" class="elementor-widget-button" style="background-color: #d9534f; color: white; border: none; padding: 10px 20px; cursor: pointer;">' . $button_text . '</button>
            </form>
        </p>';
    }


    // Handle account deletion
    add_action(hook_name: 'template_redirect', callback: 'account_deletion_handler', priority: 10, accepted_args: 0);

    function account_deletion_handler(): void
    {
        if (is_admin()) {
            return;
        }
        if (!is_user_logged_in() || !isset($_POST['delete-account'])) {
            return;
        }

        // Settings
        $messages = [
            'account_delete_error' => [
                'en' => 'You cannot delete your account at this time. Make sure all orders are completed and the last order is at least 14 days old.',
                'de' => 'Du kannst dein Konto derzeit nicht löschen. Bitte stelle sicher, dass alle Bestellungen abgeschlossen sind und deine letzte Bestellung mindestens 14 Tage alt ist.',
                'pt' => 'Você não pode excluir sua conta no momento. Certifique-se de que todos os pedidos estejam concluídos e que o último pedido tenha pelo menos 14 dias.',
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
                $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

                wc_add_notice($messages['account_delete_error'][$browsing_language], 'error');
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
        $blocked_roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber', 'shop_manager', 'register_clerk', 'outlet_manager'];

        $user = get_user_by('ID', $user_id);
        if (!$user instanceof WP_User) {
            return false;
        }

        // Block if user has any privileged role
        if (!empty(array_intersect($user->roles, $blocked_roles))) {
            return false;
        }

        // Check if the user role is allowed to delete account
        if (!empty(array_intersect($user->roles, $allowed_roles))) {
            // Get completed orders
            $orders = wc_get_orders(['customer_id' => $user_id, 'status' => array_keys(wc_get_order_statuses()), 'limit' => 1, 'orderby' => 'date', 'order' => 'DESC']);

            // Check if there are completed orders
            if (!empty($orders)) {
                $last_order = array_first($orders);
                $last_completed_date = $last_order->get_date_created();

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
