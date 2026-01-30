<?php

// WooCommerce - Display multilingual top notices and inline badges based on product attributes on edit order page
// Last update: 2026-01-30


if (function_exists('WC') && is_admin()) {

    add_action(hook_name: 'admin_notices', callback: 'woocommerce_order_page_alerts', priority: 10, accepted_args: 1);
    add_action(hook_name: 'woocommerce_after_order_itemmeta', callback: 'woocommerce_order_page_alerts', priority: 10, accepted_args: 3);

    function woocommerce_order_page_alerts(mixed $item_id = null, ?WC_Order_Item $item = null, ?WC_Product $product = null): void
    {
        // Settings
        $alerts = [
            'green_coffee' => [
                'type'    => 'attribute',
                'match' => ['coffee-processing-green-coffee-en', 'coffee-processing-green-coffee-de'],
                'alert-top'   => ['en' => '⚠️ This order contains Green Coffee.', 'de' => '⚠️ Diese Bestellung enthält Rohkaffee.',],
                'alert-badge' => ['en' => '⚠️ Green Coffee', 'de' => '⚠️ Rohkaffee',],
                'alert-color' => '#d63638'
            ],
            'gift_card_training' => [
                'type'          => 'sku',
                'match'         => ['KA-Training-Home-Barista-Gift-Card'],
                'alert-top'     => [
                    'en' => '⚠️ This order contains a gift card. If this order is cancelled, manually delete the coupon here: <a href="' . esc_url(admin_url('edit.php?post_type=shop_coupon')) . '">Manage Coupons</a>.',
                    'de' => '⚠️ Diese Bestellung enthält einen Gutschein. Falls diese Bestellung storniert wird, muss der Gutschein hier manuell gelöscht werden: <a href="' . esc_url(admin_url('edit.php?post_type=shop_coupon')) . '">Gutscheine verwalten</a>.'
                ],
                'alert-badge'   => ['en' => '🎫 Gift Card Product', 'de' => '🎫 Gutschein-Produkt'],
                'alert-color'   => '#d63638'
            ],
            'gift_card' => [
                'type'          => 'sku',
                'match'         => ['KA-Gift-Card-Online-Shop'],
                'alert-top'     => [
                    'en' => '⚠️ This order contains a gift card. If this order is cancelled, manually delete the coupon here: <a href="' . esc_url(admin_url('edit.php?post_type=shop_coupon')) . '">Manage Coupons</a>.',
                    'de' => '⚠️ Diese Bestellung enthält einen Gutschein. Falls diese Bestellung storniert wird, muss der Gutschein hier manuell gelöscht werden: <a href="' . esc_url(admin_url('edit.php?post_type=shop_coupon')) . '">Gutscheine verwalten</a>.'
                ],
                'alert-badge'   => ['en' => '🎫 Gift Card Product', 'de' => '🎫 Gutschein-Produkt'],
                'alert-color'   => '#d63638'
            ],
        ];

        // Get current language
        $user_language = substr(get_user_locale(), 0, 2);
        $current_filter = current_filter();

        // Top alert
        if ($current_filter === 'admin_notices') {
            if (get_current_screen()?->id !== 'woocommerce_page_wc-orders') {
                return;
            }

            $order_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? 0;
            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }

            $found_notices = [];

            foreach ($order->get_items() as $order_item) {
                $item_product = $order_item->get_product();

                foreach ($alerts as $key => $alert) {
                    // SKU check
                    if ($alert['type'] === 'sku' && $item_product && in_array($item_product->get_sku(), $alert['match'], true)) {
                        $found_notices[$key] = $alert['alert-top'][$user_language] ?? $alert['alert-top']['en'];
                    }

                    // Attribute check
                    if ($alert['type'] === 'attribute') {
                        foreach ($order_item->get_meta_data() as $meta) {
                            if (str_contains((string)$meta->key, 'coffee-processing') && in_array($meta->value, $alert['match'], true)) {
                                $found_notices[$key] = $alert['alert-top'][$user_language] ?? $alert['alert-top']['en'];
                            }
                        }
                    }
                }
            }

            foreach ($found_notices as $message) {
                echo '<div class="notice notice-error" style="border-left-width: 10px;"><p style="font-size: 14px">' . wp_kses_post($message) . '</p></div>';
            }
        }

        // Badge alert (inline in table)
        if ($current_filter === 'woocommerce_after_order_itemmeta') {
            if (!$item instanceof WC_Order_Item_Product) {
                return;
            }
            $item_product = $item->get_product();

            foreach ($alerts as $alert) {
                $is_match = false;

                if ($alert['type'] === 'sku' && $item_product && in_array($item_product->get_sku(), $alert['match'], true)) {
                    $is_match = true;
                }

                if ($alert['type'] === 'attribute') {
                    foreach ($item->get_meta_data() as $meta) {
                        if (str_contains((string)$meta->key, 'coffee-processing') && in_array($meta->value, $alert['match'], true)) {
                            $is_match = true;
                        }
                    }
                }

                if ($is_match) {
                    $alert_badge = $alert['alert-badge'][$user_language] ?? $alert['alert-badge']['en'];
                    echo "<style>tr.item[data-order_item_id='" . esc_attr((string)$item_id) . "'] { background: #fff0f0 !important; }</style>";
                    echo "<div style='display:inline-block; margin-top:5px; padding:2px 8px; background:" . esc_attr($alert['alert-color']) . "; color:#fff; border-radius:3px; font-size:12px;'>" . esc_html($alert_badge) . "</div>";
                }
            }
        }
    }
}
