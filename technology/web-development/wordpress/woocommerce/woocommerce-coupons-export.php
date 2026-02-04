<?php
// WooCommerce - WooCommerce Coupons Export
// Last update: 2026-02-04


if (function_exists('WC') && is_admin()) {

    // Register the Admin Menu
    add_action(hook_name: 'admin_menu', callback: function (): void {
        if (current_user_can('administrator') || current_user_can('manage_woocommerce')) {
            add_submenu_page(parent_slug: 'woocommerce-marketing', page_title: 'WooCommerce Coupons Export', menu_title: 'Coupons Export', capability: 'manage_woocommerce', menu_slug: 'woocommerce-coupons-export', callback: 'woocommerce_coupons_export_interface');
        }
    }, priority: 10, accepted_args: 0);


    // Admin interface
    function woocommerce_coupons_export_interface(): void
    {

        ?>
        <div class="wrap">
            <h1>WooCommerce Coupons Export</h1>
            <form method="get" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="generate_coupon_csv">
                <table class="form-table">
                    <tr>
                        <th>Sold Date Range</th>
                        <td>
                            From: <input type="date" name="settings_date_sold_from"> 
                            To: <input type="date" name="settings_date_sold_to">
                        </td>
                    </tr>
                    <tr>
                        <th>Expiry Date Range</th>
                        <td>
                            From: <input type="date" name="settings_date_expiry_from"> 
                            To: <input type="date" name="settings_date_expiry_to">
                        </td>
                    </tr>
                    <tr>
                        <th>Coupon Code Prefixes
                            <span class="dashicons dashicons-info" title="Separate with commas. Uses REGEXP for matching."></span>
                        </th>
                        <td>
                            <input type="text" name="settings_coupon_code_prefixes" class="regular-text" value="^KA-TRAINING-, ^KA-GIFT-">
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Download', 'primary'); ?>
            </form>
        </div>
        <?php
    }

    // CSV generation handler
    add_action(hook_name: 'admin_post_generate_coupon_csv', callback: function (): void {

        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }

        $filters = [
            'settings_date_sold_from'   => $_GET['settings_date_sold_from']   ?? '',
            'settings_date_sold_to'     => $_GET['settings_date_sold_to']     ?? '',
            'settings_date_expiry_from' => $_GET['settings_date_expiry_from'] ?? '',
            'settings_date_expiry_to'   => $_GET['settings_date_expiry_to']   ?? '',
            'settings_coupon_code_prefixes' => array_filter(array_map('trim', explode(',', $_GET['settings_coupon_code_prefixes'] ?? ''))),
        ];

        $data = get_coupons_detailed_report_data($filters);

        if (empty($data)) {
            wp_die('No data found for the selected criteria.');
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=' . "woocommerce_coupons_export_" . date('Y-m-d_Hmi') . ".csv");

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        fputcsv($output, array_keys($data[0]), ",", '"', "");
        foreach ($data as $row) {
            fputcsv($output, $row, ",", '"', "");
        }
        fclose($output);
        exit;

    }, priority: 10, accepted_args: 0);


    // Data processing logic
    function get_coupons_detailed_report_data(array $filters): array
    {
        global $wpdb;

        $regex = implode('|', $filters['settings_coupon_code_prefixes']);

        // Base Query
        $query = "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'shop_coupon' AND post_title REGEXP %s";

        // Filter by Sold Date (post_date)
        if (!empty($filters['settings_date_sold_from']) && !empty($filters['settings_date_sold_to'])) {
            $query .= $wpdb->prepare(" AND post_date BETWEEN %s AND %s", $filters['settings_date_sold_from'] . ' 00:00:00', $filters['settings_date_sold_to'] . ' 23:59:59');
        }

        $coupons = $wpdb->get_results($wpdb->prepare($query, $regex));
        $coupon_report_df = [];

        foreach ($coupons as $coupon) {
            $coupon = new WC_Coupon($coupon->ID);

            // Expiry Logic & Filtering
            $coupon_expiry_date = $coupon->get_date_expires();
            $coupon_expiry_timestamp = $coupon_expiry_date ? $coupon_expiry_date->getTimestamp() : null;

            if (!empty($filters['settings_date_expiry_from']) || !empty($filters['settings_date_expiry_to'])) {
                if (!$coupon_expiry_timestamp) {
                    continue;
                } // Skip if filtering by expiry but no expiry set

                if (!empty($filters['settings_date_expiry_from']) && $coupon_expiry_timestamp < strtotime($filters['settings_date_expiry_from'])) {
                    continue;
                }
                if (!empty($filters['settings_date_expiry_to']) && $coupon_expiry_timestamp > strtotime($filters['settings_date_expiry_to'] . ' 23:59:59')) {
                    continue;
                }
            }

            // Germanized & Multi-purpose Logic
            $coupon_is_multi_purpose = '';
            if (class_exists('WooCommerce_Germanized')) {
                $coupon_is_multi_purpose = ($coupon->get_meta('is_voucher') === 'yes') ? 'Yes' : 'No';
            }

            // Redemption History
            $coupon_redemption_history = $coupon->get_meta('_coupon_redeemed_in_order_ids');
            $coupon_redeemed_orders = is_array($coupon_redemption_history) ? implode(', ', array_keys($coupon_redemption_history)) : '';

            // Purchase Order ID
            $coupon_purchased_on_order_id = $coupon->get_meta('_coupon_purchased_on_order_id') ?: 'N/A';

            // Tax logic (from associated purchase order)
            $coupon_tax_rate = '0%';
            if ($coupon_purchased_on_order_id !== 'N/A') {
                $order = wc_get_order($coupon_purchased_on_order_id);
                if ($order) {
                    foreach ($order->get_items() as $item) {
                        $taxes = $item->get_taxes();
                        if (!empty($taxes['total'])) {
                            $tax_ids = array_keys($taxes['total']);
                            $coupon_tax_rate = WC_Tax::get_rate_percent_value($tax_ids[0]) . '%';
                            break;
                        }
                    }
                }
            }

            $coupon_amount_current = (float)$coupon->get_amount();
            $coupon_usage_count = (int)$coupon->get_usage_count();
            $coupon_amount_initial = (float)$coupon->get_meta('_coupon_value_initial');

            $coupon_report_df[] = [
                'coupon_id'                  => $coupon->get_id(),
                'coupon_name'                => $coupon->get_description(),
                'coupon_multi_purpose'       => $coupon_is_multi_purpose,
                'coupon_type'                => $coupon->get_discount_type(),
                'coupon_tax_rate'            => $coupon_tax_rate,
                'coupon_sold_at'             => $coupon->get_date_created() ? $coupon->get_date_created()->date('Y-m-d') : 'N/A',
                'coupon_purchased_in_order'  => $coupon_purchased_on_order_id,
                'coupon_valid_until'         => $coupon_expiry_date ? $coupon_expiry_date->date('Y-m-d') : 'N/A',
                'coupon_code'                => $coupon->get_code(),
                'coupon_is_active'           => $coupon->is_valid() ? 'Yes' : 'No',
                'coupon_usage_count'         => $coupon_usage_count,
                'coupon_redeemed_in_orders'  => $coupon_redeemed_orders,
                'coupon_amount_initial'      => $coupon_amount_initial,
                'coupon_amount_redeemed'     => max(0, $coupon_amount_initial - $coupon_amount_current),
                'coupon_amount_current'      => number_format($coupon_amount_current, 2, '.', ''),
            ];
        }

        return $coupon_report_df;
    }

}
